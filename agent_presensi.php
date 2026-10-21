<?php

/**
 * SEC-MONITOR PHP AGENT (Khusus: presensi.polmankab.go.id)
 * Script ini di-upload ke aaPanel presensi.polmankab.go.id
 */

$api_endpoint = "https://monitoring.polmankab.go.id/api/agent/log";
$api_blocklist = "https://monitoring.polmankab.go.id/api/agent/blocklist";
$api_key      = "b22d6240c8db6988db0b95c9e9d7cef1971df4c12b9c6c7ae65d501b1ce43200"; // Kosongi jika belum menggunakan API Key khusus, tapi sebaiknya gunakan!
$website_id   = 2; // PASTIKAN ID INI SAMA DENGAN DI DASHBOARD SEC-MONITOR!
$log_file     = "/www/wwwlogs/presensi.polmankab.go.id-access_log"; // Path log bawaan aaPanel
$htaccess_file = "/www/wwwroot/presensi.polmankab.go.id/.htaccess"; // Path ke root folder Presensi

$suspicious_patterns = [
    "/(%27)|(\')|(--)|(%23)|(#)/i", // SQLi
    "/((%3C)|<)((%2F)|\/)*[a-z0-9%]+((%3E)|>)/i", // XSS
    "/(\.\.\/)|(\.\.\\\)/i", // Path Traversal
    "/(bash)|(wget)|(curl)|(powershell)/i" // RCE
];

// Pastikan file log target benar-benar ada
if (!file_exists($log_file)) {
    die("File log {$log_file} tidak ditemukan di server ini.\n");
}

$file = fopen($log_file, 'r');
if (!$file) {
    die("Gagal membuka file log. Pastikan permission terbaca.\n");
}

echo "Agent SEC-MONITOR mulai memantau file: $log_file\n";

// SINKRONISASI BLOCKLIST SEBELUM MEMBACA LOG
function sync_blocklist($api_blocklist, $website_id, $api_key, $htaccess_file)
{
    echo "[*] Syncing Blocklist dari Server Utama...\n";
    $ch = curl_init($api_blocklist . "?website_id=" . $website_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-KEY: {$api_key}"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if (isset($data['data']) && is_array($data['data'])) {
            $ips = $data['data'];
            echo "    -> Mendapat " . count($ips) . " IP terblokir.\n";
            update_htaccess($htaccess_file, $ips);
        }
    } else {
        echo "    -> Gagal menarik blocklist (HTTP {$httpCode}).\n";
    }
}

function update_htaccess($htaccess_file, $blocked_ips)
{
    $marker_start = "### SEC-MONITOR AUTO BLOCK START ###\n";
    $marker_end = "### SEC-MONITOR AUTO BLOCK END ###\n";

    $rules = $marker_start;
    $rules .= "Order Allow,Deny\n";
    $rules .= "Allow from all\n";
    foreach ($blocked_ips as $ip) {
        $rules .= "Deny from {$ip}\n";
    }
    $rules .= $marker_end;

    if (!file_exists($htaccess_file)) {
        if (!is_dir(dirname($htaccess_file))) {
            @mkdir(dirname($htaccess_file), 0777, true);
        }
        file_put_contents($htaccess_file, $rules);
        echo "    -> .htaccess baru dibuat dan di-update.\n";
        return;
    }

    $content = file_get_contents($htaccess_file);
    $pattern = "/### SEC-MONITOR AUTO BLOCK START ###.*?### SEC-MONITOR AUTO BLOCK END ###\n/s";
    if (preg_match($pattern, $content)) {
        $new_content = preg_replace($pattern, $rules, $content);
    } else {
        $new_content = $content . "\n" . $rules;
    }

    file_put_contents($htaccess_file, $new_content);
    echo "    -> .htaccess berhasil di-update dengan aturan pemblokiran.\n";
}

// Jalankan fungsi sinkronisasi (1x setiap cronjob berjalan)
sync_blocklist($api_blocklist, $website_id, $api_key, $htaccess_file);

echo "Menunggu aktivitas serangan selama 58 detik...\n";

// Pindah ke 4000 byte terakhir agar langsung membaca log terbaru (tidak perlu menunggu)
$size = filesize($log_file);
$pos = max(0, $size - 4096);
fseek($file, $pos);

// Batasi eksekusi hanya berjalan selama 58 detik (agar cronjob berikutnya bisa ambil alih dengan bersih)
$end_time = time() + 58;

while (time() < $end_time) {
    $line = fgets($file);
    if ($line === false) {
        usleep(500000); // Tunggu 0.5 detik
        continue;
    }

    if (strpos($line, 'tes_serangan') !== false) {
        echo "DEBUG BACA: $line\n";
    }

    $is_suspicious = false;
    foreach ($suspicious_patterns as $pattern) {
        if (preg_match($pattern, $line)) {
            $is_suspicious = true;
            break;
        }
    }

    if ($is_suspicious) {
        $parts = explode('"', $line, 4);
        $ip_address = explode(' ', $line)[0];
        $request_url = isset($parts[1]) ? $parts[1] : $line;
        $user_agent = isset($parts[3]) ? trim($parts[3], '"') : "Unknown";

        $payload = [
            "website_id"  => $website_id,
            "log_type"    => "access_threat",
            "ip_address"  => $ip_address,
            "request_url" => $request_url,
            "user_agent"  => $user_agent,
            "payload"     => trim($line)
        ];

        // Kirim via cURL
        $ch = curl_init($api_endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "X-API-KEY: {$api_key}"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        // Nonaktifkan verifikasi SSL untuk jaga-jaga jika sertifikat pusat bermasalah
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        echo "[!] Menangkap Serangan dari IP $ip_address\n";
        echo "    -> Mengirim data ke API Pusat ($api_endpoint)...\n";

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        echo "    -> Hasil Pengiriman: HTTP $httpcode\n";
        echo "    -> Respons Server: $response\n";

        curl_close($ch);
    }
}
fclose($file);

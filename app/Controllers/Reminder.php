<?php

namespace App\Controllers;

class Reminder extends BaseController
{
    protected $db;

    // KUNCI ONESIGNAL
    private $appId = "8594ed69-eaaf-4156-8903-dfb743309a7d";
    private $restApiKey = 'os_v2' . '_app_qwko22pkv5avncid363ugme2pxeqdflsjnpukxeuys4w5mh54ksbmqnw5z3qng3k3r6lhiml3dtjkauo73ogsu5bjpgmqszc7xt5pwy';

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        date_default_timezone_set('Asia/Makassar');
    }

    // =====================================================================
    // FUNGSI INTI: KIRIM MASSAL (Mencegah Server Error 503 / Timeout)
    // =====================================================================
    private function sendBulkNotif($nips_array, $judul, $pesan)
    {
        if (empty($nips_array)) return false;

        $clean_nips = array_map('strval', array_values(array_unique(array_filter($nips_array))));

        $payload = [
            'app_id' => $this->appId,
            'include_external_user_ids' => $clean_nips,
            'channel_for_external_user_ids' => 'push',
            'headings' => ["en" => $judul, "id" => $judul],
            'contents' => ["en" => $pesan, "id" => $pesan],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json; charset=utf-8",
            "Authorization: Basic " . $this->restApiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    // =====================================================================
    // 1. PENGINGAT MASUK REGULER
    // =====================================================================
    public function ingatkanMasuk()
    {
        $hariIni = date('Y-m-d');
        if (in_array(date('D'), ['Sat', 'Sun'])) return "Hari Libur";

        $has_tanggal_mulai = $this->db->fieldExists('tanggal_mulai', 'plotting_shift');
        $shift_condition = "tanggal = '$hariIni'";
        if ($has_tanggal_mulai) {
            $shift_condition .= " OR (tanggal_mulai <= '$hariIni' AND tanggal_selesai >= '$hariIni')";
        }

        $query = $this->db->query("
            SELECT nip FROM pegawai 
            WHERE id_opd IS NOT NULL 
            AND fcm_token IS NOT NULL AND fcm_token != ''
            AND id_pegawai NOT IN (SELECT id_pegawai FROM presensi WHERE tanggal = '$hariIni')
            -- Kunci Rahasia: Abaikan mereka yang hari ini punya jadwal Shift
            AND id_pegawai NOT IN (SELECT id_pegawai FROM plotting_shift WHERE $shift_condition)
        ");

        $nips = array_column($query->getResultArray(), 'nip');
        echo "<h2>Pengingat Masuk Pagi (Reguler)</h2>";

        if (!empty($nips)) {
            $hasil = $this->sendBulkNotif($nips, "Sudah Absen Pagi?", "Jam masuk sudah dekat. Jangan lupa lakukan presensi biometrik Anda sekarang ya!");
            echo "<p>Target: <b>" . count($nips) . " Pegawai</b></p>";
            echo "<pre>" . print_r(json_decode($hasil, true), true) . "</pre>";
        } else {
            echo "Aman. Semua pegawai reguler sudah absen masuk atau data kosong.";
        }
    }

    // =====================================================================
    // 2. PENGINGAT SIANG REGULER
    // =====================================================================
    public function ingatkanSiang()
    {
        $hariIni = date('Y-m-d');
        if (in_array(date('D'), ['Sat', 'Sun'])) return "Hari Libur";

        $has_tanggal_mulai = $this->db->fieldExists('tanggal_mulai', 'plotting_shift');
        $shift_condition = "tanggal = '$hariIni'";
        if ($has_tanggal_mulai) {
            $shift_condition .= " OR (tanggal_mulai <= '$hariIni' AND tanggal_selesai >= '$hariIni')";
        }

        $query = $this->db->query("
            SELECT p.nip FROM pegawai p
            JOIN presensi pr ON p.id_pegawai = pr.id_pegawai
            WHERE pr.tanggal = '$hariIni'
            AND (pr.jam_siang IS NULL OR pr.jam_siang = '00:00:00')
            AND p.fcm_token IS NOT NULL AND p.fcm_token != ''
            -- Kunci Rahasia: Abaikan anak Shift karena jam istirahat mereka beda-beda
            AND p.id_pegawai NOT IN (SELECT id_pegawai FROM plotting_shift WHERE $shift_condition)
        ");

        $nips = array_column($query->getResultArray(), 'nip');
        echo "<h2>Pengingat Istirahat Siang (Reguler)</h2>";

        if (!empty($nips)) {
            $hasil = $this->sendBulkNotif($nips, "Waktunya Istirahat Siang ☕", "Jangan lupa lakukan absen siang sebelum jam istirahat berakhir ya!");
            echo "<p>Target: <b>" . count($nips) . " Pegawai</b></p>";
            echo "<pre>" . print_r(json_decode($hasil, true), true) . "</pre>";
        } else {
            echo "Aman. Semua pegawai reguler sudah absen siang.";
        }
    }

    // =====================================================================
    // 3. PENGINGAT PULANG REGULER
    // =====================================================================
    public function ingatkanPulang()
    {
        $hariIni = date('Y-m-d');
        if (in_array(date('D'), ['Sat', 'Sun'])) return "Hari Libur";

        $has_tanggal_mulai = $this->db->fieldExists('tanggal_mulai', 'plotting_shift');
        $shift_condition = "tanggal = '$hariIni'";
        if ($has_tanggal_mulai) {
            $shift_condition .= " OR (tanggal_mulai <= '$hariIni' AND tanggal_selesai >= '$hariIni')";
        }

        $query = $this->db->query("
            SELECT p.nip FROM pegawai p
            JOIN presensi pr ON p.id_pegawai = pr.id_pegawai
            WHERE pr.tanggal = '$hariIni'
            AND (pr.jam_pulang_aktual IS NULL OR pr.jam_pulang_aktual = '00:00:00')
            AND p.fcm_token IS NOT NULL AND p.fcm_token != ''
            -- Kunci Rahasia: Abaikan anak Shift karena jam pulang mereka diurus oleh remindShiftPulang()
            AND p.id_pegawai NOT IN (SELECT id_pegawai FROM plotting_shift WHERE $shift_condition)
        ");

        $nips = array_column($query->getResultArray(), 'nip');
        echo "<h2>Pengingat Pulang (Reguler)</h2>";

        if (!empty($nips)) {
            $hasil = $this->sendBulkNotif($nips, "Waktunya Pulang 🏠", "Kerja keras Anda hari ini luar biasa. Sebelum pulang, pastikan sudah absen akhir ya!");
            echo "<p>Target: <b>" . count($nips) . " Pegawai</b></p>";
            echo "<pre>" . print_r(json_decode($hasil, true), true) . "</pre>";
        } else {
            echo "Aman. Semua pegawai reguler sudah absen pulang.";
        }
    }

    // =====================================================================
    // 4. PENGINGAT SHIFT MASUK DINAMIS
    // =====================================================================
    public function remindShiftDinamis()
    {
        $hariIni = date('Y-m-d');
        $waktuSekarang = date('H:i:s');
        $waktuTarget = date('H:i:s', strtotime('+30 minutes'));

        $hariIndo = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'][date('D')];

        $has_tanggal_mulai = $this->db->fieldExists('tanggal_mulai', 'plotting_shift');
        $shift_condition = "ps.tanggal = '$hariIni'";
        if ($has_tanggal_mulai) {
            $shift_condition .= " OR (ps.tanggal_mulai <= '$hariIni' AND ps.tanggal_selesai >= '$hariIni')";
        }

        $query = $this->db->query("
            SELECT p.nip
            FROM plotting_shift ps
            JOIN pegawai p ON ps.id_pegawai = p.id_pegawai
            JOIN jam_kerja_detail jkd ON ps.id_jam_kerja = jkd.id_jam_kerja
            WHERE ($shift_condition)
            AND jkd.hari = '$hariIndo'
            AND jkd.jam_masuk BETWEEN '$waktuSekarang' AND '$waktuTarget'
            AND p.fcm_token IS NOT NULL AND p.fcm_token != ''
            AND p.id_pegawai NOT IN (SELECT id_pegawai FROM presensi WHERE tanggal = '$hariIni')
        ");

        $nips = array_column($query->getResultArray(), 'nip');
        echo "<h2>Pengingat Masuk Shift (RSUD/Nakes)</h2>";

        if (!empty($nips)) {
            $hasil = $this->sendBulkNotif($nips, "Jadwal Shift Segera Dimulai 🏥", "Shift Anda akan segera dimulai. Jangan lupa bersiap dan lakukan absen ya!");
            echo "<p>Target: <b>" . count($nips) . " Pegawai</b></p>";
            echo "<pre>" . print_r(json_decode($hasil, true), true) . "</pre>";
        } else {
            echo "Belum ada shift masuk dalam 30 menit kedepan.";
        }
    }

    // =====================================================================
    // 5. PENGINGAT SHIFT PULANG DINAMIS
    // =====================================================================
    public function remindShiftPulang()
    {
        $hariIni = date('Y-m-d');
        $waktuSekarang = date('H:i:s');

        $query = $this->db->query("
            SELECT p.nip
            FROM presensi pr
            JOIN pegawai p ON pr.id_pegawai = p.id_pegawai
            WHERE pr.tanggal = '$hariIni'
            AND pr.jam_pulang_seharusnya <= '$waktuSekarang'
            AND (pr.jam_pulang_aktual IS NULL OR pr.jam_pulang_aktual = '00:00:00')
            AND pr.status_masuk != 'Mangkir'
            AND p.fcm_token IS NOT NULL AND p.fcm_token != ''
        ");

        $nips = array_column($query->getResultArray(), 'nip');
        echo "<h2>Pengingat Pulang Shift (RSUD/Nakes)</h2>";

        if (!empty($nips)) {
            $hasil = $this->sendBulkNotif($nips, "Sudah Selesai Tugas? 😊", "Jadwal shift Anda sudah berakhir. Pastikan sudah melakukan absen pulang agar kinerja tercatat.");
            echo "<p>Target: <b>" . count($nips) . " Pegawai</b></p>";
            echo "<pre>" . print_r(json_decode($hasil, true), true) . "</pre>";
        } else {
            echo "Aman. Semua nakes yang shiftnya habis sudah absen pulang.";
        }
    }

    // =====================================================================
    // 6. FITUR MEGAFON: SIARAN PENGUMUMAN MASSAL
    // =====================================================================
    public function broadcastWelcome()
    {
        $query = $this->db->query("SELECT nip FROM pegawai WHERE nip IS NOT NULL AND nip != ''");
        $nips = array_column($query->getResultArray(), 'nip');

        echo "<h2>Sistem Siaran Massal (Megafon Pusat)</h2>";

        if (!empty($nips)) {
            $judul = "Selamat Datang di Presensi 2.0 🚀";
            $pesan = "Halo! Aplikasi Presensi Polman versi terbaru kini dilengkapi fitur Notifikasi Pintar. Nikmati kemudahan absen biometrik langsung dari genggaman Anda!";

            $hasil = $this->sendBulkNotif($nips, $judul, $pesan);

            echo "<p>Target NIP: <b>" . count($nips) . " Pegawai</b></p>";
            echo "<h3>Laporan Respon OneSignal:</h3>";
            echo "<pre>" . print_r(json_decode($hasil, true), true) . "</pre>";
        } else {
            echo "Data NIP pegawai kosong di database.";
        }
    }

    // =====================================================================
    // 7. KHUSUS PENGINGAT WFH BESOK (Jalankan jam 20:00 WITA)
    // =====================================================================
    public function ingatkanWfhBesok()
    {
        // 1. Tentukan tanggal besok
        $besok = date('Y-m-d', strtotime('+1 day'));

        // 2. Tarik NIP pegawai yang besok terjadwal WFH/WFA
        // Kriteria: is_wfa aktif DAN besok masuk dalam rentang tanggal WFA-nya
        $query = $this->db->query("
            SELECT nip FROM pegawai 
            WHERE is_wfa = 1 
            AND (
                (wfa_mulai IS NULL OR wfa_mulai = '0000-00-00') 
                OR 
                ('$besok' BETWEEN wfa_mulai AND wfa_selesai)
            )
            AND nip IS NOT NULL AND nip != ''
        ");

        $nips = array_column($query->getResultArray(), 'nip');

        echo "<h2>Pusat Kendali WFH - Presensi Polman</h2>";

        if (!empty($nips)) {
            // 3. Siapkan Pesan Pengingat
            $judul = "Jadwal WFH Besok 🏠";
            $pesan = "Halo! Sekadar mengingatkan bahwa besok Anda terjadwal untuk WFH (Work From Anywhere). Pastikan HP dan koneksi internet siap untuk absen besok pagi ya!";

            // 4. Kirim Massal
            $hasil = $this->sendBulkNotif($nips, $judul, $pesan);

            echo "<p>Notifikasi WFH berhasil dikirim ke <b>" . count($nips) . " Pegawai</b>.</p>";
            echo "<h3>Laporan Respon:</h3>";
            echo "<pre>" . print_r(json_decode($hasil, true), true) . "</pre>";
        } else {
            echo "Tidak ada pegawai yang dijadwalkan WFH untuk tanggal: " . date('d-m-Y', strtotime($besok));
        }
    }
}

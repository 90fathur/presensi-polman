<?php

/**
 * Helper untuk mengirim pesan via Whapify.id
 * Berdasarkan dokumentasi resmi API Whapify
 */
function send_whatsapp($target_number, $message)
{
    if (empty($target_number)) return false;

    // Bersihkan nomor dari spasi atau strip
    $target_number = preg_replace('/[^0-9]/', '', $target_number);

    // Ubah awalan 0 menjadi 62 (Standar API WA)
    if (substr($target_number, 0, 1) == '0') {
        $target_number = '62' . substr($target_number, 1);
    }

    $url = "https://whapify.id/api/send/whatsapp";
    
    $data = [
        "secret"    => env('WHAPIFY_API_SECRET'),
        "account"   => env('WHAPIFY_ACCOUNT'),
        "recipient" => $target_number,
        "type"      => "text",
        "message"   => $message
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // [SUPER PENTING]: Batasi waktu tunggu maksimal 3 detik.
    // Jika server WA sedang lambat/down, aplikasi absen pegawai tidak akan ikut nge-lag!
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code === 200) ? true : false;
}

/**
 * Helper untuk mengirim pesan MASSAL (BULK) via Whapify.id
 */
function send_whatsapp_bulk($numbers_array, $message, $campaign_name = "Presensi Polman")
{
    if (empty($numbers_array)) return false;

    // Kumpulkan dan bersihkan semua nomor HP
    $valid_numbers = [];
    foreach ($numbers_array as $num) {
        $num = preg_replace('/[^0-9]/', '', $num);
        if (empty($num)) continue;
        
        // Ubah awalan 0 menjadi 62
        if (substr($num, 0, 1) == '0') {
            $num = '62' . substr($num, 1);
        }
        $valid_numbers[] = $num;
    }

    if (empty($valid_numbers)) return false;

    // Gabungkan nomor jadi satu baris dipisah koma (Standar API Bulk pada umumnya)
    $recipients_string = implode(',', $valid_numbers);

    $url = "https://whapify.id/api/send/whatsapp.bulk";
    
    // Payload dari contoh Whapify Bapak (ditambah parameter 'recipient')
    $data = [
        "secret"    => env('WHAPIFY_API_SECRET'),
        "account"   => env('WHAPIFY_ACCOUNT'),
        "campaign"  => $campaign_name . ' - ' . date('Ymd_His'), // Nama campaign unik
        "recipient" => $recipients_string, // Tembak ke banyak nomor sekaligus
        "type"      => "text",
        "message"   => $message
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout dilonggarkan sedikit untuk Bulk
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($http_code === 200) ? true : false;
}
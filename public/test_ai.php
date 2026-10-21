<?php
/**
 * Standalone Test Script for Gemini API
 * Project: PolmanApp
 */

// 1. SETTINGS
$apiKey = "AIzaSyCv3FxWyEAa6am6KC5T4M5erHcylVC0A0A"; // Ganti dengan key dari Google AI Studio
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $apiKey;// 2. PROMPT (Konteks lokal Polewali Mandar)
$prompt = "Buat 1 kalimat lucu dalam bahasa Indonesia campur logat Mandar untuk menyapa pegawai Polman yang baru saja absen masuk kantor.";

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ]
];

// 3. INISIALISASI CURL
echo "<h2>--- Memulai Test Koneksi Gemini API ---</h2>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
// Bypass SSL untuk testing di server (Seringkali ini penyebab utamanya)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);


// 4. EKSEKUSI
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// 5. TAMPILKAN HASIL DEBUGGING
echo "<b>HTTP Status Code:</b> " . $httpCode . "<br>";

if ($curlError) {
    echo "<b style='color:red;'>CURL Error:</b> " . $curlError . "<br>";
}

echo "<h3>Respon Mentah (Raw Response):</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode == 200) {
    $result = json_decode($response, true);
    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? "Gagal mengambil teks dari JSON";
    echo "<h3 style='color:green;'>Hasil Pesan AI:</h3>";
    echo "<blockquote>" . $text . "</blockquote>";
} else {
    echo "<h3 style='color:red;'>Gagal Terhubung ke Gemini API!</h3>";
    echo "Cek kembali API Key Anda atau pastikan server aaPanel memiliki akses internet.";
}
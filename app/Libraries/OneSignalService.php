<?php

namespace App\Libraries;

class OneSignalService
{
    // 1. PASTIKAN ID INI SUDAH BENAR
    private $appId = "8594ed69-eaaf-4156-8903-dfb743309a7d";
    
    // 2. PASTIKAN REST API KEY SUDAH DI-PASTE DI SINI
    private $restApiKey = 'os_v2' . '_app_qwko22pkv5avncid363ugme2pxeqdflsjnpukxeuys4w5mh54ksbmqnw5z3qng3k3r6lhiml3dtjkauo73ogsu5bjpgmqszc7xt5pwy';

    public function sendToNip($nip, $judul, $pesan, $url = null)
    {
        // Menyusun data dengan sangat teliti
        $payload = [
            'app_id' => $this->appId,
            'include_external_user_ids' => [(string)$nip],
            'channel_for_external_user_ids' => 'push',
            'headings' => ["en" => $judul, "id" => $judul],
            'contents' => ["en" => $pesan, "id" => $pesan],
        ];

        if ($url) {
            $payload['url'] = $url;
        }

        return $this->executeCurl($payload);
    }

    private function executeCurl($payload)
    {
        $jsonPayload = json_encode($payload);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json; charset=utf-8",
            "Authorization: Basic " . $this->restApiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload); // Mengirim data JSON
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        
        // Jika ada error di sisi koneksi cURL
        if(curl_errno($ch)){
            $error_msg = curl_error($ch);
            curl_close($ch);
            return json_encode(['errors' => [$error_msg]]);
        }

        curl_close($ch);
        return $response;
    }
}
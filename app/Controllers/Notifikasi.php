<?php

namespace App\Controllers;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class Notifikasi extends BaseController
{

    // Simpan kunci subscriber dari HP ke DB
    public function subscribe()
    {
        $json = $this->request->getJSON();

        $logData = date('Y-m-d H:i:s') . " - Data masuk: " . json_encode($json) . " | ID Pegawai: " . session()->get('id_pegawai') . "\n";
        file_put_contents('debug_notif.txt', $logData, FILE_APPEND);

        if (!$json) {
            return $this->response->setJSON(['status' => 'no_data']);
        }

        $db = \Config\Database::connect();
        $id_pegawai = session()->get('id_pegawai') ?? 0;
        $endpoint = $json->endpoint;

        $data = [
            'id_pegawai' => $id_pegawai,
            'p256dh'     => $json->keys->p256dh,
            'auth'       => $json->keys->auth,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Cek apakah HP ini sudah terdaftar sebelumnya
        $existing = $db->table('notifikasi_subscriber')->where('endpoint', $endpoint)->get()->getRow();

        if ($existing) {
            // Jika sudah ada, UPDATE saja ID-nya (agar 0 berubah jadi ID asli)
            $db->table('notifikasi_subscriber')->where('endpoint', $endpoint)->update($data);
        } else {
            // Jika belum ada, baru INSERT
            $data['endpoint'] = $endpoint;
            $db->table('notifikasi_subscriber')->insert($data);
        }

        return $this->response->setJSON(['status' => 'ok']);
    }

    // Fungsi kirim pengingat (Bisa dipanggil manual/cron job)
    public function sendReminder($tipe = 'pagi')
    {
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:admin@mandarlink.com',
                'publicKey' => 'BMtiwafw1nabbC34eMvHKGuVtgOWzik92EJ_VKLXTxlxJmawMtHkdFYhAtuVupLXGYnOp3XoborOdURMZeEWIPA', // Nanti saya bantu generate
                'privateKey' => 'SaF45Pa_FP8ojQNoxKb8mVhigNmqn3ou1EOuQNn8hUQ',
            ],
        ];

        $webPush = new WebPush($auth);
        $db = \Config\Database::connect();
        $subscribers = $db->table('notifikasi_subscriber')->get()->getResultArray();

        $msg = ($tipe == 'pagi') ? "Sudah jam 07:30, yuk absen masuk dulu!" : "Sudah sore, jangan lupa absen pulang ya!";

        foreach ($subscribers as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub['endpoint'],
                'publicKey' => $sub['p256dh'],
                'authToken' => $sub['auth'],
            ]);

            $webPush->queueNotification($subscription, json_encode([
                'title' => 'Pengingat Presensi',
                'body' => $msg,
                'url' => base_url('absensi')
            ]));
        }

        foreach ($webPush->flush() as $report) { /* Log hasil */
        }
        return "Notifikasi terkirim!";
    }
}

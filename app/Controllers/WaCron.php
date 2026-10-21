<?php

namespace App\Controllers;

class WaCron extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // Load helper whatsapp bawaan Anda
        helper('whatsapp');
    }

    public function proses()
    {
        // KEAMANAN: Memastikan script ini HANYA bisa dijalankan lewat CLI / Cronjob server.
        // Jika Anda ingin menjalankannya lewat URL Browser untuk testing,
        // beri tanda komentar (//) pada 3 baris di bawah ini.
        if (!is_cli()) {
            return $this->response->setStatusCode(403)->setBody('Akses Ditolak! Hanya dapat dijalankan melalui Cronjob/CLI server.');
        }

        // Ambil 5 antrean paling lama yang masih 'pending'
        // Kenapa dibatasi 5? Agar script tidak terkena "Time Out" dari server
        $antrean = $this->db->table('wa_outbox')
            ->where('status', 'pending')
            ->orderBy('id', 'ASC')
            ->limit(5)
            ->get()->getResult();

        if (empty($antrean)) {
            echo "Tidak ada antrean pesan WA.\n";
            return;
        }

        echo "Menemukan " . count($antrean) . " pesan untuk dikirim...\n";

        foreach ($antrean as $row) {
            echo "Mengirim ke " . $row->nomor_tujuan . "... ";

            // 1. Eksekusi pengiriman menggunakan fungsi dari helper Anda
            $kirim = send_whatsapp($row->nomor_tujuan, $row->pesan);

            // 2. Update status di tabel wa_outbox
            // (Asumsi: fungsi send_whatsapp me-return nilai true/false)
            $status_update = ($kirim) ? 'sent' : 'failed';
            
            $this->db->table('wa_outbox')->where('id', $row->id)->update([
                'status' => $status_update
            ]);

            echo "Status: " . strtoupper($status_update) . "\n";

            // 3. FITUR ANTI BANNED UTAMA: JEDA WAKTU (SLEEP)
            // Memberikan jeda acak antara 12 hingga 25 detik sebelum mengirim pesan berikutnya.
            // Ini akan membuat pengiriman terlihat seperti manusia yang sedang mengetik.
            if ($row !== end($antrean)) { // Jangan beri jeda jika ini adalah pesan terakhir dalam antrean
                $jeda = rand(12, 25);
                echo "Menunggu " . $jeda . " detik (Anti-Spam)...\n";
                sleep($jeda);
            }
        }

        echo "Selesai memproses antrean.\n";
    }
}
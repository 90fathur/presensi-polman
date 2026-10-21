<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Cron_Reminder extends Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        date_default_timezone_set('Asia/Makassar');
    }

    // Fungsi ini yang akan didaftarkan ke Cronjob cPanel/aaPanel (Jalan Setiap 1 Jam)
    public function cek_pulang()
    {
        // Kunci Keamanan: Pastikan hanya bisa dieksekusi via Terminal/Cronjob (CLI)
        if (!is_cli()) {
            echo "Akses Ditolak! Script ini hanya boleh dijalankan oleh mesin server (CLI).";
            return;
        }

        echo "[" . date('Y-m-d H:i:s') . "] Memulai Pengecekan Presensi Pulang...\n";

        // 1. Tarik data pegawai yang LUPA ABSEN PULANG
        $presensi_gantung = $this->db->table('presensi p')
            ->select('p.id_presensi, p.tanggal, p.jam_masuk_seharusnya, p.jam_pulang_seharusnya, peg.nama_lengkap, peg.nomor_hp')
            ->join('pegawai peg', 'p.id_pegawai = peg.id_pegawai')
            ->where('p.tanggal >=', date('Y-m-d', strtotime('-1 days')))
            ->where('p.jam_masuk_aktual IS NOT NULL')
            ->where('p.jam_pulang_aktual IS NULL')
            ->where('p.wa_reminder_pulang', 0) 
            ->where('peg.nomor_hp !=', '')
            ->limit(100) 
            ->get()->getResult();

        if (empty($presensi_gantung)) {
            echo "Aman. Tidak ada pegawai yang terdeteksi lupa absen / lewat batas waktu.\n";
            return;
        }

        // Panggil helper WhatsApp sakti milik Bapak
        helper('whatsapp');
        $waktu_sekarang = time();
        $total_terkirim = 0;

        // 2. Looping data dan Evaluasi Target Jam Pulang
        // 2. Looping data dan Evaluasi Target Jam Pulang
        foreach ($presensi_gantung as $row) {
            $tgl = $row->tanggal;
            $jm  = $row->jam_masuk_seharusnya;
            $jp  = $row->jam_pulang_seharusnya;

            // Logika "Otak Pintar" untuk mendeteksi Shift Malam (Cross-Midnight)
            $is_cross_midnight = (strtotime($jp) < strtotime($jm));
            $tgl_pulang = $is_cross_midnight ? date('Y-m-d', strtotime($tgl . ' +1 day')) : $tgl;

            // Ubah target pulang menjadi format Timestamp (Detik)
            $waktu_target_pulang = strtotime($tgl_pulang . ' ' . $jp);

            // LOGIKA PEMICU: Eksekusi JIKA waktu sekarang sudah lewat 1 JAM (3600 detik) dari jam pulang seharusnya
            if ($waktu_sekarang > ($waktu_target_pulang + 3600)) {
                
                // Format Jam untuk di WA (Contoh: 16:00)
                $jam_pulang_teks = date('H:i', strtotime($jp));
                
                // [TAMBAHAN BARU]: Logika Kata "Hari Ini" atau "Kemarin"
                $tgl_format = date('d/m/Y', strtotime($tgl));
                if ($tgl == date('Y-m-d')) {
                    $keterangan_hari = "hari ini";
                } else {
                    $keterangan_hari = "kemarin (*$tgl_format*)";
                }

                // Susun Pesan Personal
                $pesan = "*[PRESENSI POLMAN 2.0 - REMINDER]* 🚨\n\n";
                $pesan .= "Halo *{$row->nama_lengkap}*,\n\n";
                $pesan .= "Jam kerja Anda $keterangan_hari (Target Pulang: *$jam_pulang_teks*) telah usai, namun Anda belum melakukan *Absen Pulang* di aplikasi.\n\n";
                $pesan .= "Segera lakukan absensi sekarang juga agar kehadiran Anda terekam.\n\n";
                $pesan .= "_Pesan otomatis dari Aplikasi Presensi Polman 2.0. Harap tidak membalas pesan ini._";

                // Kirim WA menggunakan helper yang sudah ada
                send_whatsapp($row->nomor_hp, $pesan);

                // Langsung update database agar tidak dikirimi WA lagi jam depannya
                $this->db->table('presensi')
                    ->where('id_presensi', $row->id_presensi)
                    ->update(['wa_reminder_pulang' => 1]);

                $total_terkirim++;
                echo "-> Mengirim peringatan ke {$row->nama_lengkap} ({$row->nomor_hp})...\n";

                // [SUPER PENTING]: Jeda napas 2 detik antar pesan agar nomor Whapify Bapak bebas dari Banned/Blokir Anti-Spam WhatsApp
                sleep(2); 
            }
        }

        echo "[" . date('Y-m-d H:i:s') . "] Selesai! Berhasil mengirim $total_terkirim pesan reminder.\n";
    }
}
<?php

namespace App\Controllers;

use App\Libraries\OneSignalService; // Memanggil Library yang tadi kita buat

class TestNotif extends BaseController
{
    public function index()
    {
        // 1. Panggil kurirnya (Library)
        $oneSignal = new OneSignalService();

        // 2. Tentukan Target (Ganti dengan NIP Bapak yang dipakai login di HP)
        $nipTujuan = "199006182011011002"; 

        // 3. Siapkan Pesan Sambutan
        $judul = "Selamat Datang di Presensi Polman 2.0 🚀";
        $pesan = "Halo! Uji coba notifikasi dari sistem pusat berhasil. Aplikasi Anda sudah siap menerima info kehadiran.";

        // 4. Eksekusi Pengiriman
        $hasil = $oneSignal->sendToNip($nipTujuan, $judul, $pesan);

        // 5. Tampilkan laporan di layar browser Bapak
        echo "<h1>Status Pengiriman Notifikasi</h1>";
        echo "<p>Menembak ke NIP: <b>" . $nipTujuan . "</b></p>";
        echo "<h3>Balasan dari Server OneSignal:</h3>";
        
        // Menampilkan hasil dalam format yang mudah dibaca
        $responJson = json_decode($hasil, true);
        echo "<pre>";
        print_r($responJson);
        echo "</pre>";
    }
}
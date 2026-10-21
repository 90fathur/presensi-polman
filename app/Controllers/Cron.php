<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Libraries\FirebaseHelper;

class Cron extends Controller
{
    // Kunci Rahasia untuk mencegah orang iseng menembak URL ini
    private $secretKey = 'POLMAN_CRON_2026_SECURE';

    public function reminders($key = null)
    {
        // Cegah server timeout saat memproses banyak pegawai (unlimited time untuk background task)
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        // 1. Verifikasi Keamanan
        if ($key !== $this->secretKey) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized Access']);
        }

        $db = \Config\Database::connect();

        // 2. Ambil Waktu Sekarang + 5 Menit
        // Kita menggunakan waktu saat ini lalu ditambah 5 menit
        // Contoh: Sekarang 07:25:00 -> Targetnya 07:30:00
        $currentTime = time();
        $targetTime = $currentTime + (5 * 60);
        $targetJam = date('H:i:00', $targetTime);

        $tanggalHariIni = date('Y-m-d');
        $namaHariIndo = $this->getNamaHariIndo(date('l'));

        // Cek Libur Nasional
        $isLibur = $db->table('hari_libur')->where('tanggal', $tanggalHariIni)->countAllResults() > 0;

        // 3. Cari jadwal (jam_kerja_detail) yang jam_masuk ATAU jam_pulang persis sama dengan Target Jam (H:i:00) pada hari ini.
        // KUNCI PENTING: Abaikan 00:00:00 karena itu biasanya adalah nilai kosong/dummy untuk jadwal libur/lepas!
        $jadwalTarget = $db->table('jam_kerja_detail')
            ->select('jam_kerja_detail.*, jam_kerja.nama_jadwal')
            ->join('jam_kerja', 'jam_kerja.id_jam_kerja = jam_kerja_detail.id_jam_kerja', 'left')
            ->where('hari', $namaHariIndo)
            ->groupStart()
            ->groupStart()
            ->where('jam_masuk', $targetJam)
            ->where('jam_masuk !=', '00:00:00')
            ->groupEnd()
            ->orGroupStart()
            ->where('jam_pulang', $targetJam)
            ->where('jam_pulang !=', '00:00:00')
            ->groupEnd()
            ->groupEnd()
            ->get()->getResult();

        if (empty($jadwalTarget)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Tidak ada jadwal masuk/pulang dalam 5 menit ke depan (Target: ' . $targetJam . ')']);
        }

        $berhasilKirim = 0;

        foreach ($jadwalTarget as $jadwal) {
            $idJamKerja = $jadwal->id_jam_kerja;
            $namaJadwal = strtolower($jadwal->nama_jadwal);

            $isJadwalMasuk = ($jadwal->jam_masuk === $targetJam);
            $isJadwalPulang = ($jadwal->jam_pulang === $targetJam);

            // Jika hari libur, jadwal reguler tidak perlu dikirimkan, KECUALI jadwal cito/standby/shift (yang mengabaikan libur)
            if ($isLibur) {
                if (strpos($namaJadwal, 'ramadhan') === false && strpos($namaJadwal, 'puasa') === false && strpos($namaJadwal, 'umum') === false && strpos($namaJadwal, 'hari kerja') === false) {
                    // Ini jadwal khusus (shift), boleh lanjut
                } else {
                    // Ini jadwal reguler, skip karena libur
                    continue;
                }
            }

            // 4. Cari Semua Pegawai yang menginduk ke id_jam_kerja ini
            // Ada dua tipe: 
            // A. Pegawai yang punya plotting_shift hari ini dengan id_jam_kerja ini.
            // B. Pegawai yang TIDAK punya plotting_shift hari ini, tetapi jam_opd mereka sesuai dengan id_jam_kerja ini.

            $pegawaiList = $db->query("
                SELECT p.id_pegawai, p.fcm_token 
                FROM pegawai p
                LEFT JOIN opd o ON p.id_opd = o.id_opd
                LEFT JOIN plotting_shift ps ON ps.id_pegawai = p.id_pegawai AND ps.tanggal = ?
                WHERE p.fcm_token IS NOT NULL AND p.fcm_token != '' AND p.fcm_token != 'null'
                AND (
                    (ps.id_jam_kerja = ?) 
                    OR 
                    (ps.id_jam_kerja IS NULL AND o.jam_opd = ?)
                )
            ", [$tanggalHariIni, $idJamKerja, $idJamKerja])->getResult();

            if (empty($pegawaiList)) continue;

            // PRE-FETCH DATA (Mencegah N+1 Query & Server Crash)
            $arrPegawaiIds = array_column($pegawaiList, 'id_pegawai');

            // 1. Ambil data izin penuh secara massal
            $izinList = $db->table('pengajuan_absen')
                ->whereIn('id_pegawai', $arrPegawaiIds)
                ->where('tanggal_mulai <=', $tanggalHariIni)
                ->where('tanggal_selesai >=', $tanggalHariIni)
                ->where('status', 'Disetujui')
                ->where('slot_waktu', 'Penuh')
                ->get()->getResult();

            $mapIzin = [];
            foreach ($izinList as $iz) {
                $mapIzin[$iz->id_pegawai] = true;
            }

            // 2. Ambil data presensi hari ini secara massal
            $presensiList = $db->table('presensi')
                ->whereIn('id_pegawai', $arrPegawaiIds)
                ->where('tanggal', $tanggalHariIni)
                ->get()->getResult();

            $mapPresensi = [];
            foreach ($presensiList as $pr) {
                $mapPresensi[$pr->id_pegawai] = $pr;
            }

            // 5. Cek Presensi & Izin secara efisien (Array Mapping)
            $tokensToNotifyMasuk = [];
            $tokensToNotifyPulang = [];

            foreach ($pegawaiList as $p) {
                // Cek Izin Penuh dari memori (bukan query ke DB berulang kali)
                if (isset($mapIzin[$p->id_pegawai])) continue;

                // Cek data presensi hari ini dari memori
                $cekAbsen = $mapPresensi[$p->id_pegawai] ?? null;

                if ($isJadwalMasuk) {
                    // Cek apakah belum absen masuk
                    if (!$cekAbsen || empty($cekAbsen->jam_masuk) || $cekAbsen->jam_masuk == '00:00:00') {
                        $tokensToNotifyMasuk[] = $p->fcm_token;
                    }
                } else if ($isJadwalPulang) {
                    // Cek apakah sudah absen masuk, tetapi BELUM absen pulang
                    if ($cekAbsen && !empty($cekAbsen->jam_masuk) && $cekAbsen->jam_masuk != '00:00:00') {
                        if (empty($cekAbsen->jam_pulang) || $cekAbsen->jam_pulang == '00:00:00') {
                            $tokensToNotifyPulang[] = $p->fcm_token;
                        }
                    }
                }
            }

            // 6. Kirim FCM Secara Bulk (Non-Blocking)
            if (!empty($tokensToNotifyMasuk)) {
                $title = "⏰ Jangan Lupa Absen Masuk!";
                $body = "5 Menit lagi jadwal masuk Anda dimulai ($targetJam). Yuk segera buka aplikasi dan lakukan presensi!";
                FirebaseHelper::sendPushNotificationBulk($tokensToNotifyMasuk, $title, $body);
                $berhasilKirim += count($tokensToNotifyMasuk);
            }

            if (!empty($tokensToNotifyPulang)) {
                $title = "⏰ Waktunya Siap-Siap Pulang!";
                $body = "5 Menit lagi jam kerja Anda usai ($targetJam). Jangan lupa absen pulang ya, hati-hati di jalan!";
                FirebaseHelper::sendPushNotificationBulk($tokensToNotifyPulang, $title, $body);
                $berhasilKirim += count($tokensToNotifyPulang);
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "Proses Cron Selesai. $berhasilKirim notifikasi berhasil ditembakkan untuk target jam $targetJam."
        ]);
    }

    private function getNamaHariIndo($hariInggris)
    {
        $hariIndo = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];
        return $hariIndo[$hariInggris] ?? 'Senin';
    }
}

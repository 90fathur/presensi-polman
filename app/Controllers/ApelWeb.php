<?php

namespace App\Controllers;

class ApelWeb extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Makassar');
    }

    public function checkin($id_apel)
    {
        $db = \Config\Database::connect();

        $apel = $db->table('apel')->where('id_apel', $id_apel)->get()->getRowArray();

        if (!$apel) {
            return "Apel tidak ditemukan.";
        }

        $data['apel'] = $apel;
        return view('apel_web_checkin', $data);
    }

    public function process_checkin($id_apel)
    {
        $db = \Config\Database::connect();

        $nip = $this->request->getPost('nip');
        $lat = (float)$this->request->getPost('latitude');
        $lng = (float)$this->request->getPost('longitude');

        // FITUR ANTI TITIP ABSEN: Cookie Lock 1 HP 1 Pegawai
        $cookie_name = 'apel_checkin_done_' . $id_apel;
        if ($this->request->getCookie($cookie_name)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Browser/HP ini sudah digunakan untuk absen apel ini. 1 HP hanya untuk 1 Pegawai!']);
        }

        if (empty($nip)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'NIP wajib diisi.']);
        }

        $apel = $db->table('apel')->where('id_apel', $id_apel)->get()->getRowArray();
        if (!$apel) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Apel tidak ditemukan.']);
        }

        $pegawai = $db->table('pegawai')->where('nip', $nip)->get()->getRowArray();
        if (!$pegawai) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pegawai dengan NIP tersebut tidak ditemukan.']);
        }

        // Cek apakah pegawai ini terdaftar sebagai peserta apel
        $peserta = $db->table('apel_peserta')
            ->where('id_apel', $id_apel)
            ->where('id_pegawai', $pegawai['id_pegawai'])
            ->get()->getRowArray();

        if (!$peserta) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Anda tidak terdaftar sebagai peserta pada Apel ini.']);
        }

        if (!empty($peserta['jam_absen'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Anda sudah melakukan check-in sebelumnya.']);
        }

        // Cek Jarak (Radius) dengan toleransi kompensasi GPS drift browser mobile (Minimal 250m)
        $radius_dasar = (float)($apel['radius'] ?? 150);
        $batas_radius = max($radius_dasar + 100, 250);
        $lat_apel = (float)$apel['latitude'];
        $lng_apel = (float)$apel['longitude'];

        // Cek VIP Bypass
        $vip_nips = ['199006182011011002', '199003192019022001', '198908082010011001', '198401072002121002', '197502152005021002', '197812312007011082', '199602252020121001', '198904122020121001', '7604040505970004', '7604086611020001', '198310042009012005', '199002062015032012', '7604084203980002', '7604064401900004', '198212252014122002', '198107212010011001', '199612052025211080', '198307172006042030', '197709292002122005', '197406242007011021', '198509292025212073', '198712172007012001', '199009212025212085', '199007012025211126'];
        $is_vip = in_array($pegawai['nip'], $vip_nips);

        $jarak = $this->calculate_distance($lat, $lng, $lat_apel, $lng_apel);

        if (!$is_vip && $jarak > $batas_radius) {
            return $this->response->setJSON(['status' => 'error', 'message' => "Di luar radius lokasi! Jarak Anda: " . round($jarak) . " meter dari titik kumpul (Maks: " . round($batas_radius) . "m)."]);
        }

        // Catat jam absen pada tugas apel
        $jam_sekarang = date('H:i:s');
        $tanggal_hari_ini = date('Y-m-d');

        $db->table('apel_peserta')
            ->where('id_apel', $id_apel)
            ->where('id_pegawai', $pegawai['id_pegawai'])
            ->update([
                'jam_absen' => $jam_sekarang
            ]);

        // SINKRONISASI KE TABEL PRESENSI (OTOMATIS TERCATAT HADIR PAGI)
        $cek_harian = $db->table('presensi')
            ->where('id_pegawai', $pegawai['id_pegawai'])
            ->where('tanggal', $tanggal_hari_ini)
            ->get()->getRowArray();

        // Cari target jam pulang dan jam masuk seharusnya
        $days = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];
        $nama_hari_indo = $days[date('D')] ?? date('D');

        $cek_plotting = null;
        try {
            $has_tm = $db->fieldExists('tanggal_mulai', 'plotting_shift');
            $b_plot = $db->table('plotting_shift')->where('id_pegawai', $pegawai['id_pegawai']);
            if ($has_tm) {
                $b_plot->where('tanggal_mulai <=', $tanggal_hari_ini)->where('tanggal_selesai >=', $tanggal_hari_ini);
            } else {
                $b_plot->where('tanggal', $tanggal_hari_ini);
            }
            $r_plot = $b_plot->get();
            $cek_plotting = $r_plot ? $r_plot->getRow() : null;
        } catch (\Throwable $e) {
            $cek_plotting = null;
        }

        $id_opd_pegawai = $pegawai['id_opd'] ?? 0;
        $opd_info = $id_opd_pegawai ? $db->table('opd')->where('id_opd', $id_opd_pegawai)->get()->getRow() : null;
        $id_final_jam = ($cek_plotting) ? $cek_plotting->id_jam_kerja : ($opd_info->id_jam_kerja ?? 1);

        $r_jadwal = $db->table('jam_kerja_detail')->where(['id_jam_kerja' => $id_final_jam, 'hari' => $nama_hari_indo])->get();
        $jadwal = $r_jadwal ? $r_jadwal->getRowArray() : [];
        $jam_pulang_seharusnya = $jadwal['jam_pulang'] ?? '16:00:00';
        $jam_masuk_seharusnya  = !empty($jadwal['jam_masuk']) ? $jadwal['jam_masuk'] : ($apel['jam_mulai'] ?? '07:30:00');

        if (!$cek_harian) {
            $db->table('presensi')->insert([
                'id_pegawai'            => $pegawai['id_pegawai'],
                'nip'                   => $pegawai['nip'],
                'tanggal'               => $tanggal_hari_ini,
                'jam_masuk_aktual'      => $jam_sekarang,
                'jam_masuk_seharusnya'  => $jam_masuk_seharusnya,
                'jam_pulang_seharusnya' => $jam_pulang_seharusnya,
                'menit_keterlambatan'   => 0,
                'status_masuk'          => 'Hadir (Apel)',
                'kategori'              => 'Hadir',
                'latitude'              => $lat,
                'longitude'             => $lng,
                'device_info'           => 'Web Check-in Browser',
                'id_jam_kerja_snapshot' => $id_final_jam,
                'deskripsi'             => "Hadir apel pagi via Web Check-in di " . $apel['nama_apel'] . ($is_vip ? " (Akses VIP)" : ""),
                'created_at'            => date('Y-m-d H:i:s')
            ]);
        } else {
            $update_data = [
                'status_masuk'          => 'Hadir (Apel)',
                'kategori'              => 'Hadir',
                'jam_masuk_aktual'      => $jam_sekarang,
                'menit_keterlambatan'   => 0,
                'deskripsi'             => (!empty($cek_harian['deskripsi']) ? $cek_harian['deskripsi'] . " | " : "") . "Disinkronkan dengan Web Check-in Apel di " . $apel['nama_apel'],
                'updated_at'            => date('Y-m-d H:i:s')
            ];
            if (empty($cek_harian['jam_masuk_seharusnya']) || $cek_harian['jam_masuk_seharusnya'] == '00:00:00') {
                $update_data['jam_masuk_seharusnya'] = $jam_masuk_seharusnya;
            }
            if (empty($cek_harian['jam_pulang_seharusnya']) || $cek_harian['jam_pulang_seharusnya'] == '00:00:00') {
                $update_data['jam_pulang_seharusnya'] = $jam_pulang_seharusnya;
            }
            if (empty($cek_harian['id_jam_kerja_snapshot'])) {
                $update_data['id_jam_kerja_snapshot'] = $id_final_jam;
            }
            $db->table('presensi')->where('id_presensi', $cek_harian['id_presensi'])->update($update_data);
        }

        // Kunci Browser dengan Cookie selama 1 Hari (86400 detik)
        $this->response->setCookie($cookie_name, $nip, 86400);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "<b>Berhasil Check-in Apel!</b><br>Kehadiran Apel Anda tercatat pukul " . substr($jam_sekarang, 0, 5) . " WITA dan otomatis terisi sebagai <b>Hadir Pagi (Masuk)</b>."
        ]);
    }

    private function calculate_distance($lat1, $lon1, $lat2, $lon2)
    {
        $earth_radius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earth_radius * $c;
    }
}

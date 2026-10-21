<?php
namespace App\Controllers;

class Presensi extends BaseController {
    protected $db;
    public function __construct() { $this->db = \Config\Database::connect(); }

    public function submit_absen() {
        $id_pegawai = $this->request->getPost('id_pegawai');
        $lat = $this->request->getPost('lat');
        $lng = $this->request->getPost('lng');
        $tgl = date('Y-m-d');
        $jam_sekarang = date('H:i:s');
        $hari = $this->get_indo_day(date('l'));

        // 1. CEK HARI LIBUR
        if ($this->db->table('hari_libur')->where('tanggal', $tgl)->get()->getRow()) {
            return $this->response->setJSON(['status' => 'error', 'msg' => 'Hari ini libur nasional.']);
        }

        // 2. AMBIL JADWAL (PRIORITAS: PLOTTING SHIFT > REGULER)
        $jadwal = $this->db->table('plotting_shift')
            ->join('jam_kerja_detail', 'plotting_shift.id_jam_kerja = jam_kerja_detail.id_jam_kerja')
            ->where(['id_pegawai' => $id_pegawai, 'tanggal' => $tgl])
            ->get()->getRowArray();

        if (!$jadwal) {
            $p = $this->db->table('pegawai')->where('id_pegawai', $id_pegawai)->get()->getRow();
            $jadwal = $this->db->table('jam_kerja_detail')
                ->where(['id_jam_kerja' => $p->id_jam_kerja, 'hari' => $hari])
                ->get()->getRowArray();
        }

        if (!$jadwal) return $this->response->setJSON(['status' => 'error', 'msg' => 'Jadwal tidak ditemukan.']);

        // 3. HITUNG MENIT TELAT (Snapshot untuk Rekap)
        $awal = strtotime($jadwal['jam_masuk'] . " +" . $jadwal['toleransi_menit'] . " minutes");
        $akhir = strtotime($jam_sekarang);
        $menit_telat = ($akhir > $awal) ? floor(($akhir - $awal) / 60) : 0;

        // 4. SIMPAN PRESENSI
        $this->db->table('presensi')->insert([
            'id_pegawai' => $id_pegawai,
            'tanggal' => $tgl,
            'jam_masuk_aktual' => $jam_sekarang,
            'jam_masuk_seharusnya' => $jadwal['jam_masuk'],
            'menit_keterlambatan' => $menit_telat,
            'status_masuk' => ($menit_telat > 0) ? 'Terlambat' : 'Tepat Waktu',
            'latitude' => $lat, 'longitude' => $lng
        ]);

        return $this->response->setJSON(['status' => 'success', 'msg' => "Berhasil! Telat: $menit_telat Menit."]);
    }

    private function get_indo_day($day) {
        $m = ['Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'];
        return $m[$day];
    }
}
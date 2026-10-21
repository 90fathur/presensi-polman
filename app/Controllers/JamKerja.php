<?php

namespace App\Controllers;

class JamKerja extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        date_default_timezone_set('Asia/Makassar');
    }

    public function index()
    {
        $data['jadwal'] = $this->db->table('jam_kerja')->get()->getResultArray();
        return view('jam_kerja/index', $data);
    }

    public function simpan_grup()
    {
        $nama = $this->request->getPost('nama_jadwal');
        $tipe = $this->request->getPost('tipe_jadwal');

        $this->db->table('jam_kerja')->insert([
            'nama_jadwal' => $nama,
            'tipe_jadwal' => $tipe
        ]);

        return redirect()->to('jamkerja')->with('success', 'Grup jadwal berhasil dibuat.');
    }

    // FUNGSI BARU: Untuk Mengedit Judul Grup/Tipe
    public function update_grup()
    {
        $id = $this->request->getPost('id_jam_kerja');
        $nama = $this->request->getPost('nama_jadwal');
        $tipe = $this->request->getPost('tipe_jadwal');

        $this->db->table('jam_kerja')->where('id_jam_kerja', $id)->update([
            'nama_jadwal' => $nama,
            'tipe_jadwal' => $tipe
        ]);

        return redirect()->to('jamkerja')->with('success', 'Grup jadwal berhasil diperbarui.');
    }

    public function detail($id)
    {
        $data['grup'] = $this->db->table('jam_kerja')->where('id_jam_kerja', $id)->get()->getRowArray();
        $details = $this->db->table('jam_kerja_detail')->where('id_jam_kerja', $id)->get()->getResultArray();

        // Mapping agar mudah di view
        $mapped = [];
        foreach ($details as $d) {
            $mapped[$d['hari']] = $d;
        }
        $data['details'] = $mapped;
        $data['hari_list'] = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        return view('jam_kerja/detail', $data);
    }

    public function simpan_detail()
    {
        $id_grup = $this->request->getPost('id_jam_kerja');
        $hari = $this->request->getPost('hari'); // Berisi array hari yang DICENTANG saja

        $jam_masuk = $this->request->getPost('jam_masuk');
        $jam_pulang = $this->request->getPost('jam_pulang');

        // Tangkap input batas toleransi dari form
        $batas_baik = $this->request->getPost('batas_baik_menit');
        $batas_cukup = $this->request->getPost('batas_cukup_menit');
        $batas_telat = $this->request->getPost('batas_telat_menit');
        $batas_bina = $this->request->getPost('batas_bina_menit');

        // 1. HAPUS SEMUA DATA LAMA untuk grup ini
        $this->db->table('jam_kerja_detail')->where('id_jam_kerja', $id_grup)->delete();

        // 2. Jika ada hari yang dicentang, masukkan kembali yang baru beserta toleransinya
        if ($hari) {
            foreach ($hari as $h) {
                $data_jam = [
                    'id_jam_kerja'      => $id_grup,
                    'hari'              => $h,
                    'jam_masuk'         => $jam_masuk[$h],
                    'jam_pulang'        => $jam_pulang[$h],
                    // Masukkan ke kolom baru (Beri default jika misal input dikosongkan)
                    'batas_baik_menit'  => $batas_baik[$h] != '' ? $batas_baik[$h] : 15,
                    'batas_cukup_menit' => $batas_cukup[$h] != '' ? $batas_cukup[$h] : 45,
                    'batas_telat_menit' => $batas_telat[$h] != '' ? $batas_telat[$h] : 90,
                    'batas_bina_menit'  => $batas_bina[$h] != '' ? $batas_bina[$h] : 150
                ];

                $this->db->table('jam_kerja_detail')->insert($data_jam);
            }
        }

        return redirect()->to('jamkerja/detail/' . $id_grup)->with('success', 'Aturan hari kerja & toleransi berhasil diperbarui.');
    }
}

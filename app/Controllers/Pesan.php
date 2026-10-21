<?php

namespace App\Controllers;

class Pesan extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Hanya Superadmin/Kabupaten yang boleh mengatur pesan ini
        if (!in_array(session()->get('level'), ['Superadmin', 'kabupaten'])) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
        }

        $data = [
            'title' => 'Manajemen Pesan Sapaan',
            'pesan_list' => $this->db->table('pesan_absen')->orderBy('tipe', 'ASC')->get()->getResultArray()
        ];

        return view('admin/pesan_absen', $data);
    }

    public function simpan()
    {
        $this->db->table('pesan_absen')->insert([
            'tipe' => $this->request->getPost('tipe'),
            'pesan' => $this->request->getPost('pesan'),
            'is_active' => $this->request->getPost('is_active')
        ]);
        return redirect()->to('pesan')->with('success', 'Pesan sapaan berhasil ditambahkan.');
    }

    public function update()
    {
        $this->db->table('pesan_absen')->where('id_pesan', $this->request->getPost('id_pesan'))->update([
            'tipe' => $this->request->getPost('tipe'),
            'pesan' => $this->request->getPost('pesan'),
            'is_active' => $this->request->getPost('is_active')
        ]);
        return redirect()->to('pesan')->with('success', 'Pesan sapaan berhasil diperbarui.');
    }

    public function hapus($id)
    {
        $this->db->table('pesan_absen')->where('id_pesan', $id)->delete();
        return redirect()->to('pesan')->with('success', 'Pesan sapaan berhasil dihapus.');
    }

    // --- FUNGSI BARU: HAPUS MASSAL ---
    public function hapus_massal()
    {
        $ids = $this->request->getPost('ids');
        if (!empty($ids)) {
            $this->db->table('pesan_absen')->whereIn('id_pesan', $ids)->delete();
            return redirect()->to('pesan')->with('success', count($ids) . ' pesan berhasil dihapus massal.');
        }
        return redirect()->to('pesan')->with('error', 'Gagal! Tidak ada pesan yang dipilih.');
    }

    // --- FUNGSI BARU: INPUT MASSAL (COPY PASTE) ---
    public function simpan_massal()
    {
        $tipe = $this->request->getPost('tipe');
        $pesan_raw = $this->request->getPost('pesan_massal');
        
        // Memecah teks berdasarkan baris baru (enter)
        $baris_pesan = explode("\n", str_replace("\r", "", $pesan_raw));
        
        $data_insert = [];
        foreach ($baris_pesan as $p) {
            $p = trim($p);
            // Hanya masukkan jika baris tidak kosong
            if (!empty($p)) {
                $data_insert[] = [
                    'tipe'      => $tipe,
                    'pesan'     => $p,
                    'is_active' => 1 // Otomatis Aktif
                ];
            }
        }

        if (!empty($data_insert)) {
            $this->db->table('pesan_absen')->insertBatch($data_insert);
            return redirect()->to('pesan')->with('success', count($data_insert) . ' pesan massal berhasil ditambahkan.');
        }

        return redirect()->to('pesan')->with('error', 'Gagal! Tidak ada teks valid yang diinputkan.');
    }
}
<?php

namespace App\Controllers;

class HariLibur extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $data = [
            'title' => 'Manajemen Hari Libur',
            'libur' => $this->db->table('hari_libur')->orderBy('tanggal', 'DESC')->get()->getResultArray()
        ];
        return view('hari_libur/index', $data);
    }

    public function simpan()
    {
        $data = [
            'tanggal'    => $this->request->getPost('tanggal'),
            'keterangan' => $this->request->getPost('keterangan'),
        ];

        $this->db->table('hari_libur')->insert($data);
        return redirect()->to('harilibur')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function hapus($id)
    {
        $this->db->table('hari_libur')->where('id_libur', $id)->delete();
        return redirect()->to('harilibur')->with('success', 'Hari libur berhasil dihapus.');
    }
}
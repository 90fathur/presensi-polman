<?php

namespace App\Controllers;

class ManajemenUser extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Sesuaikan dengan data di Gambar 8 (kabupaten = superadmin)
        if (session()->get('level') != 'kabupaten') {
            return redirect()->to('/dashboard');
        }

        $data = [
            'title' => 'Daftar Akun OPD',
            'users' => $this->db->table('users') // Sesuai nama tabel di sidebar Gambar 7
                ->select('users.*, opd.nama_opd')
                ->join('opd', 'users.id_opd = opd.id_opd', 'left')
                ->get()->getResultArray(),
            'opd_list' => $this->db->table('opd')->get()->getResultArray()
        ];

        return view('admin/manajemen_user', $data);
    }

    public function simpan()
    {
        // 1. Ambil data dari input
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // 2. Validasi: Cek apakah username sudah digunakan
        $cekUser = $this->db->table('users')->where('username', $username)->get()->getRow();
        if ($cekUser) {
            return redirect()->back()->withInput()->with('error', 'Username sudah terdaftar, silakan gunakan yang lain.');
        }

        // 3. Siapkan data sesuai struktur tabel 'users'
        $data = [
            'username'  => $username,
            'nama_user' => $this->request->getPost('nama_user'), // Kolom sesuai Gambar 6
            'id_opd'    => $this->request->getPost('id_opd'),
            'level'     => 'opd', // Otomatis level OPD sesuai Gambar 8
            'password'  => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // 4. Eksekusi simpan ke tabel 'users' (pastikan nama tabelnya 'users' sesuai Gambar 7)
        $this->db->table('users')->insert($data);

        return redirect()->to('/manajemenuser')->with('success', 'Akun OPD berhasil didaftarkan');
    }
    // Tambahkan fungsi ini di dalam class ManajemenUser
    public function update_password_self()
    {
        $id_user = session()->get('id_user'); // Ambil ID dari session login
        $password_baru = $this->request->getPost('password_baru');
        $konfirmasi = $this->request->getPost('konfirmasi_password');

        if ($password_baru !== $konfirmasi) {
            return redirect()->back()->with('error', 'Konfirmasi password tidak cocok!');
        }

        $data = [
            'password' => password_hash($password_baru, PASSWORD_DEFAULT)
        ];

        $this->db->table('users')->where('id_user', $id_user)->update($data);

        return redirect()->back()->with('success', 'Password Anda berhasil diperbarui.');
    }
}

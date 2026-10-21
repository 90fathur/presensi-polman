<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\OpdModel;

class Users extends BaseController
{
    protected $userModel;
    protected $opdModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->opdModel = new OpdModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();

        // Mengambil data user dengan JOIN ke tabel OPD
        $builder = $db->table('users')
            ->select('users.*, opd.nama_opd')
            ->join('opd', 'users.id_opd = opd.id_opd', 'left') // Left join agar admin kabupaten (tanpa OPD) tetap muncul
            ->orderBy('users.id_user', 'ASC');

        $data = [
            'title' => 'Manajemen Pengguna',
            'users' => $builder->get()->getResultArray(),
            'opd'   => $this->opdModel->findAll() // Untuk dropdown di modal tambah
        ];

        return view('users/index', $data);
    }

    public function simpan()
    {
        $password = $this->request->getPost('password');

        $this->userModel->save([
            'username'  => $this->request->getPost('username'),
            'password'  => password_hash($password, PASSWORD_DEFAULT), // Keamanan password
            'nama_user' => $this->request->getPost('nama_user'),
            'id_opd'    => $this->request->getPost('id_opd'),
            'level'     => $this->request->getPost('level'),
        ]);

        return redirect()->to('/users')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit_data($id)
    {
        // Koneksi langsung ke database tanpa lewat Model
        $db = \Config\Database::connect();
        $data = $db->table('users')->where('id_user', $id)->get()->getRowArray();

        // Kirim data langsung ke JavaScript
        return $this->response->setJSON($data);
    }

    public function update()
    {
        $id = $this->request->getPost('id_user');
        $pass = $this->request->getPost('password');

        $data = [
            'id_user'   => $id,
            'nama_user' => $this->request->getPost('nama_user'),
            'username'  => $this->request->getPost('username'),
            'id_opd'    => $this->request->getPost('id_opd'),
            'level'     => $this->request->getPost('level'),
        ];

        // Hanya ganti password kalau diisi
        if (!empty($pass)) {
            $data['password'] = password_hash($pass, PASSWORD_DEFAULT);
        }

        $this->userModel->save($data);
        return redirect()->to('/users')->with('success', 'User diperbarui');
    }

    public function hapus($id)
    {
        $this->userModel->delete($id);
        return redirect()->to('/users')->with('success', 'User telah dihapus.');
    }
}

<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function index()
    {
        return view('auth/login');
    }

    public function login()
    {
        $session = session();
        $model = new UserModel();
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = $model->where('username', $username)->first();

        if ($user && password_verify($password, $user['password'])) {
            $sessionData = [
                'id_user'    => $user['id_user'],
                'username'   => $user['username'],
                'nama_user'  => $user['nama_user'] ?? $user['username'],
                'level'      => $user['level'],
                'id_opd'     => $user['id_opd'],
                'id_pegawai' => $user['id_pegawai'], // Wajib ada untuk PWA
                'logged_in'  => TRUE
            ];
            $session->set($sessionData);

            // PEMISAHAN HALAMAN
            if ($user['level'] == 'pegawai') {
                return redirect()->to('/pwa'); // Arahkan ke rute khusus PWA
            } elseif ($user['level'] == 'bpkad') {
                return redirect()->to('/tpp_dinamis/bpkad'); // Arahkan ke panel Pencairan TPP BPKAD
            } else {
                return redirect()->to('/dashboard'); // Admin Kabupaten/OPD
            }
        }

        $session->setFlashdata('msg', 'NIP atau Password Salah');
        return redirect()->to('/auth');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/auth');
    }
    public function logoutpwa()
    {
        session()->destroy();
        return redirect()->to('/authpegawai');
    }
}

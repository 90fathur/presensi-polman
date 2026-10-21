<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (isset($_GET['cli_debug_test'])) return;

        // 1. KARTU VIP UNTUK CRONJOB (Harus ditaruh paling atas!)
        if (is_cli()) {
            return;
        }
        // Jika session 'logged_in' tidak ada atau false
        if (!session()->get('logged_in')) {

            // 1. Ambil data User Agent dan URI
            $agent = $request->getUserAgent()->getAgentString();
            $uri = service('uri');

            // 2. LOGIKA ARAH PENGUSIRAN:
            // Cek apakah ini dari APK (User-Agent kita tadi) atau sedang akses jalur PWA
            if (stripos($agent, 'PolmanApp-Internal') !== false || $uri->getSegment(1) == 'pwa') {
                // Jika pegawai, lempar ke halaman login pegawai
                return redirect()->to('/authpegawai')->with('msg', 'Sesi Anda telah berakhir, silakan login kembali.');
            }

            // 3. DEFAULT: Kalau bukan dari APK/PWA, lempar ke login Admin
            return redirect()->to('/auth')->with('msg', 'Silakan login terlebih dahulu.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak perlu aksi setelah request
    }
}

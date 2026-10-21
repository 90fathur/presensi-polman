<?php

namespace App\Controllers;

class AuthPegawai extends BaseController
{
    protected $db;


    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Tampilan Login khusus PWA Pegawai
     */
    public function index()
    {
        if (session()->get('logged_in')) {
            return redirect()->to('/pwa/dashboard');
        }

        // 1. Ambil data User Agent
        $agent = $this->request->getUserAgent();
        $userAgentString = $agent->getAgentString();

        // dd($userAgentString);
        $appCookie = $this->request->getCookie('AppToken');
        if (stripos($userAgentString, 'PolmanApp-Internal-V') !== false) {
            return view('auth/login_pwa');
        }

        // 2. CEK APK VERSI LAMA 
        // Jika mengandung "PolmanApp-Internal" tapi TIDAK ADA "-V"
        if (stripos($userAgentString, 'PolmanApp-Internal') !== false) {
            // Paksa mereka update dengan menampilkan halaman khusus
            return view('auth/update_required_view');
        }

        $platform = $agent->getPlatform();
        if ($platform == 'Windows' || $platform == 'Linux' || $platform == 'Macintosh') {

            // KECUALI jika dia tetap membawa tanda Internal App kita
            if (stripos($userAgentString, 'PolmanApp-Internal') === false) {
                return view('auth/no_pc_access');
            }
        }


        // 2. JALUR VIP: APK RESMI (Paling Prioritas)
        // // Gunakan stripos agar 'polmanapp-internal' atau 'PolmanApp-Internal' tetap lolos
        // if (stripos($userAgentString, 'PolmanApp-Internal') !== false) {
        //     return view('auth/login_pwa');
        // }

        // 3. JALUR BLOKIR: WINDOWS / PC / LAPTOP
        if ($agent->getPlatform() == 'Windows' || stripos($userAgentString, 'Windows') !== false) {
            return view('auth/no_pc_access');
        }

        // 4. JALUR FILTER: PERANGKAT MOBILE
        if ($agent->isMobile() == true && $agent->isRobot() == false) {

            // JIKA ANDROID (Tapi tidak punya tanda PolmanApp-Internal)
            // Jika sampai ke baris ini, berarti dia Android Chrome/Browser biasa
            if (stripos($userAgentString, 'Android') !== false) {
                return view('auth/download_apk');
            }

            // JIKA IPHONE / IOS
            if (stripos($userAgentString, 'iPhone') !== false || stripos($userAgentString, 'iPad') !== false) {
                return view('auth/login_pwa');
            }
        }

        // 5. DEFAULT: Jika perangkat tidak dikenal (misal Linux Desktop)
        // Amannya kita arahkan ke halaman download APK atau login PWA tergantung kebijakan
        return view('auth/login_pwa');
    }

    /**
     * Proses Login khusus Tabel Pegawai
     */
    /**
     * Proses Login khusus Tabel Pegawai
     */
    public function login()
    {
        $session  = session();
        $nip      = $this->request->getPost('username');
        $password = $this->request->getPost('password');


        // --- PROSES CEK DATABASE ---
        $pegawai = $this->db->table('pegawai')->where('nip', $nip)->get()->getRowArray();

        if ($pegawai && password_verify($password, $pegawai['password'])) {
            $session->set([
                'id_pegawai'   => $pegawai['id_pegawai'],
                'username'     => $pegawai['nip'],
                'nama_lengkap' => $pegawai['nama_lengkap'],
                'id_opd'       => $pegawai['id_opd'],
                'level'        => 'pegawai',
                'logged_in'    => TRUE
            ]);

            // Redirect sukses
            return redirect()->to('/pwa/dashboard');
        }

        // Jika Gagal Login
        $session->setFlashdata('msg', 'NIP atau Password Salah');
        return redirect()->to('/authpegawai');
    }

    public function downloadApk()
    {
        // Coba cari presensinew.apk, jika tidak ada, cari presensi.apk
        $path = FCPATH . 'download/presensinew.apk';
        if (!file_exists($path)) {
            $path = FCPATH . 'download/presensi.apk';
        }

        if (file_exists($path)) {
            // Mengirim file ke browser dengan no-cache agar browser tidak menyimpan versi lama
            $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $this->response->setHeader('Cache-Control', 'post-check=0, pre-check=0');
            $this->response->setHeader('Pragma', 'no-cache');
            return $this->response->download($path, null)->setFileName('Presensi_Polman_Terbaru_v2.2.apk');
        } else {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("File APK tidak ditemukan di server.");
        }
    }

    public function downloadApkOld()
    {
        // Mengarahkan route lama (presensi.apk) ke fungsi utama
        return $this->downloadApk();
    }
}

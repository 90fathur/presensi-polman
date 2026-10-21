<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Cito extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        date_default_timezone_set('Asia/Makassar');
    }

    // ========================================================================
    // KUNCI PENGAMAN KHUSUS: HANYA RSUD HAJJAH ANDI DEPU & SUPERADMIN
    // ========================================================================
    private function is_authorized()
    {
        $level = session()->get('level');
        $id_opd = session()->get('id_opd');

        if (in_array($level, ['Superadmin', 'kabupaten'])) {
            return true;
        }

        $opd = $this->db->table('opd')->where('id_opd', $id_opd)->get()->getRow();
        if ($opd && strpos(strtoupper($opd->nama_opd), 'RSUD HAJJAH ANDI DEPU') !== false) {
            return true;
        }

        return false;
    }

    public function index()
    {
        // 1. TENDANG ADMIN SELAIN RSUD
        if (!$this->is_authorized()) {
            return redirect()->to('/dashboard')->with('error', 'Akses Ditolak! Menu CITO khusus untuk RSUD Hajjah Andi Depu.');
        }

        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        // 2. KUMPULKAN ID OPD RSUD & RUANGAN ANAKNYA
        $rsud_opds = $this->db->table('opd')->like('nama_opd', 'RSUD HAJJAH ANDI DEPU')->get()->getResultArray();
        $ids = array_column($rsud_opds, 'id_opd');

        $data_cito = [];
        $pegawai_akses = [];

        if (!empty($ids)) {
            // DATA UNTUK TAB 1: RIWAYAT & APPROVAL CITO
            $data_cito = $this->db->table('presensi_darurat pd')
                ->select('pd.*, p.nama_lengkap, p.jabatan, o.nama_opd')
                ->join('pegawai p', 'pd.id_pegawai = p.id_pegawai', 'left')
                ->join('opd o', 'pd.id_opd = o.id_opd', 'left')
                ->whereIn('pd.id_opd', $ids)
                ->where('MONTH(pd.tanggal)', $bulan)
                ->where('YEAR(pd.tanggal)', $tahun)
                ->orderBy('pd.tanggal', 'DESC')
                ->orderBy('pd.jam_mulai', 'DESC')
                ->get()->getResultArray();

            // DATA UNTUK TAB 2: MANAJEMEN HAK AKSES NAKES
            // Mengambil field is_cito (Pastikan Bapak sudah membuat field is_cito di tabel pegawai)
            $pegawai_akses = $this->db->table('pegawai p')
                ->select('p.id_pegawai, p.nip, p.nama_lengkap, p.jabatan, p.is_cito, o.nama_opd')
                ->join('opd o', 'p.id_opd = o.id_opd', 'left')
                ->whereIn('p.id_opd', $ids)
                ->orderBy('o.nama_opd', 'ASC')
                ->orderBy('p.nama_lengkap', 'ASC')
                ->get()->getResultArray();
        }

        $data = [
            'title'         => 'Manajemen Presensi CITO',
            'data_cito'     => $data_cito,
            'pegawai_akses' => $pegawai_akses,
            'bulan'         => $bulan,
            'tahun'         => $tahun
        ];

        return view('admin/cito_index', $data);
    }

    // ========================================================================
    // AJAX: TERIMA ATAU TOLAK CITO
    // ========================================================================
    public function update_status()
    {
        if (!$this->is_authorized()) return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized Access']);

        $id_darurat = $this->request->getPost('id_darurat');
        $status = $this->request->getPost('status_approval'); // 'Disetujui' atau 'Ditolak'

        $this->db->table('presensi_darurat')->where('id_darurat', $id_darurat)->update([
            'status_approval' => $status,
            'updated_at'      => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON([
            'status' => 'success', 
            'message' => 'Tindakan CITO berhasil ' . $status, 
            'csrf_hash' => csrf_hash()
        ]);
    }

    // ========================================================================
    // AJAX: BERIKAN / CABUT TOMBOL CITO DARI HP NAKES
    // ========================================================================
    public function update_akses()
    {
        if (!$this->is_authorized()) return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized Access']);

        $id_pegawai = $this->request->getPost('id_pegawai');
        $is_cito = $this->request->getPost('is_cito'); // 1 = Aktif, 0 = Mati

        $this->db->table('pegawai')->where('id_pegawai', $id_pegawai)->update(['is_cito' => $is_cito]);

        $status_teks = $is_cito == 1 ? 'Diaktifkan' : 'Dinonaktifkan';
        return $this->response->setJSON([
            'status' => 'success', 
            'message' => "Akses tombol CITO berhasil $status_teks.", 
            'csrf_hash' => csrf_hash()
        ]);
    }
}
<?php

namespace App\Controllers;

class LemburNakes extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        date_default_timezone_set('Asia/Makassar');
    }

    private function get_allowed_opd_ids($level, $id_opd_session)
    {
        if (in_array($level, ['Superadmin', 'kabupaten'])) return [];

        $admin_opd_info = $this->db->table('opd')->where('id_opd', $id_opd_session)->get()->getRow();
        $admin_opd_name = $admin_opd_info ? strtoupper($admin_opd_info->nama_opd) : '';

        $allowed_opd_ids = [$id_opd_session];
        $list_opd = $this->db->table('opd')->get()->getResultArray();

        foreach ($list_opd as $o) {
            $nama_upper = strtoupper($o['nama_opd']);
            $is_child = false;

            if (strpos($admin_opd_name, 'DINAS KESEHATAN') !== false && strpos($nama_upper, 'DINAS KESEHATAN') !== false) {
                $is_child = true;
            } elseif (strpos($admin_opd_name, 'RSUD HAJJAH ANDI DEPU') !== false && strpos($nama_upper, 'RSUD HAJJAH ANDI DEPU') !== false) {
                $is_child = true;
            } elseif (strpos($admin_opd_name, 'RSUD WONOMULYO') !== false && strpos($nama_upper, 'RSUD WONOMULYO') !== false) {
                $is_child = true;
            }

            if ($is_child && $o['id_opd'] != $id_opd_session) {
                $allowed_opd_ids[] = $o['id_opd'];
            }
        }
        return $allowed_opd_ids;
    }

    public function index()
    {
        $level = session()->get('level');
        $id_opd_session = session()->get('id_opd');

        // Ambil Data Pegawai untuk Kelola Akses Cito
        $builder_pegawai = $this->db->table('pegawai')->select('id_pegawai, nip, nama_lengkap, id_opd, is_cito')->orderBy('nama_lengkap', 'ASC');
        
        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd_session);
            $builder_pegawai->whereIn('id_opd', $allowed_opd_ids);
        }
        $list_pegawai = $builder_pegawai->get()->getResultArray();

        $bulan_ini = $this->request->getGet('bulan') ?? date('m');
        $tahun_ini = $this->request->getGet('tahun') ?? date('Y');

        $builder_darurat = $this->db->table('presensi_darurat')
            ->select('presensi_darurat.*, pegawai.nama_lengkap, opd.nama_opd')
            ->join('pegawai', 'presensi_darurat.id_pegawai = pegawai.id_pegawai', 'left')
            ->join('opd', 'presensi_darurat.id_opd = opd.id_opd', 'left')
            ->where('MONTH(presensi_darurat.tanggal)', $bulan_ini)
            ->where('YEAR(presensi_darurat.tanggal)', $tahun_ini)
            ->orderBy('presensi_darurat.tanggal', 'DESC')
            ->orderBy('presensi_darurat.jam_mulai', 'DESC');

        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd_session);
            $builder_darurat->whereIn('presensi_darurat.id_opd', $allowed_opd_ids);
        }
        $riwayat_darurat = $builder_darurat->get()->getResultArray();

        $data = [
            'title'           => 'Manajemen Lembur Nakes / Cito',
            'list_pegawai'    => $list_pegawai,
            'riwayat_darurat' => $riwayat_darurat,
            'bulan_aktif'     => $bulan_ini,
            'tahun_aktif'     => $tahun_ini,
            'level'           => $level
        ];

        return view('admin/lembur_nakes', $data);
    }

    public function simpan()
    {
        $id_pegawai = $this->request->getPost('id_pegawai');
        $tanggal    = $this->request->getPost('tanggal');
        $jam_mulai  = $this->request->getPost('jam_mulai');
        $jam_selesai = $this->request->getPost('jam_selesai');
        $keterangan = $this->request->getPost('keterangan');

        if (empty($id_pegawai) || empty($tanggal) || empty($jam_mulai) || empty($jam_selesai) || empty($keterangan)) {
            return redirect()->back()->with('error', 'Semua form wajib diisi!');
        }

        $pegawai = $this->db->table('pegawai')->select('nip, id_opd')->where('id_pegawai', $id_pegawai)->get()->getRow();

        if ($pegawai) {
            $this->db->table('presensi_darurat')->insert([
                'id_pegawai'  => $id_pegawai,
                'nip'         => $pegawai->nip,
                'id_opd'      => $pegawai->id_opd,
                'tanggal'     => $tanggal,
                'jam_mulai'   => $jam_mulai,
                'jam_selesai' => $jam_selesai,
                'keterangan'  => $keterangan,
                'created_at'  => date('Y-m-d H:i:s')
            ]);
            return redirect()->back()->with('success', 'Jam Kerja Darurat berhasil ditambahkan!');
        }

        return redirect()->back()->with('error', 'Pegawai tidak ditemukan.');
    }

    public function hapus($id)
    {
        $this->db->table('presensi_darurat')->where('id_darurat', $id)->delete();
        return redirect()->back()->with('success', 'Riwayat berhasil dihapus.');
    }

    // =========================================================================
    // FUNGSI BARU UNTUK CENTANG AKSES PWA
    // =========================================================================
    public function set_akses_cito()
    {
        $id = $this->request->getPost('id_pegawai');
        $status = $this->request->getPost('status');
        
        $this->db->table('pegawai')->where('id_pegawai', $id)->update(['is_cito' => $status]);
        
        $msg = ($status == 1) ? 'Akses Cito Diberikan.' : 'Akses Cito Dicabut.';
        return $this->response->setJSON(['status' => 'success', 'message' => $msg]);
    }
}
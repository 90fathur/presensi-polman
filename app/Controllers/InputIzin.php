<?php

namespace App\Controllers;

class InputIzin extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        date_default_timezone_set('Asia/Makassar');
    }

    // ========================================================================
    // [FUNGSI BANTUAN]: DETEKSI SUB-ADMIN (UPTD ANAK)
    // ========================================================================
    private function is_sub_admin($id_opd_session)
    {
        $admin_opd_info = $this->db->table('opd')->where('id_opd', $id_opd_session)->get()->getRow();
        if ($admin_opd_info && strpos($admin_opd_info->nama_opd, ' - ') !== false) {
            return true;
        }
        return false;
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
            } elseif (strpos($admin_opd_name, 'DINAS PENDIDIKAN') !== false && (strpos($nama_upper, 'SDN ') !== false || strpos($nama_upper, 'SMPN ') !== false || strpos($nama_upper, 'TK ') !== false)) {
                $is_child = true;
            } elseif (strpos($admin_opd_name, 'RSUD HAJJAH ANDI DEPU') !== false && strpos($nama_upper, 'RSUD HAJJAH ANDI DEPU') !== false) {
                $is_child = true;
            } elseif (strpos($admin_opd_name, 'RSUD WONOMULYO') !== false && strpos($nama_upper, 'RSUD WONOMULYO') !== false) {
                $is_child = true;
            } elseif (strpos($nama_upper, $admin_opd_name . ' - ') === 0) {
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

        if ($level == 'opd' && $this->is_sub_admin($id_opd_session)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Akses Ditolak! Manajemen Izin dan Tugas Luar hanya dapat dilakukan oleh Admin Instansi Induk.');
        }

        $builderPegawai = $this->db->table('pegawai')
            ->select('pegawai.id_pegawai, pegawai.nama_lengkap, pegawai.nip, opd.nama_opd')
            ->join('opd', 'opd.id_opd = pegawai.id_opd', 'left');

        if ($level == 'opd') {
            $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd_session);
            $builderPegawai->whereIn('pegawai.id_opd', $allowed_opd_ids);
        }

        $list_opd_all = [];
        if (in_array($level, ['Superadmin', 'kabupaten'])) {
            $list_opd_all = $this->db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResult();
        }

        $data = [
            'title'      => 'Input Izin, Sakit & Tugas Luar',
            'pegawai'    => $builderPegawai->get()->getResult(),
            'list_opd'   => $list_opd_all,
            'level'      => $level
        ];

        return view('admin/input_izin_view', $data);
    }

    public function ajax_list()
    {
        try {
            $request = \Config\Services::request();
            $level = session()->get('level');
            $id_opd_session = session()->get('id_opd');

            if ($level == 'opd' && $this->is_sub_admin($id_opd_session)) {
                return $this->response->setJSON([
                    "draw" => intval($request->getPost('draw') ?? 1),
                    "recordsTotal" => 0,
                    "recordsFiltered" => 0,
                    "data" => []
                ]);
            }

            $filter_opd = $request->getPost('filter_opd');

            if (in_array($level, ['Superadmin', 'kabupaten']) && empty($filter_opd)) {
                return $this->response->setJSON([
                    "draw" => intval($request->getPost('draw') ?? 1),
                    "recordsTotal" => 0,
                    "recordsFiltered" => 0,
                    "data" => []
                ]);
            }

            $searchData = $request->getPost('search');
            $search     = is_array($searchData) && isset($searchData['value']) ? $searchData['value'] : '';
            $start      = intval($request->getPost('start') ?? 0);
            $length     = intval($request->getPost('length') ?? 10);
            $draw       = intval($request->getPost('draw') ?? 1);

            $builder = $this->db->table('pengajuan_absen')
                ->select('pengajuan_absen.*, pegawai.nama_lengkap, pegawai.nip, opd.nama_opd')
                ->join('pegawai', 'pegawai.nip = pengajuan_absen.nip', 'left')
                ->join('opd', 'opd.id_opd = pegawai.id_opd', 'left')
                ->where('pengajuan_absen.status_approval', 'Disetujui');

            if ($level == 'opd') {
                $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd_session);
                if (empty($allowed_opd_ids)) $allowed_opd_ids = [0];
                $builder->whereIn('pegawai.id_opd', $allowed_opd_ids);
            } else {
                if (!empty($filter_opd)) {
                    $builder->where('pegawai.id_opd', $filter_opd);
                }
            }

            if (!empty($search)) {
                $builder->groupStart()
                    ->like('pegawai.nama_lengkap', $search)
                    ->orLike('pegawai.nip', $search)
                    ->orLike('pengajuan_absen.kategori', $search)
                    ->groupEnd();
            }

            $recordsFiltered = $builder->countAllResults(false);

            $builder->orderBy('pengajuan_absen.id_pengajuan', 'DESC');
            if ($length != -1) {
                $builder->limit($length, $start);
            }
            $query = $builder->get()->getResult();

            $totalBuilder = $this->db->table('pengajuan_absen')->join('pegawai', 'pegawai.nip = pengajuan_absen.nip', 'left')->where('pengajuan_absen.status_approval', 'Disetujui');
            if ($level == 'opd') {
                $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd_session);
                if (empty($allowed_opd_ids)) $allowed_opd_ids = [0];
                $totalBuilder->whereIn('pegawai.id_opd', $allowed_opd_ids);
            } else {
                if (!empty($filter_opd)) $totalBuilder->where('pegawai.id_opd', $filter_opd);
            }
            $recordsTotal = $totalBuilder->countAllResults();

            $data = [];
            foreach ($query as $c) {
                $row = [];

                $row[] = '<div class="ps-2 fw-bold text-dark">' . esc($c->nama_lengkap) . '</div>
                          <div class="ps-2 small text-muted mb-1">' . esc($c->nip) . '</div>
                          <div class="ps-2"><span class="badge bg-light text-secondary border border-secondary-subtle fw-normal" style="font-size: 0.65rem;"><i class="fas fa-building me-1"></i> ' . esc($c->nama_opd ?? '-') . '</span></div>';

                $row[] = '<span class="small fw-bold text-primary">' . esc($c->kategori) . '</span><br><span class="small text-muted">' . esc($c->sub_kategori ?? '') . '</span>';

                $row[] = '<div class="small text-dark">' . date('d/m/Y', strtotime($c->tanggal_mulai ?? '')) . '</div>
                          <div class="text-muted" style="font-size: 11px;">s/d ' . date('d/m/Y', strtotime($c->tanggal_selesai ?? '')) . '</div>';

                if ($c->file_bukti) {
                    $row[] = '<div class="text-center"><a href="' . base_url('uploads/bukti_absen/' . $c->file_bukti) . '" target="_blank" class="btn btn-outline-info btn-xs rounded-pill px-3 shadow-sm" style="font-size: 11px;"><i class="fas fa-file-alt me-1"></i> Lihat Bukti</a></div>';
                } else {
                    $row[] = '<div class="text-center"><span class="text-muted small" style="font-size: 11px;">Tidak ada berkas</span></div>';
                }

                $row[] = '<div class="text-center"><a href="' . base_url('inputizin/hapus/' . $c->id_pengajuan) . '" class="btn btn-light btn-sm rounded-circle text-danger shadow-sm" onclick="return confirm(\'Hapus riwayat pengajuan ini dan kembalikan absen ke status semula?\')"><i class="fas fa-trash"></i></a></div>';

                $data[] = $row;
            }

            return $this->response->setJSON([
                "draw"            => $draw,
                "recordsTotal"    => $recordsTotal,
                "recordsFiltered" => $recordsFiltered,
                "data"            => $data,
                "csrf_hash"       => csrf_hash()
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                "error" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine()
            ]);
        }
    }

    public function simpan()
    {
        if (session()->get('level') == 'opd' && $this->is_sub_admin(session()->get('id_opd'))) {
            return redirect()->back()->with('error', 'Akses Ditolak! Pengajuan hanya dapat dilakukan oleh Admin Instansi Induk.');
        }

        $pegawai_id = $this->request->getPost('pegawai_id');
        $pegawai = $this->db->table('pegawai')->where('id_pegawai', $pegawai_id)->get()->getRow();
        if (!$pegawai) {
            return redirect()->back()->with('error', 'Pegawai tidak ditemukan.');
        }

        $fileBukti = $this->request->getFile('file_bukti');
        $namaBukti = null;

        if ($fileBukti && $fileBukti->isValid() && !$fileBukti->hasMoved()) {
            if ($fileBukti->getSizeByUnit('mb') < 10) {
                $namaBukti = $fileBukti->getRandomName();
                $fileBukti->move('uploads/bukti_absen/', $namaBukti);
            }
        }

        $kategori = $this->request->getPost('kategori');
        $sub_kategori = $this->request->getPost('sub_kategori');
        $keterangan = $this->request->getPost('keterangan');

        $data = [
            'nip'             => $pegawai->nip,
            'kategori'        => $kategori,
            'sub_kategori'    => $sub_kategori,
            'slot_waktu'      => 'Penuh',
            'tanggal_mulai'   => $this->request->getPost('tgl_mulai'),
            'tanggal_selesai' => $this->request->getPost('tgl_selesai'),
            'keterangan'      => $keterangan . " | Diinput oleh Admin",
            'file_bukti'      => $namaBukti,
            'status_approval' => 'Disetujui',
            'created_at'      => date('Y-m-d H:i:s')
        ];

        $this->db->transStart();

        $this->db->table('pengajuan_absen')->insert($data);

        // Eksekusi logic approval untuk insert ke presensi
        $begin = new \DateTime($data['tanggal_mulai']);
        $end   = new \DateTime($data['tanggal_selesai']);
        $end->modify('+1 day');
        $interval  = new \DateInterval('P1D');
        $daterange = new \DatePeriod($begin, $interval, $end);

        $hari_indo = ['Sun' => 'minggu', 'Mon' => 'senin', 'Tue' => 'selasa', 'Wed' => 'rabu', 'Thu' => 'kamis', 'Fri' => 'jumat', 'Sat' => 'sabtu'];

        foreach ($daterange as $date) {
            $tgl = $date->format("Y-m-d");
            $nama_hari = $hari_indo[date('D', strtotime($tgl))];
            $cek_presensi = $this->db->table('presensi')->where(['nip' => $pegawai->nip, 'tanggal' => $tgl])->get()->getRowArray();

            $id_jam_aktif = 1;
            $jam_masuk_seharusnya = '07:30:00';
            $jam_pulang_seharusnya = '16:00:00';

            $cek_plotting = $this->db->table('plotting_shift')->where(['id_pegawai' => $pegawai->id_pegawai, 'tanggal' => $tgl])->get()->getRow();
            if (!$cek_plotting && $this->db->fieldExists('tanggal_mulai', 'plotting_shift')) {
                $cek_plotting = $this->db->table('plotting_shift')
                    ->where('id_pegawai', $pegawai->id_pegawai)
                    ->where('tanggal_mulai <=', $tgl)
                    ->where('tanggal_selesai >=', $tgl)
                    ->get()->getRow();
            }
            $opdInfo = $this->db->table('opd')->select('id_jam_kerja')->where('id_opd', $pegawai->id_opd)->get()->getRow();

            $id_jam_aktif = ($cek_plotting) ? $cek_plotting->id_jam_kerja : ($opdInfo->id_jam_kerja ?? 1);

            $jadwal_detail = $this->db->table('jam_kerja_detail')->where(['id_jam_kerja' => $id_jam_aktif, 'hari' => $nama_hari])->get()->getRow();
            if ($jadwal_detail) {
                $jm = str_replace('.', ':', $jadwal_detail->jam_masuk);
                $jp = str_replace('.', ':', $jadwal_detail->jam_pulang);
                $jam_masuk_seharusnya  = (strlen($jm) == 5) ? $jm . ':00' : $jm;
                $jam_pulang_seharusnya = (strlen($jp) == 5) ? $jp . ':00' : $jp;
            }

            $waktu_masuk_dinamis = date('H:i:s', strtotime($jam_masuk_seharusnya) - 60);
            $waktu_siang_dinamis = date('H:i:s', strtotime($jam_masuk_seharusnya) + 14400);
            $waktu_pulang_dinamis = date('H:i:s', strtotime($jam_pulang_seharusnya) + 60);

            $updateData = [
                'deskripsi'  => strtoupper($kategori) . " (" . ($sub_kategori ?? 'Umum') . ")",
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $updateData['jam_masuk_aktual']  = $waktu_masuk_dinamis;
            $updateData['jam_siang']         = $waktu_siang_dinamis;
            $updateData['jam_pulang_aktual'] = $waktu_pulang_dinamis;

            $updateData['status_masuk']      = $kategori;
            $updateData['status_pulang']     = $kategori;
            $updateData['kategori']          = $kategori;
            $updateData['statusijin']        = $kategori;

            if ($cek_presensi) {
                $this->db->table('presensi')->where('id_presensi', $cek_presensi['id_presensi'])->update($updateData);
            } else {
                $updateData['id_pegawai'] = $pegawai->id_pegawai;
                $updateData['nip']        = $pegawai->nip;
                $updateData['tanggal']    = $tgl;
                $updateData['created_at'] = date('Y-m-d H:i:s');
                $updateData['id_jam_kerja_snapshot'] = $id_jam_aktif;
                $updateData['jam_masuk_seharusnya']  = $jam_masuk_seharusnya;
                $updateData['jam_pulang_seharusnya'] = $jam_pulang_seharusnya;

                $this->db->table('presensi')->insert($updateData);
            }
        }

        $this->db->transComplete();

        return redirect()->to(base_url('inputizin'))->with('success', 'Data Izin/Sakit/Tugas Luar berhasil disimpan dan masuk ke riwayat Presensi.');
    }

    public function hapus($id)
    {
        if (session()->get('level') == 'opd' && $this->is_sub_admin(session()->get('id_opd'))) {
            return redirect()->back()->with('error', 'Akses Ditolak! Penghapusan hanya dapat dilakukan oleh Admin Instansi Induk.');
        }

        $pengajuan = $this->db->table('pengajuan_absen')->where('id_pengajuan', $id)->get()->getRow();
        if ($pengajuan) {
            $this->db->transStart();

            $begin = new \DateTime($pengajuan->tanggal_mulai);
            $end   = new \DateTime($pengajuan->tanggal_selesai);
            $end->modify('+1 day');
            $interval  = new \DateInterval('P1D');
            $daterange = new \DatePeriod($begin, $interval, $end);

            foreach ($daterange as $date) {
                $tgl = $date->format("Y-m-d");
                $this->db->table('presensi')
                    ->where('nip', $pengajuan->nip)
                    ->where('tanggal', $tgl)
                    ->where('kategori', $pengajuan->kategori)
                    ->delete();
            }

            $this->db->table('pengajuan_absen')->where('id_pengajuan', $id)->delete();
            $this->db->transComplete();
        }

        return redirect()->to(base_url('inputizin'))->with('success', 'Data pengajuan berhasil dihapus dan presensi dikembalikan.');
    }
}

<?php

namespace App\Controllers;

class Cuti extends BaseController
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
        // Jika nama OPD mengandung spasi-strip-spasi " - ", berarti dia adalah anak UPTD/Ruangan
        if ($admin_opd_info && strpos($admin_opd_info->nama_opd, ' - ') !== false) {
            return true;
        }
        return false;
    }

    // ========================================================================
    // [FUNGSI BANTUAN]: AMBIL SELURUH OPD ANAK JIKA YANG LOGIN ADALAH INDUK
    // ========================================================================
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
                // Deteksi umum untuk dinas lain (misal: "DINAS PU - UPTD BENGKEL")
                $is_child = true;
            }

            if ($is_child && $o['id_opd'] != $id_opd_session) {
                $allowed_opd_ids[] = $o['id_opd'];
            }
        }
        return $allowed_opd_ids;
    }


    // ========================================================================
    // 1. TAMPILAN HALAMAN
    // ========================================================================
    public function index()
    {
        $level = session()->get('level');
        $id_opd_session = session()->get('id_opd');

        // [BLOKIR SUB ADMIN]
        if ($level == 'opd' && $this->is_sub_admin($id_opd_session)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Akses Ditolak! Manajemen dan Pengajuan Cuti hanya dapat dilakukan oleh Admin Instansi Induk.');
        }

        // Siapkan Builder untuk Dropdown Modal Pegawai
        $builderPegawai = $this->db->table('pegawai')
            ->select('pegawai.id_pegawai, pegawai.nama_lengkap, pegawai.nip, opd.nama_opd')
            ->join('opd', 'opd.id_opd = pegawai.id_opd', 'left');

        if ($level == 'opd') {
            // [PERBAIKAN]: Admin Induk bisa mengajukan cuti untuk anak-anak UPTD-nya
            $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd_session);
            $builderPegawai->whereIn('pegawai.id_opd', $allowed_opd_ids);
        }

        // Ambil list OPD untuk dropdown filter (khusus Kabupaten)
        $list_opd_all = [];
        if (in_array($level, ['Superadmin', 'kabupaten'])) {
            $list_opd_all = $this->db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResult();
        }

        $data = [
            'title'      => 'Data Cuti Pegawai',
            'pegawai'    => $builderPegawai->get()->getResult(),
            'jenis_cuti' => $this->db->table('master_cuti')->get()->getResult(),
            'list_opd'   => $list_opd_all,
            'level'      => $level
        ];

        return view('admin/cuti_view', $data);
    }

    // ========================================================================
    // 2. SERVER-SIDE PROCESSING DATATABLES
    // ========================================================================
    public function ajax_list()
    {
        $request = \Config\Services::request();
        $level = session()->get('level');
        $id_opd_session = session()->get('id_opd');

        // [BLOKIR SUB ADMIN DI AJAX]
        if ($level == 'opd' && $this->is_sub_admin($id_opd_session)) {
            return $this->response->setJSON([
                "draw" => intval($request->getPost('draw') ?? 1),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            ]);
        }

        // Tangkap parameter dari DataTables & Filter Kustom
        $filter_opd = $request->getPost('filter_opd');
        $search     = $request->getPost('search')['value'] ?? '';
        $start      = intval($request->getPost('start') ?? 0);
        $length     = intval($request->getPost('length') ?? 10);
        $draw       = intval($request->getPost('draw') ?? 1);

        $builder = $this->db->table('pengajuan_cuti')
            ->select('pengajuan_cuti.*, pegawai.nama_lengkap, pegawai.nip, master_cuti.nama_cuti, opd.nama_opd')
            ->join('pegawai', 'pegawai.id_pegawai = pengajuan_cuti.pegawai_id')
            ->join('opd', 'opd.id_opd = pegawai.id_opd', 'left')
            ->join('master_cuti', 'master_cuti.id_cuti = pengajuan_cuti.cuti_id');

        // Filter Hak Akses & Pilihan OPD
        if ($level == 'opd') {
            // [PERBAIKAN]: Tarik data cuti anak UPTD juga
            $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd_session);
            $builder->whereIn('pegawai.id_opd', $allowed_opd_ids);
        } else {
            if (!empty($filter_opd)) {
                $builder->where('pegawai.id_opd', $filter_opd);
            }
        }

        // Filter Pencarian
        if (!empty($search)) {
            $builder->groupStart()
                ->like('pegawai.nama_lengkap', $search)
                ->orLike('pegawai.nip', $search)
                ->orLike('master_cuti.nama_cuti', $search)
                ->groupEnd();
        }

        // Hitung total data setelah difilter (untuk paginasi)
        $recordsFiltered = $builder->countAllResults(false);

        // Sorting & Limit per halaman
        $builder->orderBy('pengajuan_cuti.id_pengajuan', 'DESC');
        if ($length != -1) {
            $builder->limit($length, $start);
        }
        $query = $builder->get()->getResult();

        // Hitung total seluruh data tanpa filter (wajib untuk DataTables)
        $totalBuilder = $this->db->table('pengajuan_cuti')->join('pegawai', 'pegawai.id_pegawai = pengajuan_cuti.pegawai_id');
        if ($level == 'opd') {
            $totalBuilder->whereIn('pegawai.id_opd', $this->get_allowed_opd_ids($level, $id_opd_session));
        } else {
            if (!empty($filter_opd)) $totalBuilder->where('pegawai.id_opd', $filter_opd);
        }
        $recordsTotal = $totalBuilder->countAllResults();

        // Format Tampilan HTML
        $data = [];
        foreach ($query as $c) {
            $row = [];

            // Kolom 1: Pegawai & Instansi
            $row[] = '<div class="ps-2 fw-bold text-dark">' . esc($c->nama_lengkap) . '</div>
                      <div class="ps-2 small text-muted mb-1">' . esc($c->nip) . '</div>
                      <div class="ps-2"><span class="badge bg-light text-secondary border border-secondary-subtle fw-normal" style="font-size: 0.65rem;"><i class="fas fa-building me-1"></i> ' . esc($c->nama_opd ?? '-') . '</span></div>';

            // Kolom 2: Jenis Cuti
            $row[] = '<span class="small fw-bold text-primary">' . esc($c->nama_cuti) . '</span>';

            // Kolom 3: Durasi
            $row[] = '<div class="text-center"><span class="badge bg-light text-dark border px-3 rounded-pill fw-normal">' . $c->jumlah_hari . ' Hari</span></div>';

            // Kolom 4: Tanggal
            $row[] = '<div class="small text-dark">' . date('d/m/Y', strtotime($c->tgl_mulai)) . '</div>
                      <div class="text-muted" style="font-size: 11px;">s/d ' . date('d/m/Y', strtotime($c->tgl_selesai)) . '</div>';

            // Kolom 5: Berkas
            if ($c->berkas) {
                $row[] = '<div class="text-center"><a href="' . base_url('uploads/cuti/' . $c->berkas) . '" target="_blank" class="btn btn-outline-info btn-xs rounded-pill px-3 shadow-sm" style="font-size: 11px;"><i class="fas fa-file-alt me-1"></i> Lihat Surat</a></div>';
            } else {
                $row[] = '<div class="text-center"><span class="text-muted small" style="font-size: 11px;">Tidak ada berkas</span></div>';
            }

            // Kolom 6: Aksi Hapus
            $row[] = '<div class="text-center"><a href="' . base_url('cuti/hapus/' . $c->id_pengajuan) . '" class="btn btn-light btn-sm rounded-circle text-danger shadow-sm" onclick="return confirm(\'Hapus data cuti ini?\')"><i class="fas fa-trash"></i></a></div>';

            $data[] = $row;
        }

        return $this->response->setJSON([
            "draw"            => $draw,
            "recordsTotal"    => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data"            => $data,
            "csrf_hash"       => csrf_hash() // Penting untuk keamanan CI4
        ]);
    }

    // ========================================================================
    // FUNGSI SIMPAN CUTI + NOTIFIKASI WA GATEWAY (DENGAN LINK BERKAS)
    // ========================================================================
    public function simpan()
    {
        // [BLOKIR SUB ADMIN DARI POST SUBMIT]
        if (session()->get('level') == 'opd' && $this->is_sub_admin(session()->get('id_opd'))) {
            return redirect()->back()->with('error', 'Akses Ditolak! Pengajuan cuti hanya dapat dilakukan oleh Admin Instansi Induk.');
        }

        // 1. Ambil file dari form
        $fileBerkas = $this->request->getFile('berkas');
        $namaBerkas = null;

        // 2. Cek apakah ada file yang diupload
        if ($fileBerkas && $fileBerkas->isValid() && !$fileBerkas->hasMoved()) {
            // Validasi file (PDF, JPG, PNG) max 10MB
            if ($fileBerkas->getSizeByUnit('mb') < 10) {
                $namaBerkas = $fileBerkas->getRandomName(); // Nama file unik
                $fileBerkas->move('uploads/cuti/', $namaBerkas); // Pindahkan ke folder public/uploads/cuti/
            }
        }

        $tgl_mulai   = $this->request->getPost('tgl_mulai');
        $tgl_selesai = $this->request->getPost('tgl_selesai') ?: $tgl_mulai;

        $data = [
            'pegawai_id'  => $this->request->getPost('pegawai_id'),
            'cuti_id'     => $this->request->getPost('cuti_id'),
            'tgl_mulai'   => $tgl_mulai,
            'tgl_selesai' => $tgl_selesai,
            'jumlah_hari' => $this->request->getPost('jumlah_hari'),
            'alasan'      => $this->request->getPost('alasan'),
            'berkas'      => $namaBerkas, // Simpan nama file ke database
            'status'      => 'disetujui'
        ];

        // Insert ke database
        $this->db->table('pengajuan_cuti')->insert($data);

        // --- [EKSEKUSI WHATSAPP GATEWAY & SINKRONISASI PRESENSI] ---
        // Ambil data detail pegawai & OPD
        $pegawaiInfo = $this->db->table('pegawai')
            ->select('pegawai.nama_lengkap, pegawai.nip, pegawai.nomor_hp, opd.nama_opd')
            ->join('opd', 'opd.id_opd = pegawai.id_opd', 'left')
            ->where('pegawai.id_pegawai', $data['pegawai_id'])
            ->get()->getRow();

        // Ambil nama jenis cuti
        $cutiInfo = $this->db->table('master_cuti')
            ->select('nama_cuti')
            ->where('id_cuti', $data['cuti_id'])
            ->get()->getRow();
        $jenis_cuti = $cutiInfo ? strtoupper($cutiInfo->nama_cuti) : 'CUTI';

        // Sinkronisasi otomatis ke tabel presensi (agar sinkron menyeluruh)
        if ($pegawaiInfo && !empty($pegawaiInfo->nip)) {
            $begin = new \DateTime($tgl_mulai);
            $end   = new \DateTime($tgl_selesai);
            $end->modify('+1 day');
            $interval  = new \DateInterval('P1D');
            $daterange = new \DatePeriod($begin, $interval, $end);

            foreach ($daterange as $date) {
                $tgl = $date->format("Y-m-d");
                $cek_presensi = $this->db->table('presensi')->where(['nip' => $pegawaiInfo->nip, 'tanggal' => $tgl])->get()->getRowArray();

                $updateData = [
                    'deskripsi'      => "CUTI (" . $jenis_cuti . ")",
                    'kategori'       => 'Cuti',
                    'statusijin'     => 'Cuti',
                    'status_masuk'   => 'Cuti',
                    'status_pulang'  => 'Cuti',
                    'updated_at'     => date('Y-m-d H:i:s')
                ];

                if ($cek_presensi) {
                    $this->db->table('presensi')->where('id_presensi', $cek_presensi['id_presensi'])->update($updateData);
                } else {
                    $updateData['id_pegawai'] = $data['pegawai_id'];
                    $updateData['nip']        = $pegawaiInfo->nip;
                    $updateData['tanggal']    = $tgl;
                    $updateData['created_at'] = date('Y-m-d H:i:s');
                    $this->db->table('presensi')->insert($updateData);
                }
            }
        }

        if ($pegawaiInfo && !empty($pegawaiInfo->nomor_hp)) {
            helper('whatsapp');

            // Format Tanggal
            $tgl_m = date('d/m/Y', strtotime($data['tgl_mulai']));
            $tgl_s = date('d/m/Y', strtotime($data['tgl_selesai']));
            $rentang_waktu = ($tgl_m == $tgl_s) ? $tgl_m : "$tgl_m s/d $tgl_s";

            $wa_msg = "*[PRESENSI POLMAN 2.0]*\n";
            $wa_msg .= "🏖️ *INFO PENETAPAN $jenis_cuti*\n\n";
            $wa_msg .= "Halo *{$pegawaiInfo->nama_lengkap}*,\n";
            $wa_msg .= "NIP: {$pegawaiInfo->nip}\n";
            $wa_msg .= "Instansi: " . ($pegawaiInfo->nama_opd ?? '-') . "\n\n";
            $wa_msg .= "Data *{$jenis_cuti}* Anda untuk tanggal *$rentang_waktu* ({$data['jumlah_hari']} Hari) telah *DISETUJUI* dan dicatat ke dalam sistem oleh Admin.\n\n";

            // [TAMBAHAN]: Sisipkan Tautan PDF/Surat jika file diupload
            if (!empty($namaBerkas)) {
                $wa_msg .= "*Lampiran Surat:* " . base_url('uploads/cuti/' . $namaBerkas) . "\n\n";
            }

            if (!empty($data['alasan'])) {
                $wa_msg .= "*Keterangan:* {$data['alasan']}\n\n";
            }

            $wa_msg .= "_Pesan otomatis dari Sistem Presensi Polman 2.0._";

            send_whatsapp($pegawaiInfo->nomor_hp, $wa_msg);
        }
        // ---------------------------------------------------

        return redirect()->to(base_url('cuti'))->with('success', 'Data cuti dan berkas berhasil disimpan');
    }

    public function hapus($id)
    {
        // [BLOKIR SUB ADMIN DARI PENGHAPUSAN]
        if (session()->get('level') == 'opd' && $this->is_sub_admin(session()->get('id_opd'))) {
            return redirect()->back()->with('error', 'Akses Ditolak! Penghapusan cuti hanya dapat dilakukan oleh Admin Instansi Induk.');
        }

        $cuti = $this->db->table('pengajuan_cuti')->where('id_pengajuan', $id)->get()->getRow();
        if ($cuti) {
            $pegawaiInfo = $this->db->table('pegawai')->select('nip')->where('id_pegawai', $cuti->pegawai_id)->get()->getRow();
            if ($pegawaiInfo && !empty($pegawaiInfo->nip)) {
                $tgl_s = !empty($cuti->tgl_selesai) ? $cuti->tgl_selesai : $cuti->tgl_mulai;
                // Bersihkan record presensi cuti otomatis
                $this->db->table('presensi')
                    ->where('nip', $pegawaiInfo->nip)
                    ->where('tanggal >=', $cuti->tgl_mulai)
                    ->where('tanggal <=', $tgl_s)
                    ->where('kategori', 'Cuti')
                    ->delete();
            }
            if (!empty($cuti->berkas) && file_exists(FCPATH . 'uploads/cuti/' . $cuti->berkas)) {
                @unlink(FCPATH . 'uploads/cuti/' . $cuti->berkas);
            }
            $this->db->table('pengajuan_cuti')->where('id_pengajuan', $id)->delete();
        }

        return redirect()->to(base_url('cuti'))->with('success', 'Data cuti berhasil dihapus');
    }
}

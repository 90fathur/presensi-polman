<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Plotting extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        date_default_timezone_set('Asia/Makassar');
    }

    // ========================================================================
    // [FUNGSI BANTUAN]: AMBIL SELURUH OPD ANAK JIKA YANG LOGIN ADALAH INDUK
    // ========================================================================
    private function get_allowed_opd_ids($level, $id_opd_session)
    {
        if (in_array($level, ['Superadmin', 'kabupaten'])) return [];

        $admin_opd_info = $this->db->table('opd')->where('id_opd', $id_opd_session)->get()->getRow();
        if (!$admin_opd_info) return [$id_opd_session];

        $admin_opd_name = trim(strtoupper($admin_opd_info->nama_opd));
        $allowed_opd_ids = [$id_opd_session];

        $is_dinkes_induk = ($admin_opd_name === 'DINAS KESEHATAN' || $admin_opd_name === 'DINAS KESEHATAN KABUPATEN POLEWALI MANDAR');
        $is_disdik_induk = ($admin_opd_name === 'DINAS PENDIDIKAN' || $admin_opd_name === 'DINAS PENDIDIKAN DAN KEBUDAYAAN');
        $is_rsud_depu_induk = ($admin_opd_name === 'RSUD HAJJAH ANDI DEPU' || $admin_opd_name === 'RSUD HAJJAH ANDI DEPU POLEWALI MANDAR');
        $is_rsud_wono_induk = ($admin_opd_name === 'RSUD WONOMULYO');

        $list_opd = $this->db->table('opd')->get()->getResultArray();

        foreach ($list_opd as $o) {
            $nama_upper = trim(strtoupper($o['nama_opd']));
            $is_child = false;

            if ($is_dinkes_induk && strpos($nama_upper, 'DINAS KESEHATAN') !== false) {
                $is_child = true;
            } elseif ($is_disdik_induk && (strpos($nama_upper, 'SDN ') !== false || strpos($nama_upper, 'SMPN ') !== false || strpos($nama_upper, 'TK ') !== false)) {
                $is_child = true;
            } elseif ($is_rsud_depu_induk && strpos($nama_upper, 'RSUD HAJJAH ANDI DEPU') !== false) {
                $is_child = true;
            } elseif ($is_rsud_wono_induk && strpos($nama_upper, 'RSUD WONOMULYO') !== false) {
                $is_child = true;
            }

            if (!$is_child && strpos($nama_upper, $admin_opd_name . ' - ') === 0) {
                $is_child = true;
            }

            if ($is_child && $o['id_opd'] != $id_opd_session) {
                $allowed_opd_ids[] = $o['id_opd'];
            }
        }
        return $allowed_opd_ids;
    }

    // ========================================================================
    // [FUNGSI CERDAS BARU]: REKALKULASI PRESENSI JIKA DI-PLOTTING KE MASA LALU/SEKARANG
    // Mengatasi bug Sabu/Minggu libur dan menghitung ulang keterlambatan otomatis.
    // ========================================================================
    private function _recalc_presensi($id_pegawai, $tanggal, $id_jam_kerja)
    {
        $presensi = $this->db->table('presensi')->where(['id_pegawai' => $id_pegawai, 'tanggal' => $tanggal])->get()->getRow();

        // Jika belum ada record absen sama sekali, kita hanya perlu mencatat bahwa ada update plotting, 
        // tapi tidak usah bikin baris kosong di tabel presensi.
        if (!$presensi) return;

        $hari_indo = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];
        $nama_hari = $hari_indo[date('D', strtotime($tanggal))];

        // BUG FIX 1: Cari detail jam masuk sesuai hari
        $detail = $this->db->table('jam_kerja_detail')->where(['id_jam_kerja' => $id_jam_kerja, 'hari' => $nama_hari])->get()->getRow();

        // Jika di-plot hari Sabtu tapi tidak ada settingan untuk hari Sabtu, COPY jadwal hari lain (biasanya Senin)
        if (!$detail) {
            $detail = $this->db->table('jam_kerja_detail')->where('id_jam_kerja', $id_jam_kerja)->get()->getRow();
        }

        // Nilai Default Jaga-jaga
        $jm = '07:30:00';
        $jp = '16:00:00';
        $batas_baik  = 15;
        $batas_cukup = 45;
        $batas_telat = 90;
        $batas_bina  = 150;

        if ($detail) {
            $jm_raw = str_replace('.', ':', $detail->jam_masuk);
            $jp_raw = str_replace('.', ':', $detail->jam_pulang);
            $jm = (strlen($jm_raw) == 5) ? $jm_raw . ':00' : $jm_raw;
            $jp = (strlen($jp_raw) == 5) ? $jp_raw . ':00' : $jp_raw;

            $batas_baik  = $detail->batas_baik_menit ?? 15;
            $batas_cukup = $detail->batas_cukup_menit ?? 45;
            $batas_telat = $detail->batas_telat_menit ?? 90;
            $batas_bina  = $detail->batas_bina_menit ?? 150;
        }

        $updateData = [
            'id_jam_kerja_snapshot' => $id_jam_kerja,
            'jam_masuk_seharusnya'  => $jm,
            'jam_pulang_seharusnya' => $jp
        ];

        // BUG FIX 2: Jika pegawai sudah absen masuk, hitung ulang keterlambatannya (Recalculate)
        if (!empty($presensi->jam_masuk_aktual)) {
            $waktu_target_masuk = strtotime($tanggal . ' ' . $jm);
            $waktu_absen_masuk = (strlen($presensi->jam_masuk_aktual) > 10)
                ? strtotime($presensi->jam_masuk_aktual)
                : strtotime($tanggal . ' ' . $presensi->jam_masuk_aktual);

            $telat_detik = $waktu_absen_masuk - $waktu_target_masuk;
            $telat_menit = ($telat_detik > 0) ? ceil($telat_detik / 60) : 0;

            $updateData['menit_keterlambatan'] = $telat_menit;

            if ($telat_menit <= 0) {
                $updateData['status_masuk'] = 'Sangat Baik / Istimewa';
            } elseif ($telat_menit <= $batas_baik) {
                $updateData['status_masuk'] = 'Baik';
            } elseif ($telat_menit <= $batas_cukup) {
                $updateData['status_masuk'] = 'Cukup Baik';
            } elseif ($telat_menit <= $batas_telat) {
                $updateData['status_masuk'] = 'Terlambat';
            } elseif ($telat_menit <= $batas_bina) {
                $updateData['status_masuk'] = 'Pembinaan';
            } else {
                $updateData['status_masuk'] = 'Teguran';
            }
        }

        // BUG FIX 3: Jika pegawai sudah absen pulang, cek apakah jadwal barunya merubah status (Selesai/Pulang Cepat)
        if (!empty($presensi->jam_pulang_aktual)) {
            $is_cross_midnight = (strtotime($jp) < strtotime($jm));
            $tgl_pulang = $is_cross_midnight ? date('Y-m-d', strtotime($tanggal . ' +1 day')) : $tanggal;
            $waktu_target_pulang = strtotime($tgl_pulang . ' ' . $jp);

            $waktu_absen_pulang = (strlen($presensi->jam_pulang_aktual) > 10)
                ? strtotime($presensi->jam_pulang_aktual)
                : strtotime($tgl_pulang . ' ' . $presensi->jam_pulang_aktual);

            if ($waktu_absen_pulang >= $waktu_target_pulang) {
                $updateData['status_pulang'] = 'Selesai';
            } else {
                $updateData['status_pulang'] = 'Cepat Pulang';
            }
        }

        // Eksekusi update senyap
        $this->db->table('presensi')->where('id_presensi', $presensi->id_presensi)->update($updateData);
    }
    // ========================================================================


    public function index()
    {
        $level  = session()->get('level');
        $id_opd = session()->get('id_opd');

        $builder_pegawai = $this->db->table('pegawai')
            ->select('pegawai.*, opd.nama_opd')
            ->join('opd', 'opd.id_opd = pegawai.id_opd', 'left')
            ->orderBy('pegawai.nama_lengkap', 'ASC');

        $builder_opd     = $this->db->table('opd')->orderBy('nama_opd', 'ASC');

        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd);
            $builder_pegawai->whereIn('pegawai.id_opd', $allowed_opd_ids);
            $builder_opd->whereIn('id_opd', $allowed_opd_ids);
        }

        $data = [
            'level'     => $level,
            'pegawai'   => $builder_pegawai->get()->getResultArray(),
            'opd'       => $builder_opd->get()->getResultArray(),
            'jam_kerja' => $this->db->table('jam_kerja')->where('tipe_jadwal', 'Shift')->get()->getResultArray()
        ];

        return view('plotting/index', $data);
    }

    public function ajax_list()
    {
        try {
            $level   = session()->get('level');
            $id_opd  = session()->get('id_opd');

            $start  = intval($this->request->getPost('start') ?? 0);
            $length = intval($this->request->getPost('length') ?? 10);

            $postSearch = $this->request->getPost('search');
            $search = (is_array($postSearch) && isset($postSearch['value'])) ? $postSearch['value'] : '';

            $draw   = intval($this->request->getPost('draw') ?? 1);

            $builder = $this->db->table('plotting_shift ps')
                ->select('ps.id_plotting, ps.tanggal, p.nama_lengkap, jk.nama_jadwal, o.nama_opd')
                ->join('pegawai p', 'ps.id_pegawai = p.id_pegawai', 'left')
                ->join('opd o', 'p.id_opd = o.id_opd', 'left')
                ->join('jam_kerja jk', 'ps.id_jam_kerja = jk.id_jam_kerja', 'left');

            if (!in_array($level, ['Superadmin', 'kabupaten'])) {
                $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd);
                $builder->whereIn('p.id_opd', $allowed_opd_ids);
            }

            if (!empty($search)) {
                $builder->groupStart()
                    ->like('p.nama_lengkap', $search)
                    ->orLike('jk.nama_jadwal', $search)
                    ->orLike('ps.tanggal', $search)
                    ->orLike('o.nama_opd', $search)
                    ->groupEnd();
            }

            $recordsFiltered = $builder->countAllResults(false);

            $builder->orderBy('ps.tanggal', 'DESC');
            if ($length != -1) {
                $builder->limit($length, $start);
            }
            $query = $builder->get()->getResult();

            $builderTotal = $this->db->table('plotting_shift ps')
                ->join('pegawai p', 'ps.id_pegawai = p.id_pegawai', 'left');
            if (!in_array($level, ['Superadmin', 'kabupaten'])) {
                $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd);
                $builderTotal->whereIn('p.id_opd', $allowed_opd_ids);
            }
            $recordsTotal = $builderTotal->countAllResults();

            $data = [];
            foreach ($query as $r) {
                $btnHapus = '<button type="button" class="btn btn-sm btn-light border text-danger shadow-sm" onclick="hapusPlotting(' . $r->id_plotting . ')"><i class="fas fa-trash"></i></button>';

                $data[] = [
                    '<span class="ps-4 small">' . date('d/m/Y', strtotime($r->tanggal)) . '</span>',
                    '<div class="fw-bold small text-dark">' . $r->nama_lengkap . '</div>
                     <div class="text-muted" style="font-size: 0.65rem;"><i class="fas fa-building me-1"></i> ' . ($r->nama_opd ?? '-') . '</div>',
                    '<span class="badge bg-info-subtle text-info border border-info-subtle">' . $r->nama_jadwal . '</span>',
                    '<div class="text-center">' . $btnHapus . '</div>'
                ];
            }

            return $this->response->setJSON([
                "draw"            => $draw,
                "recordsTotal"    => $recordsTotal,
                "recordsFiltered" => $recordsFiltered,
                "data"            => $data,
                "csrf_hash"       => csrf_hash()
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                "draw"            => intval($this->request->getPost('draw') ?? 1),
                "recordsTotal"    => 0,
                "recordsFiltered" => 0,
                "data"            => [],
                "error"           => "Server Error: " . $e->getMessage(),
                "csrf_hash"       => csrf_hash()
            ]);
        }
    }

    public function simpan()
    {
        $id_pegawai = $this->request->getPost('id_pegawai');
        $id_jam_kerja = $this->request->getPost('id_jam_kerja');
        $tanggal = $this->request->getPost('tanggal');

        $data = [
            'id_pegawai'   => $id_pegawai,
            'id_jam_kerja' => $id_jam_kerja,
            'tanggal'      => $tanggal,
        ];

        if ($this->db->fieldExists('tanggal_mulai', 'plotting_shift')) {
            $data['tanggal_mulai']   = $tanggal;
            $data['tanggal_selesai'] = $tanggal;
        }

        $this->db->transStart();

        $cek = $this->db->table('plotting_shift')
            ->where(['id_pegawai' => $data['id_pegawai'], 'tanggal' => $data['tanggal']])
            ->get()->getRow();

        if ($cek) {
            $this->db->table('plotting_shift')->where('id_plotting', $cek->id_plotting)->update($data);
            $msg = 'Jadwal berhasil diperbarui.';
        } else {
            $this->db->table('plotting_shift')->insert($data);
            $msg = 'Jadwal shift berhasil ditambahkan.';
        }

        // Eksekusi fungsi cerdas untuk re-kalkulasi status absen jika mengubah masa lalu/hari ini
        $this->_recalc_presensi($id_pegawai, $tanggal, $id_jam_kerja);

        $this->db->transComplete();

        if ($this->db->transStatus() === FALSE) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan plotting.', 'csrf_hash' => csrf_hash()]);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => $msg, 'csrf_hash' => csrf_hash()]);
    }

    public function simpan_massal()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $scope          = $this->request->getPost('scope');
        $id_opd         = $this->request->getPost('id_opd');
        $target_pegawai = $this->request->getPost('target_pegawai');
        $id_jam_kerja   = $this->request->getPost('id_jam_kerja');

        $tipe_tanggal = $this->request->getPost('tipe_tanggal') ?? 'range';
        $dates_to_process = [];

        if ($tipe_tanggal == 'range') {
            $tgl_mulai   = $this->request->getPost('tgl_mulai');
            $tgl_selesai = $this->request->getPost('tgl_selesai');

            if (empty($tgl_selesai)) {
                $tgl_selesai = $tgl_mulai;
            }

            if (empty($tgl_mulai)) return $this->response->setJSON(['status' => 'error', 'message' => 'Tanggal mulai wajib diisi.', 'csrf_hash' => csrf_hash()]);
            if ($tgl_mulai > $tgl_selesai) return $this->response->setJSON(['status' => 'error', 'message' => 'Rentang tanggal error.', 'csrf_hash' => csrf_hash()]);

            $begin = new \DateTime($tgl_mulai);
            $end   = new \DateTime($tgl_selesai);
            $end   = $end->modify('+1 day');
            $interval   = new \DateInterval('P1D');
            $daterange = new \DatePeriod($begin, $interval, $end);

            foreach ($daterange as $date) {
                $dates_to_process[] = $date->format('Y-m-d');
            }
        } else {
            $tanggal_multiple = $this->request->getPost('tanggal_multiple');
            if (empty($tanggal_multiple)) return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih minimal satu tanggal.', 'csrf_hash' => csrf_hash()]);

            $raw_dates = explode(',', $tanggal_multiple);
            foreach ($raw_dates as $rd) {
                $dates_to_process[] = trim($rd);
            }
        }

        $builder = $this->db->table('pegawai')->select('id_pegawai');
        $allowed_opd_ids = $this->get_allowed_opd_ids(session()->get('level'), session()->get('id_opd'));

        if (!in_array(session()->get('level'), ['Superadmin', 'kabupaten'])) {
            $builder->whereIn('id_opd', $allowed_opd_ids);
        }

        $list_pegawai = [];

        if ($scope == 'global') {
            $list_pegawai = $builder->get()->getResultArray();
        } elseif ($scope == 'opd') {
            if (empty($id_opd)) return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih OPD.', 'csrf_hash' => csrf_hash()]);

            if (!in_array(session()->get('level'), ['Superadmin', 'kabupaten']) && !in_array($id_opd, $allowed_opd_ids)) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak untuk OPD ini.', 'csrf_hash' => csrf_hash()]);
            }
            $list_pegawai = $builder->where('id_opd', $id_opd)->get()->getResultArray();
        } elseif ($scope == 'pegawai') {
            if (empty($target_pegawai) || !is_array($target_pegawai)) return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih minimal satu pegawai.', 'csrf_hash' => csrf_hash()]);

            if (!in_array(session()->get('level'), ['Superadmin', 'kabupaten'])) {
                $valid_pegawai = $this->db->table('pegawai')->select('id_pegawai')->whereIn('id_opd', $allowed_opd_ids)->whereIn('id_pegawai', $target_pegawai)->get()->getResultArray();
                foreach ($valid_pegawai as $vp) {
                    $list_pegawai[] = ['id_pegawai' => $vp['id_pegawai']];
                }
            } else {
                foreach ($target_pegawai as $id_peg) {
                    $list_pegawai[] = ['id_pegawai' => $id_peg];
                }
            }
        }

        if (empty($list_pegawai)) return $this->response->setJSON(['status' => 'error', 'message' => 'Pegawai tidak ditemukan di lingkup kewenangan Anda.', 'csrf_hash' => csrf_hash()]);

        $has_tanggal_mulai = $this->db->fieldExists('tanggal_mulai', 'plotting_shift');

        $this->db->transStart();

        $batchSize = 1000;
        $data_batch = [];
        $total_terproses = 0;

        foreach ($list_pegawai as $peg) {
            $this->db->table('plotting_shift')
                ->where('id_pegawai', $peg['id_pegawai'])
                ->whereIn('tanggal', $dates_to_process)
                ->delete();

            foreach ($dates_to_process as $tgl_plot) {

                $row_data = [
                    'id_pegawai'   => $peg['id_pegawai'],
                    'id_jam_kerja' => $id_jam_kerja,
                    'tanggal'      => $tgl_plot
                ];

                if ($has_tanggal_mulai) {
                    $row_data['tanggal_mulai']   = $tgl_plot;
                    $row_data['tanggal_selesai'] = $tgl_plot;
                }

                $data_batch[] = $row_data;

                if (count($data_batch) >= $batchSize) {
                    $this->db->table('plotting_shift')->insertBatch($data_batch);
                    $total_terproses += count($data_batch);
                    $data_batch = [];
                }

                // Kalkulasi ulang cerdas jika sinkronisasi massal menimpa masa lalu
                $this->_recalc_presensi($peg['id_pegawai'], $tgl_plot, $id_jam_kerja);
            }
        }

        if (!empty($data_batch)) {
            $this->db->table('plotting_shift')->insertBatch($data_batch);
            $total_terproses += count($data_batch);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === FALSE) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan (Rollback terjadi).', 'csrf_hash' => csrf_hash()]);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => "Plotting Massal Sukses! Total $total_terproses jadwal dibuat & disinkronkan.", 'csrf_hash' => csrf_hash()]);
    }

    public function hapus()
    {
        $id = $this->request->getPost('id_plotting');

        if (!$id) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID Jadwal tidak valid.', 'csrf_hash' => csrf_hash()]);
        }

        $shift = $this->db->table('plotting_shift')->where('id_plotting', $id)->get()->getRow();

        if ($shift) {
            $this->db->transStart();

            $this->db->table('plotting_shift')->where('id_plotting', $id)->delete();

            // Jika jadwal shift dihapus, kembalikan perhitungan presensi ke jadwal reguler/default OPD-nya
            $user = $this->db->table('pegawai')->join('opd', 'opd.id_opd = pegawai.id_opd', 'left')->where('id_pegawai', $shift->id_pegawai)->get()->getRow();
            $default_jam = $user->id_jam_kerja ?? 1;

            $this->_recalc_presensi($shift->id_pegawai, $shift->tanggal, $default_jam);

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menghapus jadwal (Rollback database).', 'csrf_hash' => csrf_hash()]);
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Jadwal shift berhasil dihapus dan jam absensi dinetralkan.', 'csrf_hash' => csrf_hash()]);
    }

    public function hapus_massal()
    {
        $scope          = $this->request->getPost('scope_hapus');
        $id_opd         = $this->request->getPost('id_opd_hapus');
        $target_pegawai = $this->request->getPost('target_pegawai_hapus');
        $tgl_mulai      = $this->request->getPost('tgl_mulai_hapus');
        $tgl_selesai    = $this->request->getPost('tgl_selesai_hapus');

        if (empty($tgl_selesai)) {
            $tgl_selesai = $tgl_mulai;
        }

        if ($tgl_mulai > $tgl_selesai) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Rentang tanggal tidak valid.', 'csrf_hash' => csrf_hash()]);
        }

        $builder = $this->db->table('plotting_shift');
        $allowed_opd_ids = $this->get_allowed_opd_ids(session()->get('level'), session()->get('id_opd'));

        $ids = [];

        if (!in_array(session()->get('level'), ['Superadmin', 'kabupaten'])) {
            $subQuery = $this->db->table('pegawai')->select('id_pegawai')->whereIn('id_opd', $allowed_opd_ids)->get()->getResultArray();
            $ids = array_column($subQuery, 'id_pegawai');
            if (empty($ids)) return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada data pegawai di wilayah OPD Anda.', 'csrf_hash' => csrf_hash()]);
            $builder->whereIn('id_pegawai', $ids);
        }

        if ($scope == 'opd') {
            if (empty($id_opd)) return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih OPD yang akan dihapus.', 'csrf_hash' => csrf_hash()]);
            if (!in_array(session()->get('level'), ['Superadmin', 'kabupaten']) && !in_array($id_opd, $allowed_opd_ids)) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak untuk menghapus OPD ini.', 'csrf_hash' => csrf_hash()]);
            }

            $subQuery = $this->db->table('pegawai')->select('id_pegawai')->where('id_opd', $id_opd)->get()->getResultArray();
            $ids = array_column($subQuery, 'id_pegawai');
            if (empty($ids)) return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada data pegawai di OPD tersebut.', 'csrf_hash' => csrf_hash()]);
            $builder->whereIn('id_pegawai', $ids);
        } elseif ($scope == 'pegawai') {
            if (empty($target_pegawai) || !is_array($target_pegawai)) return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih minimal satu pegawai.', 'csrf_hash' => csrf_hash()]);
            $ids = $target_pegawai;
            $builder->whereIn('id_pegawai', $ids);
        }

        $builder->where('tanggal >=', $tgl_mulai);
        $builder->where('tanggal <=', $tgl_selesai);

        $this->db->transStart();

        // Bersihkan Jadwal
        $builder->delete();

        // Kembalikan Jam Target Absensi ke posisi default
        $presensi_builder = $this->db->table('presensi')
            ->where('tanggal >=', $tgl_mulai)
            ->where('tanggal <=', $tgl_selesai);

        if (!empty($ids)) {
            $presensi_builder->whereIn('id_pegawai', $ids);
        }

        // Reset secara massal (Untuk menghindari loop yang terlalu lama)
        $presensi_builder->update([
            'id_jam_kerja_snapshot' => null,
            'jam_masuk_seharusnya'  => null,
            'jam_pulang_seharusnya' => null
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === FALSE) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal membersihkan jadwal (Rollback database).', 'csrf_hash' => csrf_hash()]);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Pembersihan jadwal massal berhasil & jam dinetralkan.', 'csrf_hash' => csrf_hash()]);
    }

    public function proses_tukar_jaga()
    {
        $request = \Config\Services::request();
        $tanggal      = $request->getPost('tanggal_tukar');
        $id_pegawai_1 = $request->getPost('id_pegawai_1');
        $id_pegawai_2 = $request->getPost('id_pegawai_2');

        if (empty($tanggal) || empty($id_pegawai_1) || empty($id_pegawai_2)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data form tidak lengkap.', 'csrf_hash' => csrf_hash()]);
        }

        if ($id_pegawai_1 == $id_pegawai_2) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak bisa bertukar jadwal dengan orang yang sama.', 'csrf_hash' => csrf_hash()]);
        }

        $this->db->transStart();
        try {
            $shift_1 = $this->db->table('plotting_shift')->where(['id_pegawai' => $id_pegawai_1, 'tanggal' => $tanggal])->get()->getRow();
            $shift_2 = $this->db->table('plotting_shift')->where(['id_pegawai' => $id_pegawai_2, 'tanggal' => $tanggal])->get()->getRow();

            if (!$shift_1 && !$shift_2) {
                throw new \Exception('Keduanya berstatus Lepas Jaga (kosong) pada tanggal tersebut, tidak ada jadwal yang bisa ditukar.');
            }

            $id_jam_baru_pegawai_1 = null;
            $id_jam_baru_pegawai_2 = null;

            if ($shift_1 && !$shift_2) {
                $id_jam_baru_pegawai_2 = $shift_1->id_jam_kerja;
                $this->db->table('plotting_shift')->where('id_plotting', $shift_1->id_plotting)->update(['id_pegawai' => $id_pegawai_2]);
            } elseif (!$shift_1 && $shift_2) {
                $id_jam_baru_pegawai_1 = $shift_2->id_jam_kerja;
                $this->db->table('plotting_shift')->where('id_plotting', $shift_2->id_plotting)->update(['id_pegawai' => $id_pegawai_1]);
            } elseif ($shift_1 && $shift_2) {
                $id_jam_baru_pegawai_1 = $shift_2->id_jam_kerja;
                $id_jam_baru_pegawai_2 = $shift_1->id_jam_kerja;

                $this->db->table('plotting_shift')->where('id_plotting', $shift_1->id_plotting)->update(['id_jam_kerja' => $id_jam_baru_pegawai_1]);
                $this->db->table('plotting_shift')->where('id_plotting', $shift_2->id_plotting)->update(['id_jam_kerja' => $id_jam_baru_pegawai_2]);
            }

            // Hitung ulang status presensi dengan sistem cerdas
            if ($id_jam_baru_pegawai_1) {
                $this->_recalc_presensi($id_pegawai_1, $tanggal, $id_jam_baru_pegawai_1);
            }
            if ($id_jam_baru_pegawai_2) {
                $this->_recalc_presensi($id_pegawai_2, $tanggal, $id_jam_baru_pegawai_2);
            }

            $this->db->transComplete();
            return $this->response->setJSON(['status' => 'success', 'message' => 'Pertukaran jadwal sukses! Data kehadiran berhasil dikalkulasi ulang.', 'csrf_hash' => csrf_hash()]);
        } catch (\Exception $e) {
            $this->db->transRollback();
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage(), 'csrf_hash' => csrf_hash()]);
        }
    }

    public function cek_shift_pegawai()
    {
        $id_pegawai = $this->request->getPost('id_pegawai');
        $tanggal    = $this->request->getPost('tanggal');

        if (!$id_pegawai || !$tanggal) {
            return $this->response->setJSON(['shift' => '-']);
        }

        $shift = $this->db->table('plotting_shift ps')
            ->select('jk.nama_jadwal')
            ->join('jam_kerja jk', 'ps.id_jam_kerja = jk.id_jam_kerja', 'left')
            ->where('ps.id_pegawai', $id_pegawai)
            ->where('ps.tanggal', $tanggal)
            ->get()->getRow();

        if ($shift) {
            return $this->response->setJSON(['shift' => $shift->nama_jadwal, 'csrf_hash' => csrf_hash()]);
        } else {
            return $this->response->setJSON(['shift' => 'Lepas Jaga (Kosong)', 'csrf_hash' => csrf_hash()]);
        }
    }

    public function ajax_kalender()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        try {
            $bulan = $this->request->getPost('bulan') ?: date('m');
            $tahun = $this->request->getPost('tahun') ?: date('Y');
            $id_opd = $this->request->getPost('id_opd');

            $level = session()->get('level');
            $id_opd_session = session()->get('id_opd');

            $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd_session);

            $builder_pegawai = $this->db->table('pegawai')->select('id_pegawai, nama_lengkap, nip');
            if (!in_array($level, ['Superadmin', 'kabupaten'])) {
                $builder_pegawai->whereIn('id_opd', $allowed_opd_ids);
                if ($id_opd && in_array($id_opd, $allowed_opd_ids)) {
                    $builder_pegawai->where('id_opd', $id_opd);
                }
            } else {
                if ($id_opd) $builder_pegawai->where('id_opd', $id_opd);
            }
            $pegawai = $builder_pegawai->orderBy('nama_lengkap', 'ASC')->get()->getResultArray();

            $pegawai_ids = array_column($pegawai, 'id_pegawai');

            $shifts = [];
            if (!empty($pegawai_ids)) {
                $plot_data = $this->db->table('plotting_shift')
                    ->select('plotting_shift.id_pegawai, plotting_shift.tanggal, jam_kerja.nama_jadwal')
                    ->join('jam_kerja', 'jam_kerja.id_jam_kerja = plotting_shift.id_jam_kerja', 'left')
                    ->whereIn('plotting_shift.id_pegawai', $pegawai_ids)
                    ->where('MONTH(plotting_shift.tanggal)', $bulan)
                    ->where('YEAR(plotting_shift.tanggal)', $tahun)
                    ->get()->getResultArray();

                foreach ($plot_data as $pd) {
                    $shifts[$pd['id_pegawai']][$pd['tanggal']] = $pd['nama_jadwal'];
                }
            }

            $days_in_month = date('t', strtotime(sprintf('%04d-%02d-01', $tahun, $bulan)));

            $html = '<table class="table table-bordered table-sm text-center align-middle" style="font-size: 0.75rem; white-space: nowrap;">';
            $html .= '<thead class="bg-light" style="position: sticky; top: 0; z-index: 2;"><tr>';
            $html .= '<th style="position: sticky; left: 0; z-index: 3; background-color: #f8f9fa;">Nama Pegawai</th>';
            for ($i = 1; $i <= $days_in_month; $i++) {
                $tgl_cek = sprintf('%04d-%02d-%02d', $tahun, $bulan, $i);
                $is_minggu = (date('w', strtotime($tgl_cek)) == 0) ? 'text-danger' : '';
                $html .= '<th class="' . $is_minggu . '">' . $i . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            if (empty($pegawai)) {
                $html .= '<tr><td colspan="' . ($days_in_month + 1) . '" class="text-muted py-3">Tidak ada data pegawai yang dapat ditampilkan.</td></tr>';
            } else {
                foreach ($pegawai as $p) {
                    $html .= '<tr>';
                    $html .= '<td class="text-start fw-bold bg-white" style="position: sticky; left: 0; z-index: 1; border-right: 2px solid #dee2e6;">' . $p['nama_lengkap'] . '</td>';

                    for ($i = 1; $i <= $days_in_month; $i++) {
                        $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $i);
                        $jadwal = $shifts[$p['id_pegawai']][$tgl] ?? 'Lepas / Libur';

                        $bg_color = '';
                        $text_color = 'text-white';
                        $label = '';

                        $jadwal_lower = strtolower($jadwal);

                        if (strpos($jadwal_lower, 'pagi') !== false) {
                            $bg_color = 'bg-primary';
                            $label = 'P';
                        } elseif (strpos($jadwal_lower, 'siang') !== false || strpos($jadwal_lower, 'sore') !== false) {
                            $bg_color = 'bg-warning';
                            $label = 'S';
                            $text_color = 'text-dark';
                        } elseif (strpos($jadwal_lower, 'malam') !== false) {
                            $bg_color = 'bg-dark';
                            $label = 'M';
                        } elseif ($jadwal_lower == 'lepas / libur' || strpos($jadwal_lower, 'lepas') !== false || strpos($jadwal_lower, 'libur') !== false) {
                            $bg_color = 'bg-danger';
                            $label = 'L';
                        } else {
                            $bg_color = 'bg-info';
                            $label = strtoupper(substr($jadwal, 0, 2));
                            $text_color = 'text-dark';
                        }

                        $html .= '<td class="' . $bg_color . ' ' . $text_color . '" data-bs-toggle="tooltip" title="' . htmlspecialchars($jadwal, ENT_QUOTES) . '" style="cursor:help;">' . $label . '</td>';
                    }
                    $html .= '</tr>';
                }
            }
            $html .= '</tbody></table>';

            $html .= '<div class="mt-3 text-start small fw-bold text-muted">';
            $html .= '<span class="badge bg-primary me-1">P</span> Pagi &nbsp;';
            $html .= '<span class="badge bg-warning text-dark me-1">S</span> Siang/Sore &nbsp;';
            $html .= '<span class="badge bg-dark me-1">M</span> Malam &nbsp;';
            $html .= '<span class="badge bg-danger me-1">L</span> Lepas/Libur &nbsp;';
            $html .= '<span class="badge bg-info text-dark me-1">XX</span> Lainnya';
            $html .= '</div>';

            return $this->response->setJSON(['status' => 'success', 'html' => $html, 'csrf_hash' => csrf_hash()]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage(), 'csrf_hash' => csrf_hash()]);
        }
    }

    // ========================================================================
    // [FITUR BARU]: EXPORT EXCEL MATRIX KALENDER
    // ========================================================================
    public function export_excel()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $bulan = $this->request->getGet('bulan') ?: date('m');
        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $id_opd = $this->request->getGet('id_opd');

        $level = session()->get('level');
        $id_opd_session = session()->get('id_opd');

        $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd_session);

        $builder_pegawai = $this->db->table('pegawai')->select('id_pegawai, nama_lengkap, nip');
        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            $builder_pegawai->whereIn('id_opd', $allowed_opd_ids);
            if ($id_opd && in_array($id_opd, $allowed_opd_ids)) {
                $builder_pegawai->where('id_opd', $id_opd);
            }
        } else {
            if ($id_opd) $builder_pegawai->where('id_opd', $id_opd);
        }
        $pegawai = $builder_pegawai->orderBy('nama_lengkap', 'ASC')->get()->getResultArray();

        $pegawai_ids = array_column($pegawai, 'id_pegawai');

        $shifts = [];
        if (!empty($pegawai_ids)) {
            $plot_data = $this->db->table('plotting_shift')
                ->select('plotting_shift.id_pegawai, plotting_shift.tanggal, jam_kerja.nama_jadwal')
                ->join('jam_kerja', 'jam_kerja.id_jam_kerja = plotting_shift.id_jam_kerja', 'left')
                ->whereIn('plotting_shift.id_pegawai', $pegawai_ids)
                ->where('MONTH(plotting_shift.tanggal)', $bulan)
                ->where('YEAR(plotting_shift.tanggal)', $tahun)
                ->get()->getResultArray();

            foreach ($plot_data as $pd) {
                $shifts[$pd['id_pegawai']][$pd['tanggal']] = $pd['nama_jadwal'];
            }
        }

        $days_in_month = date('t', strtotime(sprintf('%04d-%02d-01', $tahun, $bulan)));
        $nama_bulan = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
        $judul_bulan = $nama_bulan[$bulan] . ' ' . $tahun;

        $html = '<table border="1" cellpadding="5" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px; text-align: center;">';
        $html .= '<thead>';
        $html .= '<tr><th colspan="' . ($days_in_month + 1) . '" style="font-size: 16px; background-color: #f8f9fa;">JADWAL SHIFT PEGAWAI - ' . strtoupper($judul_bulan) . '</th></tr>';
        $html .= '<tr style="background-color: #e9ecef;"><th style="text-align: left;">Nama Pegawai</th>';

        for ($i = 1; $i <= $days_in_month; $i++) {
            $tgl_cek = sprintf('%04d-%02d-%02d', $tahun, $bulan, $i);
            $is_minggu = (date('w', strtotime($tgl_cek)) == 0) ? 'color: red;' : '';
            $html .= '<th style="' . $is_minggu . '">' . $i . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        if (empty($pegawai)) {
            $html .= '<tr><td colspan="' . ($days_in_month + 1) . '">Tidak ada data pegawai yang dapat ditampilkan.</td></tr>';
        } else {
            foreach ($pegawai as $p) {
                $html .= '<tr>';
                $html .= '<td style="text-align: left; font-weight: bold;">' . $p['nama_lengkap'] . '</td>';

                for ($i = 1; $i <= $days_in_month; $i++) {
                    $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $i);
                    $jadwal = $shifts[$p['id_pegawai']][$tgl] ?? 'Lepas / Libur';

                    $bg_color = '';
                    $text_color = '#ffffff';
                    $label = '';

                    $jadwal_lower = strtolower($jadwal);

                    if (strpos($jadwal_lower, 'pagi') !== false) {
                        $bg_color = '#0d6efd'; // Biru (Primary)
                        $label = 'P';
                    } elseif (strpos($jadwal_lower, 'siang') !== false || strpos($jadwal_lower, 'sore') !== false) {
                        $bg_color = '#ffc107'; // Kuning (Warning)
                        $label = 'S';
                        $text_color = '#000000';
                    } elseif (strpos($jadwal_lower, 'malam') !== false) {
                        $bg_color = '#212529'; // Hitam (Dark)
                        $label = 'M';
                    } elseif ($jadwal_lower == 'lepas / libur' || strpos($jadwal_lower, 'lepas') !== false || strpos($jadwal_lower, 'libur') !== false) {
                        $bg_color = '#dc3545'; // Merah (Danger)
                        $label = 'L';
                    } else {
                        $bg_color = '#0dcaf0'; // Biru Muda (Info)
                        $label = strtoupper(substr($jadwal, 0, 2));
                        $text_color = '#000000';
                    }

                    $html .= '<td style="background-color: ' . $bg_color . '; color: ' . $text_color . '; font-weight: bold;">' . $label . '</td>';
                }
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';

        $html .= '<br><table><tr><td colspan="5" style="font-weight: bold;">LEGENDA:</td></tr>';
        $html .= '<tr><td style="background-color: #0d6efd; color: #fff;">P</td><td>Pagi</td></tr>';
        $html .= '<tr><td style="background-color: #ffc107; color: #000;">S</td><td>Siang/Sore</td></tr>';
        $html .= '<tr><td style="background-color: #212529; color: #fff;">M</td><td>Malam</td></tr>';
        $html .= '<tr><td style="background-color: #dc3545; color: #fff;">L</td><td>Lepas/Libur</td></tr>';
        $html .= '</table>';

        $filename = "Jadwal_Shift_" . $bulan . "_" . $tahun . ".xls";
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=$filename");

        echo $html;
        exit;
    }

    // ========================================================================
    // [FITUR BARU]: EXPORT PDF MATRIX KALENDER (HTML PRINT MODE)
    // ========================================================================
    public function export_pdf()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $bulan = $this->request->getGet('bulan') ?: date('m');
        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $id_opd = $this->request->getGet('id_opd');

        $level = session()->get('level');
        $id_opd_session = session()->get('id_opd');

        $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd_session);

        $builder_pegawai = $this->db->table('pegawai')->select('id_pegawai, nama_lengkap, nip');
        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            $builder_pegawai->whereIn('id_opd', $allowed_opd_ids);
            if ($id_opd && in_array($id_opd, $allowed_opd_ids)) {
                $builder_pegawai->where('id_opd', $id_opd);
            }
        } else {
            if ($id_opd) $builder_pegawai->where('id_opd', $id_opd);
        }
        $pegawai = $builder_pegawai->orderBy('nama_lengkap', 'ASC')->get()->getResultArray();

        $pegawai_ids = array_column($pegawai, 'id_pegawai');

        $shifts = [];
        if (!empty($pegawai_ids)) {
            $plot_data = $this->db->table('plotting_shift')
                ->select('plotting_shift.id_pegawai, plotting_shift.tanggal, jam_kerja.nama_jadwal')
                ->join('jam_kerja', 'jam_kerja.id_jam_kerja = plotting_shift.id_jam_kerja', 'left')
                ->whereIn('plotting_shift.id_pegawai', $pegawai_ids)
                ->where('MONTH(plotting_shift.tanggal)', $bulan)
                ->where('YEAR(plotting_shift.tanggal)', $tahun)
                ->get()->getResultArray();

            foreach ($plot_data as $pd) {
                $shifts[$pd['id_pegawai']][$pd['tanggal']] = $pd['nama_jadwal'];
            }
        }

        $days_in_month = date('t', strtotime(sprintf('%04d-%02d-01', $tahun, $bulan)));
        $nama_bulan = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
        $judul_bulan = $nama_bulan[$bulan] . ' ' . $tahun;

        $html = '<!DOCTYPE html><html><head><title>Jadwal Shift ' . $judul_bulan . '</title>';
        $html .= '<style>
                    body { font-family: Arial, sans-serif; font-size: 11px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    th, td { border: 1px solid #333; padding: 4px; text-align: center; }
                    .text-start { text-align: left; }
                    .fw-bold { font-weight: bold; }
                    .bg-primary { background-color: #0d6efd !important; color: #fff !important; -webkit-print-color-adjust: exact; }
                    .bg-warning { background-color: #ffc107 !important; color: #000 !important; -webkit-print-color-adjust: exact; }
                    .bg-dark { background-color: #212529 !important; color: #fff !important; -webkit-print-color-adjust: exact; }
                    .bg-danger { background-color: #dc3545 !important; color: #fff !important; -webkit-print-color-adjust: exact; }
                    .bg-info { background-color: #0dcaf0 !important; color: #000 !important; -webkit-print-color-adjust: exact; }
                    .bg-light { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; }
                    .text-danger { color: red !important; }
                    @page { size: landscape; margin: 10mm; }
                  </style>';
        $html .= '</head><body>';

        $html .= '<h3 style="text-align: center; margin-bottom: 0;">JADWAL SHIFT PEGAWAI</h3>';
        $html .= '<h4 style="text-align: center; margin-top: 5px;">PERIODE: ' . strtoupper($judul_bulan) . '</h4>';

        $html .= '<table>';
        $html .= '<thead class="bg-light"><tr>';
        $html .= '<th class="text-start">Nama Pegawai</th>';
        for ($i = 1; $i <= $days_in_month; $i++) {
            $tgl_cek = sprintf('%04d-%02d-%02d', $tahun, $bulan, $i);
            $is_minggu = (date('w', strtotime($tgl_cek)) == 0) ? 'text-danger' : '';
            $html .= '<th class="' . $is_minggu . '">' . $i . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        if (empty($pegawai)) {
            $html .= '<tr><td colspan="' . ($days_in_month + 1) . '">Tidak ada data pegawai.</td></tr>';
        } else {
            foreach ($pegawai as $p) {
                $html .= '<tr>';
                $html .= '<td class="text-start fw-bold">' . $p['nama_lengkap'] . '</td>';

                for ($i = 1; $i <= $days_in_month; $i++) {
                    $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $i);
                    $jadwal = $shifts[$p['id_pegawai']][$tgl] ?? 'Lepas / Libur';

                    $bg_color = '';
                    $label = '';

                    $jadwal_lower = strtolower($jadwal);

                    if (strpos($jadwal_lower, 'pagi') !== false) {
                        $bg_color = 'bg-primary';
                        $label = 'P';
                    } elseif (strpos($jadwal_lower, 'siang') !== false || strpos($jadwal_lower, 'sore') !== false) {
                        $bg_color = 'bg-warning';
                        $label = 'S';
                    } elseif (strpos($jadwal_lower, 'malam') !== false) {
                        $bg_color = 'bg-dark';
                        $label = 'M';
                    } elseif ($jadwal_lower == 'lepas / libur' || strpos($jadwal_lower, 'lepas') !== false || strpos($jadwal_lower, 'libur') !== false) {
                        $bg_color = 'bg-danger';
                        $label = 'L';
                    } else {
                        $bg_color = 'bg-info';
                        $label = strtoupper(substr($jadwal, 0, 2));
                    }

                    $html .= '<td class="' . $bg_color . ' fw-bold">' . $label . '</td>';
                }
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table>';

        $html .= '<div style="margin-top: 15px; font-weight: bold; font-size: 12px;">LEGENDA:</div>';
        $html .= '<div style="display: flex; gap: 15px; margin-top: 5px;">';
        $html .= '<div><span style="display: inline-block; width: 15px; height: 15px; background: #0d6efd; color: #fff; text-align: center;">P</span> Pagi</div>';
        $html .= '<div><span style="display: inline-block; width: 15px; height: 15px; background: #ffc107; color: #000; text-align: center;">S</span> Siang/Sore</div>';
        $html .= '<div><span style="display: inline-block; width: 15px; height: 15px; background: #212529; color: #fff; text-align: center;">M</span> Malam</div>';
        $html .= '<div><span style="display: inline-block; width: 15px; height: 15px; background: #dc3545; color: #fff; text-align: center;">L</span> Lepas/Libur</div>';
        $html .= '</div>';

        $html .= '<script>window.onload = function() { window.print(); }</script>';
        $html .= '</body></html>';

        echo $html;
        exit;
    }

    // =========================================================================
    // FITUR BARU: GENERATE SIKLUS POLA SHIFT
    // =========================================================================
    public function simpan_pola_shift()
    {
        $scope = $this->request->getPost('scope_pola');
        $id_opd = $this->request->getPost('id_opd_pola');
        $target_pegawai = $this->request->getPost('target_pegawai_pola');
        $pola_jam_kerja = $this->request->getPost('pola_jam_kerja'); // array
        $tgl_mulai = $this->request->getPost('tgl_mulai');
        $tgl_selesai = $this->request->getPost('tgl_selesai');

        if (empty($tgl_mulai) || empty($tgl_selesai) || empty($pola_jam_kerja)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Lengkapi form pola dan tanggal!', 'csrf_hash' => csrf_hash()]);
        }

        if (strtotime($tgl_selesai) < strtotime($tgl_mulai)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tanggal Selesai tidak boleh lebih kecil dari Tanggal Mulai!', 'csrf_hash' => csrf_hash()]);
        }

        $list_target_pegawai = [];

        // 1. Dapatkan Daftar Pegawai
        if ($scope == 'global') {
            $level = session()->get('level');
            if (in_array($level, ['Superadmin', 'kabupaten'])) {
                $p = $this->db->table('pegawai')->select('id_pegawai, nip, id_opd')->get()->getResultArray();
            } else {
                $id_opd_session = session()->get('id_opd');
                $allowed = $this->get_allowed_opd_ids($level, $id_opd_session);
                $p = $this->db->table('pegawai')->select('id_pegawai, nip, id_opd')->whereIn('id_opd', $allowed)->get()->getResultArray();
            }
            $list_target_pegawai = $p;
        } elseif ($scope == 'opd') {
            if (empty($id_opd)) return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih OPD terlebih dahulu!', 'csrf_hash' => csrf_hash()]);
            $list_target_pegawai = $this->db->table('pegawai')->select('id_pegawai, nip, id_opd')->where('id_opd', $id_opd)->get()->getResultArray();
        } elseif ($scope == 'pegawai') {
            if (empty($target_pegawai)) return $this->response->setJSON(['status' => 'error', 'message' => 'Pilih pegawai minimal 1 orang!', 'csrf_hash' => csrf_hash()]);
            $list_target_pegawai = $this->db->table('pegawai')->select('id_pegawai, nip, id_opd')->whereIn('id_pegawai', $target_pegawai)->get()->getResultArray();
        }

        if (empty($list_target_pegawai)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Target pegawai kosong!', 'csrf_hash' => csrf_hash()]);
        }

        $total_inserted = 0;
        $total_deleted = 0;
        $tanggal_berubah = [];
        $total_pola = count($pola_jam_kerja);

        $start = new \DateTime($tgl_mulai);
        $end = new \DateTime($tgl_selesai);
        $end->modify('+1 day'); // Supaya inclusive
        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($start, $interval, $end);

        $this->db->transStart();

        foreach ($list_target_pegawai as $pg) {
            $hari_ke = 0;
            foreach ($period as $dt) {
                $tanggal_loop = $dt->format('Y-m-d');
                $index_pola = $hari_ke % $total_pola;
                $jam_kerja_terpilih = $pola_jam_kerja[$index_pola];

                // Cek apakah ada existing
                $cek = $this->db->table('plotting_shift')->where(['id_pegawai' => $pg['id_pegawai'], 'tanggal' => $tanggal_loop])->get()->getRow();

                if ($jam_kerja_terpilih === 'LIBUR') {
                    // Hapus jadwal di hari ini jika ada
                    if ($cek) {
                        $this->db->table('plotting_shift')->where(['id_pegawai' => $pg['id_pegawai'], 'tanggal' => $tanggal_loop])->delete();
                        $total_deleted++;
                    }
                } else {
                    if ($cek) {
                        // Update
                        $this->db->table('plotting_shift')->where(['id_pegawai' => $pg['id_pegawai'], 'tanggal' => $tanggal_loop])->update([
                            'id_jam_kerja' => $jam_kerja_terpilih,
                            'updated_at'   => date('Y-m-d H:i:s')
                        ]);
                    } else {
                        // Insert
                        $this->db->table('plotting_shift')->insert([
                            'id_pegawai'   => $pg['id_pegawai'],
                            'nip'          => $pg['nip'],
                            'id_opd'       => $pg['id_opd'],
                            'id_jam_kerja' => $jam_kerja_terpilih,
                            'tanggal'      => $tanggal_loop,
                            'tanggal_mulai' => $tanggal_loop,
                            'tanggal_selesai' => $tanggal_loop,
                            'created_at'   => date('Y-m-d H:i:s')
                        ]);
                        $total_inserted++;
                    }
                }
                $tanggal_berubah[] = $tanggal_loop;
                $hari_ke++;
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal memproses pola massal.', 'csrf_hash' => csrf_hash()]);
        }

        // Jalankan rekalkulasi presensi di background / secara massal
        if (!empty($tanggal_berubah)) {
            $unik_tgl = array_unique($tanggal_berubah);
            sort($unik_tgl);
            $min_tgl = $unik_tgl[0];
            $max_tgl = end($unik_tgl);
            foreach ($list_target_pegawai as $pg) {
                $this->_recalc_presensi($pg['id_pegawai'], $min_tgl, $max_tgl);
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "Pola berhasil diterapkan. $total_inserted shift, $total_deleted libur.",
            'csrf_hash' => csrf_hash()
        ]);
    }
}

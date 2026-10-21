<?php

namespace App\Controllers;

use DateTime;
use DateInterval;
use DatePeriod;

class Approval extends BaseController
{
    protected $db;

    // ========================================================================
    // KUNCI ONESIGNAL UNTUK NOTIFIKASI
    // ========================================================================
    private $appId = "8594ed69-eaaf-4156-8903-dfb743309a7d";
    private $restApiKey = 'os_v2' . '_app_qwko22pkv5avncid363ugme2pxeqdflsjnpukxeuys4w5mh54ksbmqnw5z3qng3k3r6lhiml3dtjkauo73ogsu5bjpgmqszc7xt5pwy';

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        date_default_timezone_set('Asia/Makassar');
    }

    // =====================================================================
    // [FUNGSI BARU]: KIRIM PUSH NOTIF CEPAT (ANTI-LEMOT UNTUK ADMIN UI)
    // =====================================================================
    private function sendFastNotif($nips_array, $judul, $pesan)
    {
        if (empty($nips_array)) return false;

        $clean_nips = array_map('strval', array_values(array_unique(array_filter($nips_array))));

        $db = \Config\Database::connect();
        $pegawais = $db->table('pegawai')
            ->select('fcm_token')
            ->whereIn('nip', $clean_nips)
            ->where('fcm_token IS NOT NULL')
            ->where("fcm_token != ''")
            ->get()->getResult();

        foreach ($pegawais as $p) {
            \App\Libraries\FirebaseHelper::sendPushNotification($p->fcm_token, $judul, $pesan);
        }

        return true;
    }

    // ========================================================================
    // [FUNGSI BANTUAN]: DETEKSI SUB-ADMIN YANG DIBLOKIR (KHUSUS RSUD)
    // ========================================================================
    private function is_sub_admin_blocked($id_opd_session)
    {
        if (!$id_opd_session) return false;

        $admin_opd_info = $this->db->table('opd')->where('id_opd', $id_opd_session)->get()->getRow();

        if ($admin_opd_info && strpos($admin_opd_info->nama_opd, ' - ') !== false) {
            $nama_upper = strtoupper($admin_opd_info->nama_opd);

            if (strpos($nama_upper, 'RSUD') !== false) {
                return true;
            }

            return false;
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
        if (!$admin_opd_info) return [$id_opd_session];

        $admin_opd_name = strtoupper($admin_opd_info->nama_opd);
        $allowed_opd_ids = [$id_opd_session];

        $is_admin_induk = (strpos($admin_opd_name, ' - ') === false);

        $list_opd = $this->db->table('opd')->get()->getResultArray();

        foreach ($list_opd as $o) {
            $nama_upper = strtoupper($o['nama_opd']);
            $is_child = false;

            if ($is_admin_induk) {
                if (strpos($admin_opd_name, 'DINAS KESEHATAN') !== false && strpos($nama_upper, 'DINAS KESEHATAN') !== false) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'DINAS PENDIDIKAN') !== false && (strpos($nama_upper, 'SDN ') !== false || strpos($nama_upper, 'SMPN ') !== false || strpos($nama_upper, 'TK ') !== false)) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'RSUD HAJJAH ANDI DEPU') !== false && strpos($nama_upper, 'RSUD HAJJAH ANDI DEPU') !== false) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'RSUD WONOMULYO') !== false && strpos($nama_upper, 'RSUD WONOMULYO') !== false) {
                    $is_child = true;
                }
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

    // =======================================================
    // 1. TAMPILAN HALAMAN 
    // =======================================================
    public function index()
    {
        $level = session()->get('level');
        $id_opd_session = session()->get('id_opd');

        if ($level == 'opd' && $this->is_sub_admin_blocked($id_opd_session)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Akses Ditolak! Persetujuan Izin dan Sakit RSUD dikelola terpusat oleh Admin RSUD Induk.');
        }

        $data = [
            'list_opd' => $this->db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray(),
            'level'    => $level,
            'title'    => 'Antrean Approval Izin & Sakit'
        ];
        return view('admin/approval_izin', $data);
    }

    public function riwayat()
    {
        $level = session()->get('level');
        $id_opd_session = session()->get('id_opd');

        if ($level == 'opd' && $this->is_sub_admin_blocked($id_opd_session)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Akses Ditolak! Riwayat Persetujuan Izin RSUD dikelola terpusat oleh Admin RSUD Induk.');
        }

        $data = [
            'list_opd' => $this->db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray(),
            'level'    => $level,
            'title'    => 'Riwayat Approval Selesai'
        ];
        return view('admin/approval_riwayat', $data);
    }

    // =======================================================
    // 2. SERVER-SIDE PROCESSING (AJAX DATATABLES)
    // =======================================================
    public function ajax_pending()
    {
        return $this->_get_datatables_data('Pending');
    }

    public function ajax_riwayat()
    {
        return $this->_get_datatables_data('Riwayat');
    }

    private function _get_datatables_data($jenis)
    {
        try {
            $request = \Config\Services::request();
            $level = session()->get('level');
            $id_opd_session = session()->get('id_opd') ?? 0;

            if ($level == 'opd' && $this->is_sub_admin_blocked($id_opd_session)) {
                return $this->response->setJSON([
                    "draw" => intval($request->getPost('draw') ?? 1),
                    "recordsTotal" => 0,
                    "recordsFiltered" => 0,
                    "data" => []
                ]);
            }

            $filter_opd = $request->getPost('id_opd');
            $filter_tanggal = $request->getPost('tanggal');

            // [BUG FIX]: Aman dari PHP 8 Array Error untuk DataTables Search
            $searchData = $request->getPost('search');
            $search = is_array($searchData) && isset($searchData['value']) ? $searchData['value'] : '';

            $start = intval($request->getPost('start') ?? 0);
            $length = intval($request->getPost('length') ?? 10);

            $builder = $this->db->table('pengajuan_absen')
                ->select('pengajuan_absen.*, pegawai.nama_lengkap, pegawai.eselon, opd.nama_opd')
                ->join('pegawai', 'pengajuan_absen.nip = pegawai.nip', 'left')
                ->join('opd', 'pegawai.id_opd = opd.id_opd', 'left');

            if ($jenis == 'Pending') {
                $builder->where('pengajuan_absen.status_approval', 'Pending');
            } else {
                $builder->where('pengajuan_absen.status_approval !=', 'Pending');
                if (!empty($filter_tanggal)) {
                    $builder->where("pengajuan_absen.tanggal_mulai <=", $filter_tanggal);
                    $builder->where("pengajuan_absen.tanggal_selesai >=", $filter_tanggal);
                }
            }

            // =========================================================================
            // [PERBAIKAN ENTERPRISE]: MENANGANI FILTER GABUNGAN & ESELON DARI UI KABUPATEN
            // =========================================================================
            if (in_array($level, ['Superadmin', 'kabupaten'])) {
                if (!empty($filter_opd)) {
                    if (strpos($filter_opd, 'gabungan||') === 0) {
                        $prefix = str_replace('gabungan||', '', $filter_opd);
                        $builder->like('opd.nama_opd', $prefix, 'both');
                    } elseif ($filter_opd == 'rekap_eselon_2') {
                        $builder->whereIn('pegawai.eselon', ['II.a', 'II.b']);
                    } elseif ($filter_opd == 'rekap_eselon_3') {
                        $builder->whereIn('pegawai.eselon', ['III.a', 'III.b']);
                    } elseif ($filter_opd == 'rekap_eselon_4') {
                        $builder->whereIn('pegawai.eselon', ['IV.a', 'IV.b']);
                    } elseif ($filter_opd == 'rekap_eselon_gabungan') {
                        $builder->whereIn('pegawai.eselon', ['II.a', 'II.b', 'III.a', 'III.b', 'IV.a', 'IV.b']);
                    } else {
                        $builder->where('pegawai.id_opd', $filter_opd);
                    }
                }
            } else {
                $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd_session);

                // [BUG FIX]: Mencegah Error SQL "WHERE IN ()" jika array kosong
                if (empty($allowed_opd_ids)) {
                    $allowed_opd_ids = [0];
                }

                if (!empty($filter_opd)) {
                    if (strpos($filter_opd, 'gabungan||') === 0) {
                        $builder->whereIn('pegawai.id_opd', $allowed_opd_ids);
                    } else {
                        if (in_array($filter_opd, $allowed_opd_ids)) {
                            $builder->where('pegawai.id_opd', $filter_opd);
                        } else {
                            $builder->whereIn('pegawai.id_opd', $allowed_opd_ids);
                        }
                    }
                } else {
                    $builder->whereIn('pegawai.id_opd', $allowed_opd_ids);
                }
            }
            // =========================================================================

            if (!empty($search)) {
                $builder->groupStart()
                    ->like('pegawai.nama_lengkap', $search)
                    ->orLike('pengajuan_absen.nip', $search)
                    ->orLike('pengajuan_absen.kategori', $search)
                    ->orLike('opd.nama_opd', $search)
                    ->groupEnd();
            }

            $totalFiltered = $builder->countAllResults(false);
            $builder->orderBy('pengajuan_absen.created_at', 'DESC');

            // [BUG FIX]: Mencegah Error Limit -1 saat User memilih "Tampilkan Semua Data"
            if ($length != -1) {
                $builder->limit($length, $start);
            }

            $data_db = $builder->get()->getResultArray();

            $data = [];
            foreach ($data_db as $p) {
                $row = [];

                if ($jenis == 'Pending') {
                    $row[] = '<input type="checkbox" class="form-check-input rowCheck shadow-none" value="' . $p['id_pengajuan'] . '">';
                }

                $tgl_mulai = !empty($p['tanggal_mulai']) ? $p['tanggal_mulai'] : '1970-01-01';
                $tgl_range = date('d/m/Y', strtotime($tgl_mulai));
                if (!empty($p['tanggal_selesai']) && $p['tanggal_mulai'] != $p['tanggal_selesai']) {
                    $tgl_range .= ' s/d ' . date('d/m/Y', strtotime($p['tanggal_selesai']));
                }
                $row[] = '<div class="fw-bold text-primary">' . $tgl_range . '</div>
                      <span class="text-muted" style="font-size: 0.65rem;">Ket: ' . substr($p['keterangan'] ?? '', 0, 200) . '...</span>';

                $row[] = '<div class="fw-bold text-dark">' . ($p['nama_lengkap'] ?? '-') . '</div>
                      <div class="text-muted" style="font-size: 0.7rem;">NIP. ' . $p['nip'] . '</div>
                      <div class="text-muted mt-1" style="font-size: 0.65rem;"><i class="fas fa-building me-1"></i> ' . ($p['nama_opd'] ?? '-') . '</div>';

                $slot_display = $p['slot_waktu'] ?? 'Penuh';
                $row[] = '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 mb-1">' . strtoupper($p['kategori'] ?? '') . '</span>
                      <div class="text-muted" style="font-size: 0.7rem;"><i class="far fa-clock"></i> Sesi: ' . $slot_display . '</div>';

                if (!empty($p['file_bukti'])) {
                    $ext = strtolower(pathinfo($p['file_bukti'], PATHINFO_EXTENSION));
                    if ($ext === 'pdf') {
                        $row[] = '<a href="' . base_url('uploads/bukti_absen/' . $p['file_bukti']) . '" target="_blank" class="btn btn-sm btn-light border rounded-pill px-3 text-danger shadow-sm" style="font-size: 0.75rem; font-weight: 600;"><i class="fas fa-file-pdf me-1"></i> Bukti PDF</a>';
                    } else {
                        $row[] = '<a href="' . base_url('uploads/bukti_absen/' . $p['file_bukti']) . '" target="_blank" class="btn btn-sm btn-light border rounded-pill px-3 text-primary shadow-sm" style="font-size: 0.75rem; font-weight: 600;"><i class="fas fa-file-image me-1"></i> Bukti Gambar</a>';
                    }
                } else {
                    $row[] = '<span class="text-muted fst-italic">-</span>';
                }

                if ($jenis == 'Pending') {
                    $row[] = '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2">Pending</span>';

                    $nama_esc = addslashes($p['nama_lengkap'] ?? 'Pegawai');
                    $row[] = '<div class="d-flex justify-content-center gap-1">
                            <button type="button" class="btn btn-sm btn-outline-success rounded-circle" title="Setujui" onclick="modalApproval(' . $p['id_pengajuan'] . ', \'' . $nama_esc . '\', \'' . $p['kategori'] . '\', \'setuju\')"><i class="fas fa-check"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" title="Tolak" onclick="modalApproval(' . $p['id_pengajuan'] . ', \'' . $nama_esc . '\', \'' . $p['kategori'] . '\', \'tolak\')"><i class="fas fa-times"></i></button>
                          </div>';
                } else {
                    if ($p['status_approval'] == 'Disetujui') {
                        $row[] = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2">Disetujui</span>';
                    } else {
                        $row[] = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2">Ditolak</span>';
                    }

                    $row[] = '<div class="d-flex justify-content-center gap-1">
                            <a href="javascript:void(0)" onclick="batalkanRiwayat(' . $p['id_pengajuan'] . ')" class="btn btn-sm btn-outline-warning rounded-circle" title="Batalkan & Kembalikan ke Antrean"><i class="fas fa-undo-alt"></i></a>
                            <a href="javascript:void(0)" onclick="hapusRiwayat(' . $p['id_pengajuan'] . ')" class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus Permanen"><i class="fas fa-trash-alt"></i></a>
                          </div>';
                }

                $row_clean = [];
                foreach ($row as $r_idx => $r_val) {
                    $row_clean[$r_idx] = mb_convert_encoding((string)$r_val, 'UTF-8', 'UTF-8');
                }
                $data[] = $row_clean;
            }

            return $this->response->setJSON([
                "draw" => intval($request->getPost('draw') ?? 1),
                "recordsTotal" => $totalFiltered,
                "recordsFiltered" => $totalFiltered,
                "data" => $data
            ]);
        } catch (\Throwable $e) {
            file_put_contents(WRITEPATH . 'logs/ci4_ajax_error.txt', $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setJSON([
                "draw" => 1,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => [],
                "error" => $e->getMessage()
            ]);
        }
    }

    // =======================================================
    // 3. FUNGSI PROSES & EKSEKUSI 
    // =======================================================
    public function proses()
    {
        if (session()->get('level') == 'opd' && $this->is_sub_admin_blocked(session()->get('id_opd'))) {
            return redirect()->back()->with('error', 'Akses Ditolak! Persetujuan izin RSUD dikelola terpusat.');
        }

        $id_pengajuan = $this->request->getPost('id_pengajuan');
        $aksi         = $this->request->getPost('aksi');
        $catatan      = $this->request->getPost('catatan');

        $this->eksekusi_approval($id_pengajuan, $aksi, $catatan);
        return redirect()->to(base_url('approval'))->with('success', 'Data berhasil diproses.');
    }

    public function proses_massal()
    {
        if (session()->get('level') == 'opd' && $this->is_sub_admin_blocked(session()->get('id_opd'))) {
            return redirect()->back()->with('error', 'Akses Ditolak! Persetujuan izin RSUD dikelola terpusat.');
        }

        $ids = json_decode($this->request->getPost('ids'));
        $aksi = $this->request->getPost('aksi');

        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih.');
        }

        foreach ($ids as $id_pengajuan) {
            $this->eksekusi_approval($id_pengajuan, $aksi, "Disetujui Massal oleh Admin");
        }

        return redirect()->to(base_url('approval'))->with('success', count($ids) . ' data berhasil diproses sekaligus.');
    }

    public function hapus($id_pengajuan)
    {
        if (session()->get('level') == 'opd' && $this->is_sub_admin_blocked(session()->get('id_opd'))) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses Ditolak.']);
        }

        $this->db->table('pengajuan_absen')->where('id_pengajuan', $id_pengajuan)->delete();
        return $this->response->setJSON(['status' => 'success', 'message' => 'Riwayat berhasil dihapus.']);
    }

    // =======================================================================
    // FUNGSI EKSEKUSI APPROVAL (DIPERBARUI DENGAN LOGIKA PARSIAL)
    // =======================================================================
    private function eksekusi_approval($id_pengajuan, $aksi, $catatan)
    {
        $pengajuan = $this->db->table('pengajuan_absen')->where('id_pengajuan', $id_pengajuan)->get()->getRow();
        if (!$pengajuan || $pengajuan->status_approval != 'Pending') return false;

        $pegawaiInfo = $this->db->table('pegawai')->select('id_pegawai, id_opd, nomor_hp, nama_lengkap, fcm_token')->where('nip', $pengajuan->nip)->get()->getRow();
        $id_pegawai = $pegawaiInfo ? $pegawaiInfo->id_pegawai : 0;

        $this->db->transStart();

        $status_baru = ($aksi == 'setuju') ? 'Disetujui' : 'Ditolak';
        $this->db->table('pengajuan_absen')->where('id_pengajuan', $id_pengajuan)->update([
            'status_approval' => $status_baru,
            'keterangan'      => $pengajuan->keterangan . " | Catatan Admin: " . $catatan
        ]);

        if ($aksi == 'setuju') {
            $begin = new DateTime($pengajuan->tanggal_mulai);
            $tgl_selesai_val = empty($pengajuan->tanggal_selesai) ? $pengajuan->tanggal_mulai : $pengajuan->tanggal_selesai;
            $end   = new DateTime($tgl_selesai_val);
            $end->modify('+1 day');
            $interval  = new DateInterval('P1D');
            $daterange = new DatePeriod($begin, $interval, $end);

            $hari_indo = ['Sun' => 'minggu', 'Mon' => 'senin', 'Tue' => 'selasa', 'Wed' => 'rabu', 'Thu' => 'kamis', 'Fri' => 'jumat', 'Sat' => 'sabtu'];

            foreach ($daterange as $date) {
                $tgl = $date->format("Y-m-d");
                $nama_hari = $hari_indo[date('D', strtotime($tgl))];
                $cek_presensi = $this->db->table('presensi')->where(['nip' => $pengajuan->nip, 'tanggal' => $tgl])->get()->getRowArray();

                $id_jam_aktif = 1;
                $jam_masuk_seharusnya = '07:30:00';
                $jam_pulang_seharusnya = '16:00:00';

                if ($pegawaiInfo) {
                    $cek_plotting = $this->db->table('plotting_shift')->where(['id_pegawai' => $pegawaiInfo->id_pegawai, 'tanggal' => $tgl])->get()->getRow();
                    if (!$cek_plotting && $this->db->fieldExists('tanggal_mulai', 'plotting_shift')) {
                        $cek_plotting = $this->db->table('plotting_shift')
                            ->where('id_pegawai', $pegawaiInfo->id_pegawai)
                            ->where('tanggal_mulai <=', $tgl)
                            ->where('tanggal_selesai >=', $tgl)
                            ->get()->getRow();
                    }
                    $opdInfo = $this->db->table('opd')->select('id_jam_kerja')->where('id_opd', $pegawaiInfo->id_opd)->get()->getRow();

                    $id_jam_aktif = ($cek_plotting) ? $cek_plotting->id_jam_kerja : ($opdInfo->id_jam_kerja ?? 1);

                    $jadwal_detail = $this->db->table('jam_kerja_detail')->where(['id_jam_kerja' => $id_jam_aktif, 'hari' => $nama_hari])->get()->getRow();
                    if ($jadwal_detail) {
                        $jm = str_replace('.', ':', $jadwal_detail->jam_masuk);
                        $jp = str_replace('.', ':', $jadwal_detail->jam_pulang);
                        $jam_masuk_seharusnya  = (strlen($jm) == 5) ? $jm . ':00' : $jm;
                        $jam_pulang_seharusnya = (strlen($jp) == 5) ? $jp . ':00' : $jp;
                    }
                }

                $waktu_masuk_dinamis = date('H:i:s', strtotime($jam_masuk_seharusnya) - 60);
                $waktu_siang_dinamis = date('H:i:s', strtotime($jam_masuk_seharusnya) + 14400);
                $waktu_pulang_dinamis = date('H:i:s', strtotime($jam_pulang_seharusnya) + 60);

                $updateData = [
                    'deskripsi'  => strtoupper($pengajuan->kategori) . " (" . ($pengajuan->sub_kategori ?? 'Umum') . ")",
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $slot_waktu = strtoupper(trim($pengajuan->slot_waktu ?? 'PENUH'));
                $is_full_day = false;

                if (
                    $slot_waktu == 'PENUH' || $slot_waktu == '' ||
                    strpos(strtoupper($pengajuan->sub_kategori ?? ''), 'PENUH') !== false ||
                    strpos(strtoupper($pengajuan->sub_kategori ?? ''), 'LUAR DAERAH') !== false ||
                    strtoupper($pengajuan->kategori) == 'SAKIT'
                ) {
                    $is_full_day = true;
                }

                $is_koreksi_shift = (strpos(strtoupper($pengajuan->sub_kategori ?? ''), 'KOREKSI SHIFT') !== false);

                if ($is_koreksi_shift) {
                    $updateData['status_masuk']        = 'Sangat Baik / Istimewa';
                    $updateData['status_pulang']       = 'Selesai';
                    $updateData['kategori']            = 'Hadir';
                    $updateData['statusijin']          = null;
                    $updateData['menit_keterlambatan'] = 0;
                    $updateData['deskripsi']           = 'Koreksi Kehadiran Shift (Disetujui Admin OPD)';

                    if (empty($cek_presensi['jam_masuk_aktual']) || $cek_presensi['jam_masuk_aktual'] === '00:00:00' || $cek_presensi['jam_masuk_aktual'] === '-') {
                        $updateData['jam_masuk_aktual'] = $jam_masuk_seharusnya;
                    }
                    if (empty($cek_presensi['jam_pulang_aktual']) || $cek_presensi['jam_pulang_aktual'] === '00:00:00' || $cek_presensi['jam_pulang_aktual'] === '-') {
                        $updateData['jam_pulang_aktual'] = $jam_pulang_seharusnya;
                    }
                } elseif (!$is_full_day) {
                    if (strpos($slot_waktu, 'PAGI') !== false) {
                        $updateData['jam_masuk_aktual']    = $waktu_masuk_dinamis;
                        $updateData['status_masuk']        = 'Sangat Baik / Istimewa';
                        $updateData['menit_keterlambatan'] = 0;
                    } elseif (strpos($slot_waktu, 'SIANG') !== false) {
                        $updateData['jam_siang']           = $waktu_siang_dinamis;
                    } elseif (strpos($slot_waktu, 'SORE') !== false) {
                        $updateData['jam_pulang_aktual']   = $waktu_pulang_dinamis;
                        $updateData['status_pulang']       = 'Selesai';
                    }

                    if (!$cek_presensi) {
                        $updateData['kategori']   = 'Hadir';
                        $updateData['statusijin'] = null;
                    }
                } else {
                    $updateData['jam_masuk_aktual']  = $waktu_masuk_dinamis;
                    $updateData['jam_siang']         = $waktu_siang_dinamis;
                    $updateData['jam_pulang_aktual'] = $waktu_pulang_dinamis;

                    $updateData['status_masuk']      = $pengajuan->kategori;
                    $updateData['status_pulang']     = $pengajuan->kategori;
                    $updateData['kategori']          = $pengajuan->kategori;
                    $updateData['statusijin']        = $pengajuan->kategori;
                }

                if ($cek_presensi) {
                    $this->db->table('presensi')->where('id_presensi', $cek_presensi['id_presensi'])->update($updateData);
                } else {
                    $updateData['id_pegawai'] = $id_pegawai;
                    $updateData['nip']        = $pengajuan->nip;
                    $updateData['tanggal']    = $tgl;
                    $updateData['created_at'] = date('Y-m-d H:i:s');

                    $updateData['id_jam_kerja_snapshot'] = $id_jam_aktif;
                    $updateData['jam_masuk_seharusnya']  = $jam_masuk_seharusnya;
                    $updateData['jam_pulang_seharusnya'] = $jam_pulang_seharusnya;

                    $this->db->table('presensi')->insert($updateData);
                }
            }
        }

        $this->db->transComplete();

        // ---------------------------------------------------------------------
        // PROSES PENGIRIMAN WA OUTBOX & ONESIGNAL PUSH NOTIFICATION
        // ---------------------------------------------------------------------
        if ($this->db->transStatus() !== false && $pegawaiInfo) {

            $tgl_m = date('d/m/Y', strtotime($pengajuan->tanggal_mulai));
            $tgl_s = date('d/m/Y', strtotime($pengajuan->tanggal_selesai));
            $rentang_waktu = ($tgl_m == $tgl_s) ? $tgl_m : "$tgl_m s/d $tgl_s";

            $icon = ($aksi == 'setuju') ? '✅' : '❌';
            $status_teks = ($aksi == 'setuju') ? 'DISETUJUI' : 'DITOLAK';

            // 1. MASUKKAN KE ANTREAN WA (Jika Pegawai Punya Nomor HP)
            if (!empty($pegawaiInfo->nomor_hp)) {
                $opdInfo = $this->db->table('opd')->select('nama_opd')->where('id_opd', $pegawaiInfo->id_opd)->get()->getRow();
                $nama_opd = $opdInfo ? $opdInfo->nama_opd : '-';

                $tautan_bukti = "";
                if (!empty($pengajuan->file_bukti)) {
                    $tautan_bukti = "\n_Catatan: Ini adalah bukti pengajuan anda._";
                }

                $sapaan_array = ["Halo", "Hai", "Info untuk", "Selamat informasinya"];
                $sapaan = $sapaan_array[array_rand($sapaan_array)];

                $wa_msg = "*[PRESENSI POLMAN 2.0]*\n";
                $wa_msg .= "$icon *INFO PENGAJUAN " . strtoupper($pengajuan->kategori) . "*\n\n";
                $wa_msg .= "$sapaan *{$pegawaiInfo->nama_lengkap}*,\n";
                $wa_msg .= "NIP: {$pengajuan->nip}\n";
                $wa_msg .= "Instansi: {$nama_opd}\n\n";
                $wa_msg .= "Pengajuan Anda pada tanggal *$rentang_waktu* telah *$status_teks* oleh Admin.";
                $wa_msg .= "$tautan_bukti\n\n";
                $wa_msg .= "*Catatan Admin:* $catatan\n";

                $this->db->table('wa_outbox')->insert([
                    'nomor_tujuan' => $pegawaiInfo->nomor_hp,
                    'pesan'        => $wa_msg,
                    'status'       => 'pending',
                    'created_at'   => date('Y-m-d H:i:s')
                ]);
            }

            // 2. KIRIM PUSH NOTIFIKASI FIREBASE FCM (Langsung ke Aplikasi HP Pegawai)
            $notif_judul = "Pengajuan " . ucfirst(strtolower($status_teks)) . " $icon";
            $notif_pesan = "Halo " . $pegawaiInfo->nama_lengkap . ", pengajuan " . strtoupper($pengajuan->kategori) . " Anda untuk tanggal $rentang_waktu telah " . strtolower($status_teks) . " oleh Admin.";

            if (!empty($pegawaiInfo->fcm_token)) {
                \App\Libraries\FirebaseHelper::sendPushNotification($pegawaiInfo->fcm_token, $notif_judul, $notif_pesan);
            }
        }

        return $this->db->transStatus();
    }

    private function generateRandomTime($start, $end)
    {
        $st = strtotime($start);
        $en = strtotime($end);
        return date("H:i:s", mt_rand($st, $en));
    }

    public function batal($id_pengajuan)
    {
        if (session()->get('level') == 'opd' && $this->is_sub_admin_blocked(session()->get('id_opd'))) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses Ditolak.']);
        }

        $pengajuan = $this->db->table('pengajuan_absen')->where('id_pengajuan', $id_pengajuan)->get()->getRow();

        if (!$pengajuan) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data pengajuan tidak ditemukan.']);
        }

        $this->db->transStart();

        if ($pengajuan->status_approval == 'Disetujui') {
            $begin = new DateTime($pengajuan->tanggal_mulai);
            $end   = new DateTime($pengajuan->tanggal_selesai);
            $end->modify('+1 day');
            $interval  = new DateInterval('P1D');
            $daterange = new DatePeriod($begin, $interval, $end);

            foreach ($daterange as $date) {
                $tgl = $date->format("Y-m-d");

                $this->db->table('presensi')
                    ->where('nip', $pengajuan->nip)
                    ->where('tanggal', $tgl)
                    ->where('kategori', $pengajuan->kategori)
                    ->delete();
            }
        }

        $catatan_bersih = explode(" | Catatan Admin:", $pengajuan->keterangan)[0];

        $this->db->table('pengajuan_absen')->where('id_pengajuan', $id_pengajuan)->update([
            'status_approval' => 'Pending',
            'keterangan'      => $catatan_bersih
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal membatalkan pengajuan.']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Pengajuan dibatalkan dan dikembalikan ke Antrean.']);
    }
}

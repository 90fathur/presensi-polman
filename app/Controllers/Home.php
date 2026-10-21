<?php

namespace App\Controllers;

class Home extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        date_default_timezone_set('Asia/Makassar');
    }

    public function index(): string
    {
        $level = session()->get('level');
        $id_opd_admin = session()->get('id_opd');
        $tanggal_hari_ini = date('Y-m-d');
        $akses_global = ['Superadmin', 'kabupaten', 'Administrator'];
        $hari_ini = date('Y-m-d');

        // --- 1. QUERY DATA ---
        $builder = $this->db->table('presensi')
            ->select('presensi.*, pegawai.id_opd')
            ->join('pegawai', 'presensi.nip = pegawai.nip', 'left')
            ->where('presensi.tanggal', $tanggal_hari_ini);

        if (!in_array($level, $akses_global)) {
            $builder->where('pegawai.id_opd', $id_opd_admin);
            $data['nama_kantor'] = session()->get('nama_opd') ?? 'Instansi Anda';
        } else {
            $data['nama_kantor'] = "Kabupaten Polewali Mandar";
        }

        $all_stats = $builder->get()->getResultArray();

        // --- 2. LOGIKA HITUNG KATEGORI BERDASARKAN DATABASE DINAMIS ---
        $total_hadir = 0;
        $total_izin = 0;
        $total_cepat = 0;

        $cat_istimewa = 0;
        $cat_baik     = 0;
        $cat_cukup    = 0;
        $cat_telat    = 0;
        $cat_bina     = 0;
        $cat_teguran  = 0;
        $cat_ditangguhkan = 0;
        $cat_dianulir = 0;

        foreach ($all_stats as $row) {
            if (($row['status_verifikasi'] ?? '') === 'Ditangguhkan') {
                $cat_ditangguhkan++;
            } elseif (($row['status_verifikasi'] ?? '') === 'Dianulir') {
                $cat_dianulir++;
            }
            // Hitung Izin/Sakit/Cuti
            if (stripos($row['kategori'] ?? '', 'Izin') !== false || stripos($row['kategori'] ?? '', 'Sakit') !== false || stripos($row['kategori'] ?? '', 'Cuti') !== false || stripos($row['kategori'] ?? '', 'Luar') !== false) {
                $total_izin++;
                continue;
            }

            if (ucwords(strtolower($row['status_pulang'] ?? '')) == 'Cepat Pulang') {
                $total_cepat++;
            }

            $jam_masuk = $row['jam_masuk_aktual'];

            if ($jam_masuk != null && $jam_masuk != '00:00:00') {
                $total_hadir++;
                $status_db = $row['status_masuk'] ?? '';

                // Mapping Langsung Berdasarkan Teks di Database
                if ($status_db == 'Sangat Baik / Istimewa' || $status_db == 'Sangat Baik' || strpos($status_db, 'Apel') !== false) {
                    $cat_istimewa++;
                } elseif ($status_db == 'Baik') {
                    $cat_baik++;
                } elseif ($status_db == 'Cukup Baik') {
                    $cat_cukup++;
                } elseif ($status_db == 'Terlambat') {
                    $cat_telat++;
                } elseif ($status_db == 'Pembinaan') {
                    $cat_bina++;
                } elseif ($status_db == 'Teguran') {
                    $cat_teguran++;
                } else {
                    // Fallback jika statusnya "Tepat Waktu" (Inputan Manual Admin)
                    $cat_baik++;
                }
            }
        }

        // --- 3. OPD PERFORMANCE ---
        $opd_stats = [];
        if (in_array($level, $akses_global)) {
            $opd_list = $this->db->table('opd')->get()->getResultArray();
            foreach ($opd_list as $o) {
                $total_peg_opd = $this->db->table('pegawai')->where('id_opd', $o['id_opd'])->countAllResults();
                if ($total_peg_opd > 0) {
                    $hadir_opd = $this->db->table('presensi')->join('pegawai', 'presensi.nip = pegawai.nip')
                        ->where(['presensi.tanggal' => $tanggal_hari_ini, 'pegawai.id_opd' => $o['id_opd']])
                        ->where('presensi.jam_masuk_aktual !=', null)->countAllResults();
                    $persen = round(($hadir_opd / $total_peg_opd) * 100);
                    $opd_stats[] = ['nama' => $o['nama_opd'], 'persen' => $persen];
                }
            }
            usort($opd_stats, fn($a, $b) => $b['persen'] <=> $a['persen']);
        }

        $p_builder = $this->db->table('pegawai');
        if (!in_array($level, $akses_global)) $p_builder->where('id_opd', $id_opd_admin);
        $total_pegawai = $p_builder->countAllResults();
        $belum_absen = max(0, $total_pegawai - ($total_hadir + $total_izin));

        // --- 4. KIRIM DATA KE VIEW ---
        $data['total_pegawai'] = $total_pegawai;
        $data['total_hadir']   = $total_hadir;
        $data['total_telat']   = $cat_telat + $cat_bina + $cat_teguran;
        $data['total_izin']    = $total_izin;
        $data['total_cepat']   = $total_cepat;
        $data['opd_performance'] = array_slice($opd_stats, 0, 50);

        // Kirim Array Lengkap Untuk Pie Chart
        $data['chart_data'] = [$cat_istimewa, $cat_baik, $cat_cukup, $cat_telat, $cat_bina, $cat_teguran, $total_izin, $belum_absen];

        // Kirim Detail Variabel Kategori untuk Widget Card
        $data['cat_istimewa'] = $cat_istimewa;
        $data['cat_baik']     = $cat_baik;
        $data['cat_cukup']    = $cat_cukup;
        $data['cat_telat']    = $cat_telat;
        $data['cat_bina']     = $cat_bina;
        $data['cat_teguran']  = $cat_teguran;
        $data['cat_ditangguhkan'] = $cat_ditangguhkan;
        $data['cat_dianulir'] = $cat_dianulir;
        $data['belum_absen']  = $belum_absen;

        return view('dashboard', $data);
    }

    // --- 5. DATA TABLES (AJAX) ---
    // --- 5. DATA TABLES (AJAX) ---
    public function ajax_aktivitas()
    {
        $request = \Config\Services::request();
        $level = session()->get('level');
        $id_opd_admin = session()->get('id_opd');
        $tanggal_hari_ini = date('Y-m-d');
        $akses_global = ['Superadmin', 'kabupaten', 'Administrator'];

        // [PERBAIKAN]: Menambahkan LEFT JOIN ke tabel jam_kerja agar kita bisa memunculkan Nama Shift di Dashboard
        $builder = $this->db->table('pegawai')
            ->select('presensi.*, pegawai.nama_lengkap, pegawai.nip, opd.nama_opd, 
                      COALESCE(jk_snap.nama_jadwal, jk_opd.nama_jadwal, "Reguler") as nama_shift')
            ->join('presensi', "presensi.nip = pegawai.nip AND presensi.tanggal = '$tanggal_hari_ini'", 'left')
            ->join('opd', 'pegawai.id_opd = opd.id_opd', 'left')
            ->join('jam_kerja jk_snap', 'presensi.id_jam_kerja_snapshot = jk_snap.id_jam_kerja', 'left')
            ->join('jam_kerja jk_opd', 'opd.id_jam_kerja = jk_opd.id_jam_kerja', 'left');

        if (!in_array($level, $akses_global)) {
            $builder->where('pegawai.id_opd', $id_opd_admin);
        }

        // Fitur Pencarian DataTables
        $searchValue = $request->getPost('search')['value'] ?? '';
        if ($searchValue) {
            $builder->groupStart()
                ->like('pegawai.nama_lengkap', $searchValue)
                ->orLike('pegawai.nip', $searchValue)
                ->groupEnd();
        }

        // Logika Penangkapan Filter Klik Card Kategori
        $filter_kategori = $request->getPost('filter_kategori');
        if (!empty($filter_kategori)) {
            if ($filter_kategori == 'Istimewa') {
                $builder->groupStart()
                    ->where('presensi.status_masuk', 'Sangat Baik / Istimewa')
                    ->orWhere('presensi.status_masuk', 'Sangat Baik')
                    ->orLike('presensi.status_masuk', 'Apel')
                    ->groupEnd();
            } elseif ($filter_kategori == 'Baik') {
                $builder->groupStart()
                    ->where('presensi.status_masuk', 'Baik')
                    ->orWhere('presensi.status_masuk', 'Tepat Waktu')
                    ->orGroupStart()
                    ->where('presensi.jam_masuk_aktual IS NOT NULL')
                    ->where('presensi.jam_masuk_aktual !=', '00:00:00')
                    ->whereNotIn('presensi.status_masuk', ['Sangat Baik / Istimewa', 'Sangat Baik', 'Cukup Baik', 'Terlambat', 'Pembinaan', 'Teguran'])
                    ->notLike('presensi.status_masuk', 'Apel')
                    ->groupEnd()
                    ->groupEnd();
            } elseif ($filter_kategori == 'Cukup Baik') {
                $builder->where('presensi.status_masuk', 'Cukup Baik');
            } elseif ($filter_kategori == 'Terlambat') {
                $builder->where('presensi.status_masuk', 'Terlambat');
            } elseif ($filter_kategori == 'Pembinaan') {
                $builder->where('presensi.status_masuk', 'Pembinaan');
            } elseif ($filter_kategori == 'Teguran') {
                $builder->where('presensi.status_masuk', 'Teguran');
            } elseif ($filter_kategori == 'Izin/Sakit/TL') {
                $builder->groupStart()
                    ->like('presensi.kategori', 'Izin')
                    ->orLike('presensi.kategori', 'Sakit')
                    ->orLike('presensi.kategori', 'Cuti')
                    ->orLike('presensi.kategori', 'Luar')
                    ->groupEnd();
            } elseif ($filter_kategori == 'Ditangguhkan') {
                $builder->where('presensi.status_verifikasi', 'Ditangguhkan');
            } elseif ($filter_kategori == 'Belum Absen') {
                $builder->groupStart()
                    ->where('presensi.id_presensi IS NULL')
                    ->orGroupStart()
                    ->groupStart()
                    ->where('presensi.jam_masuk_aktual IS NULL')
                    ->orWhere('presensi.jam_masuk_aktual', '00:00:00')
                    ->groupEnd()
                    ->groupStart()
                    ->where('presensi.kategori IS NULL')
                    ->orWhere('presensi.kategori', '')
                    ->orGroupStart()
                    ->notLike('presensi.kategori', 'Izin')
                    ->notLike('presensi.kategori', 'Sakit')
                    ->notLike('presensi.kategori', 'Cuti')
                    ->notLike('presensi.kategori', 'Luar')
                    ->groupEnd()
                    ->groupEnd()
                    ->groupEnd()
                    ->groupEnd();
            }
        } else {
            $builder->where('presensi.id_presensi IS NOT NULL');
        }

        $totalFiltered = $builder->countAllResults(false);

        $length = $request->getPost('length') ?? 10;
        $start = $request->getPost('start') ?? 0;

        $result = $builder->orderBy('presensi.jam_masuk_aktual', 'DESC')
            ->limit($length, $start)
            ->get()->getResultArray();

        $data = [];
        foreach ($result as $row) {
            $nestedData = [];

            // 1. Profil Pegawai + Badge Shift Baru
            $avatar = "https://ui-avatars.com/api/?name=" . urlencode($row['nama_lengkap']) . "&background=random&color=fff&bold=true";

            $shift_badge = '<div class="mt-1"><span class="badge bg-light text-dark border border-secondary border-opacity-25 rounded-pill" style="font-size: 0.55rem; padding: 2px 6px;"><i class="fas fa-business-time text-warning me-1"></i> ' . strtoupper($row['nama_shift']) . '</span></div>';

            $nestedData[] = '<div class="d-flex align-items-center text-start ps-3">
                                <img src="' . $avatar . '" class="avatar-circle me-3">
                                <div>
                                    <div class="fw-bold text-dark mb-0">' . $row['nama_lengkap'] . '</div>
                                    <div class="text-muted small" style="font-size: 0.7rem;">' . $row['nama_opd'] . '</div>
                                    ' . $shift_badge . '
                                </div>
                             </div>';

            // LOGIKA PENYEMBUNYIAN JAM UNTUK IZIN/SAKIT/TL/CUTI
            $is_absen_khusus = ($row['kategori'] && $row['kategori'] != 'Hadir' && strpos($row['kategori'], 'Apel') === false);

            if ($is_absen_khusus) {
                $nestedData[] = '<span class="text-muted">--:--</span>';
                $nestedData[] = '<span class="text-muted">--:--</span>';
                $nestedData[] = '<span class="text-muted">--:--</span>';
                $nestedData[] = '<span class="badge bg-izin rounded-pill px-3">' . strtoupper($row['kategori']) . '</span>';
                $nestedData[] = '<span class="badge bg-light text-muted border">-</span>';
            } else {
                $nestedData[] = ($row['jam_masuk_aktual'] ? date('H:i', strtotime($row['jam_masuk_aktual'])) : '<span class="text-muted">--:--</span>');
                $nestedData[] = ($row['jam_siang'] ? date('H:i', strtotime($row['jam_siang'])) : '<span class="text-muted">--:--</span>');
                $nestedData[] = ($row['jam_pulang_aktual'] ? date('H:i', strtotime($row['jam_pulang_aktual'])) : '<span class="text-muted">--:--</span>');

                // Status Masuk
                $jamMasuk = $row['jam_masuk_aktual'];
                if (!$jamMasuk) {
                    $badgeMasuk = '<span class="badge bg-light text-muted border">Belum Absen</span>';
                } else {
                    $status_db = $row['status_masuk'] ?? '';
                    if ($status_db == 'Sangat Baik / Istimewa' || $status_db == 'Sangat Baik') {
                        $badgeMasuk = '<span class="badge bg-istimewa rounded-pill px-3">' . $status_db . '</span>';
                    } elseif ($status_db == 'Baik') {
                        $badgeMasuk = '<span class="badge bg-baik rounded-pill px-3">' . $status_db . '</span>';
                    } elseif ($status_db == 'Cukup Baik') {
                        $badgeMasuk = '<span class="badge bg-cukup text-dark rounded-pill px-3">' . $status_db . '</span>';
                    } elseif ($status_db == 'Terlambat') {
                        $badgeMasuk = '<span class="badge bg-telat text-dark rounded-pill px-3">' . $status_db . '</span>';
                    } elseif ($status_db == 'Pembinaan') {
                        $badgeMasuk = '<span class="badge bg-bina text-dark rounded-pill px-3">' . $status_db . '</span>';
                    } elseif ($status_db == 'Teguran') {
                        $badgeMasuk = '<span class="badge bg-teguran rounded-pill px-3">' . $status_db . '</span>';
                    } elseif (strpos($status_db, 'Apel') !== false) {
                        $badgeMasuk = '<span class="badge bg-primary rounded-pill px-3">' . $status_db . '</span>';
                    } else {
                        $badgeMasuk = '<span class="badge bg-secondary rounded-pill px-3">' . ($status_db ?: 'Hadir') . '</span>';
                    }
                }
                $nestedData[] = $badgeMasuk;

                // Status Pulang
                $statusPulangDB = ucwords(strtolower($row['status_pulang'] ?? ''));
                if ($statusPulangDB == 'Cepat Pulang') {
                    $badgePulang = '<span class="badge bg-cepul rounded-pill px-3">Cepat Pulang</span>';
                } elseif ($statusPulangDB == 'Selesai') {
                    $badgePulang = '<span class="badge bg-selesai rounded-pill px-3">Selesai</span>';
                } else {
                    $badgePulang = '<span class="badge bg-light text-muted border">-</span>';
                }
                $nestedData[] = $badgePulang;
            }

            $data[] = $nestedData;
        }

        return $this->response->setJSON([
            "draw" => intval($request->getPost('draw') ?? 1),
            "recordsTotal" => $totalFiltered,
            "recordsFiltered" => $totalFiltered,
            "data" => $data
        ]);
    }
}

<?php

namespace App\Controllers;

class Live extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        date_default_timezone_set('Asia/Makassar');
    }

    public function index()
    {
        $data['list_opd'] = $this->db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();
        $data['list_shift'] = $this->db->table('jam_kerja')->get()->getResultArray();

        $data['title'] = "Command Center - Presensi Polman 2.0";
        $tanggal_hari_ini = date('Y-m-d');

        $total_pegawai = $this->db->table('pegawai')->countAllResults();

        // Ambil semua data presensi hari ini untuk dihitung detailnya
        $all_stats = $this->db->table('presensi')
            ->where('tanggal', $tanggal_hari_ini)
            ->get()->getResultArray();

        $total_hadir = 0; $total_izin = 0; $cat_istimewa = 0; $cat_baik = 0;
        $cat_cukup = 0; $cat_telat = 0; $cat_bina = 0; $cat_teguran = 0;

        foreach ($all_stats as $row) {
            if (stripos($row['kategori'] ?? '', 'Izin') !== false || stripos($row['kategori'] ?? '', 'Sakit') !== false || stripos($row['kategori'] ?? '', 'Cuti') !== false || stripos($row['kategori'] ?? '', 'Luar') !== false) {
                $total_izin++;
                continue;
            }

            if (!empty($row['jam_masuk_aktual']) && $row['jam_masuk_aktual'] != '00:00:00') {
                $total_hadir++;
                $status_db = $row['status_masuk'] ?? '';

                if ($status_db == 'Sangat Baik / Istimewa' || $status_db == 'Sangat Baik' || strpos($status_db, 'Apel') !== false) {
                    $cat_istimewa++;
                } elseif ($status_db == 'Baik' || $status_db == 'Tepat Waktu') {
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
                    $cat_baik++;
                }
            }
        }

        $data['stat_total']   = $total_pegawai;
        $data['stat_hadir']   = $total_hadir;
        $data['stat_izin']    = $total_izin;
        $data['cat_istimewa'] = $cat_istimewa;
        $data['cat_baik']     = $cat_baik;
        $data['cat_cukup']    = $cat_cukup;
        $data['cat_telat']    = $cat_telat;
        $data['cat_bina']     = $cat_bina;
        $data['cat_teguran']  = $cat_teguran;
        $data['stat_belum']   = max(0, $total_pegawai - ($total_hadir + $total_izin));

        return view('pimpinan/live_dashboard', $data);
    }

    public function ajax_stream()
    {
        $request = \Config\Services::request();
        $hari_ini = date('Y-m-d');

        $filter_opd = $request->getPost('filter_opd');
        $filter_shift = $request->getPost('filter_shift');

        // Optimasi: Hanya tarik yang sudah ada di tabel presensi
        $builder = $this->db->table('presensi pr')
            ->select('pr.jam_masuk_aktual, pr.jam_siang, pr.jam_pulang_aktual, pr.status_masuk, pr.status_pulang, pr.kategori, 
                      p.nama_lengkap, p.nip, p.jabatan, o.nama_opd, pr.id_presensi,
                      COALESCE(jk_snap.nama_jadwal, jk_opd.nama_jadwal, "Reguler") as nama_shift')
            ->join('pegawai p', 'pr.nip = p.nip')
            ->join('opd o', 'p.id_opd = o.id_opd', 'left')
            ->join('jam_kerja jk_snap', 'pr.id_jam_kerja_snapshot = jk_snap.id_jam_kerja', 'left')
            ->join('jam_kerja jk_opd', 'o.id_jam_kerja = jk_opd.id_jam_kerja', 'left')
            ->where('pr.tanggal', $hari_ini);

        if (!empty($filter_opd)) {
            $builder->where('p.id_opd', $filter_opd);
        }
        if (!empty($filter_shift)) {
            $builder->groupStart()
                ->where('jk_snap.id_jam_kerja', $filter_shift)
                ->orGroupStart()
                ->where('pr.id_jam_kerja_snapshot IS NULL')
                ->where('jk_opd.id_jam_kerja', $filter_shift)
                ->groupEnd()
                ->groupEnd();
        }

        $searchValue = $request->getPost('search')['value'] ?? '';
        if ($searchValue) {
            $builder->groupStart()
                ->like('p.nama_lengkap', $searchValue)
                ->orLike('o.nama_opd', $searchValue)
                ->groupEnd();
        }

        $totalFiltered = $builder->countAllResults(false);

        $length = $request->getPost('length') ?? 10;
        $start = $request->getPost('start') ?? 0;

        // Sorting Super Ketat (Hadir di atas, Izin di bawah)
        $result = $builder
            ->orderBy('(CASE WHEN pr.kategori = "Hadir" THEN 2 ELSE 1 END)', 'DESC', false)
            ->orderBy('GREATEST(COALESCE(pr.jam_pulang_aktual, "00:00:00"), COALESCE(pr.jam_siang, "00:00:00"), COALESCE(pr.jam_masuk_aktual, "00:00:00"))', 'DESC', false)
            ->limit($length, $start)
            ->get()->getResultArray();

        $data = [];

        foreach ($result as $row) {
            $nestedData = [];
            $avatar = "https://ui-avatars.com/api/?name=" . urlencode($row['nama_lengkap']) . "&background=0D8ABC&color=fff&bold=true";
            
            $is_absen_khusus = (!empty($row['kategori']) && $row['kategori'] != 'Hadir' && strpos($row['kategori'], 'Apel') === false);

            $jam_masuk_html = ''; $jam_siang_html = ''; $jam_pulang_html = '';

            // --- TAMPILAN WAKTU SELALU 3 KOLOM SEJAJAR ---
            if ($is_absen_khusus) {
                $jam_masuk_html = '
                    <div class="text-center px-4 border-end border-secondary border-opacity-25">
                        <span class="d-block text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 1px;">KETERANGAN</span>
                        <span class="fw-bold text-info fs-6">--:--</span>
                    </div>';
            } else {
                // JAM MASUK
                $jam_masuk = (!empty($row['jam_masuk_aktual']) && $row['jam_masuk_aktual'] != '00:00:00') ? date('H:i', strtotime($row['jam_masuk_aktual'])) : '--:--';
                $color_masuk = ($jam_masuk != '--:--') ? 'text-success' : 'text-muted opacity-50';
                $jam_masuk_html = '
                    <div class="text-center px-3 border-end border-secondary border-opacity-25">
                        <span class="d-block text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 1px;">MASUK</span>
                        <span class="fw-bold '.$color_masuk.' fs-5">' . $jam_masuk . '</span>
                    </div>';

                // JAM SIANG
                $jam_siang = (!empty($row['jam_siang']) && $row['jam_siang'] != '00:00:00') ? date('H:i', strtotime($row['jam_siang'])) : '--:--';
                $color_siang = ($jam_siang != '--:--') ? 'text-warning' : 'text-muted opacity-50';
                $jam_siang_html = '
                    <div class="text-center px-3 border-end border-secondary border-opacity-25">
                        <span class="d-block text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 1px;">SIANG</span>
                        <span class="fw-bold '.$color_siang.' fs-5">' . $jam_siang . '</span>
                    </div>';

                // JAM PULANG
                $jam_pulang = (!empty($row['jam_pulang_aktual']) && $row['jam_pulang_aktual'] != '00:00:00') ? date('H:i', strtotime($row['jam_pulang_aktual'])) : '--:--';
                $color_pulang = ($jam_pulang != '--:--') ? 'text-primary' : 'text-muted opacity-50';
                $jam_pulang_html = '
                    <div class="text-center px-3 border-end border-secondary border-opacity-25">
                        <span class="d-block text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 1px;">PULANG</span>
                        <span class="fw-bold '.$color_pulang.' fs-5">' . $jam_pulang . '</span>
                    </div>';
            }

            // --- STATUS BADGE & WARNA BORDER KARTU ---
            if ($is_absen_khusus) {
                $status_badge = '<span class="badge bg-info text-white rounded-pill px-3 py-2 fw-normal"><i class="fas fa-info-circle me-1"></i> ' . strtoupper($row['kategori']) . '</span>';
                $border_card = 'border-info';
            } else {
                if (empty($row['jam_masuk_aktual']) || $row['jam_masuk_aktual'] == '00:00:00') {
                    $status_badge = '<span class="badge bg-secondary rounded-pill px-3 py-2 fw-normal"><i class="fas fa-minus-circle me-1"></i> Belum Absen</span>';
                    $border_card = 'border-secondary';
                } else {
                    $st = $row['status_masuk'] ?? '';
                    $bg = 'bg-secondary'; $icon = 'fa-check-circle'; $border_card = 'border-secondary';

                    if ($st == 'Sangat Baik / Istimewa' || $st == 'Sangat Baik') {
                        $bg = 'bg-primary'; $icon = 'fa-star'; $border_card = 'border-primary';
                    } elseif ($st == 'Baik' || $st == 'Tepat Waktu') {
                        $bg = 'bg-success'; $icon = 'fa-check-circle'; $border_card = 'border-success';
                    } elseif ($st == 'Cukup Baik') {
                        $bg = 'bg-success bg-opacity-75'; $icon = 'fa-check-circle'; $border_card = 'border-success';
                    } elseif ($st == 'Terlambat') {
                        $bg = 'bg-warning text-dark'; $icon = 'fa-clock'; $border_card = 'border-warning';
                    } elseif ($st == 'Pembinaan') {
                        $bg = 'bg-warning text-dark'; $icon = 'fa-exclamation-triangle'; $border_card = 'border-warning';
                    } elseif ($st == 'Teguran') {
                        $bg = 'bg-danger'; $icon = 'fa-times-circle'; $border_card = 'border-danger';
                    } elseif (strpos($st, 'Apel') !== false) {
                        $bg = 'bg-primary'; $icon = 'fa-users'; $border_card = 'border-primary';
                    } else {
                        $bg = 'bg-success'; $icon = 'fa-check-circle'; $border_card = 'border-success';
                    }

                    $label_st = $st ?: 'Hadir';
                    $status_badge = '<span class="badge ' . $bg . ' rounded-pill px-3 py-2 shadow-sm fw-normal"><i class="fas ' . $icon . ' me-1"></i> ' . strtoupper($label_st) . '</span>';
                }
            }

            // --- MEMBANGUN HTML CARD ESTETIK ---
            $card_html = '
            <div class="user-card bg-white d-flex flex-wrap align-items-center justify-content-between p-3 mb-3 border-start border-4 shadow-sm ' . $border_card . '" style="border-radius: 12px; transition: transform 0.2s;">
                
                <div class="d-flex align-items-center gap-3 mb-2 mb-md-0" style="min-width: 350px;">
                    <img src="' . $avatar . '" class="rounded-circle shadow-sm" width="55" height="55" style="border: 2px solid #f1f5f9;">
                    <div class="text-start">
                        <div class="fw-bolder text-dark" style="font-size: 1.05rem;">' . $row['nama_lengkap'] . '</div>
                        <div class="text-primary fw-semibold" style="font-size: 0.8rem;"><i class="fas fa-building me-1"></i>' . $row['nama_opd'] . '</div>
                        <div class="mt-1 d-flex gap-2">
                            <span class="badge bg-warning text-dark border border-warning-subtle" style="font-size: 0.65rem;"><i class="fas fa-clock me-1"></i>SHIFT: ' . strtoupper($row['nama_shift']) . '</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center">
                        ' . $jam_masuk_html . '
                        ' . $jam_siang_html . '
                        ' . $jam_pulang_html . '
                    </div>
                    <div style="min-width: 150px; text-align: right;">
                        ' . $status_badge . '
                    </div>
                </div>

            </div>';

            // Masukkan seluruh string HTML Card ini ke dalam 1 kolom Array DataTable
            $nestedData[] = $card_html;
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
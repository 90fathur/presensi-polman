<?php

namespace App\Controllers;

class LaporanTukin extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $level = session()->get('level');
        $id_opd_admin = session()->get('id_opd');

        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        // Admin OPD hanya bisa lihat OPD-nya sendiri
        $id_opd_filter = $this->request->getGet('id_opd');
        if ($level != 'kabupaten' && $level != 'Superadmin') {
            $id_opd_filter = $id_opd_admin;
        }

        $data['bulan'] = $bulan;
        $data['tahun'] = $tahun;
        $data['id_opd_filter'] = $id_opd_filter;
        $data['opd_list'] = [];

        if ($level == 'kabupaten' || $level == 'Superadmin') {
            $data['opd_list'] = $this->db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();
        }

        return view('laporan_tukin/index', $data);
    }

    public function ajax_list()
    {
        try {
            $request = \Config\Services::request();
            $level = session()->get('level');
            $id_opd_admin = session()->get('id_opd');

            $bulan = $request->getPost('bulan') ?? date('m');
            $tahun = $request->getPost('tahun') ?? date('Y');
            $id_opd_filter = $request->getPost('id_opd');

            if ($level != 'kabupaten' && $level != 'Superadmin') {
                $id_opd_filter = $id_opd_admin;
            }

            // 1. Calculate Total Filtered Without Presensi Join
            $builderCount = $this->db->table('pegawai p');
            if (!empty($id_opd_filter)) {
                $builderCount->where('p.id_opd', $id_opd_filter);
            }
            $search = $request->getPost('search');
            $searchValue = isset($search['value']) ? $search['value'] : '';
            if ($searchValue) {
                $builderCount->groupStart()
                    ->like('p.nama_lengkap', $searchValue)
                    ->orLike('p.nip', $searchValue)
                    ->groupEnd();
            }
            $totalFiltered = $builderCount->countAllResults();

            // 2. Fetch Paginated Pegawai
            $length = (int)($request->getPost('length') ?? 10);
            $start = (int)($request->getPost('start') ?? 0);

            $builderPegawai = $this->db->table('pegawai p')
                ->select('p.id_pegawai, p.nip, p.nama_lengkap, o.nama_opd, o.id_jam_kerja as id_jam_opd')
                ->join('opd o', 'p.id_opd = o.id_opd', 'left');

            if (!empty($id_opd_filter)) {
                $builderPegawai->where('p.id_opd', $id_opd_filter);
            }
            if ($searchValue) {
                $builderPegawai->groupStart()
                    ->like('p.nama_lengkap', $searchValue)
                    ->orLike('p.nip', $searchValue)
                    ->groupEnd();
            }

            $pegawaiList = $builderPegawai->orderBy('o.nama_opd', 'ASC')
                ->orderBy('p.nama_lengkap', 'ASC')
                ->limit($length, $start)
                ->get()->getResult();

            // 3. Fetch Presensi Aggregations only for these nips
            $nips = [];
            $ids = [];
            foreach ($pegawaiList as $p) {
                $nips[] = $p->nip;
                $ids[] = $p->id_pegawai;
            }

            $presensiMap = [];
            $bulan_int = (int)$bulan;
            $tahun_int = (int)$tahun;
            if ($bulan_int == 0) $bulan_int = (int)date('m');
            if ($tahun_int == 0) $tahun_int = (int)date('Y');

            if (!empty($nips)) {
                $startDate = sprintf('%04d-%02d-01', $tahun_int, $bulan_int);
                $endDate = date('Y-m-t', strtotime($startDate));

                $presensiResult = $this->db->table('presensi pr')
                    ->select("
                        pr.nip,
                        COALESCE(SUM(CASE WHEN pr.jam_masuk_aktual IS NOT NULL AND pr.jam_masuk_aktual != '00:00:00' THEN 1 ELSE 0 END), 0) as total_hadir,
                        COALESCE(SUM(CASE WHEN pr.status_masuk = 'Terlambat' THEN 1 ELSE 0 END), 0) as total_telat,
                        COALESCE(SUM(CASE WHEN pr.status_pulang = 'Cepat Pulang' THEN 1 ELSE 0 END), 0) as total_cepat,
                        COALESCE(SUM(CASE WHEN pr.kategori LIKE '%Sakit%' THEN 1 ELSE 0 END), 0) as total_sakit,
                        COALESCE(SUM(CASE WHEN pr.kategori LIKE '%Izin%' THEN 1 ELSE 0 END), 0) as total_izin_only,
                        COALESCE(SUM(CASE WHEN pr.kategori LIKE '%Luar%' THEN 1 ELSE 0 END), 0) as total_tl,
                        COALESCE(SUM(CASE WHEN pr.kategori LIKE '%Cuti%' THEN 1 ELSE 0 END), 0) as total_cuti
                    ")
                    ->whereIn('pr.nip', $nips)
                    ->where('pr.tanggal >=', $startDate)
                    ->where('pr.tanggal <=', $endDate)
                    ->groupBy('pr.nip')
                    ->get()->getResult();

                foreach ($presensiResult as $pr) {
                    $presensiMap[$pr->nip] = $pr;
                }
            }

            $total_days_in_month = (int)date('t', strtotime(sprintf('%04d-%02d-01', $tahun_int, $bulan_int)));
            $hari_kerja_global = $this->countHariKerja($bulan_int, $tahun_int);
            $libur_global = $total_days_in_month - $hari_kerja_global;

            // [SUPER FIX]: HITUNG HARI KERJA PERSONAL UNTUK NAKES (SHIFT)
            $map_plotting_count = [];
            $is_nakes_map = [];
            if (!empty($ids)) {
                $startDate = sprintf('%04d-%02d-01', $tahun_int, $bulan_int);
                $endDate = date('Y-m-t', strtotime($startDate));

                $builder_plot = $this->db->table('plotting_shift')->whereIn('id_pegawai', $ids);
                if ($this->db->fieldExists('tanggal_mulai', 'plotting_shift')) {
                    $builder_plot->where("tanggal_mulai <=", $endDate)->where("tanggal_selesai >=", $startDate);
                } else {
                    $builder_plot->where("MONTH(tanggal)", $bulan_int)->where("YEAR(tanggal)", $tahun_int);
                }
                $plotting_db = $builder_plot->get()->getResultArray();

                // Fetch jam kerja master to exclude ramadhan/puasa from nakes status
                $master_jam = $this->db->table('jam_kerja')->get()->getResultArray();
                $map_jam_name = [];
                foreach ($master_jam as $mj) $map_jam_name[$mj['id_jam_kerja']] = $mj['nama_jadwal'] ?? 'Reguler';

                foreach ($plotting_db as $pl) {
                    $nama_jadwal_master = strtolower($map_jam_name[$pl['id_jam_kerja']] ?? '');
                    if (strpos($nama_jadwal_master, 'ramadhan') === false && strpos($nama_jadwal_master, 'puasa') === false && strpos($nama_jadwal_master, 'umum') === false) {
                        $is_nakes_map[$pl['id_pegawai']] = true;
                    }

                    if (isset($pl['tanggal_mulai'])) {
                        $start = new \DateTime(max($pl['tanggal_mulai'], $startDate));
                        $end = new \DateTime(min($pl['tanggal_selesai'], $endDate));
                        $end->modify('+1 day');
                        foreach (new \DatePeriod($start, new \DateInterval('P1D'), $end) as $date) {
                            if (!isset($map_plotting_count[$pl['id_pegawai']])) $map_plotting_count[$pl['id_pegawai']] = 0;
                            $map_plotting_count[$pl['id_pegawai']]++;
                        }
                    } else {
                        if (!isset($map_plotting_count[$pl['id_pegawai']])) $map_plotting_count[$pl['id_pegawai']] = 0;
                        $map_plotting_count[$pl['id_pegawai']]++;
                    }
                }
            }

            $data = [];
            $no = $start + 1;

            foreach ($pegawaiList as $row) {
                // Tentukan Hari Kerja Personal
                $hari_kerja_personal = $hari_kerja_global;
                $libur_personal = $libur_global;

                if (isset($is_nakes_map[$row->id_pegawai])) {
                    $hari_kerja_personal = $map_plotting_count[$row->id_pegawai] ?? 0;
                    $libur_personal = $total_days_in_month - $hari_kerja_personal;
                }

                // Default zero if no presensi found
                $pData = isset($presensiMap[$row->nip]) ? $presensiMap[$row->nip] : (object)[
                    'total_hadir' => 0,
                    'total_telat' => 0,
                    'total_cepat' => 0,
                    'total_sakit' => 0,
                    'total_izin_only' => 0,
                    'total_tl' => 0,
                    'total_cuti' => 0
                ];

                $nestedData = [];
                $nestedData[] = '<div class="text-center text-muted">' . $no++ . '</div>';
                $nestedData[] = '<div class="fw-bold text-dark">' . htmlspecialchars($row->nama_lengkap ?? '') . '</div><div class="small text-muted">' . $row->nip . '</div>';
                $nestedData[] = '<div class="small text-secondary">' . htmlspecialchars($row->nama_opd ?? '') . '</div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-success-subtle text-success rounded-pill px-3">' . $pData->total_hadir . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-danger-subtle text-danger rounded-pill px-3">' . $pData->total_telat . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-warning-subtle text-warning rounded-pill px-3">' . $pData->total_cepat . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-danger-subtle text-danger rounded-pill px-3">' . $pData->total_sakit . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-info-subtle text-info rounded-pill px-3">' . $pData->total_izin_only . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-primary-subtle text-primary rounded-pill px-3">' . $pData->total_tl . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-secondary-subtle text-secondary rounded-pill px-3">' . $pData->total_cuti . '</span></div>';

                $alpha = $hari_kerja_personal - ($pData->total_hadir + $pData->total_sakit + $pData->total_izin_only + $pData->total_tl + $pData->total_cuti);
                if ($alpha < 0) $alpha = 0;
                $nestedData[] = '<div class="text-center"><span class="badge bg-dark-subtle text-dark rounded-pill px-3">' . $alpha . '</span></div>';

                $nestedData[] = '<div class="text-center"><span class="badge bg-light text-dark rounded-pill border px-3">' . $hari_kerja_personal . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-light text-dark border border-secondary-subtle rounded-pill px-3">' . $libur_personal . '</span></div>';
                $data[] = $nestedData;
            }

            return $this->response->setJSON([
                "draw" => intval($request->getPost('draw') ?? 1),
                "recordsTotal" => $totalFiltered,
                "recordsFiltered" => $totalFiltered,
                "data" => $data
            ]);
        } catch (\Throwable $e) {
            @file_put_contents(FCPATH . '../writable/logs/ci4_ajax_error.txt', $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setJSON(["error" => "An error occurred: " . $e->getMessage()]);
        }
    }

    public function exportExcel()
    {
        $level = session()->get('level');
        $id_opd_admin = session()->get('id_opd');

        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');
        $id_opd_filter = $this->request->getGet('id_opd');

        if ($level != 'kabupaten' && $level != 'Superadmin') {
            $id_opd_filter = $id_opd_admin;
        }

        $nama_opd = "Semua_OPD";
        if (!empty($id_opd_filter)) {
            $opd_row = $this->db->table('opd')->where('id_opd', $id_opd_filter)->get()->getRow();
            if ($opd_row) $nama_opd = str_replace(' ', '_', $opd_row->nama_opd);
        }

        $builder = $this->db->table('pegawai p')
            ->select("
                p.id_pegawai, p.nip, p.nama_lengkap, o.nama_opd,
                COALESCE(SUM(CASE WHEN pr.jam_masuk_aktual IS NOT NULL AND pr.jam_masuk_aktual != '00:00:00' THEN 1 ELSE 0 END), 0) as total_hadir,
                COALESCE(SUM(CASE WHEN pr.status_masuk = 'Terlambat' THEN 1 ELSE 0 END), 0) as total_telat,
                COALESCE(SUM(CASE WHEN pr.status_pulang = 'Cepat Pulang' THEN 1 ELSE 0 END), 0) as total_cepat,
                COALESCE(SUM(CASE WHEN pr.kategori LIKE '%Sakit%' THEN 1 ELSE 0 END), 0) as total_sakit,
                COALESCE(SUM(CASE WHEN pr.kategori LIKE '%Izin%' THEN 1 ELSE 0 END), 0) as total_izin_only,
                COALESCE(SUM(CASE WHEN pr.kategori LIKE '%Luar%' THEN 1 ELSE 0 END), 0) as total_tl,
                COALESCE(SUM(CASE WHEN pr.kategori LIKE '%Cuti%' THEN 1 ELSE 0 END), 0) as total_cuti
            ")
            ->join('opd o', 'p.id_opd = o.id_opd', 'left')
            ->join('presensi pr', "p.nip = pr.nip AND MONTH(pr.tanggal) = '$bulan' AND YEAR(pr.tanggal) = '$tahun'", 'left')
            ->groupBy('p.nip');

        if (!empty($id_opd_filter)) {
            $builder->where('p.id_opd', $id_opd_filter);
        }

        $rekap = $builder->orderBy('o.nama_opd', 'ASC')->orderBy('p.nama_lengkap', 'ASC')->get()->getResult();

        $bulan_int = (int)$bulan;
        $tahun_int = (int)$tahun;
        $total_days_in_month = (int)date('t', strtotime(sprintf('%04d-%02d-01', $tahun_int, $bulan_int)));
        $hari_kerja_global = $this->countHariKerja($bulan_int, $tahun_int);
        $libur_global = $total_days_in_month - $hari_kerja_global;

        $ids = [];
        foreach ($rekap as $r) {
            $ids[] = $r->id_pegawai;
        }

        $map_plotting_count = [];
        $is_nakes_map = [];
        if (!empty($ids)) {
            $startDate = sprintf('%04d-%02d-01', $tahun_int, $bulan_int);
            $endDate = date('Y-m-t', strtotime($startDate));

            $builder_plot = $this->db->table('plotting_shift')->whereIn('id_pegawai', $ids);
            if ($this->db->fieldExists('tanggal_mulai', 'plotting_shift')) {
                $builder_plot->where("tanggal_mulai <=", $endDate)->where("tanggal_selesai >=", $startDate);
            } else {
                $builder_plot->where("MONTH(tanggal)", $bulan_int)->where("YEAR(tanggal)", $tahun_int);
            }
            $plotting_db = $builder_plot->get()->getResultArray();

            $master_jam = $this->db->table('jam_kerja')->get()->getResultArray();
            $map_jam_name = [];
            foreach ($master_jam as $mj) $map_jam_name[$mj['id_jam_kerja']] = $mj['nama_jadwal'] ?? 'Reguler';

            foreach ($plotting_db as $pl) {
                $nama_jadwal_master = strtolower($map_jam_name[$pl['id_jam_kerja']] ?? '');
                if (strpos($nama_jadwal_master, 'ramadhan') === false && strpos($nama_jadwal_master, 'puasa') === false && strpos($nama_jadwal_master, 'umum') === false) {
                    $is_nakes_map[$pl['id_pegawai']] = true;
                }

                if (isset($pl['tanggal_mulai'])) {
                    $start = new \DateTime(max($pl['tanggal_mulai'], $startDate));
                    $end = new \DateTime(min($pl['tanggal_selesai'], $endDate));
                    $end->modify('+1 day');
                    foreach (new \DatePeriod($start, new \DateInterval('P1D'), $end) as $date) {
                        if (!isset($map_plotting_count[$pl['id_pegawai']])) $map_plotting_count[$pl['id_pegawai']] = 0;
                        $map_plotting_count[$pl['id_pegawai']]++;
                    }
                } else {
                    if (!isset($map_plotting_count[$pl['id_pegawai']])) $map_plotting_count[$pl['id_pegawai']] = 0;
                    $map_plotting_count[$pl['id_pegawai']]++;
                }
            }
        }

        $nama_bulan = date('F', mktime(0, 0, 0, $bulan_int, 10));

        // Native Excel Output
        header("Content-type: application/vnd-ms-excel");
        header("Content-Disposition: attachment; filename=Laporan_Tukin_{$nama_opd}_{$nama_bulan}_{$tahun}.xls");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '<table border="1">';
        echo '<tr>
                <th style="background-color: #f2f2f2;">No</th>
                <th style="background-color: #f2f2f2;">NIP</th>
                <th style="background-color: #f2f2f2;">Nama Lengkap</th>
                <th style="background-color: #f2f2f2;">OPD</th>
                <th style="background-color: #e8f5e9;">Total Hadir</th>
                <th style="background-color: #ffebee;">Terlambat</th>
                <th style="background-color: #fff3e0;">Pulang Cepat</th>
                <th style="background-color: #ffebee;">Sakit</th>
                <th style="background-color: #e3f2fd;">Izin</th>
                <th style="background-color: #e8eaf6;">Tugas Luar</th>
                <th style="background-color: #eceff1;">Cuti</th>
                <th style="background-color: #ffebee;">Alpha</th>
                <th style="background-color: #f2f2f2;">Perkiraan Hari Kerja</th>
                <th style="background-color: #f9f9f9;">Libur / Lepas Jaga</th>
              </tr>';

        $no = 1;
        if ($tahun_int == 0) $tahun_int = (int)date('Y');

        foreach ($rekap as $r) {
            $hari_kerja_personal = $hari_kerja_global;
            $libur_personal = $libur_global;

            if (isset($is_nakes_map[$r->id_pegawai])) {
                $hari_kerja_personal = $map_plotting_count[$r->id_pegawai] ?? 0;
                $libur_personal = $total_days_in_month - $hari_kerja_personal;
            }

            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td>' . $r->nip . '</td>';
            echo '<td>' . htmlspecialchars($r->nama_lengkap) . '</td>';
            echo '<td>' . htmlspecialchars($r->nama_opd) . '</td>';
            echo '<td>' . $r->total_hadir . '</td>';
            echo '<td>' . $r->total_telat . '</td>';
            echo '<td>' . $r->total_cepat . '</td>';
            echo '<td>' . $r->total_sakit . '</td>';
            echo '<td>' . $r->total_izin_only . '</td>';
            echo '<td>' . $r->total_tl . '</td>';
            echo '<td>' . $r->total_cuti . '</td>';

            $alpha = $hari_kerja_personal - ($r->total_hadir + $r->total_sakit + $r->total_izin_only + $r->total_tl + $r->total_cuti);
            if ($alpha < 0) $alpha = 0;
            echo '<td>' . $alpha . '</td>';

            echo '<td>' . $hari_kerja_personal . '</td>';
            echo '<td>' . $libur_personal . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        exit;
    }

    private function countHariKerja($bulan, $tahun)
    {
        $bulan_int = (int)$bulan;
        $tahun_int = (int)$tahun;
        if ($bulan_int == 0) $bulan_int = (int)date('m');
        if ($tahun_int == 0) $tahun_int = (int)date('Y');

        $total_days = (int)date('t', strtotime(sprintf('%04d-%02d-01', $tahun_int, $bulan_int)));
        $workdays = 0;
        for ($i = 1; $i <= $total_days; $i++) {
            $date = $tahun_int . '-' . str_pad($bulan_int, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
            $dayOfWeek = date('N', strtotime($date));
            // Cek libur nasional
            $isLibur = $this->db->table('hari_libur')->where('tanggal', $date)->countAllResults();
            if ($dayOfWeek < 6 && $isLibur == 0) {
                $workdays++;
            }
        }
        return $workdays;
    }
}

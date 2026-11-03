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

            // Hitung akumulasi kehadiran, hari kerja, dan lepas jaga secara akurat
            $calculated = $this->_calculate_rekap_pegawai($pegawaiList, $bulan, $tahun);

            $data = [];
            $no = $start + 1;

            foreach ($pegawaiList as $row) {
                $res = $calculated[$row->nip] ?? [
                    'total_hadir'      => 0,
                    'total_telat'      => 0,
                    'total_cepat'      => 0,
                    'total_sakit'      => 0,
                    'total_izin_only'  => 0,
                    'total_tl'         => 0,
                    'total_cuti'       => 0,
                    'alpha'            => 0,
                    'hari_kerja'       => 0,
                    'libur_lepas_jaga' => 0
                ];

                $nestedData = [];
                $nestedData[] = '<div class="text-center text-muted">' . $no++ . '</div>';
                $nestedData[] = '<div class="fw-bold text-dark">' . htmlspecialchars($row->nama_lengkap ?? '') . '</div><div class="small text-muted">' . $row->nip . '</div>';
                $nestedData[] = '<div class="small text-secondary">' . htmlspecialchars($row->nama_opd ?? '') . '</div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-success-subtle text-success rounded-pill px-3">' . $res['total_hadir'] . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-danger-subtle text-danger rounded-pill px-3">' . $res['total_telat'] . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-warning-subtle text-warning rounded-pill px-3">' . $res['total_cepat'] . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-danger-subtle text-danger rounded-pill px-3">' . $res['total_sakit'] . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-info-subtle text-info rounded-pill px-3">' . $res['total_izin_only'] . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-primary-subtle text-primary rounded-pill px-3">' . $res['total_tl'] . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-secondary-subtle text-secondary rounded-pill px-3">' . $res['total_cuti'] . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-dark-subtle text-dark rounded-pill px-3">' . $res['alpha'] . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-light text-dark rounded-pill border px-3">' . $res['hari_kerja'] . '</span></div>';
                $nestedData[] = '<div class="text-center"><span class="badge bg-light text-dark border border-secondary-subtle rounded-pill px-3">' . $res['libur_lepas_jaga'] . '</span></div>';
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
            ->select('p.id_pegawai, p.nip, p.nama_lengkap, o.nama_opd, o.id_jam_kerja as id_jam_opd')
            ->join('opd o', 'p.id_opd = o.id_opd', 'left');

        if (!empty($id_opd_filter)) {
            $builder->where('p.id_opd', $id_opd_filter);
        }

        $rekap = $builder->orderBy('o.nama_opd', 'ASC')->orderBy('p.nama_lengkap', 'ASC')->get()->getResult();
        $calculated = $this->_calculate_rekap_pegawai($rekap, $bulan, $tahun);

        $bulan_int = (int)$bulan;
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
        foreach ($rekap as $r) {
            $res = $calculated[$r->nip] ?? [
                'total_hadir'      => 0,
                'total_telat'      => 0,
                'total_cepat'      => 0,
                'total_sakit'      => 0,
                'total_izin_only'  => 0,
                'total_tl'         => 0,
                'total_cuti'       => 0,
                'alpha'            => 0,
                'hari_kerja'       => 0,
                'libur_lepas_jaga' => 0
            ];

            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td>' . $r->nip . '</td>';
            echo '<td>' . htmlspecialchars($r->nama_lengkap) . '</td>';
            echo '<td>' . htmlspecialchars($r->nama_opd) . '</td>';
            echo '<td>' . $res['total_hadir'] . '</td>';
            echo '<td>' . $res['total_telat'] . '</td>';
            echo '<td>' . $res['total_cepat'] . '</td>';
            echo '<td>' . $res['total_sakit'] . '</td>';
            echo '<td>' . $res['total_izin_only'] . '</td>';
            echo '<td>' . $res['total_tl'] . '</td>';
            echo '<td>' . $res['total_cuti'] . '</td>';
            echo '<td>' . $res['alpha'] . '</td>';
            echo '<td>' . $res['hari_kerja'] . '</td>';
            echo '<td>' . $res['libur_lepas_jaga'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        exit;
    }

    private function _calculate_rekap_pegawai($pegawaiList, $bulan, $tahun)
    {
        $bulan_int = (int)$bulan;
        $tahun_int = (int)$tahun;
        if ($bulan_int == 0) $bulan_int = (int)date('m');
        if ($tahun_int == 0) $tahun_int = (int)date('Y');

        $startDate = sprintf('%04d-%02d-01', $tahun_int, $bulan_int);
        $endDate = date('Y-m-t', strtotime($startDate));
        $total_days_in_month = (int)date('t', strtotime($startDate));
        $hari_ini_global = date('Y-m-d');

        $nips = [];
        $ids = [];
        foreach ($pegawaiList as $p) {
            $nip = is_object($p) ? $p->nip : $p['nip'];
            $id = is_object($p) ? $p->id_pegawai : $p['id_pegawai'];
            $nips[] = $nip;
            $ids[] = $id;
        }

        // 1. Hari Libur Nasional
        $liburDates = [];
        if ($this->db->tableExists('hari_libur')) {
            $libur_db = $this->db->table('hari_libur')->where('tanggal >=', $startDate)->where('tanggal <=', $endDate)->get()->getResultArray();
            $liburDates = array_column($libur_db, 'tanggal');
        }

        // 2. Master Jam Kerja & Detail
        $map_jam_name = [];
        if ($this->db->tableExists('jam_kerja')) {
            $master_jam = $this->db->table('jam_kerja')->get()->getResultArray();
            foreach ($master_jam as $mj) $map_jam_name[$mj['id_jam_kerja']] = $mj['nama_jadwal'] ?? 'Reguler';
        }

        $map_hari_kerja = [];
        if ($this->db->tableExists('jam_kerja_detail')) {
            $jam_detail = $this->db->table('jam_kerja_detail')->get()->getResultArray();
            foreach ($jam_detail as $jd) {
                $map_hari_kerja[$jd['id_jam_kerja']][] = strtolower($jd['hari']);
            }
        }

        // 3. Plotting Shift
        $map_plotting = [];
        $is_nakes_map = [];
        if (!empty($ids) && $this->db->tableExists('plotting_shift')) {
            $builder_plot = $this->db->table('plotting_shift')->whereIn('id_pegawai', $ids);
            if ($this->db->fieldExists('tanggal_mulai', 'plotting_shift')) {
                $builder_plot->where("tanggal_mulai <=", $endDate)->where("tanggal_selesai >=", $startDate);
            } else {
                $builder_plot->where("MONTH(tanggal)", $bulan_int)->where("YEAR(tanggal)", $tahun_int);
            }
            $plotting_db = $builder_plot->get()->getResultArray();
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
                        $map_plotting[$pl['id_pegawai']][$date->format('Y-m-d')] = $pl['id_jam_kerja'];
                    }
                } else {
                    $map_plotting[$pl['id_pegawai']][$pl['tanggal']] = $pl['id_jam_kerja'];
                }
            }
        }

        // 4. Presensi Raw
        $presensiMap = [];
        if (!empty($nips) && $this->db->tableExists('presensi')) {
            $presensi_raw = $this->db->table('presensi')
                ->whereIn('nip', $nips)
                ->where('tanggal >=', $startDate)
                ->where('tanggal <=', $endDate)
                ->get()->getResultArray();
            foreach ($presensi_raw as $pr) {
                $presensiMap[$pr['nip']][$pr['tanggal']] = $pr;
            }
        }

        // 5. Pengajuan Absen (Cuti, Izin, Sakit, Tugas Luar)
        $map_izin = [];
        if (!empty($nips) && $this->db->tableExists('pengajuan_absen')) {
            $izin_raw = $this->db->table('pengajuan_absen')
                ->whereIn('nip', $nips)
                ->where("LOWER(TRIM(status_approval))", 'disetujui')
                ->where("tanggal_mulai <= '$endDate' AND COALESCE(NULLIF(tanggal_selesai, ''), tanggal_mulai) >= '$startDate'")
                ->get()->getResultArray();
            foreach ($izin_raw as $iz) {
                $s_i = new \DateTime(max($iz['tanggal_mulai'], $startDate));
                $tgl_sel = empty($iz['tanggal_selesai']) ? $iz['tanggal_mulai'] : $iz['tanggal_selesai'];
                $e_i = new \DateTime(min($tgl_sel, $endDate));
                $e_i->modify('+1 day');
                foreach (new \DatePeriod($s_i, new \DateInterval('P1D'), $e_i) as $dt) {
                    $map_izin[$iz['nip']][$dt->format('Y-m-d')] = strtoupper($iz['kategori']);
                }
            }
        }

        // 6. Tarik Hadir Apel Gabungan / Kantor Bupati jika ada di apel_peserta
        if (!empty($ids) && $this->db->tableExists('apel_peserta') && $this->db->tableExists('apel')) {
            $attended_apels = $this->db->table('apel_peserta')
                ->select('apel_peserta.*, apel.tanggal, pegawai.nip')
                ->join('apel', 'apel.id_apel = apel_peserta.id_apel')
                ->join('pegawai', 'pegawai.id_pegawai = apel_peserta.id_pegawai')
                ->whereIn('apel_peserta.id_pegawai', $ids)
                ->where('apel.tanggal >=', $startDate)
                ->where('apel.tanggal <=', $endDate)
                ->where('apel_peserta.jam_absen IS NOT NULL')
                ->where("apel_peserta.jam_absen != ''")
                ->get()->getResultArray();

            foreach ($attended_apels as $ap) {
                $nip = $ap['nip'];
                $tgl = $ap['tanggal'];
                if (!isset($presensiMap[$nip][$tgl])) {
                    $presensiMap[$nip][$tgl] = [
                        'jam_masuk_aktual' => $ap['jam_absen'],
                        'status_masuk'     => 'Hadir (Apel)',
                        'status_pulang'    => null,
                        'kategori'         => 'Hadir'
                    ];
                } elseif (empty($presensiMap[$nip][$tgl]['jam_masuk_aktual']) || $presensiMap[$nip][$tgl]['jam_masuk_aktual'] == '00:00:00' || strtolower($presensiMap[$nip][$tgl]['kategori'] ?? '') === 'alpa') {
                    $presensiMap[$nip][$tgl]['jam_masuk_aktual'] = $ap['jam_absen'];
                    $presensiMap[$nip][$tgl]['status_masuk'] = 'Hadir (Apel)';
                    $presensiMap[$nip][$tgl]['kategori'] = 'Hadir';
                }
            }
        }

        $hari_indo = ['Sun' => 'minggu', 'Mon' => 'senin', 'Tue' => 'selasa', 'Wed' => 'rabu', 'Thu' => 'kamis', 'Fri' => 'jumat', 'Sat' => 'sabtu'];

        $calculated = [];
        foreach ($pegawaiList as $row) {
            $id_pgw = is_object($row) ? $row->id_pegawai : $row['id_pegawai'];
            $nip = is_object($row) ? $row->nip : $row['nip'];
            $id_jam_opd = is_object($row) ? ($row->id_jam_opd ?? 1) : ($row['id_jam_opd'] ?? 1);
            $is_nakes = isset($is_nakes_map[$id_pgw]);

            $tot_hadir = 0;
            $tot_telat = 0;
            $tot_cepat = 0;
            $tot_sakit = 0;
            $tot_izin = 0;
            $tot_tl = 0;
            $tot_cuti = 0;
            $tot_alpha = 0;
            $hari_kerja = 0;
            $libur_lepas_jaga = 0;

            for ($d = 1; $d <= $total_days_in_month; $d++) {
                $tgl = sprintf('%04d-%02d-%02d', $tahun_int, $bulan_int, $d);
                $day_short = date('D', strtotime($tgl));
                $nama_hari = $hari_indo[$day_short];
                $is_libur_nas = in_array($tgl, $liburDates);

                $ada_jadwal = isset($map_plotting[$id_pgw][$tgl]);
                $is_lepas_jaga = ($is_nakes && !$ada_jadwal);

                $id_jam_aktif = $ada_jadwal ? $map_plotting[$id_pgw][$tgl] : $id_jam_opd;
                $nama_jk_aktif = strtolower($map_jam_name[$id_jam_aktif] ?? '');
                $is_cito = (strpos($nama_jk_aktif, 'cito') !== false || strpos($nama_jk_aktif, 'standby') !== false);

                $hari_kerja_aktif = $map_hari_kerja[$id_jam_aktif] ?? ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
                $is_hari_kerja = in_array($nama_hari, $hari_kerja_aktif);

                // Hari Lepas Jaga (libur shift) atau Hari Libur Nasional (non-shift) atau Akhir Pekan (bukan hari kerja)
                if ($is_lepas_jaga) {
                    $libur_lepas_jaga++;
                    continue;
                }
                if ($is_libur_nas && !$is_nakes) {
                    $libur_lepas_jaga++;
                    continue;
                }
                if (!$is_hari_kerja && !$is_cito) {
                    $libur_lepas_jaga++;
                    continue;
                }

                // Hari ini resmi Hari Kerja
                $hari_kerja++;

                $pr = $presensiMap[$nip][$tgl] ?? null;
                $izin_kat = $map_izin[$nip][$tgl] ?? '';

                $hadir_hari_ini = false;
                if ($pr && !empty($pr['jam_masuk_aktual']) && $pr['jam_masuk_aktual'] != '00:00:00' && strtolower($pr['kategori'] ?? '') != 'alpa') {
                    $tot_hadir++;
                    $hadir_hari_ini = true;
                    if (($pr['status_masuk'] ?? '') == 'Terlambat') $tot_telat++;

                    $jam_pulang_target = (!empty($pr['jam_pulang_seharusnya']) && $pr['jam_pulang_seharusnya'] != '00:00:00') ? $pr['jam_pulang_seharusnya'] : '16:00:00';
                    $is_tidak_pulang = (empty($pr['jam_pulang_aktual']) || $pr['jam_pulang_aktual'] == '00:00:00' || $pr['jam_pulang_aktual'] <= '04:00:00') && $tgl < $hari_ini_global;
                    $is_cepat_pulang = (!empty($pr['jam_pulang_aktual']) && $pr['jam_pulang_aktual'] > '04:00:00' && (($pr['status_pulang'] ?? '') == 'Cepat Pulang' || $pr['jam_pulang_aktual'] < $jam_pulang_target));

                    if (($is_tidak_pulang || $is_cepat_pulang) && !$is_cito) {
                        $tot_cepat++;
                    }
                }

                if (!empty($izin_kat)) {
                    if (strpos($izin_kat, 'SAKIT') !== false) $tot_sakit++;
                    elseif (strpos($izin_kat, 'IZIN') !== false) $tot_izin++;
                    elseif (strpos($izin_kat, 'LUAR') !== false || strpos($izin_kat, 'DINAS') !== false) $tot_tl++;
                    elseif (strpos($izin_kat, 'CUTI') !== false) $tot_cuti++;
                } elseif (!$hadir_hari_ini) {
                    $pr_kat = strtoupper($pr['kategori'] ?? '');
                    if (strpos($pr_kat, 'SAKIT') !== false) $tot_sakit++;
                    elseif (strpos($pr_kat, 'IZIN') !== false) $tot_izin++;
                    elseif (strpos($pr_kat, 'LUAR') !== false || strpos($pr_kat, 'DINAS') !== false) $tot_tl++;
                    elseif (strpos($pr_kat, 'CUTI') !== false) $tot_cuti++;
                    elseif ($tgl <= $hari_ini_global && !$is_cito) {
                        $tot_alpha++;
                    }
                }
            }

            $calculated[$nip] = [
                'total_hadir'      => $tot_hadir,
                'total_telat'      => $tot_telat,
                'total_cepat'      => $tot_cepat,
                'total_sakit'      => $tot_sakit,
                'total_izin_only'  => $tot_izin,
                'total_tl'         => $tot_tl,
                'total_cuti'       => $tot_cuti,
                'alpha'            => $tot_alpha,
                'hari_kerja'       => $hari_kerja,
                'libur_lepas_jaga' => $libur_lepas_jaga,
            ];
        }

        return $calculated;
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

<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TppDinamis extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->ensure_schema();
    }

    /**
     * Pastikan tabel dan kolom yang dibutuhkan oleh prototype TPP Dinamis sudah dibuat
     */
    private function ensure_schema()
    {
        try {
            if (!$this->db->fieldExists('tpp_dinamis_aktif', 'opd')) {
                $this->db->query("ALTER TABLE opd ADD COLUMN tpp_dinamis_aktif TINYINT(1) NOT NULL DEFAULT 0 AFTER nama_opd");
            }
            if (!$this->db->tableExists('tpp_ekin_data')) {
                $this->db->query("CREATE TABLE IF NOT EXISTS `tpp_ekin_data` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_opd` INT(11) NOT NULL,
                    `nip` VARCHAR(50) NOT NULL,
                    `nama_pegawai` VARCHAR(255) NULL,
                    `bulan` INT(2) NOT NULL,
                    `tahun` INT(4) NOT NULL,
                    `wke` VARCHAR(50) NULL,
                    `predikat_kinerja` VARCHAR(100) NULL,
                    `persen_kinerja` DECIMAL(5,2) NOT NULL DEFAULT '100.00',
                    `tpp_bk` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
                    `tpp_kk` DECIMAL(15,2) NOT NULL DEFAULT '0.00',
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_tpp_opd_periode` (`id_opd`, `bulan`, `tahun`),
                    KEY `idx_tpp_nip_periode` (`nip`, `bulan`, `tahun`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
            }
        } catch (\Throwable $e) {
            log_message('error', 'TppDinamis ensure_schema error: ' . $e->getMessage());
        }
    }

    /**
     * Helper nama hari Indonesia
     */
    private function get_nama_hari($day_short)
    {
        $map = [
            'Sun' => 'minggu',
            'Mon' => 'senin',
            'Tue' => 'selasa',
            'Wed' => 'rabu',
            'Thu' => 'kamis',
            'Fri' => 'jumat',
            'Sat' => 'sabtu'
        ];
        return $map[$day_short] ?? 'senin';
    }

    /**
     * Hitung tarif PPh 21 berdasarkan Golongan ASN
     * Golongan IV: 15% (0.15)
     * Golongan III: 5% (0.05)
     * Golongan II / I / Lainnya: 0%
     */
    private function get_tarif_pph($golongan)
    {
        $gol = strtoupper(trim((string)$golongan));
        if (preg_match('/(^|\b|\/)(IV)(\b|[a-e]|\/)/i', $gol) || strpos($gol, 'IV') !== false) {
            return 0.15;
        } elseif (preg_match('/(^|\b|\/)(III)(\b|[a-e]|\/)/i', $gol) || strpos($gol, 'III') !== false) {
            return 0.05;
        }
        return 0.00;
    }

    /**
     * Halaman Utama: Rekap TPP BK & KK Dinamis (Beban Kerja 60% & Disiplin 40%)
     */
    public function index()
    {
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '-1');

        $level = session()->get('level');
        $id_opd_admin = session()->get('id_opd');
        $request = \Config\Services::request();

        $bulan = (int)($request->getGet('bulan') ?? date('m'));
        $tahun = (int)($request->getGet('tahun') ?? date('Y'));
        $status_asn = $request->getGet('status_asn');

        // Pastikan kolom tpp_dinamis_aktif ada
        if (!$this->db->fieldExists('tpp_dinamis_aktif', 'opd')) {
            $this->db->query("ALTER TABLE opd ADD COLUMN tpp_dinamis_aktif TINYINT(1) NOT NULL DEFAULT 0 AFTER nama_opd");
        }

        // Ambil daftar OPD
        $list_opd_all = $this->db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();
        
        // Filter OPD prototype yang aktif
        $list_opd_aktif = [];
        foreach ($list_opd_all as $o) {
            if (!empty($o['tpp_dinamis_aktif'])) {
                $list_opd_aktif[] = $o;
            }
        }

        // Tentukan OPD yang dipilih
        $id_opd = $request->getGet('id_opd');
        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            $id_opd = $id_opd_admin;
        }

        // Cek apakah OPD admin aktif prototipenya jika bukan kabupaten
        $is_opd_prototype_active = true;
        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            $checkMyOpd = $this->db->table('opd')->where('id_opd', $id_opd_admin)->get()->getRow();
            $is_opd_prototype_active = !empty($checkMyOpd->tpp_dinamis_aktif);
        }

        // Jika id_opd belum dipilih dan ada OPD aktif, default ke OPD aktif pertama
        if (empty($id_opd) && !empty($list_opd_aktif)) {
            $id_opd = $list_opd_aktif[0]['id_opd'];
        }

        $opd_info = $id_opd ? $this->db->table('opd')->where('id_opd', $id_opd)->get()->getRow() : null;
        $nama_opd = $opd_info->nama_opd ?? "Pilih OPD";

        $data_pegawai = [];
        $summary = [
            'total_pegawai'      => 0,
            'total_tpp_kotor'    => 0,
            'total_bpjs'         => 0,
            'total_pph21'        => 0,
            'total_tpp_bersih'   => 0,
            'total_kinerja'      => 0,
            'total_disiplin'     => 0
        ];

        if ($id_opd) {
            // Hitung data presensi & ketidakhadiran (Disiplin)
            $presensi_calc = $this->calculate_presensi_disiplin($id_opd, $bulan, $tahun, $status_asn);
            $pegawai_raw = $presensi_calc['pegawai'];
            $potongan_map = $presensi_calc['potongan_map'];

            // Ambil data E-Kinerja & Pagu TPP (WKE, % Kinerja, TPP BK, TPP KK)
            $nips = array_column($pegawai_raw, 'nip');
            $ekin_map = [];
            if (!empty($nips)) {
                $ekin_rows = $this->db->table('tpp_ekin_data')
                    ->whereIn('nip', $nips)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->get()->getResultArray();
                foreach ($ekin_rows as $er) {
                    $ekin_map[$er['nip']] = $er;
                }

                // Cek fallback jika di bulan ini belum ada tapi ada data bulan sebelumnya untuk pagu dasar TPP BK & KK
                $fallback_rows = $this->db->table('tpp_ekin_data')
                    ->whereIn('nip', $nips)
                    ->orderBy('tahun', 'DESC')
                    ->orderBy('bulan', 'DESC')
                    ->get()->getResultArray();
                $fallback_map = [];
                foreach ($fallback_rows as $fr) {
                    if (!isset($fallback_map[$fr['nip']])) {
                        $fallback_map[$fr['nip']] = $fr;
                    }
                }
            }

            $no = 1;
            foreach ($pegawai_raw as $pgw) {
                $nip = $pgw['nip'];
                $pot = $potongan_map[$nip] ?? [
                    'hari_kerja' => 0,
                    'total_persen' => 0,
                    'p1' => 0, 'p2' => 0, 'p3' => 0, 'p4' => 0,
                    'pc1' => 0, 'pc2' => 0, 'pc3' => 0, 'pc4' => 0,
                    'tdk' => 0, 'ta' => 0, 'i' => 0, 'a' => 0
                ];

                $ekin = $ekin_map[$nip] ?? ($fallback_map[$nip] ?? null);
                
                $wke = $ekin['wke'] ?? '0';
                $persen_kinerja = isset($ekin['persen_kinerja']) ? (float)$ekin['persen_kinerja'] : 100.00;
                $tpp_bk = isset($ekin['tpp_bk']) ? (float)$ekin['tpp_bk'] : 0.00;
                $tpp_kk = isset($ekin['tpp_kk']) ? (float)$ekin['tpp_kk'] : 0.00;
                $predikat = $ekin['predikat_kinerja'] ?? 'BAIK';

                // =========================================================
                // PERHITUNGAN TPP SESUAI EXCEL SHEET TPP REKAP BK&KK
                // =========================================================
                // 1. Jumlah TPP = TPP BK + TPP KK
                $jumlah_tpp = $tpp_bk + $tpp_kk;

                // 2. BPJS 1% = Jumlah TPP * 1%
                $bpjs_1pct = $jumlah_tpp * 0.01;

                // 3. TPP Setelah BPJS = Jumlah TPP - BPJS 1%
                $tpp_setelah_bpjs = $jumlah_tpp - $bpjs_1pct;

                // 4. Predikat Kinerja (Beban Kerja 60%)
                $tpp_60 = $tpp_setelah_bpjs * 0.60;
                // % Kinerja Pegawai (desimal: 100% = 1.0)
                $faktor_kinerja = ($persen_kinerja > 1.0) ? ($persen_kinerja / 100.0) : $persen_kinerja;
                $jumlah_tpp_kinerja = $tpp_60 * $faktor_kinerja;

                // 5. Disiplin Kerja (Kedisiplinan 40%)
                $tpp_40 = $tpp_setelah_bpjs * 0.40;
                $persen_pot_disiplin = (float)($pot['total_persen'] ?? 0);
                // Nominal Pengurangan = TPP 40% * (% Potongan / 100)
                $nominal_pengurangan_disiplin = $tpp_40 * ($persen_pot_disiplin / 100.0);
                // Jumlah Disiplin = TPP 40% - Pengurangan
                $jumlah_tpp_disiplin = max(0, $tpp_40 - $nominal_pengurangan_disiplin);

                // 6. Jumlah TPP Kotor = Jumlah Kinerja + Jumlah Disiplin
                $tpp_kotor = $jumlah_tpp_kinerja + $jumlah_tpp_disiplin;

                // 7. PPh 21
                $tarif_pph = $this->get_tarif_pph($pgw['golongan'] ?? $pgw['pangkat'] ?? '');
                $nominal_pph21 = $tpp_kotor * $tarif_pph;

                // 8. Jumlah TPP Bersih = Jumlah TPP Kotor - PPh 21
                $tpp_bersih = $tpp_kotor - $nominal_pph21;

                $row = [
                    'no'                  => $no++,
                    'id_pegawai'          => $pgw['id_pegawai'],
                    'nama'                => $pgw['nama_lengkap'],
                    'nip'                 => $pgw['nip'],
                    'pangkat'             => $pgw['pangkat'] ?? '-',
                    'golongan'            => $pgw['golongan'] ?? '-',
                    'jabatan'             => $pgw['jabatan'] ?? '-',
                    'wke'                 => $wke,
                    'tpp_bk'              => $tpp_bk,
                    'tpp_kk'              => $tpp_kk,
                    'jumlah_tpp'          => $jumlah_tpp,
                    'bpjs_1pct'           => $bpjs_1pct,
                    'tpp_setelah_bpjs'    => $tpp_setelah_bpjs,
                    // Kinerja 60%
                    'tpp_60'              => $tpp_60,
                    'persen_kinerja'      => $persen_kinerja,
                    'predikat_kinerja'    => $predikat,
                    'jumlah_tpp_kinerja'  => $jumlah_tpp_kinerja,
                    // Disiplin 40%
                    'tpp_40'              => $tpp_40,
                    'persen_pot_disiplin' => $persen_pot_disiplin,
                    'nominal_pot_disiplin'=> $nominal_pengurangan_disiplin,
                    'jumlah_tpp_disiplin' => $jumlah_tpp_disiplin,
                    // Disiplin detail
                    'pot_detail'          => $pot,
                    // Hasil Akhir
                    'tpp_kotor'           => $tpp_kotor,
                    'tarif_pph'           => $tarif_pph,
                    'nominal_pph21'       => $nominal_pph21,
                    'tpp_bersih'          => $tpp_bersih,
                    'has_ekin'            => isset($ekin_map[$nip])
                ];

                $summary['total_pegawai']++;
                $summary['total_tpp_kotor']    += $tpp_kotor;
                $summary['total_bpjs']         += $bpjs_1pct;
                $summary['total_pph21']        += $nominal_pph21;
                $summary['total_tpp_bersih']   += $tpp_bersih;
                $summary['total_kinerja']      += $jumlah_tpp_kinerja;
                $summary['total_disiplin']     += $jumlah_tpp_disiplin;

                $data_pegawai[] = $row;
            }
        }

        return view('admin/rekap_tpp_dinamis', [
            'pegawai'                 => $data_pegawai,
            'summary'                 => $summary,
            'list_opd_all'            => $list_opd_all,
            'list_opd_aktif'          => $list_opd_aktif,
            'selected_opd'            => $id_opd,
            'selected_status_asn'     => $status_asn,
            'nama_opd'                => $nama_opd,
            'bulan'                   => $bulan,
            'tahun'                   => $tahun,
            'level'                   => $level,
            'is_opd_prototype_active' => $is_opd_prototype_active
        ]);
    }

    /**
     * Hitung Kedisiplinan & Potongan Presensi untuk seluruh pegawai di OPD
     */
    private function calculate_presensi_disiplin($id_opd, $bulan, $tahun, $status_asn = null)
    {
        $db = $this->db;
        $hari_libur = $db->table('hari_libur')->where('MONTH(tanggal)', $bulan)->where('YEAR(tanggal)', $tahun)->get()->getResultArray();
        $array_tgl_libur = array_column($hari_libur, 'tanggal');

        $tgl_awal_bulan = sprintf('%04d-%02d-01', $tahun, $bulan);
        $tgl_akhir_bulan = date('Y-m-t', strtotime($tgl_awal_bulan));
        $hari_ini_global = date('Y-m-d');
        $endDate = (date('m') == $bulan && date('Y') == $tahun) ? $hari_ini_global : $tgl_akhir_bulan;
        $jml_hari_bulan_ini = (int)date('t', strtotime($tgl_awal_bulan));

        $builder = $db->table('pegawai p')
            ->select('p.*, o.nama_opd, o.id_jam_kerja as id_jam_opd')
            ->join('opd o', 'p.id_opd = o.id_opd', 'left')
            ->where('p.id_opd', $id_opd);

        if (!empty($status_asn)) {
            $builder->where('p.status_asn', $status_asn);
        }

        $pegawai = $builder
            ->orderBy("CASE 
                WHEN TRIM(p.eselon) = 'II.a' THEN 1 
                WHEN TRIM(p.eselon) = 'II.b' THEN 2
                WHEN TRIM(p.eselon) = 'III.a' THEN 3 
                WHEN TRIM(p.eselon) = 'III.b' THEN 4
                WHEN TRIM(p.eselon) = 'IV.a' THEN 5 
                WHEN TRIM(p.eselon) = 'IV.b' THEN 6
                WHEN p.level_jabatan LIKE '%Fungsional%' THEN 7
                WHEN p.level_jabatan LIKE '%Staf%' OR TRIM(p.eselon) = 'Non Eselon' THEN 8
                ELSE 9 END", "ASC", false)
            ->orderBy('p.nama_lengkap', 'ASC')
            ->get()->getResultArray();

        $ids_pegawai = array_column($pegawai, 'id_pegawai');
        $nips_pegawai = array_column($pegawai, 'nip');

        $map_ta = [];
        $all_presensi = [];
        $map_izin_full = [];
        $map_cuti = [];

        if (!empty($ids_pegawai)) {
            // Apel / Upacara tidak hadir
            if ($db->tableExists('apel_peserta') && $db->tableExists('apel')) {
                try {
                    $unattended_apel = $db->table('apel_peserta')
                        ->select('apel_peserta.id_pegawai, COUNT(*) as ta_count')
                        ->join('apel', 'apel.id_apel = apel_peserta.id_apel')
                        ->whereIn('apel_peserta.id_pegawai', $ids_pegawai)
                        ->where('apel.tanggal >=', $tgl_awal_bulan)
                        ->where('apel.tanggal <=', $endDate)
                        ->groupStart()
                        ->where('apel_peserta.jam_absen', null)
                        ->orWhere('apel_peserta.jam_absen', '')
                        ->groupEnd()
                        ->groupBy('apel_peserta.id_pegawai')
                        ->get()->getResultArray();
                    foreach ($unattended_apel as $ua) {
                        $map_ta[$ua['id_pegawai']] = (int)$ua['ta_count'];
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Apel check error: ' . $e->getMessage());
                }
            }

            // Presensi raw
            if ($db->tableExists('presensi') && !empty($nips_pegawai)) {
                try {
                    $logs_raw = $db->table('presensi')
                        ->whereIn('nip', $nips_pegawai)
                        ->where('MONTH(tanggal)', $bulan)
                        ->where('YEAR(tanggal)', $tahun)
                        ->get()->getResultArray();
                    foreach ($logs_raw as $l) {
                        $all_presensi[$l['nip']][$l['tanggal']] = $l;
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Presensi query error: ' . $e->getMessage());
                }
            }

            // Pengajuan Cuti (tabel: pengajuan_cuti)
            if ($db->tableExists('pengajuan_cuti')) {
                try {
                    $cuti_raw = $db->table('pengajuan_cuti')
                        ->where("LOWER(TRIM(status))", 'disetujui')
                        ->where("tgl_mulai <=", $tgl_akhir_bulan)
                        ->where("COALESCE(NULLIF(tgl_selesai, ''), tgl_mulai) >=", $tgl_awal_bulan)
                        ->get()->getResultArray();
                    foreach ($cuti_raw as $c) {
                        $tgl_selesai_val = empty($c['tgl_selesai']) ? $c['tgl_mulai'] : $c['tgl_selesai'];
                        $start = new \DateTime(max($c['tgl_mulai'], $tgl_awal_bulan));
                        $end = new \DateTime(min($tgl_selesai_val, $endDate));
                        $end->modify('+1 day');
                        foreach (new \DatePeriod($start, new \DateInterval('P1D'), $end) as $date) {
                            if ($date->format('m') == sprintf('%02d', $bulan) && $date->format('Y') == $tahun) {
                                $map_cuti[$c['pegawai_id']][] = $date->format('Y-m-d');
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Cuti query error: ' . $e->getMessage());
                }
            }

            // Pengajuan izin / sakit / tugas luar approved (tabel: pengajuan_absen)
            if ($db->tableExists('pengajuan_absen') && !empty($nips_pegawai)) {
                try {
                    $izin_raw = $db->table('pengajuan_absen')
                        ->whereIn('nip', $nips_pegawai)
                        ->where("LOWER(TRIM(status_approval))", 'disetujui')
                        ->where("tanggal_mulai <= LAST_DAY('$tahun-$bulan-01') AND COALESCE(NULLIF(tanggal_selesai, ''), tanggal_mulai) >= '$tahun-$bulan-01'")
                        ->get()->getResultArray();

                    foreach ($izin_raw as $iz) {
                        $start_i = new \DateTime(max($iz['tanggal_mulai'], $tgl_awal_bulan));
                        $tgl_selesai_val = empty($iz['tanggal_selesai']) ? $iz['tanggal_mulai'] : $iz['tanggal_selesai'];
                        $end_i   = new \DateTime(min($tgl_selesai_val, $tgl_akhir_bulan));
                        $end_i->modify('+1 day');

                        foreach (new \DatePeriod($start_i, new \DateInterval('P1D'), $end_i) as $dt) {
                            $slot = strtoupper(trim($iz['slot_waktu'] ?? 'PENUH'));
                            if ($slot == 'PENUH' || $slot == '') {
                                $map_izin_full[$iz['nip']][$dt->format('Y-m-d')] = strtoupper($iz['kategori'] ?? 'IZIN');
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Pengajuan absen query error: ' . $e->getMessage());
                }
            }
        }

        $potongan_map = [];

        foreach ($pegawai as $pgw) {
            $nip = $pgw['nip'];
            $log_map = $all_presensi[$nip] ?? [];

            $res = [
                'hari_kerja' => 0,
                'p1' => 0, 'p2' => 0, 'p3' => 0, 'p4' => 0,
                'pc1' => 0, 'pc2' => 0, 'pc3' => 0, 'pc4' => 0,
                'c' => 0, 'i' => 0, 'ish' => 0, 'a' => 0,
                's' => 0, 'dl' => 0, 'tdk' => 0,
                'ta' => $map_ta[$pgw['id_pegawai']] ?? 0,
                'total_persen' => 0
            ];

            for ($d = 1; $d <= $jml_hari_bulan_ini; $d++) {
                $tgl_loop = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                if ($tgl_loop > $hari_ini_global) break;

                $day_short = date('D', strtotime($tgl_loop));
                $nama_hari_loop = strtolower($this->get_nama_hari($day_short));
                $is_libur_nasional = in_array($tgl_loop, $array_tgl_libur);

                // Hari kerja reguler Senin s.d. Jumat (kecuali libur nasional)
                $is_weekend = in_array($nama_hari_loop, ['sabtu', 'minggu']);
                if ($is_weekend || $is_libur_nasional) {
                    continue;
                }

                $res['hari_kerja']++;

                // Cek Cuti
                if (isset($map_cuti[$pgw['id_pegawai']]) && in_array($tgl_loop, $map_cuti[$pgw['id_pegawai']])) {
                    $res['c']++;
                    continue;
                }

                if (isset($map_izin_full[$nip][$tgl_loop])) {
                    $kategori_izin = $map_izin_full[$nip][$tgl_loop];
                    if (strpos($kategori_izin, 'IZIN') !== false) $res['i']++;
                    elseif (strpos($kategori_izin, 'SAKIT') !== false) $res['s']++;
                    elseif (strpos($kategori_izin, 'LUAR') !== false || strpos($kategori_izin, 'DINAS') !== false || strpos($kategori_izin, 'TUGAS') !== false) $res['dl']++;
                    else $res['i']++;
                    continue;
                }

                if (isset($log_map[$tgl_loop])) {
                    $l = $log_map[$tgl_loop];
                    $is_full_day = false;
                    $kategori = ucwords(strtolower(trim($l['kategori'] ?? '')));
                    $deskripsi = strtolower($l['deskripsi'] ?? '');

                    if (($l['status_verifikasi'] ?? '') === 'Dianulir' || ($l['status_verifikasi'] ?? '') === 'Ditangguhkan') {
                        $kategori = 'Alpa';
                    }

                    if ($kategori == 'Cuti') {
                        $res['c']++;
                        $is_full_day = true;
                    } elseif (in_array($kategori, ['Izin', 'Sakit/izin', 'Sakit/Izin'])) {
                        if (strpos($deskripsi, 'setengah') !== false || $kategori == 'Izin Setengah Hari') {
                            $res['ish']++;
                        } else {
                            $res['i']++;
                            $is_full_day = true;
                        }
                    } elseif ($kategori == 'Sakit') {
                        $res['s']++;
                        $is_full_day = true;
                    } elseif (in_array($kategori, ['Tugas Luar', 'Dinas Luar'])) {
                        $res['dl']++;
                        $is_full_day = true;
                    } elseif ($kategori == 'Alpa') {
                        $res['a']++;
                        $is_full_day = true;
                    }

                    if (!$is_full_day) {
                        // Keterlambatan P1 s.d P4
                        if (!empty($l['jam_masuk_aktual']) && $l['jam_masuk_aktual'] > '04:00:00') {
                            $jam_seharusnya = !empty($l['jam_masuk_seharusnya']) ? $l['jam_masuk_seharusnya'] : '07:30:00';
                            if ($l['jam_masuk_aktual'] > $jam_seharusnya) {
                                $selisih = strtotime($l['jam_masuk_aktual']) - strtotime($jam_seharusnya);
                                $menit_telat = floor($selisih / 60);

                                if ($menit_telat >= 1 && $menit_telat <= 30) $res['p1']++;
                                elseif ($menit_telat >= 31 && $menit_telat <= 60) $res['p2']++;
                                elseif ($menit_telat >= 61 && $menit_telat <= 90) $res['p3']++;
                                elseif ($menit_telat > 90) $res['p4']++;
                            }
                        }

                        // Pulang Cepat Pc1 s.d Pc4
                        if (!empty($l['jam_pulang_seharusnya']) && !empty($l['jam_pulang_aktual']) && $l['jam_pulang_aktual'] < $l['jam_pulang_seharusnya']) {
                            $selisih = strtotime($l['jam_pulang_seharusnya']) - strtotime($l['jam_pulang_aktual']);
                            $menit_pc = floor($selisih / 60);

                            if ($menit_pc >= 1 && $menit_pc <= 30) $res['pc1']++;
                            elseif ($menit_pc >= 31 && $menit_pc <= 60) $res['pc2']++;
                            elseif ($menit_pc >= 61 && $menit_pc <= 90) $res['pc3']++;
                            elseif ($menit_pc > 90) $res['pc4']++;
                        }
                    }
                } else {
                    // Tidak ada log presensi sama sekali pada hari kerja
                    if ($tgl_loop < $hari_ini_global) {
                        $res['a']++;
                        if ($nama_hari_loop == 'senin') {
                            $res['tdk']++;
                        }
                    }
                }
            }

            // Rumus potongan disiplin persis standar Tabel_Perhitungan:
            // Tb: P1 (0.5%), P2 (1%), P3 (1.25%), P4 (1.5%)
            // Pc: Pc1 (0.5%), Pc2 (1%), Pc3 (1.25%), Pc4 (1.5%)
            // Dialog/Apel/Upacara: TDK (0.5%) + TA (2%)
            // Alpa: A (3%)
            $res['total_persen'] = ($res['p1'] * 0.5) + ($res['p2'] * 1.0) + ($res['p3'] * 1.25) + ($res['p4'] * 1.5) +
                ($res['pc1'] * 0.5) + ($res['pc2'] * 1.0) + ($res['pc3'] * 1.25) + ($res['pc4'] * 1.5) +
                ($res['i'] * 0.0) + ($res['a'] * 3.0) + ($res['tdk'] * 0.5) + ($res['ta'] * 2.0);

            $potongan_map[$nip] = $res;
        }

        return [
            'pegawai'      => $pegawai,
            'potongan_map' => $potongan_map
        ];
    }

    /**
     * Halaman Upload & Kelola Data E-Kinerja (Ekin) dan Pagu TPP
     */
    public function kelola_ekin()
    {
        $level = session()->get('level');
        $id_opd_admin = session()->get('id_opd');
        $request = \Config\Services::request();

        $bulan = (int)($request->getGet('bulan') ?? date('m'));
        $tahun = (int)($request->getGet('tahun') ?? date('Y'));
        $id_opd = $request->getGet('id_opd');

        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            $id_opd = $id_opd_admin;
        }

        $list_opd = $this->db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();
        if (empty($id_opd) && !empty($list_opd)) {
            $id_opd = $list_opd[0]['id_opd'];
        }

        $opd_info = $id_opd ? $this->db->table('opd')->where('id_opd', $id_opd)->get()->getRow() : null;

        // Ambil data pegawai dan ekin yang ada
        $pegawai = [];
        if ($id_opd) {
            $pegawai = $this->db->table('pegawai p')
                ->select('p.id_pegawai, p.nip, p.nama_lengkap, p.jabatan, p.golongan, p.pangkat,
                          e.id as id_ekin, e.wke, e.predikat_kinerja, e.persen_kinerja, e.tpp_bk, e.tpp_kk, e.updated_at')
                ->join('tpp_ekin_data e', "p.nip = e.nip AND e.bulan = $bulan AND e.tahun = $tahun", 'left')
                ->where('p.id_opd', $id_opd)
                ->orderBy('p.nama_lengkap', 'ASC')
                ->get()->getResultArray();
        }

        return view('admin/kelola_ekin', [
            'list_opd' => $list_opd,
            'selected_opd' => $id_opd,
            'opd_info' => $opd_info,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'pegawai' => $pegawai,
            'level' => $level
        ]);
    }

    /**
     * Upload File Excel Ekin / TPP
     * Mendukung:
     * 1. Sheet 'TPP REKAP BK&KK' (file seperti TPP_BKPSDM_AGUSTUS 2026.xlsx)
     * 2. Sheet 'PK PNS' (sheet produktivitas PNS)
     * 3. Template Generic (kolom NIP, WKE, % Kinerja, TPP BK, TPP KK)
     */
    public function upload_ekin()
    {
        $request = \Config\Services::request();
        $file = $request->getFile('file_excel');
        $id_opd = (int)$request->getPost('id_opd');
        $bulan = (int)$request->getPost('bulan');
        $tahun = (int)$request->getPost('tahun');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'File tidak valid atau belum dipilih.']);
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Hanya file format .xlsx, .xls, atau .csv yang diperbolehkan.']);
        }

        try {
            $filePath = $file->getTempName();
            $reader = IOFactory::createReaderForFile($filePath);
            $spreadsheet = $reader->load($filePath);
            $sheetNames = $spreadsheet->getSheetNames();

            $parsedData = [];

            // Case 1: Terdapat Sheet 'TPP REKAP BK&KK'
            if (in_array('TPP REKAP BK&KK', $sheetNames)) {
                $sheet = $spreadsheet->getSheetByName('TPP REKAP BK&KK');
                $highestRow = $sheet->getHighestRow();
                for ($r = 12; $r <= $highestRow; $r += 2) {
                    $nama = trim((string)$sheet->getCell("B$r")->getValue());
                    $nipRaw = trim((string)$sheet->getCell("B" . ($r + 1))->getValue());
                    if (empty($nama) && empty($nipRaw)) continue;

                    $nip = preg_replace('/[^0-9]/', '', $nipRaw);
                    if (empty($nip)) continue;

                    $wke = (string)$sheet->getCell("E$r")->getCalculatedValue();
                    if (is_array($wke)) $wke = '0';
                    $tpp_bk = (float)$sheet->getCell("F$r")->getValue();
                    $tpp_kk = (float)$sheet->getCell("G$r")->getValue();
                    $pct = (float)$sheet->getCell("L$r")->getCalculatedValue();
                    if ($pct <= 1.0 && $pct > 0) $pct *= 100;
                    if ($pct == 0) $pct = 100;

                    $parsedData[$nip] = [
                        'nip'             => $nip,
                        'nama'            => $nama,
                        'wke'             => $wke,
                        'predikat'        => 'BAIK',
                        'persen_kinerja'  => $pct,
                        'tpp_bk'          => $tpp_bk,
                        'tpp_kk'          => $tpp_kk
                    ];
                }
            }
            // Case 2: Terdapat Sheet 'PK PNS'
            elseif (in_array('PK PNS', $sheetNames)) {
                $sheet = $spreadsheet->getSheetByName('PK PNS');
                $highestRow = $sheet->getHighestRow();
                for ($r = 10; $r <= $highestRow; $r += 2) {
                    $nama = trim((string)$sheet->getCell("B$r")->getValue());
                    $nipRaw = trim((string)$sheet->getCell("B" . ($r + 1))->getValue());
                    if (empty($nama) && empty($nipRaw)) continue;

                    $nip = preg_replace('/[^0-9]/', '', $nipRaw);
                    if (empty($nip)) continue;

                    $wke = (string)$sheet->getCell("E$r")->getValue();
                    $rating = trim((string)$sheet->getCell("H$r")->getValue()) ?: 'BAIK';
                    $pct = (float)$sheet->getCell("I$r")->getValue();
                    if ($pct <= 1.0 && $pct > 0) $pct *= 100;
                    if ($pct == 0) $pct = 100;

                    $parsedData[$nip] = [
                        'nip'             => $nip,
                        'nama'            => $nama,
                        'wke'             => $wke,
                        'predikat'        => $rating,
                        'persen_kinerja'  => $pct,
                        'tpp_bk'          => null,
                        'tpp_kk'          => null
                    ];
                }
            }
            // Case 3: Format Generic / Template
            else {
                $sheet = $spreadsheet->getActiveSheet();
                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn();

                // Cari baris header
                $headerRow = 1;
                $colMap = [];
                for ($r = 1; $r <= min(10, $highestRow); $r++) {
                    for ($c = 'A'; $c <= $highestCol; $c++) {
                        $val = strtolower(trim((string)$sheet->getCell($c . $r)->getValue()));
                        if (strpos($val, 'nip') !== false) $colMap['nip'] = $c;
                        if (strpos($val, 'wke') !== false) $colMap['wke'] = $c;
                        if (strpos($val, 'kinerja') !== false || strpos($val, 'capaian') !== false || strpos($val, 'produktivitas') !== false) $colMap['kinerja'] = $c;
                        if (strpos($val, 'rating') !== false || strpos($val, 'predikat') !== false) $colMap['predikat'] = $c;
                        if (strpos($val, 'tpp bk') !== false || strpos($val, 'beban kerja') !== false) $colMap['bk'] = $c;
                        if (strpos($val, 'tpp kk') !== false || strpos($val, 'kondisi kerja') !== false) $colMap['kk'] = $c;
                        if (strpos($val, 'nama') !== false) $colMap['nama'] = $c;
                    }
                    if (isset($colMap['nip'])) {
                        $headerRow = $r;
                        break;
                    }
                }

                if (!isset($colMap['nip'])) {
                    return $this->response->setJSON(['status' => 'error', 'message' => 'Kolom NIP tidak ditemukan pada file Excel. Silakan gunakan format template resmi.']);
                }

                for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
                    $nipRaw = trim((string)$sheet->getCell($colMap['nip'] . $r)->getValue());
                    $nip = preg_replace('/[^0-9]/', '', $nipRaw);
                    if (empty($nip)) continue;

                    $wke = isset($colMap['wke']) ? (string)$sheet->getCell($colMap['wke'] . $r)->getCalculatedValue() : '0';
                    $pct = isset($colMap['kinerja']) ? (float)$sheet->getCell($colMap['kinerja'] . $r)->getCalculatedValue() : 100.0;
                    if ($pct <= 1.0 && $pct > 0) $pct *= 100;
                    if ($pct == 0 && isset($colMap['kinerja'])) $pct = 0;
                    elseif ($pct == 0) $pct = 100;

                    $predikat = isset($colMap['predikat']) ? trim((string)$sheet->getCell($colMap['predikat'] . $r)->getValue()) : 'BAIK';
                    $bk = isset($colMap['bk']) ? (float)$sheet->getCell($colMap['bk'] . $r)->getValue() : null;
                    $kk = isset($colMap['kk']) ? (float)$sheet->getCell($colMap['kk'] . $r)->getValue() : null;
                    $nama = isset($colMap['nama']) ? trim((string)$sheet->getCell($colMap['nama'] . $r)->getValue()) : '';

                    $parsedData[$nip] = [
                        'nip'             => $nip,
                        'nama'            => $nama,
                        'wke'             => $wke,
                        'predikat'        => $predikat ?: 'BAIK',
                        'persen_kinerja'  => $pct,
                        'tpp_bk'          => $bk,
                        'tpp_kk'          => $kk
                    ];
                }
            }

            if (empty($parsedData)) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada data pegawai yang dapat dibaca dari file Excel.']);
            }

            // Simpan / Upsert ke tabel tpp_ekin_data
            $inserted = 0;
            $updated = 0;

            foreach ($parsedData as $nip => $d) {
                // Cari data existing jika ada
                $existing = $this->db->table('tpp_ekin_data')
                    ->where('nip', $nip)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->get()->getRow();

                $bk_val = ($d['tpp_bk'] !== null) ? $d['tpp_bk'] : ($existing->tpp_bk ?? 0.00);
                $kk_val = ($d['tpp_kk'] !== null) ? $d['tpp_kk'] : ($existing->tpp_kk ?? 0.00);

                // Cek id_opd pegawai asli jika tidak ditentukan
                $peg = $this->db->table('pegawai')->select('id_opd, nama_lengkap')->where('nip', $nip)->get()->getRow();
                $actual_opd = $peg ? $peg->id_opd : $id_opd;
                $nama_fix = !empty($d['nama']) ? $d['nama'] : ($peg->nama_lengkap ?? '');

                $payload = [
                    'id_opd'           => $actual_opd,
                    'nip'              => $nip,
                    'nama_pegawai'     => $nama_fix,
                    'bulan'            => $bulan,
                    'tahun'            => $tahun,
                    'wke'              => (string)$d['wke'],
                    'predikat_kinerja' => $d['predikat'],
                    'persen_kinerja'   => $d['persen_kinerja'],
                    'tpp_bk'           => $bk_val,
                    'tpp_kk'           => $kk_val,
                    'updated_at'       => date('Y-m-d H:i:s')
                ];

                if ($existing) {
                    $this->db->table('tpp_ekin_data')->where('id', $existing->id)->update($payload);
                    $updated++;
                } else {
                    $payload['created_at'] = date('Y-m-d H:i:s');
                    $this->db->table('tpp_ekin_data')->insert($payload);
                    $inserted++;
                }
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => "Berhasil memproses data E-Kinerja! Total: " . count($parsedData) . " pegawai (Baru: $inserted, Diperbarui: $updated)."
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Terjadi kesalahan saat memproses file: ' . $e->getMessage()]);
        }
    }

    /**
     * Simpan / Update Manual Data Ekin & Pagu TPP per Pegawai
     */
    public function save_manual_ekin()
    {
        $request = \Config\Services::request();
        $nip = $request->getPost('nip');
        $bulan = (int)$request->getPost('bulan');
        $tahun = (int)$request->getPost('tahun');
        $wke = $request->getPost('wke');
        $persen_kinerja = (float)$request->getPost('persen_kinerja');
        $tpp_bk = (float)$request->getPost('tpp_bk');
        $tpp_kk = (float)$request->getPost('tpp_kk');
        $predikat = $request->getPost('predikat_kinerja') ?: 'BAIK';

        if (empty($nip) || empty($bulan) || empty($tahun)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Parameter tidak lengkap.']);
        }

        $peg = $this->db->table('pegawai')->where('nip', $nip)->get()->getRow();
        if (!$peg) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pegawai tidak ditemukan.']);
        }

        $existing = $this->db->table('tpp_ekin_data')
            ->where('nip', $nip)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()->getRow();

        $data = [
            'id_opd'           => $peg->id_opd,
            'nip'              => $nip,
            'nama_pegawai'     => $peg->nama_lengkap,
            'bulan'            => $bulan,
            'tahun'            => $tahun,
            'wke'              => (string)$wke,
            'predikat_kinerja' => $predikat,
            'persen_kinerja'   => $persen_kinerja,
            'tpp_bk'           => $tpp_bk,
            'tpp_kk'           => $tpp_kk,
            'updated_at'       => date('Y-m-d H:i:s')
        ];

        if ($existing) {
            $this->db->table('tpp_ekin_data')->where('id', $existing->id)->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('tpp_ekin_data')->insert($data);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Data E-Kinerja & Pagu TPP berhasil disimpan!']);
    }

    /**
     * Download Template Excel E-Kinerja & Pagu TPP
     */
    public function download_template()
    {
        $request = \Config\Services::request();
        $id_opd = (int)$request->getGet('id_opd');
        $bulan = (int)($request->getGet('bulan') ?? date('m'));
        $tahun = (int)($request->getGet('tahun') ?? date('Y'));

        $opd = $this->db->table('opd')->where('id_opd', $id_opd)->get()->getRow();
        $nama_opd = $opd ? $opd->nama_opd : 'SEMUA_OPD';

        $builder = $this->db->table('pegawai p')
            ->select('p.nip, p.nama_lengkap, p.pangkat, p.golongan, p.jabatan,
                      e.wke, e.predikat_kinerja, e.persen_kinerja, e.tpp_bk, e.tpp_kk')
            ->join('tpp_ekin_data e', "p.nip = e.nip AND e.bulan = $bulan AND e.tahun = $tahun", 'left');

        if ($id_opd) {
            $builder->where('p.id_opd', $id_opd);
        }

        $pegawai = $builder->orderBy('p.nama_lengkap', 'ASC')->get()->getResultArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DATA_EKIN');

        // Judul
        $sheet->setCellValue('A1', 'TEMPLATE INPUT E-KINERJA & PAGU TPP');
        $sheet->setCellValue('A2', "OPD: $nama_opd | BULAN: $bulan | TAHUN: $tahun");
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);

        // Header
        $headers = [
            'A4' => 'NO',
            'B4' => 'NIP (Wajib)',
            'C4' => 'NAMA PEGAWAI',
            'D4' => 'PANGKAT / GOL',
            'E4' => 'JABATAN',
            'F4' => 'JUMLAH WKE',
            'G4' => 'RATING / PREDIKAT',
            'H4' => '% KINERJA (0 - 100)',
            'I4' => 'TPP BK (Beban Kerja)',
            'J4' => 'TPP KK (Kondisi Kerja)'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $styleHeader = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A4:J4')->applyFromArray($styleHeader);

        $row = 5;
        $no = 1;
        foreach ($pegawai as $p) {
            $sheet->setCellValue("A$row", $no++);
            $sheet->setCellValueExplicit("B$row", (string)$p['nip'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C$row", $p['nama_lengkap']);
            $sheet->setCellValue("D$row", $p['pangkat'] . ' (' . $p['golongan'] . ')');
            $sheet->setCellValue("E$row", $p['jabatan']);
            $sheet->setCellValue("F$row", $p['wke'] ?? '0');
            $sheet->setCellValue("G$row", $p['predikat_kinerja'] ?? 'BAIK');
            $sheet->setCellValue("H$row", $p['persen_kinerja'] ?? '100');
            $sheet->setCellValue("I$row", $p['tpp_bk'] ?? '0');
            $sheet->setCellValue("J$row", $p['tpp_kk'] ?? '0');
            $row++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "Template_Ekin_TPP_" . str_replace(' ', '_', $nama_opd) . "_{$bulan}_{$tahun}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Pengaturan Prototype OPD (Admin Kabupaten & Superadmin)
     */
    public function setting_opd()
    {
        $level = session()->get('level');
        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            return redirect()->to(base_url('tpp_dinamis'))->with('error', 'Hanya Admin Kabupaten yang dapat mengakses pengaturan ini.');
        }

        $list_opd = $this->db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        return view('admin/setting_tpp_opd', [
            'list_opd' => $list_opd
        ]);
    }

    /**
     * AJAX Toggle Aktif / Nonaktifkan Prototype TPP Dinamis untuk suatu OPD
     */
    public function toggle_opd()
    {
        $level = session()->get('level');
        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak.']);
        }

        $id_opd = (int)$this->request->getPost('id_opd');
        $status = (int)$this->request->getPost('status'); // 1 atau 0

        $opd = $this->db->table('opd')->where('id_opd', $id_opd)->get()->getRow();
        if (!$opd) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'OPD tidak ditemukan.']);
        }

        $this->db->table('opd')->where('id_opd', $id_opd)->update(['tpp_dinamis_aktif' => $status]);

        $statusTxt = $status ? 'diaktifkan' : 'dinonaktifkan';
        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Prototype TPP Dinamis untuk {$opd->nama_opd} berhasil {$statusTxt}."
        ]);
    }

    /**
     * Export Excel Rekap TPP BK & KK (Format Persis Sheet 'TPP REKAP BK&KK')
     */
    public function excel()
    {
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '-1');

        $request = \Config\Services::request();
        $id_opd = (int)$request->getGet('id_opd');
        $bulan = (int)($request->getGet('bulan') ?? date('m'));
        $tahun = (int)($request->getGet('tahun') ?? date('Y'));
        $status_asn = $request->getGet('status_asn');

        $opd = $this->db->table('opd')->where('id_opd', $id_opd)->get()->getRow();
        $nama_opd = $opd ? $opd->nama_opd : 'SEMUA_OPD';

        $presensi_calc = $this->calculate_presensi_disiplin($id_opd, $bulan, $tahun, $status_asn);
        $pegawai_raw = $presensi_calc['pegawai'];
        $potongan_map = $presensi_calc['potongan_map'];

        $nips = array_column($pegawai_raw, 'nip');
        $ekin_map = [];
        if (!empty($nips)) {
            $ekin_rows = $this->db->table('tpp_ekin_data')
                ->whereIn('nip', $nips)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get()->getResultArray();
            foreach ($ekin_rows as $er) {
                $ekin_map[$er['nip']] = $er;
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('TPP REKAP BK&KK');

        $nama_bulan_arr = [
            1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
            5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
            9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
        ];
        $nama_bulan = $nama_bulan_arr[$bulan] ?? 'BULAN';

        // Header Laporan
        $sheet->setCellValue('A2', 'TABEL PERHITUNGAN TPP BEBAN KERJA DAN KONDISI KERJA (BK & KK)');
        $sheet->setCellValue('A5', 'PERANGKAT DAERAH');
        $sheet->setCellValue('C5', ': ' . strtoupper($nama_opd));
        $sheet->setCellValue('A6', 'BULAN');
        $sheet->setCellValue('C6', ': ' . $nama_bulan . ' ' . $tahun);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A5:C6')->getFont()->setBold(true)->setSize(10);

        // Header Tabel Utama (Baris 8, 9, 10)
        $sheet->setCellValue('A8', 'NO')->mergeCells('A8:A9');
        $sheet->setCellValue('B8', 'NAMA/NIP')->mergeCells('B8:B9');
        $sheet->setCellValue('C8', 'PANGKAT/GOL. RUANG')->mergeCells('C8:C9');
        $sheet->setCellValue('D8', 'JABATAN')->mergeCells('D8:D9');
        $sheet->setCellValue('E8', 'JUMLAH WKE')->mergeCells('E8:E9');

        $sheet->setCellValue('F8', 'JENIS TPP')->mergeCells('F8:G8');
        $sheet->setCellValue('F9', 'TPP BK');
        $sheet->setCellValue('G9', 'TPP KK');

        $sheet->setCellValue('H8', "JUMLAH TPP\n(BK+KK)")->mergeCells('H8:H9');
        $sheet->setCellValue('I8', 'BPJS 1%')->mergeCells('I8:I9');
        $sheet->setCellValue('J8', 'JUMLAH TPP SETELAH POTONGAN BPJS')->mergeCells('J8:J9');

        $sheet->setCellValue('K8', 'PREDIKAT KINERJA (60%)')->mergeCells('K8:M8');
        $sheet->setCellValue('K9', 'TPP (60%)');
        $sheet->setCellValue('L9', '% KINERJA PEGAWAI');
        $sheet->setCellValue('M9', 'JUMLAH');

        $sheet->setCellValue('N8', 'DISIPLIN KERJA (40%)')->mergeCells('N8:P8');
        $sheet->setCellValue('N9', 'TPP (40%)');
        $sheet->setCellValue('O9', 'PENGURANGAN');
        $sheet->setCellValue('P9', 'JUMLAH');

        $sheet->setCellValue('Q8', 'JUMLAH TPP KOTOR')->mergeCells('Q8:Q9');
        $sheet->setCellValue('R8', 'PPh 21')->mergeCells('R8:S8');
        $sheet->setCellValue('R9', 'TARIF');
        $sheet->setCellValue('S9', 'NOMINAL');

        $sheet->setCellValue('T8', 'JUMLAH TPP BERSIH')->mergeCells('T8:T9');
        $sheet->setCellValue('U8', 'TANDA TANGAN')->mergeCells('U8:U9');

        // Kolom Baris 10: Kode / Formula Index
        $indices = [
            'A' => '1', 'B' => '2', 'C' => '3', 'D' => '4', 'E' => '5',
            'F' => '6', 'G' => '8', 'H' => '9 (6+8)', 'I' => '10 (9*1%)', 'J' => '11 (9-10)',
            'K' => '12 (11*60%)', 'L' => '13', 'M' => '14 (12*13)', 'N' => '15 (11*40%)',
            'O' => '16', 'P' => '17 (15-16)', 'Q' => '18 (14+17)', 'R' => '19', 'S' => '19 (18*19)',
            'T' => '20 (18-S)', 'U' => '21'
        ];
        foreach ($indices as $col => $val) {
            $sheet->setCellValue($col . '10', $val);
        }

        $styleHeaderTable = [
            'font' => ['bold' => true, 'size' => 9],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']]
        ];
        $sheet->getStyle('A8:U10')->applyFromArray($styleHeaderTable);

        // Data Rows
        $row = 12;
        $no = 1;

        foreach ($pegawai_raw as $pgw) {
            $nip = $pgw['nip'];
            $pot = $potongan_map[$nip] ?? ['total_persen' => 0];
            $ekin = $ekin_map[$nip] ?? null;

            $wke = $ekin['wke'] ?? '0';
            $persen_kinerja = isset($ekin['persen_kinerja']) ? (float)$ekin['persen_kinerja'] : 100.00;
            $tpp_bk = isset($ekin['tpp_bk']) ? (float)$ekin['tpp_bk'] : 0.00;
            $tpp_kk = isset($ekin['tpp_kk']) ? (float)$ekin['tpp_kk'] : 0.00;
            $tarif_pph = $this->get_tarif_pph($pgw['golongan'] ?? $pgw['pangkat'] ?? '');

            // Row 1: Nama, Pangkat, Jabatan, Data Utama
            $sheet->setCellValue("A$row", $no);
            $sheet->setCellValue("B$row", $pgw['nama_lengkap']);
            $sheet->setCellValue("C$row", $pgw['pangkat'] . ' / ' . $pgw['golongan']);
            $sheet->setCellValue("D$row", $pgw['jabatan']);
            $sheet->setCellValue("E$row", $wke);
            $sheet->setCellValue("F$row", $tpp_bk);
            $sheet->setCellValue("G$row", $tpp_kk);

            // Formula Excel persis sheet TPP REKAP BK&KK:
            $sheet->setCellValue("H$row", "=SUM(F$row:G$row)");
            $sheet->setCellValue("I$row", "=H$row*0.01");
            $sheet->setCellValue("J$row", "=H$row-I$row");

            $sheet->setCellValue("K$row", "=J$row*0.60");
            $sheet->setCellValue("L$row", ($persen_kinerja / 100.0));
            $sheet->setCellValue("M$row", "=K$row*L$row");

            $sheet->setCellValue("N$row", "=J$row*0.40");
            $persen_pot = (float)($pot['total_persen'] ?? 0) / 100.0;
            $sheet->setCellValue("O$row", "=N$row*$persen_pot");
            $sheet->setCellValue("P$row", "=N$row-O$row");

            $sheet->setCellValue("Q$row", "=M$row+P$row");
            $sheet->setCellValue("R$row", $tarif_pph);
            $sheet->setCellValue("S$row", "=Q$row*R$row");
            $sheet->setCellValue("T$row", "=Q$row-S$row");
            $sheet->setCellValue("U$row", $no);

            // Row 2: NIP
            $nipRow = $row + 1;
            $sheet->setCellValueExplicit("B$nipRow", "NIP. " . $nip, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            // Formatting Number
            $sheet->getStyle("F$row:J$row")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("K$row")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("L$row")->getNumberFormat()->setFormatCode('0%');
            $sheet->getStyle("M$row:Q$row")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("R$row")->getNumberFormat()->setFormatCode('0%');
            $sheet->getStyle("S$row:T$row")->getNumberFormat()->setFormatCode('#,##0');

            // Borders
            $sheet->getStyle("A$row:U$nipRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A$row:A$nipRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->mergeCells("A$row:A$nipRow");
            $sheet->mergeCells("C$row:C$nipRow");
            $sheet->mergeCells("D$row:D$nipRow");
            $sheet->mergeCells("E$row:E$nipRow");
            $sheet->mergeCells("U$row:U$nipRow");

            $row += 2;
            $no++;
        }

        foreach (range('A', 'U') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "TPP_REKAP_BKKK_" . str_replace(' ', '_', $nama_opd) . "_{$nama_bulan}_{$tahun}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Rekap extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // makassar
        date_default_timezone_set('Asia/Makassar');
    }

    public function index()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        $level = session()->get('level');
        $id_opd_admin = session()->get('id_opd');

        $bulan = $request->getGet('bulan') ?? date('m');
        $tahun = $request->getGet('tahun') ?? date('Y');

        $status_asn = $request->getGet('status_asn');

        $filter_shift = $request->getGet('id_jam_kerja');

        $list_opd = $db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        // =========================================================================
        // [PERBAIKAN ENTERPRISE]: DETEKSI DINAS INDUK & UPTD ANAK (STRICT MODE)
        // =========================================================================
        $admin_opd_info = $db->table('opd')->where('id_opd', $id_opd_admin)->get()->getRow();
        $admin_opd_name = $admin_opd_info ? strtoupper($admin_opd_info->nama_opd) : '';

        $list_gabungan = [];
        $child_opds = [];
        $is_induk_opd = false;

        foreach ($list_opd as $o) {
            if (strpos($o['nama_opd'], ' - ') !== false) {
                $induk = trim(explode(' - ', $o['nama_opd'])[0]);
                $list_gabungan[$induk] = $induk;
            }
            $nama_upper = strtoupper($o['nama_opd']);
            if (strpos($nama_upper, 'RSUD HAJJAH ANDI DEPU') !== false) $list_gabungan['RSUD HAJJAH ANDI DEPU'] = 'RSUD HAJJAH ANDI DEPU';
            if (strpos($nama_upper, 'RSUD WONOMULYO') !== false) $list_gabungan['RSUD WONOMULYO'] = 'RSUD WONOMULYO';
            if (strpos($nama_upper, 'DINAS KESEHATAN') !== false) $list_gabungan['DINAS KESEHATAN'] = 'DINAS KESEHATAN';
            if (strpos($nama_upper, 'DINAS PENDIDIKAN') !== false) $list_gabungan['DINAS PENDIDIKAN'] = 'DINAS PENDIDIKAN';

            if (!in_array($level, ['Superadmin', 'kabupaten'])) {
                $is_child = false;

                // [PERBAIKAN STRICT]: Anak harus mengandung kata DINAS KESEHATAN
                if (strpos($admin_opd_name, 'DINAS KESEHATAN') !== false && strpos($nama_upper, 'DINAS KESEHATAN') !== false) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'DINAS PENDIDIKAN') !== false && (strpos($nama_upper, 'SDN ') !== false || strpos($nama_upper, 'SMPN ') !== false || strpos($nama_upper, 'TK ') !== false)) {
                    $is_child = true;
                }

                if ($is_child && $o['id_opd'] != $id_opd_admin) {
                    $is_induk_opd = true;
                    $child_opds[] = $o;
                }
            }
        }
        $list_gabungan = array_values($list_gabungan);

        $id_opd = $request->getGet('id_opd');

        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            if ($is_induk_opd && !empty($id_opd)) {
                if (strpos($id_opd, 'gabungan||') === 0) {
                    $req_gab = str_replace('gabungan||', '', $id_opd);
                    if (strpos($admin_opd_name, strtoupper($req_gab)) === false) $id_opd = $id_opd_admin;
                } else {
                    $valid = false;
                    foreach ($child_opds as $c) {
                        if ($c['id_opd'] == $id_opd) $valid = true;
                    }
                    if (!$valid && $id_opd != $id_opd_admin) $id_opd = $id_opd_admin;
                }
            } else {
                $id_opd = $id_opd_admin;
            }
        }
        // =========================================================================

        $is_gabungan = false;
        $prefix_gabungan = '';
        if ($id_opd && strpos($id_opd, 'gabungan||') === 0) {
            $is_gabungan = true;
            $prefix_gabungan = str_replace('gabungan||', '', $id_opd);
        }

        $master_jam = $db->table('jam_kerja')->get()->getResultArray();
        $map_jam_name = [];
        foreach ($master_jam as $mj) {
            $map_jam_name[$mj['id_jam_kerja']] = $mj['nama_jadwal'] ?? 'Reguler';
        }

        $libur_db = $db->table('hari_libur')
            ->where("MONTH(tanggal)", $bulan)->where("YEAR(tanggal)", $tahun)
            ->get()->getResultArray();
        $libur_nasional = [];
        foreach ($libur_db as $l) $libur_nasional[$l['tanggal']] = $l['keterangan'];

        $jadwal_db = $db->table('jam_kerja_detail')->get()->getResultArray();
        $map_jadwal = [];
        $jadwal_kerja = [];
        foreach ($jadwal_db as $j) {
            $map_jadwal[$j['id_jam_kerja']][$j['hari']] = $j;
            $jadwal_kerja[$j['id_jam_kerja']][$j['hari']] = true;
        }

        $hari_indo_map = [
            'Sun' => 'Minggu',
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu'
        ];

        $rekap_final = [];
        $list_tanggal = [];

        if ($id_opd) {
            $tgl_awal_bulan = sprintf('%04d-%02d-01', $tahun, $bulan);
            $tgl_akhir_bulan = date('Y-m-t', strtotime($tgl_awal_bulan));

            $jumlah_hari = date('t', mktime(0, 0, 0, $bulan, 1, $tahun));
            for ($d = 1; $d <= $jumlah_hari; $d++) {
                $list_tanggal[] = date('Y-m-d', mktime(0, 0, 0, $bulan, $d, $tahun));
            }

            $builder_pegawai = $db->table('pegawai p')
                ->select('p.*, opd.nama_opd, opd.id_jam_kerja as id_jam_opd')
                ->join('opd', 'p.id_opd = opd.id_opd', 'left');

            if ($is_gabungan) {
                $builder_pegawai->like('opd.nama_opd', $prefix_gabungan, 'both');
            } elseif ($id_opd == 'rekap_eselon_2') {
                $builder_pegawai->whereIn('p.eselon', ['II.a', 'II.b']);
            } elseif ($id_opd == 'rekap_eselon_3') {
                $builder_pegawai->whereIn('p.eselon', ['III.a', 'III.b']);
            } elseif ($id_opd == 'rekap_eselon_4') {
                $builder_pegawai->whereIn('p.eselon', ['IV.a', 'IV.b']);
            } elseif ($id_opd == 'rekap_eselon_gabungan') {
                $builder_pegawai->whereIn('p.eselon', ['II.a', 'II.b', 'III.a', 'III.b', 'IV.a', 'IV.b']);
            } else {
                $builder_pegawai->where("(
                    p.id_pegawai IN (
                        SELECT id_pegawai FROM riwayat_penempatan 
                        WHERE id_opd = '$id_opd' 
                        AND tmt_mulai <= '$tgl_akhir_bulan' 
                        AND (tmt_selesai >= '$tgl_awal_bulan' OR tmt_selesai IS NULL)
                    )
                    OR
                    (p.id_opd = '$id_opd' AND p.id_pegawai NOT IN (SELECT id_pegawai FROM riwayat_penempatan))
                )");
            }

            if (!empty($status_asn)) {
                $builder_pegawai->where('p.status_asn', $status_asn);
            }

            if ($is_gabungan) {
                $builder_pegawai->orderBy('opd.nama_opd', 'ASC');
            }

            $pegawai = $builder_pegawai->orderBy("CASE 
                    WHEN TRIM(p.eselon) = 'II.a' THEN 1 WHEN TRIM(p.eselon) = 'II.b' THEN 2
                    WHEN TRIM(p.eselon) = 'III.a' THEN 3 WHEN TRIM(p.eselon) = 'III.b' THEN 4
                    WHEN TRIM(p.eselon) = 'IV.a' THEN 5 WHEN TRIM(p.eselon) = 'IV.b' THEN 6
                    ELSE 7 END", "ASC", false)
                ->orderBy('p.nama_lengkap', 'ASC')
                ->get()->getResultArray();

            $ids_pegawai = array_column($pegawai, 'id_pegawai');
            $nips_pegawai = array_column($pegawai, 'nip');

            if (!empty($ids_pegawai)) {
                $map_plotting = [];
                $builder_plot = $db->table('plotting_shift')->whereIn('id_pegawai', $ids_pegawai);

                if ($db->fieldExists('tanggal_mulai', 'plotting_shift')) {
                    $builder_plot->where("tanggal_mulai <=", "$tahun-$bulan-31")
                        ->where("tanggal_selesai >=", "$tahun-$bulan-01");
                } else {
                    $builder_plot->where("MONTH(tanggal)", $bulan)->where("YEAR(tanggal)", $tahun);
                }
                $plotting_db = $builder_plot->get()->getResultArray();

                $is_nakes_map = [];

                foreach ($plotting_db as $pl) {
                    $nama_jadwal_master = strtolower($map_jam_name[$pl['id_jam_kerja']] ?? '');
                    if (strpos($nama_jadwal_master, 'ramadhan') === false && strpos($nama_jadwal_master, 'puasa') === false) {
                        $is_nakes_map[$pl['id_pegawai']] = true;
                    }

                    if (isset($pl['tanggal_mulai'])) {
                        $start = new \DateTime($pl['tanggal_mulai']);
                        $end = new \DateTime($pl['tanggal_selesai']);
                        $end->modify('+1 day');
                        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
                        foreach ($period as $date) {
                            $map_plotting[$pl['id_pegawai']][$date->format('Y-m-d')] = $pl['id_jam_kerja'];
                        }
                    } else {
                        $map_plotting[$pl['id_pegawai']][$pl['tanggal']] = $pl['id_jam_kerja'];
                    }
                }

                // ==============================================================
                // [FITUR BARU] 1. TARIK DATA CUTI DISETUJUI
                // ==============================================================
                $cuti_raw = $db->table('pengajuan_cuti')
                    ->select('pengajuan_cuti.*, master_cuti.nama_cuti')
                    ->join('master_cuti', 'master_cuti.id_cuti = pengajuan_cuti.cuti_id')
                    ->whereIn('pegawai_id', $ids_pegawai)
                    ->where("LOWER(TRIM(status))", 'disetujui')
                    ->where("tgl_mulai <= LAST_DAY('$tahun-$bulan-01') AND COALESCE(NULLIF(tgl_selesai, ''), tgl_mulai) >= '$tahun-$bulan-01'")
                    ->get()->getResultArray();

                $map_cuti = [];
                foreach ($cuti_raw as $c) {
                    $start = new \DateTime($c['tgl_mulai']);
                    $tgl_selesai_val = empty($c['tgl_selesai']) ? $c['tgl_mulai'] : $c['tgl_selesai'];
                    $end = new \DateTime($tgl_selesai_val);
                    $end->modify('+1 day');
                    $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
                    foreach ($period as $date) {
                        if ($date->format('m') == $bulan && $date->format('Y') == $tahun) {
                            $map_cuti[$c['pegawai_id']][$date->format('Y-m-d')] = $c['nama_cuti'];
                        }
                    }
                }

                // ==============================================================
                // [FITUR BARU] 2. TARIK DATA IZIN/SAKIT/TL DISETUJUI (Anti-Alpha)
                // ==============================================================
                $izin_raw = $db->table('pengajuan_absen')
                    ->whereIn('nip', $nips_pegawai)
                    ->where("LOWER(TRIM(status_approval))", 'disetujui')
                    ->where("tanggal_mulai <= LAST_DAY('$tahun-$bulan-01') AND COALESCE(NULLIF(tanggal_selesai, ''), tanggal_mulai) >= '$tahun-$bulan-01'")
                    ->get()->getResultArray();

                $map_izin_today = [];
                foreach ($izin_raw as $iz) {
                    $start = new \DateTime($iz['tanggal_mulai']);
                    $tgl_selesai_val = empty($iz['tanggal_selesai']) ? $iz['tanggal_mulai'] : $iz['tanggal_selesai'];
                    $end = new \DateTime($tgl_selesai_val);
                    $end->modify('+1 day');
                    $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
                    foreach ($period as $date) {
                        if ($date->format('m') == $bulan && $date->format('Y') == $tahun) {
                            $map_izin_today[$iz['nip']][$date->format('Y-m-d')] = [
                                'kategori' => strtoupper($iz['kategori']),
                                'sub' => strtoupper($iz['sub_kategori'] ?? ''),
                                'slot' => strtoupper($iz['slot_waktu'] ?? 'PENUH')
                            ];
                        }
                    }
                }

                $presensi_raw = $db->table('presensi')
                    ->whereIn('nip', $nips_pegawai)
                    ->where("MONTH(presensi.tanggal)", $bulan)
                    ->where("YEAR(presensi.tanggal)", $tahun)
                    ->get()->getResultArray();

                $data_presensi = [];
                foreach ($presensi_raw as $p) {
                    $data_presensi[$p['nip']][$p['tanggal']] = $p;
                }

                foreach ($pegawai as $pgw) {
                    $row = [
                        'nama'    => $pgw['nama_lengkap'],
                        'nip'     => $pgw['nip'],
                        'jabatan' => $pgw['jabatan'] ?? '-',
                        'nama_opd' => $pgw['nama_opd'],
                        'harian'  => []
                    ];

                    $id_jk_default = $pgw['id_jam_opd'] ?? 1;
                    $show_employee = empty($filter_shift) ? true : false;

                    foreach ($list_tanggal as $tgl) {
                        $day_eng = date('D', strtotime($tgl));
                        $nama_hari = $hari_indo_map[$day_eng];

                        $ada_jadwal_shift_hari_ini = isset($map_plotting[$pgw['id_pegawai']][$tgl]);
                        $punya_histori_shift_bulan_ini = isset($is_nakes_map[$pgw['id_pegawai']]);
                        $is_lepas_jaga = ($punya_histori_shift_bulan_ini && !$ada_jadwal_shift_hari_ini);

                        $active_id_jam = $id_jk_default;
                        if ($ada_jadwal_shift_hari_ini) {
                            $active_id_jam = $map_plotting[$pgw['id_pegawai']][$tgl];
                        }
                        if (isset($data_presensi[$pgw['nip']][$tgl]['id_jam_kerja_snapshot']) && !empty($data_presensi[$pgw['nip']][$tgl]['id_jam_kerja_snapshot'])) {
                            $active_id_jam = $data_presensi[$pgw['nip']][$tgl]['id_jam_kerja_snapshot'];
                        }

                        $nama_shift_display = $map_jam_name[$active_id_jam] ?? 'Reguler';
                        if ($is_lepas_jaga) {
                            $nama_shift_display = 'Lepas Jaga';
                        }

                        // =============================================================
                        // DETEKSI CITO UNTUK BYPASS SANKSI & LIBUR
                        // =============================================================
                        $nama_jadwal_master_aktif = strtolower($nama_shift_display);
                        $is_jadwal_cito = (strpos($nama_jadwal_master_aktif, 'cito') !== false || strpos($nama_jadwal_master_aktif, 'standby') !== false);
                        // =============================================================

                        if (!empty($filter_shift)) {
                            if ($filter_shift == 'lepas_jaga' && $is_lepas_jaga) {
                                $show_employee = true;
                            } elseif ($filter_shift != 'lepas_jaga' && !$is_lepas_jaga && $active_id_jam == $filter_shift) {
                                $show_employee = true;
                            }
                        }

                        $is_hari_libur_override = false;
                        $label_libur_override = '';

                        $is_shift_murni = isset($is_nakes_map[$pgw['id_pegawai']]);

                        if ($is_lepas_jaga) {
                            $is_hari_libur_override = true;
                            $label_libur_override = 'Lepas Jaga';
                        } elseif (isset($libur_nasional[$tgl]) && !$is_shift_murni) {
                            $is_hari_libur_override = true;
                            $label_libur_override = 'Libur: ' . $libur_nasional[$tgl];
                        } elseif (!isset($jadwal_kerja[$active_id_jam][$nama_hari]) && !$is_jadwal_cito) {
                            // Jika bukan CITO dan Sabtu/Minggu tidak ada jadwal, berarti Libur
                            $is_hari_libur_override = true;
                            $label_libur_override = ($day_eng == 'Sun' || $day_eng == 'Sat') ? 'Libur Akhir Pekan' : 'Bukan Hari Kerja';
                        }

                        $data_hari = [
                            'tanggal' => $tgl,
                            'hari' => $nama_hari,
                            'masuk' => '-',
                            'siang' => '-',
                            'pulang' => '-',
                            'status' => '',
                            'bg' => '',
                            'telat' => 0,
                            'cepat' => 0,
                            'status_pulang_label' => '',
                            'nama_shift' => $nama_shift_display
                        ];

                        $is_cuti = isset($map_cuti[$pgw['id_pegawai']][$tgl]);

                        // Cek Izin/Sakit/TL Full Day dari tabel pengajuan_absen
                        $is_izin_full = false;
                        $label_izin = '';
                        if (isset($map_izin_today[$pgw['nip']][$tgl])) {
                            $iz = $map_izin_today[$pgw['nip']][$tgl];
                            if ($iz['slot'] == 'PENUH' || $iz['slot'] == '') {
                                $is_izin_full = true;
                                $label_izin = $iz['kategori'] . ($iz['sub'] ? ' (' . $iz['sub'] . ')' : '');
                            }
                        }

                        $p = isset($data_presensi[$pgw['nip']][$tgl]) ? $data_presensi[$pgw['nip']][$tgl] : null;

                        // ==============================================================
                        // [SUPER FIX]: PRIORITAS TINGKAT TINGGI (Cuti & Izin Mengalahkan Weekend)
                        // ==============================================================
                        if ($is_cuti) {
                            $data_hari['status'] = 'CUTI: ' . strtoupper($map_cuti[$pgw['id_pegawai']][$tgl]);
                            $data_hari['status_pulang_label'] = '-';
                        } elseif ($is_izin_full) {
                            $data_hari['status'] = $label_izin;
                            $data_hari['status_pulang_label'] = '-';
                        } elseif ($is_hari_libur_override) {
                            $data_hari['status'] = $label_libur_override;
                            $data_hari['status_pulang_label'] = '-';
                        } elseif ($p && ($p['kategori'] == 'Hadir' || strpos($p['kategori'], 'Apel') !== false)) {
                            $data_hari['masuk']  = $p['jam_masuk_aktual'] ? date('H:i', strtotime($p['jam_masuk_aktual'])) : '-';
                            $data_hari['siang']  = $p['jam_siang'] ? date('H:i', strtotime($p['jam_siang'])) : '-';
                            $data_hari['pulang'] = $p['jam_pulang_aktual'] ? date('H:i', strtotime($p['jam_pulang_aktual'])) : '-';

                            $status_db = $p['status_masuk'];

                            if (empty($status_db) && !empty($p['jam_masuk_aktual'])) {
                                // BYPASS SANKSI TELAT JIKA DIA CITO
                                if ($is_jadwal_cito) {
                                    $status_db = 'Hadir (Standby)';
                                } else {
                                    $config = $map_jadwal[$active_id_jam][$nama_hari] ?? null;
                                    if ($config) {
                                        $diff = (strtotime($p['jam_masuk_aktual']) - strtotime(str_replace('.', ':', $config['jam_masuk']))) / 60;
                                        if ($diff <= 0) $status_db = 'Sangat Baik / Istimewa';
                                        elseif ($diff <= ($config['batas_baik_menit'] ?? 15)) $status_db = 'Baik';
                                        elseif ($diff <= ($config['batas_cukup_menit'] ?? 45)) $status_db = 'Cukup Baik';
                                        elseif ($diff <= ($config['batas_telat_menit'] ?? 90)) $status_db = 'Terlambat';
                                        elseif ($diff <= ($config['batas_bina_menit'] ?? 150)) $status_db = 'Pembinaan';
                                        else $status_db = 'Teguran';
                                    }
                                }
                            }

                            $data_hari['status'] = $status_db ?: 'Hadir';

                            if (in_array($data_hari['status'], ['Terlambat', 'Pembinaan', 'Teguran'])) {
                                $data_hari['telat'] = intval($p['menit_keterlambatan']);
                            } else {
                                $data_hari['telat'] = 0;
                            }

                            $st_pulang = $p['status_pulang'] ?? '';
                            if (empty($st_pulang) && !empty($p['jam_pulang_aktual'])) {
                                // BYPASS PULANG CEPAT JIKA DIA CITO
                                if ($is_jadwal_cito) {
                                    $st_pulang = 'Selesai';
                                } else {
                                    $config = $map_jadwal[$active_id_jam][$nama_hari] ?? null;
                                    if ($config && !empty($config['jam_pulang'])) {
                                        if ($p['jam_pulang_aktual'] < str_replace('.', ':', $config['jam_pulang'])) {
                                            $st_pulang = 'Cepat Pulang';
                                        } else {
                                            $st_pulang = 'Selesai';
                                        }
                                    } else {
                                        $st_pulang = 'Selesai';
                                    }
                                }
                            }
                            $data_hari['status_pulang_label'] = empty($st_pulang) ? 'Belum Absen' : ucwords(strtolower($st_pulang));
                        } elseif ($p && $p['kategori']) {
                            // Backup jika Izin tercatat secara manual di tabel Presensi
                            $data_hari['status'] = strtoupper($p['kategori']);
                            $data_hari['status_pulang_label'] = '';
                        } else {
                            // JIKA TIDAK ABSEN SAMA SEKALI HARI INI
                            if ($tgl > date('Y-m-d')) {
                                $data_hari['status'] = 'Belum Ada Data';
                            } elseif ($is_jadwal_cito) {
                                $data_hari['status'] = 'Standby (Bebas Alpa)';
                                $data_hari['status_pulang_label'] = '-';
                            } else {
                                $data_hari['status'] = 'Tanpa Keterangan (Alpha)';
                            }
                        }

                        $row['harian'][] = $data_hari;
                    }

                    if (!$show_employee) continue;

                    $rekap_final[] = $row;
                }
            }
        }

        $data = [
            'title'               => 'Rekapitulasi Presensi',
            'list_opd'            => $list_opd,
            'list_gabungan'       => $list_gabungan,
            'rekap'               => $rekap_final,
            'bulan'               => $bulan,
            'tahun'               => $tahun,
            'selected_opd'        => $id_opd,
            'selected_status_asn' => $status_asn,
            'list_jam_kerja'      => $master_jam,
            'selected_shift'      => $filter_shift,
            'is_induk_opd'        => $is_induk_opd,
            'child_opds'          => $child_opds,
            'admin_opd_name'      => $admin_opd_name
        ];

        return view('admin/rekap_presensi', $data);
    }

    private function get_nama_hari($day)
    {
        $days = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];
        return $days[$day] ?? 'Minggu';
    }

    public function manual()
    {
        $db = $this->db;
        $request = \Config\Services::request();
        $session = session();

        $level = $session->get('level');
        $id_opd_session = $session->get('id_opd');

        $id_opd_filter = $request->getGet('id_opd');

        if ($level == 'opd') {
            $id_final_opd = $id_opd_session;
        } else {
            $id_final_opd = $id_opd_filter;
        }

        $list_opd = $db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        $builder = $db->table('pegawai');
        if ($id_final_opd) {
            $builder->where('id_opd', $id_final_opd);
        }

        $builder->whereIn('eselon', ['II.a', 'II.b']);

        if (!$id_final_opd && in_array($level, ['Superadmin', 'kabupaten'])) {
            $pegawai = [];
        } else {
            $pegawai = $builder->orderBy('nama_lengkap', 'ASC')->get()->getResultArray();
        }

        return view('admin/absen_manual', [
            'title'        => 'Input Absen Manual Eselon II',
            'list_opd'     => $list_opd,
            'pegawai'      => $pegawai,
            'selected_opd' => $id_final_opd,
            'level'        => $level
        ]);
    }

    public function simpan_manual()
    {
        $db      = \Config\Database::connect();
        $request = \Config\Services::request();
        $session = session();

        $nip         = trim($request->getPost('nip'));
        $tgl_mulai   = $request->getPost('tgl_mulai');
        $tgl_selesai = $request->getPost('tgl_selesai');
        $kategori    = $request->getPost('kategori');
        $jam_masuk   = $request->getPost('jam_masuk');
        $jam_pulang  = $request->getPost('jam_pulang');
        $keterangan  = $request->getPost('keterangan');

        if ($tgl_mulai > $tgl_selesai) {
            return redirect()->back()->with('error', 'Rentang tanggal tidak valid!');
        }

        $pegawai = $db->table('pegawai')->where('nip', $nip)->get()->getRow();
        if (!$pegawai) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan!');
        }

        $begin = new \DateTime($tgl_mulai);
        $end   = new \DateTime($tgl_selesai);
        $end->modify('+1 day');

        $interval = new \DateInterval('P1D');
        $daterange = new \DatePeriod($begin, $interval, $end);

        $db->transStart();
        $total_diproses = 0;

        foreach ($daterange as $date) {
            $tanggal_loop = $date->format('Y-m-d');
            $day_eng = $date->format('D');

            if ($day_eng == 'Sat' || $day_eng == 'Sun') {
                continue;
            }

            $cek = $db->table('presensi')
                ->where([
                    'id_pegawai' => $pegawai->id_pegawai,
                    'tanggal'    => $tanggal_loop
                ])
                ->get()->getRow();

            $data = [
                'id_pegawai'            => $pegawai->id_pegawai,
                'nip'                   => $nip,
                'tanggal'               => $tanggal_loop,
                'jam_masuk_aktual'      => $jam_masuk,
                'jam_pulang_aktual'     => $jam_pulang,
                'jam_siang'             => ($kategori == 'Hadir') ? '12:05:00' : null,
                'status_masuk'          => 'Sangat Baik / Istimewa',
                'status_pulang'         => 'Selesai',
                'menit_keterlambatan'   => 0,
                'kategori'              => $kategori,
                'keterangan'            => $keterangan . " (Input Manual oleh Admin: " . $session->get('nama') . ")",
                'latitude'              => 'Manual Eselon 2',
                'longitude'             => 'Manual Eselon 2',
                'device_info'           => 'Web Admin',
            ];

            if ($cek) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $db->table('presensi')->where('id_presensi', $cek->id_presensi)->update($data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->table('presensi')->insert($data);
            }

            $total_diproses++;
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat menyimpan data.');
        } else {
            return redirect()->to(base_url('rekap/manual'))->with('success', "Presensi manual untuk <b>{$pegawai->nama_lengkap}</b> berhasil disimpan sebanyak <b>{$total_diproses} hari</b> kerja.");
        }
    }

    public function keterlambatan()
    {
        $db      = \Config\Database::connect();
        $request = \Config\Services::request();
        $session = session();

        $bulan   = $request->getGet('bulan') ?? date('m');
        $tahun   = $request->getGet('tahun') ?? date('Y');
        $id_opd  = $request->getGet('id_opd');
        $status_asn = $request->getGet('status_asn');

        $filter_shift = $request->getGet('id_jam_kerja');

        $id_opd_admin = $session->get('id_opd');

        if ($session->get('level') == 'opd') {
            if (empty($id_opd)) {
                $id_opd = $id_opd_admin;
            }
        }

        $list_opd = $db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        // =========================================================================
        // DETEKSI DINAS INDUK & UPTD ANAK
        // =========================================================================
        $admin_opd_info = $db->table('opd')->where('id_opd', $id_opd_admin)->get()->getRow();
        $admin_opd_name = $admin_opd_info ? strtoupper($admin_opd_info->nama_opd) : '';

        $list_gabungan = [];
        $child_opds = [];
        $is_induk_opd = false;

        $is_admin_induk = (strpos($admin_opd_name, ' - ') === false);

        foreach ($list_opd as $o) {
            if (strpos($o['nama_opd'], ' - ') !== false) {
                $induk = trim(explode(' - ', $o['nama_opd'])[0]);
                $list_gabungan[$induk] = $induk;
            }
            $nama_upper = strtoupper($o['nama_opd']);
            if (strpos($nama_upper, 'RSUD HAJJAH ANDI DEPU') !== false) $list_gabungan['RSUD HAJJAH ANDI DEPU'] = 'RSUD HAJJAH ANDI DEPU';
            if (strpos($nama_upper, 'RSUD WONOMULYO') !== false) $list_gabungan['RSUD WONOMULYO'] = 'RSUD WONOMULYO';
            if (strpos($nama_upper, 'DINAS KESEHATAN') !== false) $list_gabungan['DINAS KESEHATAN'] = 'DINAS KESEHATAN';
            if (strpos($nama_upper, 'DINAS PENDIDIKAN') !== false) $list_gabungan['DINAS PENDIDIKAN'] = 'DINAS PENDIDIKAN';

            if (!in_array($session->get('level'), ['Superadmin', 'kabupaten'])) {
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

                if ($is_child && $o['id_opd'] != $id_opd_admin) {
                    $is_induk_opd = true;
                    $child_opds[] = $o;
                }
            }
        }
        $list_gabungan = array_values($list_gabungan);

        if (!in_array($session->get('level'), ['Superadmin', 'kabupaten'])) {
            if ($is_induk_opd && !empty($id_opd)) {
                if (strpos($id_opd, 'gabungan||') === 0) {
                    $req_gab = str_replace('gabungan||', '', $id_opd);
                    if (strpos($admin_opd_name, strtoupper($req_gab)) === false) $id_opd = $id_opd_admin;
                } else {
                    $valid = false;
                    foreach ($child_opds as $c) {
                        if ($c['id_opd'] == $id_opd) $valid = true;
                    }
                    if (!$valid && $id_opd != $id_opd_admin) $id_opd = $id_opd_admin;
                }
            } else {
                $id_opd = $id_opd_admin;
            }
        }

        $is_nakes_view = false;
        $target_opd_name = '';
        if ($id_opd && strpos($id_opd, 'gabungan||') === 0) {
            $target_opd_name = strtoupper(str_replace('gabungan||', '', $id_opd));
        } elseif ($id_opd && strpos($id_opd, 'rekap_') === false) {
            $cek_opd = $db->table('opd')->where('id_opd', $id_opd)->get()->getRow();
            $target_opd_name = $cek_opd ? strtoupper($cek_opd->nama_opd) : '';
        } else {
            $target_opd_name = $admin_opd_name;
        }

        if (strpos($target_opd_name, 'RSUD') !== false || strpos($target_opd_name, 'PUSKESMAS') !== false || strpos($target_opd_name, 'KESEHATAN') !== false) {
            $is_nakes_view = true;
        }

        $is_gabungan = false;
        $prefix_gabungan = '';
        if ($id_opd && strpos($id_opd, 'gabungan||') === 0) {
            $is_gabungan = true;
            $prefix_gabungan = str_replace('gabungan||', '', $id_opd);
        }

        $master_jam = $db->table('jam_kerja')->get()->getResultArray();
        $map_jam_name = [];
        foreach ($master_jam as $mj) {
            $map_jam_name[$mj['id_jam_kerja']] = $mj['nama_jadwal'] ?? 'Reguler';
        }

        $jadwal_db = $db->table('jam_kerja_detail')->get()->getResultArray();
        $jadwal_kerja = [];
        foreach ($jadwal_db as $j) {
            $jadwal_kerja[$j['id_jam_kerja']][$j['hari']] = true;
        }

        $today = date('Y-m-d');
        $last_day_of_month = date('Y-m-t', strtotime("$tahun-$bulan-01"));
        $endDate = (date('m') == $bulan && date('Y') == $tahun) ? $today : $last_day_of_month;

        $libur_raw = $db->table('hari_libur')->where("MONTH(tanggal)", $bulan)->where("YEAR(tanggal)", $tahun)->get()->getResultArray();
        $list_libur = array_column($libur_raw, 'tanggal');

        $hari_kerja_efektif = 0;
        $start_date = new \DateTime("$tahun-$bulan-01");
        $end_date   = new \DateTime($endDate);
        $end_date->modify('+1 day');
        foreach (new \DatePeriod($start_date, new \DateInterval('P1D'), $end_date) as $date) {
            if ($date->format('N') < 6 && !in_array($date->format('Y-m-d'), $list_libur)) {
                $hari_kerja_efektif++;
            }
        }

        $cuti_raw = $db->table('pengajuan_cuti')
            ->where("LOWER(TRIM(status))", 'disetujui')
            ->where("tgl_mulai <=", $last_day_of_month)
            ->where("COALESCE(NULLIF(tgl_selesai, ''), tgl_mulai) >=", "$tahun-$bulan-01")
            ->get()->getResultArray();

        $map_cuti = [];
        foreach ($cuti_raw as $c) {
            $tgl_selesai_val = empty($c['tgl_selesai']) ? $c['tgl_mulai'] : $c['tgl_selesai'];
            $start_c = new \DateTime(max($c['tgl_mulai'], "$tahun-$bulan-01"));
            $end_c   = new \DateTime(min($tgl_selesai_val, $endDate));
            $end_c->modify('+1 day');

            foreach (new \DatePeriod($start_c, new \DateInterval('P1D'), $end_c) as $dt) {
                $tgl = $dt->format('Y-m-d');
                $map_cuti[$c['pegawai_id']][$tgl] = true;
            }
        }

        $hari_ini_global = date('Y-m-d');

        $builder = $db->table('pegawai p');
        $builder->select("p.id_pegawai, p.nama_lengkap, p.nip, p.level_jabatan, p.eselon, p.jabatan, o.nama_opd, o.id_jam_kerja as id_jam_opd,
            COUNT(CASE WHEN pr.kategori = 'Hadir' AND COALESCE(pr.status_verifikasi, '') != 'Dianulir' THEN 1 END) as total_hadir, 
            
            SUM(CASE WHEN pr.status_masuk IN ('Terlambat', 'Pembinaan', 'Teguran') 
                     AND COALESCE(pr.status_verifikasi, '') != 'Dianulir'
                     AND COALESCE(pr.deskripsi, '') NOT LIKE '%pagi%' 
                     AND COALESCE(pr.deskripsi, '') NOT LIKE '%awal%' 
                     AND COALESCE(pr.status_masuk, '') NOT LIKE '%izin%' 
                     AND COALESCE(pr.status_masuk, '') NOT LIKE '%sakit%' 
                 THEN 1 ELSE 0 END) as frekuensi_telat,

            SUM(CASE WHEN pr.status_masuk IN ('Terlambat', 'Pembinaan', 'Teguran') 
                     AND COALESCE(pr.status_verifikasi, '') != 'Dianulir'
                     AND COALESCE(pr.deskripsi, '') NOT LIKE '%pagi%' 
                     AND COALESCE(pr.deskripsi, '') NOT LIKE '%awal%' 
                     AND COALESCE(pr.status_masuk, '') NOT LIKE '%izin%' 
                     AND COALESCE(pr.status_masuk, '') NOT LIKE '%sakit%' 
                 THEN CASE WHEN COALESCE(pr.menit_keterlambatan, 0) > 45 THEN pr.menit_keterlambatan - 45 ELSE 0 END 
                 ELSE 0 END) as total_menit_telat,
            
            SUM(CASE WHEN pr.status_masuk = 'Sangat Baik / Istimewa' AND COALESCE(pr.status_verifikasi, '') != 'Dianulir' THEN 1 ELSE 0 END) as st_istimewa,
            SUM(CASE WHEN pr.status_masuk = 'Baik' AND COALESCE(pr.status_verifikasi, '') != 'Dianulir' THEN 1 ELSE 0 END) as st_baik,
            SUM(CASE WHEN pr.status_masuk = 'Cukup Baik' AND COALESCE(pr.status_verifikasi, '') != 'Dianulir' THEN 1 ELSE 0 END) as st_cukup,
            SUM(CASE WHEN pr.status_masuk = 'Terlambat' AND COALESCE(pr.status_verifikasi, '') != 'Dianulir' THEN 1 ELSE 0 END) as st_telat,
            SUM(CASE WHEN pr.status_masuk = 'Pembinaan' AND COALESCE(pr.status_verifikasi, '') != 'Dianulir' THEN 1 ELSE 0 END) as st_bina,
            SUM(CASE WHEN pr.status_masuk = 'Teguran' AND COALESCE(pr.status_verifikasi, '') != 'Dianulir' THEN 1 ELSE 0 END) as st_teguran,
            
            SUM(CASE 
                WHEN pr.kategori = 'Hadir' AND COALESCE(pr.status_verifikasi, '') != 'Dianulir' AND pr.jam_pulang_aktual IS NOT NULL AND pr.jam_pulang_aktual != '00:00:00' AND pr.jam_pulang_aktual > '04:00:00' AND pr.jam_pulang_aktual < pr.jam_pulang_seharusnya 
                     AND COALESCE(pr.deskripsi, '') NOT LIKE '%sore%' 
                     AND COALESCE(pr.deskripsi, '') NOT LIKE '%akhir%' 
                THEN 1 ELSE 0 END) as frekuensi_pc,

            SUM(CASE 
                WHEN pr.kategori = 'Hadir' AND COALESCE(pr.status_verifikasi, '') != 'Dianulir' AND pr.tanggal < '{$hari_ini_global}' AND (pr.jam_pulang_aktual IS NULL OR pr.jam_pulang_aktual = '00:00:00' OR pr.jam_pulang_aktual <= '04:00:00') 
                     AND COALESCE(pr.deskripsi, '') NOT LIKE '%sore%' 
                     AND COALESCE(pr.deskripsi, '') NOT LIKE '%akhir%' 
                THEN 1 ELSE 0 END) as frekuensi_lupa_pulang,

            SUM(CASE 
                WHEN pr.kategori = 'Hadir' AND COALESCE(pr.status_verifikasi, '') != 'Dianulir' AND pr.jam_pulang_aktual IS NOT NULL AND pr.jam_pulang_aktual != '00:00:00' AND pr.jam_pulang_aktual > '04:00:00' AND pr.jam_pulang_aktual < pr.jam_pulang_seharusnya 
                     AND COALESCE(pr.deskripsi, '') NOT LIKE '%sore%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%akhir%' 
                THEN TIMESTAMPDIFF(MINUTE, pr.jam_pulang_aktual, pr.jam_pulang_seharusnya) 
                WHEN pr.kategori = 'Hadir' AND COALESCE(pr.status_verifikasi, '') != 'Dianulir' AND pr.tanggal < '{$hari_ini_global}' AND (pr.jam_pulang_aktual IS NULL OR pr.jam_pulang_aktual = '00:00:00' OR pr.jam_pulang_aktual <= '04:00:00') 
                     AND COALESCE(pr.deskripsi, '') NOT LIKE '%sore%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%akhir%' 
                THEN 91 ELSE 0 END) as total_menit_pc,

            SUM(CASE WHEN pr.kategori IN ('Izin', 'Sakit/izin', 'Sakit/Izin') AND COALESCE(pr.deskripsi, '') NOT LIKE '%sebagian%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%setengah%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%pagi%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%siang%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%sore%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%awal%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%akhir%' THEN 1 ELSE 0 END) as total_izin_khusus,
            SUM(CASE WHEN pr.kategori = 'Sakit' AND COALESCE(pr.deskripsi, '') NOT LIKE '%sebagian%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%setengah%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%pagi%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%siang%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%sore%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%awal%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%akhir%' THEN 1 ELSE 0 END) as total_sakit_khusus,
            SUM(CASE WHEN pr.kategori IN ('Tugas Luar', 'Dinas Luar') AND COALESCE(pr.deskripsi, '') NOT LIKE '%sebagian%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%setengah%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%pagi%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%siang%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%sore%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%awal%' AND COALESCE(pr.deskripsi, '') NOT LIKE '%akhir%' THEN 1 ELSE 0 END) as total_dl,
            SUM(CASE WHEN pr.kategori LIKE '%Cuti%' THEN 1 ELSE 0 END) as total_cuti_presensi", false);

        $builder->join('presensi pr', 'p.nip = pr.nip AND MONTH(pr.tanggal) = ' . $db->escape($bulan) . ' AND YEAR(pr.tanggal) = ' . $db->escape($tahun), 'left');
        $builder->join('opd o', 'p.id_opd = o.id_opd', 'left');

        if ($is_gabungan) $builder->like('o.nama_opd', $prefix_gabungan, 'both');
        elseif ($id_opd == 'rekap_eselon_2') $builder->whereIn('p.eselon', ['II.a', 'II.b']);
        elseif ($id_opd == 'rekap_eselon_3') $builder->whereIn('p.eselon', ['III.a', 'III.b']);
        elseif ($id_opd == 'rekap_eselon_4') $builder->whereIn('p.eselon', ['IV.a', 'IV.b']);
        elseif ($id_opd == 'rekap_eselon_gabungan') $builder->whereIn('p.eselon', ['II.a', 'II.b', 'III.a', 'III.b', 'IV.a', 'IV.b']);
        else $builder->where('p.id_opd', $id_opd);

        if (!empty($status_asn)) $builder->where('p.status_asn', $status_asn);

        $builder->groupBy('p.nip');
        if ($is_gabungan) $builder->orderBy('o.nama_opd', 'ASC');

        $builder->orderBy("CASE 
            WHEN TRIM(p.eselon) = 'II.a' THEN 1 WHEN TRIM(p.eselon) = 'II.b' THEN 2
            WHEN TRIM(p.eselon) = 'III.a' THEN 3 WHEN TRIM(p.eselon) = 'III.b' THEN 4
            WHEN TRIM(p.eselon) = 'IV.a' THEN 5 WHEN TRIM(p.eselon) = 'IV.b' THEN 6
            WHEN p.level_jabatan LIKE '%Fungsional%' THEN 7
            WHEN p.level_jabatan LIKE '%Staf%' OR TRIM(p.eselon) = 'Non Eselon' THEN 8
            ELSE 9 END", "ASC", false);

        $pegawai_raw = $builder->get()->getResultArray();

        $ids_pegawai_raw = array_column($pegawai_raw, 'id_pegawai');
        $nips_pegawai_raw = array_column($pegawai_raw, 'nip');

        $map_cito = [];
        $map_plotting = [];
        $is_nakes_map = [];
        $presensi_dates = [];
        $map_izin_full = [];

        if (!empty($ids_pegawai_raw)) {
            $cito_raw = $db->table('presensi_darurat')
                ->whereIn('id_pegawai', $ids_pegawai_raw)
                ->where('status_approval', 'Disetujui')
                ->where('MONTH(tanggal)', $bulan)
                ->where('YEAR(tanggal)', $tahun)
                ->get()->getResultArray();

            foreach ($cito_raw as $c) {
                $id_peg = $c['id_pegawai'];
                if (!isset($map_cito[$id_peg])) $map_cito[$id_peg] = ['kali' => 0, 'menit' => 0];
                $map_cito[$id_peg]['kali'] += 1;
                if (!empty($c['jam_selesai']) && $c['jam_selesai'] != '00:00:00') {
                    $start = strtotime($c['jam_mulai']);
                    $end = strtotime($c['jam_selesai']);
                    if ($end > $start) $map_cito[$id_peg]['menit'] += floor(($end - $start) / 60);
                }
            }

            $builder_plot = $db->table('plotting_shift')->whereIn('id_pegawai', $ids_pegawai_raw);
            if ($db->fieldExists('tanggal_mulai', 'plotting_shift')) {
                $builder_plot->where("tanggal_mulai <=", "$tahun-$bulan-31")->where("tanggal_selesai >=", "$tahun-$bulan-01");
            } else {
                $builder_plot->where("MONTH(tanggal)", $bulan)->where("YEAR(tanggal)", $tahun);
            }
            $plotting_db = $builder_plot->get()->getResultArray();

            foreach ($plotting_db as $pl) {
                $nama_jadwal_master = strtolower($map_jam_name[$pl['id_jam_kerja']] ?? '');
                if (strpos($nama_jadwal_master, 'ramadhan') === false && strpos($nama_jadwal_master, 'puasa') === false) {
                    $is_nakes_map[$pl['id_pegawai']] = true;
                }

                if (isset($pl['tanggal_mulai'])) {
                    $start = new \DateTime($pl['tanggal_mulai']);
                    $end = new \DateTime($pl['tanggal_selesai']);
                    $end->modify('+1 day');
                    $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
                    foreach ($period as $date) {
                        $map_plotting[$pl['id_pegawai']][$date->format('Y-m-d')] = $pl['id_jam_kerja'];
                    }
                } else {
                    $map_plotting[$pl['id_pegawai']][$pl['tanggal']] = $pl['id_jam_kerja'];
                }
            }

            $izin_raw = $db->table('pengajuan_absen')
                ->whereIn('nip', $nips_pegawai_raw)
                ->where("LOWER(TRIM(status_approval))", 'disetujui')
                ->where("tanggal_mulai <= LAST_DAY('$tahun-$bulan-01') AND COALESCE(NULLIF(tanggal_selesai, ''), tanggal_mulai) >= '$tahun-$bulan-01'")
                ->get()->getResultArray();

            foreach ($izin_raw as $iz) {
                $start_i = new \DateTime(max($iz['tanggal_mulai'], "$tahun-$bulan-01"));
                $tgl_selesai_val = empty($iz['tanggal_selesai']) ? $iz['tanggal_mulai'] : $iz['tanggal_selesai'];
                $end_i   = new \DateTime(min($tgl_selesai_val, $last_day_of_month));
                $end_i->modify('+1 day');

                foreach (new \DatePeriod($start_i, new \DateInterval('P1D'), $end_i) as $dt) {
                    if (empty($iz['slot_waktu']) || strtoupper($iz['slot_waktu']) == 'PENUH') {
                        $map_izin_full[$iz['nip']][$dt->format('Y-m-d')] = strtoupper($iz['kategori']);
                    }
                }
            }

            $pr_raw = $db->table('presensi')->select('nip, tanggal')->whereIn('nip', $nips_pegawai_raw)->where('MONTH(tanggal)', $bulan)->where('YEAR(tanggal)', $tahun)->get()->getResultArray();
            foreach ($pr_raw as $pr) {
                $presensi_dates[$pr['nip']][$pr['tanggal']] = true;
            }
        }

        $hari_indo_map = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];

        $list_tanggal_kalkulasi = [];
        $jumlah_hari_bulan_ini = date('t', mktime(0, 0, 0, $bulan, 1, $tahun));
        for ($d = 1; $d <= $jumlah_hari_bulan_ini; $d++) {
            $tgl_loop = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
            if ($tgl_loop <= $hari_ini_global) $list_tanggal_kalkulasi[] = $tgl_loop;
        }

        $data_final = [];
        foreach ($pegawai_raw as $row) {
            if (!empty($filter_shift)) {
                $id_jam_opd = $row['id_jam_opd'] ?? 1;
                if ($id_jam_opd != $filter_shift) continue;
            }

            $total_alpa_sebenarnya = 0;
            $total_standby_hari = 0;
            $total_cuti_kalkulasi = 0;
            $real_izin = 0;
            $real_sakit = 0;
            $real_dl = 0;
            $id_jam_opd = $row['id_jam_opd'] ?? 1;

            foreach ($list_tanggal_kalkulasi as $tgl) {
                $day_eng = date('D', strtotime($tgl));
                $nama_hari = $hari_indo_map[$day_eng];

                $ada_jadwal = isset($map_plotting[$row['id_pegawai']][$tgl]);
                $is_nakes = isset($is_nakes_map[$row['id_pegawai']]);
                $is_lepas_jaga = ($is_nakes && !$ada_jadwal);

                $active_id_jam = $ada_jadwal ? $map_plotting[$row['id_pegawai']][$tgl] : $id_jam_opd;
                $nama_jk = strtolower($map_jam_name[$active_id_jam] ?? '');
                $is_cito = (strpos($nama_jk, 'cito') !== false || strpos($nama_jk, 'standby') !== false);

                $is_libur = in_array($tgl, $list_libur);
                $is_hari_kerja = isset($jadwal_kerja[$active_id_jam][$nama_hari]);

                $is_cuti = isset($map_cuti[$row['id_pegawai']][$tgl]);
                $is_izin_full = isset($map_izin_full[$row['nip']][$tgl]);
                $kategori_izin = $is_izin_full ? $map_izin_full[$row['nip']][$tgl] : '';

                $sudah_absen = isset($presensi_dates[$row['nip']][$tgl]);

                if ($is_cuti) $total_cuti_kalkulasi++;
                if ($is_izin_full) {
                    if (strpos($kategori_izin, 'IZIN') !== false) $real_izin++;
                    elseif (strpos($kategori_izin, 'SAKIT') !== false) $real_sakit++;
                    elseif (strpos($kategori_izin, 'LUAR') !== false) $real_dl++;
                }

                $is_hari_libur_override = false;
                if ($is_lepas_jaga) $is_hari_libur_override = true;
                elseif ($is_libur && !$is_nakes) $is_hari_libur_override = true;
                elseif (!$is_hari_kerja && !$is_cito) $is_hari_libur_override = true;

                if (!$sudah_absen && !$is_cuti && !$is_izin_full && !$is_hari_libur_override) {
                    if ($is_cito) $total_standby_hari++;
                    else $total_alpa_sebenarnya++;
                }
            }

            $total_izin  = max(intval($row['total_izin_khusus']), $real_izin);
            $total_sakit = max(intval($row['total_sakit_khusus']), $real_sakit);
            $total_dl    = max(intval($row['total_dl']), $real_dl);
            $final_cuti  = max($total_cuti_kalkulasi, intval($row['total_cuti_presensi']));

            // [PERBAIKAN ERROR]: Menyisipkan total_izin_sakit agar dikenali oleh View HTML
            $row['total_izin_khusus']  = $total_izin;
            $row['total_sakit_khusus'] = $total_sakit;
            $row['total_izin_sakit']   = $total_izin + $total_sakit;
            $row['total_dl']           = $total_dl;
            $row['total_cuti']         = $final_cuti;
            $row['total_alpa']         = $total_alpa_sebenarnya;

            $frekuensi_telat = intval($row['frekuensi_telat']);
            $alpa = $row['total_alpa'];
            $potongan_sakit = max(0, $total_sakit - 3);
            $potongan_tl = max(0, $total_dl - 3);

            $kehadiran_remun = $hari_kerja_efektif - $alpa - $frekuensi_telat - $total_izin - $final_cuti - $potongan_sakit - $potongan_tl;
            $row['kehadiran_remun'] = max(0, $kehadiran_remun);

            $row['potongan_sakit'] = $potongan_sakit;
            $row['potongan_tl']    = $potongan_tl;

            $row['cito_kali'] = $map_cito[$row['id_pegawai']]['kali'] ?? 0;
            $row['cito_menit'] = $map_cito[$row['id_pegawai']]['menit'] ?? 0;

            $data_final[] = $row;
        }

        $data = [
            'title'               => 'Rekap Disiplin Kerja',
            'pegawai'             => $data_final,
            'list_opd'            => $list_opd,
            'list_gabungan'       => $list_gabungan,
            'bulan'               => $bulan,
            'tahun'               => $tahun,
            'selected_opd'        => $id_opd,
            'selected_status_asn' => $status_asn,
            'hari_kerja'          => $hari_kerja_efektif,
            'list_jam_kerja'      => $master_jam,
            'selected_shift'      => $filter_shift,
            'is_induk_opd'        => $is_induk_opd ?? false,
            'child_opds'          => $child_opds ?? [],
            'admin_opd_name'      => $admin_opd_name ?? '',
            'is_nakes_view'       => $is_nakes_view
        ];

        return view('rekap/keterlambatan', $data);
    }

    public function tpp()
    {
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '-1');

        $db = \Config\Database::connect();
        $level = session()->get('level');
        $id_opd_admin = session()->get('id_opd');
        $request = \Config\Services::request();

        $bulan = $request->getGet('bulan') ?? date('m');
        $tahun = $request->getGet('tahun') ?? date('Y');
        $status_asn = $request->getGet('status_asn');

        $list_opd = $db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        // =========================================================================
        // [PERBAIKAN ENTERPRISE]: DETEKSI DINAS INDUK & UPTD ANAK
        // =========================================================================
        $admin_opd_info = $db->table('opd')->where('id_opd', $id_opd_admin)->get()->getRow();
        $admin_opd_name = $admin_opd_info ? strtoupper($admin_opd_info->nama_opd) : '';

        $list_gabungan = [];
        $child_opds = [];
        $is_induk_opd = false;

        foreach ($list_opd as $o) {
            if (strpos($o['nama_opd'], ' - ') !== false) {
                $induk = trim(explode(' - ', $o['nama_opd'])[0]);
                $list_gabungan[$induk] = $induk;
            }
            $nama_upper = strtoupper($o['nama_opd']);
            if (strpos($nama_upper, 'RSUD HAJJAH ANDI DEPU') !== false) $list_gabungan['RSUD HAJJAH ANDI DEPU'] = 'RSUD HAJJAH ANDI DEPU';
            if (strpos($nama_upper, 'RSUD WONOMULYO') !== false) $list_gabungan['RSUD WONOMULYO'] = 'RSUD WONOMULYO';
            if (strpos($nama_upper, 'DINAS KESEHATAN') !== false) $list_gabungan['DINAS KESEHATAN'] = 'DINAS KESEHATAN';
            if (strpos($nama_upper, 'DINAS PENDIDIKAN') !== false) $list_gabungan['DINAS PENDIDIKAN'] = 'DINAS PENDIDIKAN';

            if (!in_array($level, ['Superadmin', 'kabupaten'])) {
                $is_child = false;

                if (strpos($admin_opd_name, 'DINAS KESEHATAN') !== false && strpos($nama_upper, 'DINAS KESEHATAN') !== false) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'DINAS PENDIDIKAN') !== false && (strpos($nama_upper, 'SDN ') !== false || strpos($nama_upper, 'SMPN ') !== false || strpos($nama_upper, 'TK ') !== false)) {
                    $is_child = true;
                }

                if ($is_child && $o['id_opd'] != $id_opd_admin) {
                    $is_induk_opd = true;
                    $child_opds[] = $o;
                }
            }
        }
        $list_gabungan = array_values($list_gabungan);

        $id_opd = $request->getGet('id_opd');

        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            if ($is_induk_opd && !empty($id_opd)) {
                if (strpos($id_opd, 'gabungan||') === 0) {
                    $req_gab = str_replace('gabungan||', '', $id_opd);
                    if (strpos($admin_opd_name, strtoupper($req_gab)) === false) $id_opd = $id_opd_admin;
                } else {
                    $valid = false;
                    foreach ($child_opds as $c) {
                        if ($c['id_opd'] == $id_opd) $valid = true;
                    }
                    if (!$valid && $id_opd != $id_opd_admin) $id_opd = $id_opd_admin;
                }
            } else {
                $id_opd = $id_opd_admin;
            }
        }
        // =========================================================================

        $is_gabungan = false;
        $prefix_gabungan = '';
        if ($id_opd && strpos($id_opd, 'gabungan||') === 0) {
            $is_gabungan = true;
            $prefix_gabungan = str_replace('gabungan||', '', $id_opd);
        }

        $data_pegawai = [];
        $opd_info = $db->table('opd')->where('id_opd', $id_opd)->get()->getRow();
        if ($is_gabungan) $nama_opd = "GABUNGAN " . strtoupper($prefix_gabungan);
        elseif ($id_opd == 'rekap_eselon_2') $nama_opd = "Rekap Eselon II";
        elseif ($id_opd == 'rekap_eselon_3') $nama_opd = "Rekap Eselon III";
        elseif ($id_opd == 'rekap_eselon_4') $nama_opd = "Rekap Eselon IV";
        elseif ($id_opd == 'rekap_eselon_gabungan') $nama_opd = "Rekap Gabungan Eselon 2, 3, dan 4";
        else $nama_opd = $opd_info->nama_opd ?? "Semua Instansi";

        if ($id_opd) {
            $hari_libur = $db->table('hari_libur')->where('MONTH(tanggal)', $bulan)->where('YEAR(tanggal)', $tahun)->get()->getResultArray();
            $array_tgl_libur = array_column($hari_libur, 'tanggal');

            $jam_kerja_raw = $db->table('jam_kerja_detail')->get()->getResultArray();
            $map_hari_kerja = [];
            $map_jadwal_full = [];
            foreach ($jam_kerja_raw as $jk) {
                $map_hari_kerja[$jk['id_jam_kerja']][] = strtolower($jk['hari']);
                $map_jadwal_full[$jk['id_jam_kerja']][strtolower($jk['hari'])] = $jk;
            }

            $tgl_awal_bulan = sprintf('%04d-%02d-01', $tahun, $bulan);
            $tgl_akhir_bulan = date('Y-m-t', strtotime($tgl_awal_bulan));
            $hari_ini_global = date('Y-m-d');
            $endDate = (date('m') == $bulan && date('Y') == $tahun) ? $hari_ini_global : $tgl_akhir_bulan;

            $cuti_raw = $db->table('pengajuan_cuti')
                ->where("LOWER(TRIM(status))", 'disetujui')
                ->where("tgl_mulai <=", $tgl_akhir_bulan)
                ->where("COALESCE(NULLIF(tgl_selesai, ''), tgl_mulai) >=", $tgl_awal_bulan)
                ->get()->getResultArray();

            $map_cuti = [];
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

            $builder_pegawai = $db->table('pegawai p')
                ->select('p.*, o.nama_opd, o.id_jam_kerja as id_jam_opd')
                ->join('opd o', 'p.id_opd = o.id_opd', 'left');

            if ($is_gabungan) $builder_pegawai->like('o.nama_opd', $prefix_gabungan, 'both');
            elseif ($id_opd == 'rekap_eselon_2') $builder_pegawai->whereIn('p.eselon', ['II.a', 'II.b']);
            elseif ($id_opd == 'rekap_eselon_3') $builder_pegawai->whereIn('p.eselon', ['III.a', 'III.b']);
            elseif ($id_opd == 'rekap_eselon_4') $builder_pegawai->whereIn('p.eselon', ['IV.a', 'IV.b']);
            elseif ($id_opd == 'rekap_eselon_gabungan') $builder_pegawai->whereIn('p.eselon', ['II.a', 'II.b', 'III.a', 'III.b', 'IV.a', 'IV.b']);
            else $builder_pegawai->where('p.id_opd', $id_opd);

            if (!empty($status_asn)) {
                $builder_pegawai->where('p.status_asn', $status_asn);
            }

            if ($is_gabungan) {
                $builder_pegawai->orderBy('o.nama_opd', 'ASC');
            }

            $pegawai = $builder_pegawai
                ->orderBy("CASE 
                    WHEN TRIM(p.eselon) = 'II.a' THEN 1 WHEN TRIM(p.eselon) = 'II.b' THEN 2
                    WHEN TRIM(p.eselon) = 'III.a' THEN 3 WHEN TRIM(p.eselon) = 'III.b' THEN 4
                    WHEN TRIM(p.eselon) = 'IV.a' THEN 5 WHEN TRIM(p.eselon) = 'IV.b' THEN 6
                    WHEN p.level_jabatan LIKE '%Fungsional%' THEN 7
                    WHEN p.level_jabatan LIKE '%Staf%' OR TRIM(p.eselon) = 'Non Eselon' THEN 8
                    ELSE 9 END", "ASC", false)
                ->orderBy('p.nama_lengkap', 'ASC')
                ->get()->getResultArray();

            $jml_hari_bulan_ini = date('t', strtotime("$tahun-$bulan-01"));

            $ids_pegawai = array_column($pegawai, 'id_pegawai');
            $nips_pegawai = array_column($pegawai, 'nip');

            $is_nakes_map = [];
            $map_plotting = [];
            $map_izin_full = [];
            $map_izin_partial = [];
            $map_ta = [];

            if (!empty($ids_pegawai)) {
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
                    $map_ta[$ua['id_pegawai']] = $ua['ta_count'];
                }
                $builder_plot = $db->table('plotting_shift')->whereIn('id_pegawai', $ids_pegawai);
                if ($db->fieldExists('tanggal_mulai', 'plotting_shift')) {
                    $builder_plot->where("tanggal_mulai <=", "$tahun-$bulan-31")
                        ->where("tanggal_selesai >=", "$tahun-$bulan-01");
                } else {
                    $builder_plot->where("MONTH(tanggal)", $bulan)->where("YEAR(tanggal)", $tahun);
                }
                $plotting_db = $builder_plot->get()->getResultArray();

                $master_jam = $db->table('jam_kerja')->get()->getResultArray();
                $map_jam_name = [];
                foreach ($master_jam as $mj) $map_jam_name[$mj['id_jam_kerja']] = $mj['nama_jadwal'] ?? '';

                foreach ($plotting_db as $pl) {
                    $nama_jadwal_master = strtolower($map_jam_name[$pl['id_jam_kerja']] ?? '');
                    if (strpos($nama_jadwal_master, 'ramadhan') === false && strpos($nama_jadwal_master, 'puasa') === false) {
                        $is_nakes_map[$pl['id_pegawai']] = true;
                    }

                    if (isset($pl['tanggal_mulai'])) {
                        $start = new \DateTime($pl['tanggal_mulai']);
                        $end = new \DateTime($pl['tanggal_selesai']);
                        $end->modify('+1 day');
                        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
                        foreach ($period as $date) {
                            $map_plotting[$pl['id_pegawai']][$date->format('Y-m-d')] = $pl['id_jam_kerja'];
                        }
                    } else {
                        $map_plotting[$pl['id_pegawai']][$pl['tanggal']] = $pl['id_jam_kerja'];
                    }
                }

                // ==============================================================
                // [SUPER FIX]: TARIK DATA IZIN/SAKIT/TL FULL & PARSIAL
                // ==============================================================
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
                            $map_izin_full[$iz['nip']][$dt->format('Y-m-d')] = strtoupper($iz['kategori']);
                        } else {
                            $map_izin_partial[$iz['nip']][$dt->format('Y-m-d')][] = $slot; // Simpan status PAGI/SIANG/SORE
                        }
                    }
                }
            }

            $all_presensi = [];
            if (!empty($nips_pegawai)) {
                $logs_raw = $db->table('presensi')
                    ->whereIn('nip', $nips_pegawai)
                    ->where('MONTH(tanggal)', $bulan)
                    ->where('YEAR(tanggal)', $tahun)
                    ->get()->getResultArray();
                foreach ($logs_raw as $l) {
                    $all_presensi[$l['nip']][$l['tanggal']] = $l;
                }
            }

            foreach ($pegawai as $pgw) {
                $log_map = $all_presensi[$pgw['nip']] ?? [];

                $res = [
                    'nama' => $pgw['nama_lengkap'],
                    'nip' => $pgw['nip'],
                    'jab' => $pgw['jabatan'],
                    'pangkat' => $pgw['pangkat'] ?? '-',
                    'golongan' => $pgw['golongan'] ?? '-',
                    'hari_kerja' => 0,
                    'p1' => 0,
                    'p2' => 0,
                    'p3' => 0,
                    'p4' => 0,
                    'pc1' => 0,
                    'pc2' => 0,
                    'pc3' => 0,
                    'pc4' => 0,
                    'c' => 0,
                    'i' => 0,
                    'ish' => 0,
                    'a' => 0,
                    's' => 0,
                    'dl' => 0,
                    'tdk' => 0,
                    'ta' => $map_ta[$pgw['id_pegawai']] ?? 0,
                    'total_persen' => 0
                ];

                for ($d = 1; $d <= $jml_hari_bulan_ini; $d++) {
                    $tgl_loop = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                    if ($tgl_loop > $hari_ini_global) break;

                    $nama_hari_loop = strtolower($this->get_nama_hari(date('D', strtotime($tgl_loop))));
                    $is_libur_nasional = in_array($tgl_loop, $array_tgl_libur);
                    $is_shift_murni = isset($is_nakes_map[$pgw['id_pegawai']]);

                    $ada_jadwal = isset($map_plotting[$pgw['id_pegawai']][$tgl_loop]);
                    $is_lepas_jaga = ($is_shift_murni && !$ada_jadwal);

                    $id_jam_aktif = $pgw['id_jam_opd'] ?? 1;
                    if (isset($log_map[$tgl_loop]) && !empty($log_map[$tgl_loop]['id_jam_kerja_snapshot'])) {
                        $id_jam_aktif = $log_map[$tgl_loop]['id_jam_kerja_snapshot'];
                    } elseif ($ada_jadwal) {
                        $id_jam_aktif = $map_plotting[$pgw['id_pegawai']][$tgl_loop];
                    }

                    $nama_jk_aktif = strtolower($map_jam_name[$id_jam_aktif] ?? '');
                    $is_cito = (strpos($nama_jk_aktif, 'cito') !== false || strpos($nama_jk_aktif, 'standby') !== false);

                    $hari_kerja_aktif = $map_hari_kerja[$id_jam_aktif] ?? ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
                    $is_hari_kerja = in_array($nama_hari_loop, $hari_kerja_aktif);

                    // ==============================================================
                    // BYPASS PENALTI JIKA ADA IZIN PARSIAL
                    // ==============================================================
                    $partial_slots = $map_izin_partial[$pgw['nip']][$tgl_loop] ?? [];
                    $is_izin_pagi = in_array('PAGI', $partial_slots) || in_array('SESI AWAL (ABSEN MASUK)', $partial_slots);
                    $is_izin_sore = in_array('SORE', $partial_slots) || in_array('SESI AKHIR (ABSEN PULANG)', $partial_slots);

                    if ($is_lepas_jaga) continue;
                    if ($is_libur_nasional && !$is_shift_murni) continue;
                    if (!$is_hari_kerja && !$is_cito) continue;

                    if (isset($map_cuti[$pgw['id_pegawai']]) && in_array($tgl_loop, $map_cuti[$pgw['id_pegawai']])) {
                        $res['c']++;
                        continue;
                    }

                    $res['hari_kerja']++;

                    if (isset($map_izin_full[$pgw['nip']][$tgl_loop])) {
                        $kategori_izin = $map_izin_full[$pgw['nip']][$tgl_loop];
                        if (strpos($kategori_izin, 'IZIN') !== false) $res['i']++;
                        elseif (strpos($kategori_izin, 'SAKIT') !== false) $res['s']++;
                        elseif (strpos($kategori_izin, 'LUAR') !== false) $res['dl']++;
                        continue;
                    }

                    if (isset($log_map[$tgl_loop])) {
                        $l = $log_map[$tgl_loop];
                        $is_full_day = false;
                        $kategori = ucwords(strtolower(trim($l['kategori'] ?? '')));
                        $deskripsi = strtolower($l['deskripsi'] ?? '');
                        $status_masuk = strtolower($l['status_masuk'] ?? '');
                        $status_pulang = strtolower($l['status_pulang'] ?? '');

                        // FIX: Jika status_verifikasi adalah Dianulir atau Ditangguhkan, maka hitung sebagai Alpha
                        if (($l['status_verifikasi'] ?? '') === 'Dianulir' || ($l['status_verifikasi'] ?? '') === 'Ditangguhkan') {
                            $kategori = 'Alpa';
                        }

                        // DETEKSI PARSIAL DARI DATABASE PRESENSI (Fallback)
                        $is_partial_db = false;
                        if (strpos($deskripsi, 'sebagian') !== false || strpos($deskripsi, 'setengah') !== false || strpos($deskripsi, 'pagi') !== false || strpos($deskripsi, 'siang') !== false || strpos($deskripsi, 'sore') !== false || strpos($status_masuk, 'izin') !== false || strpos($status_masuk, 'sakit') !== false || strpos($status_masuk, 'luar') !== false || strpos($status_pulang, 'izin') !== false || !empty($partial_slots)) {
                            $is_partial_db = true;
                            if (strpos($deskripsi, 'pagi') !== false || strpos($status_masuk, 'izin') !== false || strpos($status_masuk, 'sakit') !== false || strpos($status_masuk, 'luar') !== false) $is_izin_pagi = true;
                            if (strpos($deskripsi, 'sore') !== false || strpos($status_pulang, 'izin') !== false || strpos($status_pulang, 'luar') !== false) $is_izin_sore = true;
                        }

                        if (!$is_partial_db) {
                            if ($kategori == 'Cuti') {
                                $res['c']++;
                                $is_full_day = true;
                            } elseif (in_array($kategori, ['Izin', 'Sakit/izin', 'Sakit/Izin'])) {
                                $res['i']++;
                                $is_full_day = true;
                            } elseif ($kategori == 'Sakit') {
                                $res['s']++;
                                $is_full_day = true;
                            } elseif (in_array($kategori, ['Tugas Luar', 'Dinas Luar'])) {
                                $res['dl']++;
                                $is_full_day = true;
                            } elseif ($kategori == 'Alpa' || $kategori == 'Alpha') {
                                $res['a']++;
                                $is_full_day = true;
                            }
                        } else {
                            if (strpos($deskripsi, 'setengah') !== false || $kategori == 'Izin Setengah Hari') {
                                // Izin Setengah Hari dikategorikan sebagai Hadir
                                $res['ish']++;
                                // $is_full_day dibiarkan false agar tetap dihitung sesi yang tidak diizinkan
                            }
                        }

                        if (!$is_full_day) {
                            if ($nama_hari_loop == 'senin' && (empty($l['jam_masuk_aktual']) || $l['jam_masuk_aktual'] == '00:00:00')) {
                                $res['tdk']++;
                            }

                            if (!$is_izin_pagi) {
                                if (!empty($l['jam_masuk_aktual']) && $l['jam_masuk_aktual'] > '04:00:00') {
                                    if (in_array($l['status_masuk'], ['Terlambat', 'Pembinaan', 'Teguran']) && !$is_cito) {
                                        $config_jam = $map_jadwal_full[$id_jam_aktif][$nama_hari_loop] ?? null;
                                        $batas_toleransi = $config_jam ? ($config_jam['batas_cukup_menit'] ?? 45) : 45;
                                        $m = max(0, $l['menit_keterlambatan'] - $batas_toleransi);

                                        if ($m > 0) {
                                            if ($m <= 30) $res['p1']++;
                                            elseif ($m <= 60) $res['p2']++;
                                            elseif ($m <= 90) $res['p3']++;
                                            else $res['p4']++;
                                        }
                                    }
                                } else {
                                    if ($tgl_loop < $hari_ini_global && !$is_cito) $res['p4']++;
                                }
                            }

                            // Hitung Pulang Cepat (Abaikan Jika Izin Sore)
                            if (!$is_izin_sore) {
                                if (empty($l['jam_pulang_aktual']) || $l['jam_pulang_aktual'] <= '04:00:00') {
                                    if ($tgl_loop < $hari_ini_global && !$is_cito) $res['pc4']++;
                                } else {
                                    if (!empty($l['jam_pulang_seharusnya']) && $l['jam_pulang_aktual'] < $l['jam_pulang_seharusnya'] && !$is_cito) {
                                        $selisih = strtotime($l['jam_pulang_seharusnya']) - strtotime($l['jam_pulang_aktual']);
                                        $menit_pc = floor($selisih / 60);

                                        if ($menit_pc >= 1 && $menit_pc <= 30) $res['pc1']++;
                                        elseif ($menit_pc >= 31 && $menit_pc <= 60) $res['pc2']++;
                                        elseif ($menit_pc >= 61 && $menit_pc <= 90) $res['pc3']++;
                                        elseif ($menit_pc > 90) $res['pc4']++;
                                    }
                                }
                            }
                        }
                    } else {
                        // Jika sama sekali tidak ada log absensi
                        if (!$is_cito && !isset($map_izin_full[$pgw['nip']][$tgl_loop])) {
                            $res['a']++;
                            if ($nama_hari_loop == 'senin') {
                                $res['tdk']++;
                            }
                        }
                    }
                }

                $res['total_persen'] = ($res['p1'] * 0.5) + ($res['p2'] * 1) + ($res['p3'] * 1.25) + ($res['p4'] * 1.5) +
                    ($res['pc1'] * 0.5) + ($res['pc2'] * 1) + ($res['pc3'] * 1.25) + ($res['pc4'] * 1.5) +
                    ($res['i'] * 0) + ($res['a'] * 3) + ($res['tdk'] * 0.5) + ($res['ta'] * 2); // ish (Izin Setengah Hari) tidak lagi dikenakan potongan karena dikategorikan hadir

                $data_pegawai[] = $res;
            }

            return view('admin/rekap_tpp', [
                'pegawai'             => $data_pegawai,
                'list_opd'            => $list_opd,
                'list_gabungan'       => $list_gabungan,
                'bulan'               => $bulan,
                'tahun'               => $tahun,
                'selected_opd'        => $id_opd,
                'selected_status_asn' => $status_asn,
                'nama_opd'            => $nama_opd,
                'is_induk_opd'        => $is_induk_opd,
                'child_opds'          => $child_opds,
                'admin_opd_name'      => $admin_opd_name
            ]);
        }

        return view('admin/rekap_tpp', [
            'pegawai'             => [],
            'list_opd'            => $list_opd,
            'list_gabungan'       => $list_gabungan,
            'bulan'               => $bulan,
            'tahun'               => $tahun,
            'selected_opd'        => $id_opd,
            'selected_status_asn' => $status_asn,
            'nama_opd'            => $nama_opd,
            'is_induk_opd'        => $is_induk_opd ?? false,
            'child_opds'          => $child_opds ?? [],
            'admin_opd_name'      => $admin_opd_name ?? ''
        ]);
    }

    public function excel()
    {
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '-1');

        $db = \Config\Database::connect();
        $level = session()->get('level');
        $id_opd_admin = session()->get('id_opd');
        $request = \Config\Services::request();

        $bulan = $request->getGet('bulan') ?? date('m');
        $tahun = $request->getGet('tahun') ?? date('Y');
        $id_opd = $request->getGet('id_opd');
        $status_asn = $request->getGet('status_asn');

        $list_opd = $db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        // =========================================================================
        // [PERBAIKAN ENTERPRISE]: DETEKSI DINAS INDUK & UPTD ANAK
        // =========================================================================
        $admin_opd_info = $db->table('opd')->where('id_opd', $id_opd_admin)->get()->getRow();
        $admin_opd_name = $admin_opd_info ? strtoupper($admin_opd_info->nama_opd) : '';

        $list_gabungan = [];
        $child_opds = [];
        $is_induk_opd = false;

        foreach ($list_opd as $o) {
            if (strpos($o['nama_opd'], ' - ') !== false) {
                $induk = trim(explode(' - ', $o['nama_opd'])[0]);
                $list_gabungan[$induk] = $induk;
            }
            $nama_upper = strtoupper($o['nama_opd']);
            if (strpos($nama_upper, 'RSUD HAJJAH ANDI DEPU') !== false) $list_gabungan['RSUD HAJJAH ANDI DEPU'] = 'RSUD HAJJAH ANDI DEPU';
            if (strpos($nama_upper, 'RSUD WONOMULYO') !== false) $list_gabungan['RSUD WONOMULYO'] = 'RSUD WONOMULYO';
            if (strpos($nama_upper, 'DINAS KESEHATAN') !== false) $list_gabungan['DINAS KESEHATAN'] = 'DINAS KESEHATAN';
            if (strpos($nama_upper, 'DINAS PENDIDIKAN') !== false) $list_gabungan['DINAS PENDIDIKAN'] = 'DINAS PENDIDIKAN';

            if (!in_array($level, ['Superadmin', 'kabupaten'])) {
                $is_child = false;

                if (strpos($admin_opd_name, 'DINAS KESEHATAN') !== false && strpos($nama_upper, 'DINAS KESEHATAN') !== false) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'DINAS PENDIDIKAN') !== false && (strpos($nama_upper, 'SDN ') !== false || strpos($nama_upper, 'SMPN ') !== false || strpos($nama_upper, 'TK ') !== false)) {
                    $is_child = true;
                }

                if ($is_child && $o['id_opd'] != $id_opd_admin) {
                    $is_induk_opd = true;
                    $child_opds[] = $o;
                }
            }
        }

        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            if ($is_induk_opd && !empty($id_opd)) {
                if (strpos($id_opd, 'gabungan||') === 0) {
                    $req_gab = str_replace('gabungan||', '', $id_opd);
                    if (strpos($admin_opd_name, strtoupper($req_gab)) === false) $id_opd = $id_opd_admin;
                } else {
                    $valid = false;
                    foreach ($child_opds as $c) {
                        if ($c['id_opd'] == $id_opd) $valid = true;
                    }
                    if (!$valid && $id_opd != $id_opd_admin) $id_opd = $id_opd_admin;
                }
            } else {
                $id_opd = $id_opd_admin;
            }
        }

        if (!$id_opd) {
            return redirect()->back()->with('error', 'Silakan pilih OPD terlebih dahulu untuk mencetak Excel.');
        }

        $is_gabungan = false;
        $prefix_gabungan = '';
        if (strpos($id_opd, 'gabungan||') === 0) {
            $is_gabungan = true;
            $prefix_gabungan = str_replace('gabungan||', '', $id_opd);
        }

        $opd_info = $db->table('opd')->where('id_opd', $id_opd)->get()->getRow();
        if ($is_gabungan) $nama_opd = "GABUNGAN " . strtoupper($prefix_gabungan);
        elseif ($id_opd == 'rekap_eselon_2') $nama_opd = "Rekap Eselon II";
        elseif ($id_opd == 'rekap_eselon_3') $nama_opd = "Rekap Eselon III";
        elseif ($id_opd == 'rekap_eselon_4') $nama_opd = "Rekap Eselon IV";
        elseif ($id_opd == 'rekap_eselon_gabungan') $nama_opd = "Rekap Gabungan Eselon 2, 3, dan 4";
        else $nama_opd = $opd_info->nama_opd ?? "Semua Instansi";

        $hari_libur = $db->table('hari_libur')->where('MONTH(tanggal)', $bulan)->where('YEAR(tanggal)', $tahun)->get()->getResultArray();
        $array_tgl_libur = array_column($hari_libur, 'tanggal');

        $jam_kerja_raw = $db->table('jam_kerja_detail')->get()->getResultArray();
        $map_hari_kerja = [];
        $map_jadwal_full = [];
        foreach ($jam_kerja_raw as $jk) {
            $map_hari_kerja[$jk['id_jam_kerja']][] = strtolower($jk['hari']);
            $map_jadwal_full[$jk['id_jam_kerja']][strtolower($jk['hari'])] = $jk;
        }

        $tgl_awal_bulan = sprintf('%04d-%02d-01', $tahun, $bulan);
        $tgl_akhir_bulan = date('Y-m-t', strtotime($tgl_awal_bulan));
        $hari_ini_global = date('Y-m-d');
        $endDate = (date('m') == $bulan && date('Y') == $tahun) ? $hari_ini_global : $tgl_akhir_bulan;

        $cuti_raw = $db->table('pengajuan_cuti')
            ->where("LOWER(TRIM(status))", 'disetujui')
            ->where("tgl_mulai <=", $tgl_akhir_bulan)
            ->where("COALESCE(NULLIF(tgl_selesai, ''), tgl_mulai) >=", $tgl_awal_bulan)
            ->get()->getResultArray();

        $map_cuti = [];
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

        $builder_pegawai = $db->table('pegawai p')
            ->select('p.*, o.nama_opd, o.id_jam_kerja as id_jam_opd')
            ->join('opd o', 'p.id_opd = o.id_opd', 'left');

        if ($is_gabungan) $builder_pegawai->like('o.nama_opd', $prefix_gabungan, 'both');
        elseif ($id_opd == 'rekap_eselon_2') $builder_pegawai->whereIn('p.eselon', ['II.a', 'II.b']);
        elseif ($id_opd == 'rekap_eselon_3') $builder_pegawai->whereIn('p.eselon', ['III.a', 'III.b']);
        elseif ($id_opd == 'rekap_eselon_4') $builder_pegawai->whereIn('p.eselon', ['IV.a', 'IV.b']);
        elseif ($id_opd == 'rekap_eselon_gabungan') $builder_pegawai->whereIn('p.eselon', ['II.a', 'II.b', 'III.a', 'III.b', 'IV.a', 'IV.b']);
        else {
            $builder_pegawai->where("(
                p.id_pegawai IN (
                    SELECT id_pegawai FROM riwayat_penempatan 
                    WHERE id_opd = '$id_opd' 
                    AND tmt_mulai <= '$tgl_akhir_bulan' 
                    AND (tmt_selesai >= '$tgl_awal_bulan' OR tmt_selesai IS NULL)
                )
                OR
                (p.id_opd = '$id_opd' AND p.id_pegawai NOT IN (SELECT id_pegawai FROM riwayat_penempatan))
            )");
        }

        if (!empty($status_asn)) {
            $builder_pegawai->where('p.status_asn', $status_asn);
        }

        if ($is_gabungan) {
            $builder_pegawai->orderBy('o.nama_opd', 'ASC');
        }

        $pegawai = $builder_pegawai
            ->orderBy("CASE 
                WHEN TRIM(o.nama_opd) LIKE 'SEKRETARIAT DAERAH%' THEN 1
                WHEN TRIM(o.nama_opd) LIKE 'SEKRETARIAT DPRD%' THEN 2
                WHEN TRIM(o.nama_opd) LIKE 'INSPEKTORAT%' THEN 3
                WHEN TRIM(o.nama_opd) LIKE 'BADAN%' THEN 4
                WHEN TRIM(o.nama_opd) LIKE 'DINAS%' THEN 5
                WHEN TRIM(o.nama_opd) LIKE 'RSUD%' THEN 6
                WHEN TRIM(o.nama_opd) LIKE 'KANTOR CAMAT%' THEN 7
                ELSE 8 END", "ASC", false)
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

        $jml_hari_bulan_ini = date('t', strtotime("$tahun-$bulan-01"));
        $ids_pegawai = array_column($pegawai, 'id_pegawai');
        $nips_pegawai = array_column($pegawai, 'nip');

        $is_nakes_map = [];
        $map_plotting = [];
        $map_izin_full = [];
        $map_ta = [];

        if (!empty($ids_pegawai)) {
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
                $map_ta[$ua['id_pegawai']] = $ua['ta_count'];
            }

            $builder_plot = $db->table('plotting_shift')->whereIn('id_pegawai', $ids_pegawai);
            if ($db->fieldExists('tanggal_mulai', 'plotting_shift')) {
                $builder_plot->where("tanggal_mulai <=", "$tahun-$bulan-31")
                    ->where("tanggal_selesai >=", "$tahun-$bulan-01");
            } else {
                $builder_plot->where("MONTH(tanggal)", $bulan)->where("YEAR(tanggal)", $tahun);
            }
            $plotting_db = $builder_plot->get()->getResultArray();

            $master_jam = $db->table('jam_kerja')->get()->getResultArray();
            $map_jam_name = [];
            foreach ($master_jam as $mj) $map_jam_name[$mj['id_jam_kerja']] = $mj['nama_jadwal'] ?? '';

            foreach ($plotting_db as $pl) {
                $nama_jadwal_master = strtolower($map_jam_name[$pl['id_jam_kerja']] ?? '');
                if (strpos($nama_jadwal_master, 'ramadhan') === false && strpos($nama_jadwal_master, 'puasa') === false) {
                    $is_nakes_map[$pl['id_pegawai']] = true;
                }

                if (isset($pl['tanggal_mulai'])) {
                    $start = new \DateTime($pl['tanggal_mulai']);
                    $end = new \DateTime($pl['tanggal_selesai']);
                    $end->modify('+1 day');
                    $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
                    foreach ($period as $date) {
                        $map_plotting[$pl['id_pegawai']][$date->format('Y-m-d')] = $pl['id_jam_kerja'];
                    }
                } else {
                    $map_plotting[$pl['id_pegawai']][$pl['tanggal']] = $pl['id_jam_kerja'];
                }
            }

            // ==============================================================
            // [SUPER FIX]: TARIK DATA IZIN/SAKIT/TL DISETUJUI (Anti-Alpha)
            // ==============================================================
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
                    // Hanya mendeteksi Izin FULL DAY
                    if (empty($iz['slot_waktu']) || strtoupper($iz['slot_waktu']) == 'PENUH') {
                        $map_izin_full[$iz['nip']][$dt->format('Y-m-d')] = strtoupper($iz['kategori']);
                    }
                }
            }
        }

        $all_presensi = [];
        if (!empty($nips_pegawai)) {
            $logs_raw = $db->table('presensi')
                ->whereIn('nip', $nips_pegawai)
                ->where('MONTH(tanggal)', $bulan)
                ->where('YEAR(tanggal)', $tahun)
                ->get()->getResultArray();
            foreach ($logs_raw as $l) {
                $all_presensi[$l['nip']][$l['tanggal']] = $l;
            }
        }

        $data_pegawai = [];
        foreach ($pegawai as $pgw) {
            $log_map = $all_presensi[$pgw['nip']] ?? [];

            $res = [
                'nama' => $pgw['nama_lengkap'],
                'nip' => $pgw['nip'],
                'jab' => $pgw['jabatan'],
                'pangkat' => $pgw['pangkat'] ?? '-',
                'golongan' => $pgw['golongan'] ?? '-',
                'hari_kerja' => 0,
                'p1' => 0,
                'p2' => 0,
                'p3' => 0,
                'p4' => 0,
                'pc1' => 0,
                'pc2' => 0,
                'pc3' => 0,
                'pc4' => 0,
                'c' => 0,
                'i' => 0,
                'ish' => 0,
                'a' => 0,
                's' => 0,
                'dl' => 0,
                'tdk' => 0,
                'ta' => $map_ta[$pgw['id_pegawai']] ?? 0,
                'total_persen' => 0
            ];

            for ($d = 1; $d <= $jml_hari_bulan_ini; $d++) {
                $tgl_loop = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                if ($tgl_loop > $hari_ini_global) break;

                $nama_hari_loop = strtolower($this->get_nama_hari(date('D', strtotime($tgl_loop))));
                $is_libur_nasional = in_array($tgl_loop, $array_tgl_libur);

                $ada_jadwal = isset($map_plotting[$pgw['id_pegawai']][$tgl_loop]);
                $is_nakes = isset($is_nakes_map[$pgw['id_pegawai']]);
                $is_lepas_jaga = ($is_nakes && !$ada_jadwal);

                $id_jam_aktif = $ada_jadwal ? $map_plotting[$pgw['id_pegawai']][$tgl_loop] : ($pgw['id_jam_opd'] ?? 1);
                if (isset($log_map[$tgl_loop]) && !empty($log_map[$tgl_loop]['id_jam_kerja_snapshot'])) {
                    $id_jam_aktif = $log_map[$tgl_loop]['id_jam_kerja_snapshot'];
                }

                $nama_jk_aktif = strtolower($map_jam_name[$id_jam_aktif] ?? '');
                $is_cito = (strpos($nama_jk_aktif, 'cito') !== false || strpos($nama_jk_aktif, 'standby') !== false);

                $hari_kerja_aktif = $map_hari_kerja[$id_jam_aktif] ?? ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
                $is_hari_kerja = in_array($nama_hari_loop, $hari_kerja_aktif);

                // ==============================================================
                // [SUPER FIX]: PRIORITAS TINGKAT TINGGI
                // ==============================================================
                $is_cuti = isset($map_cuti[$pgw['id_pegawai']]) && in_array($tgl_loop, $map_cuti[$pgw['id_pegawai']]);
                $is_izin_full = isset($map_izin_full[$pgw['nip']][$tgl_loop]);

                if ($is_lepas_jaga) continue;
                if ($is_libur_nasional && !$is_nakes) continue;
                if (!$is_hari_kerja && !$is_cito) continue;

                if ($is_cuti) {
                    $res['c']++;
                    continue;
                }

                $res['hari_kerja']++;

                if ($is_izin_full) {
                    $kategori_izin = $map_izin_full[$pgw['nip']][$tgl_loop];
                    if (strpos($kategori_izin, 'IZIN') !== false) $res['i']++;
                    elseif (strpos($kategori_izin, 'SAKIT') !== false) $res['s']++;
                    elseif (strpos($kategori_izin, 'LUAR') !== false) $res['dl']++;
                    continue;
                }
                // ==============================================================

                if (isset($log_map[$tgl_loop])) {
                    $l = $log_map[$tgl_loop];
                    $is_full_day = false;
                    $kategori = ucwords(strtolower(trim($l['kategori'] ?? '')));
                    $deskripsi = strtolower($l['deskripsi'] ?? '');
                    $status_masuk = strtolower($l['status_masuk'] ?? '');
                    $status_pulang = strtolower($l['status_pulang'] ?? '');

                    // FIX: Jika status_verifikasi adalah Dianulir atau Ditangguhkan, maka hitung sebagai Alpha
                    if (($l['status_verifikasi'] ?? '') === 'Dianulir' || ($l['status_verifikasi'] ?? '') === 'Ditangguhkan') {
                        $kategori = 'Alpa';
                    }

                    $is_izin_pagi = false;
                    $is_izin_sore = false;
                    if (strpos($deskripsi, 'pagi') !== false || strpos($status_masuk, 'izin') !== false || strpos($status_masuk, 'sakit') !== false || strpos($status_masuk, 'luar') !== false) $is_izin_pagi = true;
                    if (strpos($deskripsi, 'siang') !== false || strpos($deskripsi, 'sore') !== false || strpos($status_pulang, 'izin') !== false || strpos($status_pulang, 'luar') !== false) $is_izin_sore = true;

                    if ($kategori == 'Cuti') {
                        $res['c']++;
                        $is_full_day = true;
                    } elseif (in_array($kategori, ['Izin', 'Sakit/izin', 'Sakit/Izin'])) {
                        if (strpos($deskripsi, 'sebagian') !== false || strpos($deskripsi, 'setengah') !== false || $kategori == 'Izin Setengah Hari') {
                            $res['ish']++;
                            // $is_full_day dibiarkan false agar tetap dihitung sesi yang tidak diizinkan
                        } else {
                            $res['i']++;
                            $is_full_day = true;
                        }
                    } elseif ($kategori == 'Sakit') {
                        $res['s']++;
                        $is_full_day = true;
                    } elseif (in_array($kategori, ['Tugas Luar', 'Dinas Luar'])) {
                        if (strpos($deskripsi, 'sebagian') === false) {
                            $res['dl']++;
                            $is_full_day = true;
                        }
                    } elseif ($kategori == 'Alpa') {
                        $res['a']++;
                        $is_full_day = true;
                    }

                    if (!$is_full_day) {
                        if ($nama_hari_loop == 'senin' && (empty($l['jam_masuk_aktual']) || $l['jam_masuk_aktual'] == '00:00:00')) {
                            $res['tdk']++;
                        }

                        if (!$is_izin_pagi) {
                            if (!empty($l['jam_masuk_aktual']) && $l['jam_masuk_aktual'] > '04:00:00') {
                                if (in_array($l['status_masuk'], ['Terlambat', 'Pembinaan', 'Teguran']) && !$is_cito) {
                                    $config_jam = $map_jadwal_full[$id_jam_aktif][$nama_hari_loop] ?? null;
                                    $batas_toleransi = $config_jam ? ($config_jam['batas_cukup_menit'] ?? 45) : 45;
                                    $m = max(0, $l['menit_keterlambatan'] - $batas_toleransi);

                                    if ($m > 0) {
                                        if ($m >= 1 && $m <= 30) $res['p1']++;
                                        elseif ($m >= 31 && $m <= 60) $res['p2']++;
                                        elseif ($m >= 61 && $m <= 90) $res['p3']++;
                                        elseif ($m > 90) $res['p4']++;
                                    }
                                }
                            } else {
                                if ($tgl_loop < $hari_ini_global && !$is_cito) $res['p4']++;
                            }
                        }

                        // Hitung Pulang Cepat (Abaikan Jika Izin Sore)
                        if (!$is_izin_sore) {
                            if (empty($l['jam_pulang_aktual']) || $l['jam_pulang_aktual'] <= '04:00:00') {
                                if ($tgl_loop < $hari_ini_global && !$is_cito) $res['pc4']++;
                            } else {
                                if (!empty($l['jam_pulang_seharusnya']) && $l['jam_pulang_aktual'] < $l['jam_pulang_seharusnya'] && !$is_cito) {
                                    $selisih = strtotime($l['jam_pulang_seharusnya']) - strtotime($l['jam_pulang_aktual']);
                                    $menit_pc = floor($selisih / 60);

                                    if ($menit_pc >= 1 && $menit_pc <= 30) $res['pc1']++;
                                    elseif ($menit_pc >= 31 && $menit_pc <= 60) $res['pc2']++;
                                    elseif ($menit_pc >= 61 && $menit_pc <= 90) $res['pc3']++;
                                    elseif ($menit_pc > 90) $res['pc4']++;
                                }
                            }
                        }
                    }
                } else {
                    // [FITUR BARU]: Jika tidak absen sama sekali, Nakes CITO bebas Alpha
                    if (!$is_cito) {
                        $res['a']++;
                        if ($nama_hari_loop == 'senin') {
                            $res['tdk']++;
                        }
                    }
                }
            }

            $res['total_persen'] = ($res['p1'] * 0.5) + ($res['p2'] * 1) + ($res['p3'] * 1.25) + ($res['p4'] * 1.5) +
                ($res['pc1'] * 0.5) + ($res['pc2'] * 1) + ($res['pc3'] * 1.25) + ($res['pc4'] * 1.5) +
                ($res['i'] * 0) + ($res['a'] * 3) + ($res['tdk'] * 0.5) + ($res['ta'] * 2); // ish tidak ada potongan

            $data_pegawai[] = $res;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $nama_bulan = date('F', mktime(0, 0, 0, $bulan, 10));

        $sheet->setCellValue('A1', 'REKAPITULASI KETAATAN JAM KERJA & POTONGAN TPP');
        $sheet->setCellValue('A2', 'UNIT KERJA : ' . strtoupper($nama_opd));

        $periode_txt = 'PERIODE : ' . strtoupper($nama_bulan) . ' ' . $tahun;
        if (!empty($status_asn)) {
            $periode_txt .= ' | STATUS ASN : ' . strtoupper($status_asn);
        }
        $sheet->setCellValue('A3', $periode_txt);

        $sheet->mergeCells('A1:AC1');
        $sheet->mergeCells('A2:AC2');
        $sheet->mergeCells('A3:AC3');
        $sheet->getStyle('A1:A3')->getFont()->setBold(true)->setSize(12);

        $styleHeader = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']]
        ];

        $sheet->setCellValue('A5', 'NO')->mergeCells('A5:A7');
        $sheet->setCellValue('B5', 'NAMA')->mergeCells('B5:B7');
        $sheet->setCellValue('C5', 'PANGKAT / GOL. RUANG')->mergeCells('C5:C7');
        $sheet->setCellValue('D5', 'JABATAN')->mergeCells('D5:D7');
        $sheet->setCellValue('E5', "JUMLAH\nHARI\nKERJA")->mergeCells('E5:E7');

        $sheet->setCellValue('F5', 'JUMLAH HARI TIDAK MASUK KERJA')->mergeCells('F5:Q5');
        $sheet->setCellValue('R5', 'PERHITUNGAN % KETIDAKTAATAN JAM KERJA')->mergeCells('R5:AC5');
        $sheet->setCellValue('AD5', "JML\nPOT (%)")->mergeCells('AD5:AD7');

        $sheet->setCellValue('F6', 'Tb (menit)')->mergeCells('F6:I6');
        $sheet->setCellValue('J6', 'Pc (menit)')->mergeCells('J6:M6');
        $sheet->setCellValue('N6', "TDK\n(perkeg)")->mergeCells('N6:N7');
        $sheet->setCellValue('O6', "TA\n(perkeg)")->mergeCells('O6:O7');
        $sheet->setCellValue('P6', "I\n(hari)")->mergeCells('P6:P7');
        $sheet->setCellValue('Q6', "A\n(hari)")->mergeCells('Q6:Q7');

        $sheet->setCellValue('R6', 'Tb')->mergeCells('R6:U6');
        $sheet->setCellValue('V6', 'Pc')->mergeCells('V6:Y6');
        $sheet->setCellValue('Z6', "TDK\n(0,5%)")->mergeCells('Z6:Z7');
        $sheet->setCellValue('AA6', "TA\n(2%)")->mergeCells('AA6:AA7');
        $sheet->setCellValue('AB6', "I\n(0%)")->mergeCells('AB6:AB7');
        $sheet->setCellValue('AC6', "A\n(3%)")->mergeCells('AC6:AC7');

        $headers_l3 = [
            'F' => 'P1',
            'G' => 'P2',
            'H' => 'P3',
            'I' => 'P4',
            'J' => 'P1',
            'K' => 'P2',
            'L' => 'P3',
            'M' => 'P4',
            'R' => "P1\n0,5%",
            'S' => "P2\n1,0%",
            'T' => "P3\n1,25%",
            'U' => "P4\n1,5%",
            'V' => "P1\n0,5%",
            'W' => "P2\n1,0%",
            'X' => "P3\n1,25%",
            'Y' => "P4\n1,5%"
        ];
        foreach ($headers_l3 as $col => $val) {
            $sheet->setCellValue($col . '7', $val);
        }

        $sheet->getStyle('A5:AD7')->applyFromArray($styleHeader);
        $sheet->getStyle('A5:AD7')->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(30);

        $row = 8;
        $no = 1;
        $styleData = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];

        foreach ($data_pegawai as $p) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $p['nama'] . "\nNIP. " . $p['nip']);
            $sheet->getStyle('B' . $row)->getAlignment()->setWrapText(true);
            $sheet->setCellValue('C' . $row, $p['pangkat'] . "\n" . $p['golongan']);
            $sheet->getStyle('C' . $row)->getAlignment()->setWrapText(true);
            $sheet->setCellValue('D' . $row, $p['jab']);
            $sheet->setCellValue('E' . $row, $p['hari_kerja']);

            $sheet->setCellValue('F' . $row, $p['p1'] ?: '-');
            $sheet->setCellValue('G' . $row, $p['p2'] ?: '-');
            $sheet->setCellValue('H' . $row, $p['p3'] ?: '-');
            $sheet->setCellValue('I' . $row, $p['p4'] ?: '-');
            $sheet->setCellValue('J' . $row, $p['pc1'] ?: '-');
            $sheet->setCellValue('K' . $row, $p['pc2'] ?: '-');
            $sheet->setCellValue('L' . $row, $p['pc3'] ?: '-');
            $sheet->setCellValue('M' . $row, $p['pc4'] ?: '-');

            $sheet->setCellValue('N' . $row, $p['tdk'] ?: '-');
            $sheet->setCellValue('O' . $row, $p['ta'] ?: '-');
            $sheet->setCellValue('P' . $row, $p['i'] ?: '-');
            $sheet->setCellValue('Q' . $row, $p['a'] ?: '-');

            $sheet->setCellValue('R' . $row, ($p['p1'] > 0) ? ($p['p1'] * 0.5) : '');
            $sheet->setCellValue('S' . $row, ($p['p2'] > 0) ? ($p['p2'] * 1) : '');
            $sheet->setCellValue('T' . $row, ($p['p3'] > 0) ? ($p['p3'] * 1.25) : '');
            $sheet->setCellValue('U' . $row, ($p['p4'] > 0) ? ($p['p4'] * 1.5) : '');
            $sheet->setCellValue('V' . $row, ($p['pc1'] > 0) ? ($p['pc1'] * 0.5) : '');
            $sheet->setCellValue('W' . $row, ($p['pc2'] > 0) ? ($p['pc2'] * 1) : '');
            $sheet->setCellValue('X' . $row, ($p['pc3'] > 0) ? ($p['pc3'] * 1.25) : '');
            $sheet->setCellValue('Y' . $row, ($p['pc4'] > 0) ? ($p['pc4'] * 1.5) : '');

            $sheet->setCellValue('Z' . $row, ($p['tdk'] > 0) ? ($p['tdk'] * 0.5) : '');
            $sheet->setCellValue('AA' . $row, ($p['ta'] > 0) ? ($p['ta'] * 2) : '');
            $sheet->setCellValue('AB' . $row, ($p['i'] > 0) ? ($p['i'] * 0) : '');
            $sheet->setCellValue('AC' . $row, ($p['a'] > 0) ? ($p['a'] * 3) : '');

            $sheet->setCellValue('AD' . $row, $p['total_persen'] . '%');
            $row++;
        }

        $sheet->getStyle('A8:AD' . ($row - 1))->applyFromArray($styleData);
        $sheet->getStyle('A8:A' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E8:AD' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $row += 2;
        $sheet->setCellValue('B' . $row, 'Keterangan :');
        $sheet->getStyle('B' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('B' . $row, "Tb : Terlambat datang {(P1: s.d 30' = 0,5%), (P2: 31' s.d 60' = 1%), (P3: 61' s.d 90' = 1,25%), (P4: > 90' = 1,5%)} x jumlah keterlambatan 1 bulan");
        $row++;
        $sheet->setCellValue('B' . $row, "Pc : Pulang Cepat {(P1: s.d 30' = 0,5%), (P2: 31' s.d 60' = 1%), (P3: 61' s.d 90' = 1,25%), (P4: > 90' = 1,5%)} x jumlah pulang cepat 1 bulan");
        $row++;
        $sheet->setCellValue('B' . $row, "C : Cuti sakit lebih dari 12 hari s/d 1 tahun / Cuti bersalin ketiga / Cuti besar (0%)");
        $row++;
        $sheet->setCellValue('B' . $row, "I : Izin tidak masuk kerja / izin sakit / izin setelah cuti sakit (0% x jumlah hari tidak masuk)");
        $row++;
        $sheet->setCellValue('B' . $row, "A : Alpa (3% x jumlah hari Alpa)");
        $row++;
        $sheet->setCellValue('B' . $row, "TDK : Tidak mengikuti Dialog Kinerja Senin (0,5% x Jumlah tidak mengikuti Dialog dalam 1 bulan)\nTA : Tidak Apel (2% x Jumlah Apel yang tidak diikuti)");

        $filename = "Laporan_TPP_" . str_replace([' ', ','], '_', $nama_opd) . "_" . $bulan . "_" . $tahun . ".xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function harian()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();
        $session = session();

        $level = $session->get('level');
        $id_opd_admin = $session->get('id_opd');
        $tanggal = $request->getGet('tanggal') ?? date('Y-m-d');
        $status_asn = $request->getGet('status_asn');
        $filter_shift = $request->getGet('id_jam_kerja');
        $sesi = $request->getGet('sesi') ?? 'semua';
        $keyword = $request->getGet('keyword');

        $list_opd = $db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        // =========================================================================
        // [PERBAIKAN ENTERPRISE]: DETEKSI DINAS INDUK & UPTD ANAK
        // =========================================================================
        $admin_opd_info = $db->table('opd')->where('id_opd', $id_opd_admin)->get()->getRow();
        $admin_opd_name = $admin_opd_info ? strtoupper($admin_opd_info->nama_opd) : '';

        $list_gabungan = [];
        $child_opds = [];
        $is_induk_opd = false;

        foreach ($list_opd as $o) {
            if (strpos($o['nama_opd'], ' - ') !== false) {
                $induk = trim(explode(' - ', $o['nama_opd'])[0]);
                $list_gabungan[$induk] = $induk;
            }
            $nama_upper = strtoupper($o['nama_opd']);
            if (strpos($nama_upper, 'RSUD HAJJAH ANDI DEPU') !== false) $list_gabungan['RSUD HAJJAH ANDI DEPU'] = 'RSUD HAJJAH ANDI DEPU';
            if (strpos($nama_upper, 'RSUD WONOMULYO') !== false) $list_gabungan['RSUD WONOMULYO'] = 'RSUD WONOMULYO';
            if (strpos($nama_upper, 'DINAS KESEHATAN') !== false) $list_gabungan['DINAS KESEHATAN'] = 'DINAS KESEHATAN';
            if (strpos($nama_upper, 'DINAS PENDIDIKAN') !== false) $list_gabungan['DINAS PENDIDIKAN'] = 'DINAS PENDIDIKAN';

            if (!in_array($level, ['Superadmin', 'kabupaten'])) {
                $is_child = false;

                if (strpos($admin_opd_name, 'DINAS KESEHATAN') !== false && strpos($nama_upper, 'DINAS KESEHATAN') !== false) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'DINAS PENDIDIKAN') !== false && (strpos($nama_upper, 'SDN ') !== false || strpos($nama_upper, 'SMPN ') !== false || strpos($nama_upper, 'TK ') !== false)) {
                    $is_child = true;
                }

                if ($is_child && $o['id_opd'] != $id_opd_admin) {
                    $is_induk_opd = true;
                    $child_opds[] = $o;
                }
            }
        }
        $list_gabungan = array_values($list_gabungan);

        $id_opd = $request->getGet('id_opd');

        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            if ($is_induk_opd && !empty($id_opd)) {
                if (strpos($id_opd, 'gabungan||') === 0) {
                    $req_gab = str_replace('gabungan||', '', $id_opd);
                    if (strpos($admin_opd_name, strtoupper($req_gab)) === false) $id_opd = $id_opd_admin;
                } else {
                    $valid = false;
                    foreach ($child_opds as $c) {
                        if ($c['id_opd'] == $id_opd) $valid = true;
                    }
                    if (!$valid && $id_opd != $id_opd_admin) $id_opd = $id_opd_admin;
                }
            } else {
                $id_opd = $id_opd_admin;
            }
        }
        // =========================================================================

        $is_gabungan = false;
        $prefix_gabungan = '';
        if ($id_opd && strpos($id_opd, 'gabungan||') === 0) {
            $is_gabungan = true;
            $prefix_gabungan = str_replace('gabungan||', '', $id_opd);
        }

        $cek_libur = $db->table('hari_libur')->where('tanggal', $tanggal)->get()->getRow();
        $is_libur_nasional = $cek_libur ? true : false;
        $nama_libur_nasional = $cek_libur ? $cek_libur->keterangan : '';

        $rekap_harian = [];
        $rekap_grouped = [];

        if (!$is_gabungan) {
            $rekap_grouped = [
                'ESELON II (JPT)' => [],
                'ESELON III (ADMINISTRATOR)' => [],
                'ESELON IV (PENGAWAS / LURAH)' => [],
                'PNS (FUNGSIONAL / PELAKSANA)' => [],
                'PPPK' => [],
                'TENAGA KONTRAK / PPPKPW / NON-ASN' => []
            ];
        }

        $master_jam = $db->table('jam_kerja')->get()->getResultArray();
        $map_jam_name = [];
        foreach ($master_jam as $mj) {
            $map_jam_name[$mj['id_jam_kerja']] = $mj['nama_jadwal'] ?? 'Reguler';
        }

        if (!empty($id_opd)) {
            $nama_hari = $this->get_nama_hari(date('D', strtotime($tanggal)));

            $jadwal_db = $db->table('jam_kerja_detail')->where('hari', $nama_hari)->get()->getResultArray();
            $map_jk = [];
            foreach ($jadwal_db as $j) $map_jk[$j['id_jam_kerja']] = $j;

            $builder = $db->table('pegawai p')
                ->select('p.id_pegawai, p.nip, p.nama_lengkap, p.eselon, p.level_jabatan, o.id_jam_kerja, o.nama_opd, pr.id_presensi, pr.status_verifikasi, pr.alasan_tangguh, pr.jam_masuk_aktual, pr.jam_siang, pr.jam_pulang_aktual, pr.kategori, pr.status_masuk, pr.status_pulang, pr.id_jam_kerja_snapshot, pr.menit_keterlambatan')
                ->join('opd o', 'p.id_opd = o.id_opd', 'left')
                ->join('presensi pr', "p.id_pegawai = pr.id_pegawai AND pr.tanggal = '$tanggal'", 'left');

            if ($is_gabungan) $builder->like('o.nama_opd', $prefix_gabungan, 'both');
            elseif ($id_opd == 'rekap_eselon_2') $builder->whereIn('p.eselon', ['II.a', 'II.b']);
            elseif ($id_opd == 'rekap_eselon_3') $builder->whereIn('p.eselon', ['III.a', 'III.b']);
            elseif ($id_opd == 'rekap_eselon_4') $builder->whereIn('p.eselon', ['IV.a', 'IV.b']);
            elseif ($id_opd == 'rekap_eselon_gabungan') $builder->whereIn('p.eselon', ['II.a', 'II.b', 'III.a', 'III.b', 'IV.a', 'IV.b']);
            else $builder->where('p.id_opd', $id_opd);

            if (!empty($status_asn)) $builder->where('p.status_asn', $status_asn);

            if (!empty($keyword)) {
                $builder->groupStart()
                    ->like('p.nama_lengkap', $keyword)
                    ->orLike('p.nip', $keyword)
                    ->groupEnd();
            }

            $builder->groupBy('p.id_pegawai');
            if ($is_gabungan) $builder->orderBy('o.nama_opd', 'ASC');

            $results = $builder->orderBy("CASE 
                    WHEN TRIM(p.eselon) = 'II.a' THEN 1 WHEN TRIM(p.eselon) = 'II.b' THEN 2
                    WHEN TRIM(p.eselon) = 'III.a' THEN 3 WHEN TRIM(p.eselon) = 'III.b' THEN 4
                    WHEN TRIM(p.eselon) = 'IV.a' THEN 5 WHEN TRIM(p.eselon) = 'IV.b' THEN 6
                    ELSE 7 END", "ASC", false)
                ->orderBy('p.nama_lengkap', 'ASC')
                ->get()->getResultArray();

            $ids_pegawai = array_column($results, 'id_pegawai');
            $nips_pegawai = array_column($results, 'nip');

            $map_plotting_today = [];
            $is_nakes_map = [];
            $map_cuti_today = [];
            $map_izin_today = [];

            if (!empty($ids_pegawai)) {
                // 1. Tarik Plotting
                $builder_plot = $db->table('plotting_shift')->whereIn('id_pegawai', $ids_pegawai);
                if ($db->fieldExists('tanggal_mulai', 'plotting_shift')) {
                    $builder_plot->where("tanggal_mulai <=", $tanggal)->where("tanggal_selesai >=", $tanggal);
                } else {
                    $builder_plot->where("tanggal", $tanggal);
                }
                $plotting_db = $builder_plot->get()->getResultArray();

                foreach ($plotting_db as $pl) {
                    $map_plotting_today[$pl['id_pegawai']] = $pl['id_jam_kerja'];
                    $nama_jadwal_master = strtolower($map_jam_name[$pl['id_jam_kerja']] ?? '');
                    if (strpos($nama_jadwal_master, 'ramadhan') === false && strpos($nama_jadwal_master, 'puasa') === false) {
                        $is_nakes_map[$pl['id_pegawai']] = true;
                    }
                }

                // 2. Tarik Cuti
                $cuti_raw = $db->table('pengajuan_cuti')
                    ->select('pegawai_id, master_cuti.nama_cuti')
                    ->join('master_cuti', 'master_cuti.id_cuti = pengajuan_cuti.cuti_id')
                    ->whereIn('pegawai_id', $ids_pegawai)
                    ->where("LOWER(TRIM(status))", 'disetujui')
                    ->where("'$tanggal' >= tgl_mulai AND '$tanggal' <= COALESCE(NULLIF(tgl_selesai, ''), tgl_mulai)")
                    ->get()->getResultArray();
                foreach ($cuti_raw as $c) $map_cuti_today[$c['pegawai_id']] = $c['nama_cuti'];

                // 3. Tarik Izin/Sakit/TL (Anti-Alpha)
                $izin_raw = $db->table('pengajuan_absen')
                    ->whereIn('nip', $nips_pegawai)
                    ->where("LOWER(TRIM(status_approval))", 'disetujui')
                    ->where("'$tanggal' >= tanggal_mulai AND '$tanggal' <= COALESCE(NULLIF(tanggal_selesai, ''), tanggal_mulai)")
                    ->get()->getResultArray();
                foreach ($izin_raw as $iz) {
                    if (empty($iz['slot_waktu']) || strtoupper($iz['slot_waktu']) == 'PENUH') {
                        $map_izin_today[$iz['nip']] = strtoupper($iz['kategori']) . ($iz['sub_kategori'] ? ' (' . $iz['sub_kategori'] . ')' : '');
                    }
                }
            }

            foreach ($results as $r) {
                $status = "Tanpa Keterangan";
                $bg = "bg-st-default";
                $status_pulang_label = "-";
                $r['telat'] = 0;

                $active_id_jam = $r['id_jam_kerja'] ?? 1;
                if (isset($map_plotting_today[$r['id_pegawai']])) $active_id_jam = $map_plotting_today[$r['id_pegawai']];
                if (!empty($r['id_jam_kerja_snapshot'])) $active_id_jam = $r['id_jam_kerja_snapshot'];

                $nama_jk_aktif = strtolower($map_jam_name[$active_id_jam] ?? '');
                $is_cito = (strpos($nama_jk_aktif, 'cito') !== false || strpos($nama_jk_aktif, 'standby') !== false);
                $is_lepas_jaga = (isset($is_nakes_map[$r['id_pegawai']]) && !isset($map_plotting_today[$r['id_pegawai']]));

                $r['nama_shift'] = $is_lepas_jaga ? 'Lepas Jaga' : ($map_jam_name[$active_id_jam] ?? 'Reguler');

                $is_libur_akhir_pekan = in_array($nama_hari, ['Minggu', 'Sabtu']) && !isset($map_jk[$active_id_jam]);
                $is_libur_nasional_today = $is_libur_nasional && !isset($is_nakes_map[$r['id_pegawai']]);

                // PRIORITAS 1: CUTI
                if (isset($map_cuti_today[$r['id_pegawai']])) {
                    $status = 'CUTI: ' . strtoupper($map_cuti_today[$r['id_pegawai']]);
                    $bg = "bg-st-izin";
                    $status_pulang_label = "-";
                }
                // PRIORITAS 2: IZIN / SAKIT / TL FULL DAY
                elseif (isset($map_izin_today[$r['nip']])) {
                    $status = $map_izin_today[$r['nip']];
                    $bg = "bg-st-izin";
                    $status_pulang_label = "-";
                }
                // PRIORITAS 3: HARI LIBUR / LEPAS JAGA
                elseif ($is_lepas_jaga || $is_libur_nasional_today || ($is_libur_akhir_pekan && !$is_cito)) {
                    $status = $is_lepas_jaga ? 'Lepas Jaga' : ($is_libur_nasional_today ? 'Libur: ' . $nama_libur_nasional : 'Libur Akhir Pekan');
                    $bg = "bg-light text-dark border";
                    $status_pulang_label = "-";
                }
                // PRIORITAS 4: DATA PRESENSI MASUK
                elseif ($r['kategori'] == 'Hadir' || strpos($r['kategori'], 'Apel') !== false) {
                    if (!empty($r['jam_masuk_aktual']) && $r['jam_masuk_aktual'] > '04:00:00') {
                        $st_final = $r['status_masuk'];
                        if ($is_cito && empty($st_final)) $st_final = 'Hadir (Standby)';

                        $lbl_m = strtolower($st_final);
                        if (strpos($lbl_m, 'istimewa') !== false || strpos($lbl_m, 'sangat baik') !== false) $bg = 'bg-st-istimewa';
                        elseif ($lbl_m == 'baik') $bg = 'bg-st-baik';
                        elseif (strpos($lbl_m, 'cukup') !== false) $bg = 'bg-st-cukup';
                        elseif (strpos($lbl_m, 'terlambat') !== false) $bg = 'bg-st-telat';
                        elseif (strpos($lbl_m, 'pembinaan') !== false) $bg = 'bg-st-bina';
                        elseif (strpos($lbl_m, 'teguran') !== false) $bg = 'bg-st-teguran';
                        elseif (strpos($lbl_m, 'standby') !== false) $bg = 'bg-info text-dark border-info';
                        else $bg = 'bg-st-baik';

                        $status = ucwords($st_final) . ' (' . date('H:i', strtotime($r['jam_masuk_aktual'])) . ')';
                        if (in_array($st_final, ['Terlambat', 'Pembinaan', 'Teguran']) && !$is_cito) {
                            $config = $map_jk[$active_id_jam] ?? null;
                            $batas = $config ? ($config['batas_cukup_menit'] ?? 45) : 45;
                            $r['telat'] = max(0, intval($r['menit_keterlambatan']) - $batas);
                        }
                    } else {
                        $bg = 'bg-danger text-white';
                        $status = 'Masuk Kosong';
                    }

                    $st_pulang = $r['status_pulang'] ?? '';
                    if ($is_cito && !empty($r['jam_pulang_aktual'])) $st_pulang = 'Selesai';
                    $status_pulang_label = empty($st_pulang) ? 'Belum Absen' : ucwords(strtolower($st_pulang));

                    // CEK APAKAH DITANGGUHKAN / DIANULIR
                    if (!empty($r['status_verifikasi']) && $r['status_verifikasi'] == 'Ditangguhkan') {
                        $bg = 'bg-danger text-white border-danger';
                        $status = 'DITANGGUHKAN (Klarifikasi Pimpinan)';
                    } elseif (!empty($r['status_verifikasi']) && $r['status_verifikasi'] == 'Dianulir') {
                        $bg = 'bg-danger text-white border-danger';
                        $status = 'DIANULIR (Dianggap Alpha)';
                    }
                }
                // PRIORITAS 5: ALPHA / STANDBY TANPA PANGGILAN
                else {
                    if ($tanggal > date('Y-m-d')) {
                        $status = 'Belum Ada Data';
                        $bg = 'bg-light text-muted';
                    } elseif ($is_cito) {
                        $status = 'Standby (Bebas Alpa)';
                        $bg = 'bg-info text-dark border-info';
                    } else {
                        $status = 'Tanpa Keterangan (Alpha)';
                        $bg = 'bg-danger text-white';
                    }
                }

                $r['status_label'] = $status;
                $r['status_bg'] = $bg;
                $r['status_pulang_label'] = $status_pulang_label;

                $rekap_harian[] = $r;

                if (!$is_gabungan) {
                    $eselon = trim($r['eselon'] ?? '');
                    $level_j = strtoupper(trim($r['level_jabatan'] ?? ''));
                    if (in_array($eselon, ['II.a', 'II.b'])) $rekap_grouped['ESELON II (JPT)'][] = $r;
                    elseif (in_array($eselon, ['III.a', 'III.b'])) $rekap_grouped['ESELON III (ADMINISTRATOR)'][] = $r;
                    elseif (in_array($eselon, ['IV.a', 'IV.b'])) $rekap_grouped['ESELON IV (PENGAWAS / LURAH)'][] = $r;
                    else {
                        if (strpos($level_j, 'FUNGSIONAL') !== false || strpos($level_j, 'GURU') !== false || strpos($level_j, 'MEDIS') !== false || strpos($level_j, 'PNS') !== false) $rekap_grouped['PNS (FUNGSIONAL / PELAKSANA)'][] = $r;
                        elseif (strpos($level_j, 'PPPK') !== false) $rekap_grouped['PPPK'][] = $r;
                        else $rekap_grouped['TENAGA KONTRAK / PPPKPW / NON-ASN'][] = $r;
                    }
                } else {
                    $ruang = strtoupper($r['nama_opd']);
                    if (!isset($rekap_grouped[$ruang])) $rekap_grouped[$ruang] = [];
                    $rekap_grouped[$ruang][] = $r;
                }
            }

            return view('admin/rekap_harian', [
                'title'               => 'Rekap Harian Presensi',
                'rekap'               => $rekap_harian,
                'rekap_grouped'       => $rekap_grouped,
                'list_opd'            => $list_opd,
                'list_gabungan'       => $list_gabungan,
                'tanggal'             => $tanggal,
                'selected_opd'        => $id_opd,
                'selected_status_asn' => $status_asn,
                'sesi'                => $sesi,
                'keyword'             => $keyword,
                'list_jam_kerja'      => $master_jam,
                'selected_shift'      => $filter_shift,
                'is_induk_opd'        => $is_induk_opd,
                'child_opds'          => $child_opds,
                'admin_opd_name'      => $admin_opd_name
            ]);
        }

        return view('admin/rekap_harian', [
            'title'               => 'Rekap Harian Presensi',
            'rekap'               => [],
            'rekap_grouped'       => [],
            'list_opd'            => $list_opd,
            'list_gabungan'       => $list_gabungan,
            'tanggal'             => $tanggal,
            'selected_opd'        => $id_opd,
            'selected_status_asn' => $status_asn,
            'sesi'                => $sesi,
            'keyword'             => $keyword,
            'list_jam_kerja'      => $master_jam,
            'selected_shift'      => $filter_shift,
            'is_induk_opd'        => $is_induk_opd ?? false,
            'child_opds'          => $child_opds ?? [],
            'admin_opd_name'      => $admin_opd_name ?? ''
        ]);
    }

    public function bulanan_matriks()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();
        $session = session();

        $level = $session->get('level');
        $id_opd_admin = $session->get('id_opd');

        $bulan = $request->getGet('bulan') ?? date('m');
        $tahun = $request->getGet('tahun') ?? date('Y');
        $status_asn = $request->getGet('status_asn');

        $list_opd = $db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        // =========================================================================
        // [PERBAIKAN ENTERPRISE]: DETEKSI DINAS INDUK & UPTD ANAK
        // =========================================================================
        $admin_opd_info = $db->table('opd')->where('id_opd', $id_opd_admin)->get()->getRow();
        $admin_opd_name = $admin_opd_info ? strtoupper($admin_opd_info->nama_opd) : '';

        $list_gabungan = [];
        $child_opds = [];
        $is_induk_opd = false;

        foreach ($list_opd as $o) {
            if (strpos($o['nama_opd'], ' - ') !== false) {
                $induk = trim(explode(' - ', $o['nama_opd'])[0]);
                $list_gabungan[$induk] = $induk;
            }
            $nama_upper = strtoupper($o['nama_opd']);
            if (strpos($nama_upper, 'RSUD HAJJAH ANDI DEPU') !== false) $list_gabungan['RSUD HAJJAH ANDI DEPU'] = 'RSUD HAJJAH ANDI DEPU';
            if (strpos($nama_upper, 'RSUD WONOMULYO') !== false) $list_gabungan['RSUD WONOMULYO'] = 'RSUD WONOMULYO';
            if (strpos($nama_upper, 'DINAS KESEHATAN') !== false) $list_gabungan['DINAS KESEHATAN'] = 'DINAS KESEHATAN';
            if (strpos($nama_upper, 'DINAS PENDIDIKAN') !== false) $list_gabungan['DINAS PENDIDIKAN'] = 'DINAS PENDIDIKAN';

            if (!in_array($level, ['Superadmin', 'kabupaten'])) {
                $is_child = false;

                // [PERBAIKAN STRICT]
                if (strpos($admin_opd_name, 'DINAS KESEHATAN') !== false && strpos($nama_upper, 'DINAS KESEHATAN') !== false) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'DINAS PENDIDIKAN') !== false && (strpos($nama_upper, 'SDN ') !== false || strpos($nama_upper, 'SMPN ') !== false || strpos($nama_upper, 'TK ') !== false)) {
                    $is_child = true;
                }

                if ($is_child && $o['id_opd'] != $id_opd_admin) {
                    $is_induk_opd = true;
                    $child_opds[] = $o;
                }
            }
        }
        $list_gabungan = array_values($list_gabungan);

        $id_opd = $request->getGet('id_opd');

        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            if ($is_induk_opd && !empty($id_opd)) {
                if (strpos($id_opd, 'gabungan||') === 0) {
                    $req_gab = str_replace('gabungan||', '', $id_opd);
                    if (strpos($admin_opd_name, strtoupper($req_gab)) === false) $id_opd = $id_opd_admin;
                } else {
                    $valid = false;
                    foreach ($child_opds as $c) {
                        if ($c['id_opd'] == $id_opd) $valid = true;
                    }
                    if (!$valid && $id_opd != $id_opd_admin) $id_opd = $id_opd_admin;
                }
            } else {
                $id_opd = $id_opd_admin;
            }
        }
        // =========================================================================

        $tgl_awal_bulan = sprintf('%04d-%02d-01', $tahun, $bulan);
        $tgl_akhir_bulan = date('Y-m-t', strtotime($tgl_awal_bulan));

        $is_gabungan = false;
        $prefix_gabungan = '';
        if ($id_opd && strpos($id_opd, 'gabungan||') === 0) {
            $is_gabungan = true;
            $prefix_gabungan = str_replace('gabungan||', '', $id_opd);
        }

        $matrix_data = [];
        $list_tanggal = [];

        $hari_singkat = ['Sun' => 'M', 'Mon' => 'S', 'Tue' => 'S', 'Wed' => 'R', 'Thu' => 'K', 'Fri' => 'J', 'Sat' => 'S'];

        $jml_hari = date('t', mktime(0, 0, 0, $bulan, 1, $tahun));
        for ($d = 1; $d <= $jml_hari; $d++) {
            $date_str = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
            $day_eng = date('D', strtotime($date_str));
            $list_tanggal[$d] = [
                'tgl' => $date_str,
                'hari' => $hari_singkat[$day_eng],
                'is_weekend' => ($day_eng == 'Sat' || $day_eng == 'Sun')
            ];
        }

        $libur_db = $db->table('hari_libur')->where("MONTH(tanggal)", $bulan)->where("YEAR(tanggal)", $tahun)->get()->getResultArray();
        $libur_nasional = array_column($libur_db, 'tanggal');

        $jadwal_db = $db->table('jam_kerja_detail')->get()->getResultArray();
        $map_jadwal = [];
        foreach ($jadwal_db as $j) $map_jadwal[$j['id_jam_kerja']][$j['hari']] = $j;

        $master_jam = $db->table('jam_kerja')->get()->getResultArray();
        $map_jam_name = [];
        foreach ($master_jam as $mj) $map_jam_name[$mj['id_jam_kerja']] = $mj['nama_jadwal'] ?? 'Reguler';

        if ($id_opd) {
            $builder_pegawai = $db->table('pegawai p')
                ->select('p.id_pegawai, p.nip, p.nama_lengkap, p.eselon, p.jabatan, o.nama_opd, o.id_jam_kerja as id_jam_opd')
                ->join('opd o', 'p.id_opd = o.id_opd', 'left');

            if ($is_gabungan) $builder_pegawai->like('o.nama_opd', $prefix_gabungan, 'both');
            elseif ($id_opd == 'rekap_eselon_2') $builder_pegawai->whereIn('p.eselon', ['II.a', 'II.b']);
            elseif ($id_opd == 'rekap_eselon_3') $builder_pegawai->whereIn('p.eselon', ['III.a', 'III.b']);
            elseif ($id_opd == 'rekap_eselon_4') $builder_pegawai->whereIn('p.eselon', ['IV.a', 'IV.b']);
            elseif ($id_opd == 'rekap_eselon_gabungan') $builder_pegawai->whereIn('p.eselon', ['II.a', 'II.b', 'III.a', 'III.b', 'IV.a', 'IV.b']);
            else {
                $builder_pegawai->where("(
                    p.id_pegawai IN (
                        SELECT id_pegawai FROM riwayat_penempatan 
                        WHERE id_opd = '$id_opd' 
                        AND tmt_mulai <= '$tgl_akhir_bulan' 
                        AND (tmt_selesai >= '$tgl_awal_bulan' OR tmt_selesai IS NULL)
                    )
                    OR
                    (p.id_opd = '$id_opd' AND p.id_pegawai NOT IN (SELECT id_pegawai FROM riwayat_penempatan))
                )");
            }

            if (!empty($status_asn)) $builder_pegawai->where('p.status_asn', $status_asn);
            if ($is_gabungan) $builder_pegawai->orderBy('o.nama_opd', 'ASC');

            $pegawai = $builder_pegawai
                ->orderBy("CASE 
                    WHEN TRIM(o.nama_opd) LIKE 'SEKRETARIAT DAERAH%' THEN 1
                    WHEN TRIM(o.nama_opd) LIKE 'SEKRETARIAT DPRD%' THEN 2
                    WHEN TRIM(o.nama_opd) LIKE 'INSPEKTORAT%' THEN 3
                    WHEN TRIM(o.nama_opd) LIKE 'BADAN%' THEN 4
                    WHEN TRIM(o.nama_opd) LIKE 'DINAS%' THEN 5
                    WHEN TRIM(o.nama_opd) LIKE 'RSUD%' THEN 6
                    WHEN TRIM(o.nama_opd) LIKE 'KANTOR CAMAT%' THEN 7
                    ELSE 8 END", "ASC", false)
                ->orderBy("CASE 
                    WHEN TRIM(p.eselon) = 'II.a' THEN 1 
                    WHEN TRIM(p.eselon) = 'II.b' THEN 2
                    WHEN TRIM(p.eselon) = 'III.a' THEN 3 
                    WHEN TRIM(p.eselon) = 'III.b' THEN 4
                    WHEN TRIM(p.eselon) = 'IV.a' THEN 5 
                    WHEN TRIM(p.eselon) = 'IV.b' THEN 6
                    ELSE 7 END", "ASC", false)
                ->orderBy('p.nama_lengkap', 'ASC')
                ->get()->getResultArray();

            $nips = array_column($pegawai, 'nip');
            $ids_pegawai = array_column($pegawai, 'id_pegawai');

            $map_plotting = [];
            $is_nakes_map = [];
            $map_cuti = [];
            $map_izin_full = []; // Penampung Izin/Sakit/TL Full Day

            if (!empty($nips)) {
                // 1. Tarik Data Plotting
                $builder_plot = $db->table('plotting_shift')->whereIn('id_pegawai', $ids_pegawai);
                if ($db->fieldExists('tanggal_mulai', 'plotting_shift')) {
                    $builder_plot->where("tanggal_mulai <=", "$tahun-$bulan-31")->where("tanggal_selesai >=", "$tahun-$bulan-01");
                } else {
                    $builder_plot->where("MONTH(tanggal)", $bulan)->where("YEAR(tanggal)", $tahun);
                }
                $plotting_db = $builder_plot->get()->getResultArray();

                foreach ($plotting_db as $pl) {
                    $nama_jadwal_master = strtolower($map_jam_name[$pl['id_jam_kerja']] ?? '');
                    if (strpos($nama_jadwal_master, 'ramadhan') === false && strpos($nama_jadwal_master, 'puasa') === false) {
                        $is_nakes_map[$pl['id_pegawai']] = true;
                    }

                    if (isset($pl['tanggal_mulai'])) {
                        $start = new \DateTime($pl['tanggal_mulai']);
                        $end = new \DateTime($pl['tanggal_selesai']);
                        $end->modify('+1 day');
                        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
                        foreach ($period as $date) {
                            $map_plotting[$pl['id_pegawai']][$date->format('Y-m-d')] = $pl['id_jam_kerja'];
                        }
                    } else {
                        $map_plotting[$pl['id_pegawai']][$pl['tanggal']] = $pl['id_jam_kerja'];
                    }
                }

                // 2. Tarik Data Cuti
                $tgl_awal_bulan = sprintf('%04d-%02d-01', $tahun, $bulan);
                $tgl_akhir_bulan = date('Y-m-t', strtotime($tgl_awal_bulan));

                $cuti_raw = $db->table('pengajuan_cuti')
                    ->select('pengajuan_cuti.*, master_cuti.nama_cuti')
                    ->join('master_cuti', 'master_cuti.id_cuti = pengajuan_cuti.cuti_id')
                    ->whereIn('pegawai_id', $ids_pegawai)
                    ->where("LOWER(TRIM(status))", 'disetujui')
                    ->where("tgl_mulai <= LAST_DAY('$tahun-$bulan-01') AND COALESCE(NULLIF(tgl_selesai, ''), tgl_mulai) >= '$tahun-$bulan-01'")
                    ->get()->getResultArray();

                foreach ($cuti_raw as $c) {
                    $start = new \DateTime($c['tgl_mulai']);
                    $tgl_selesai_val = empty($c['tgl_selesai']) ? $c['tgl_mulai'] : $c['tgl_selesai'];
                    $end = new \DateTime($tgl_selesai_val);
                    $end->modify('+1 day');
                    $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
                    foreach ($period as $date) {
                        if ($date->format('m') == sprintf('%02d', $bulan) && $date->format('Y') == $tahun) {
                            $map_cuti[$c['pegawai_id']][$date->format('Y-m-d')] = $c['nama_cuti'];
                        }
                    }
                }

                // 3. Tarik Data Izin/Sakit/TL
                $izin_raw = $db->table('pengajuan_absen')
                    ->whereIn('nip', $nips)
                    ->where("LOWER(TRIM(status_approval))", 'disetujui')
                    ->where("tanggal_mulai <= LAST_DAY('$tahun-$bulan-01') AND COALESCE(NULLIF(tanggal_selesai, ''), tanggal_mulai) >= '$tahun-$bulan-01'")
                    ->get()->getResultArray();

                foreach ($izin_raw as $iz) {
                    $start_i = new \DateTime(max($iz['tanggal_mulai'], $tgl_awal_bulan));
                    $tgl_selesai_val = empty($iz['tanggal_selesai']) ? $iz['tanggal_mulai'] : $iz['tanggal_selesai'];
                    $end_i   = new \DateTime(min($tgl_selesai_val, $tgl_akhir_bulan));
                    $end_i->modify('+1 day');

                    foreach (new \DatePeriod($start_i, new \DateInterval('P1D'), $end_i) as $dt) {
                        // Tarik hanya Izin Penuh (Full Day)
                        if (empty($iz['slot_waktu']) || strtoupper($iz['slot_waktu']) == 'PENUH') {
                            $map_izin_full[$iz['nip']][$dt->format('Y-m-d')] = strtoupper($iz['kategori']);
                        }
                    }
                }

                // 4. Tarik Data Presensi Harian
                $presensi_raw = $db->table('presensi')
                    ->whereIn('nip', $nips)->where("MONTH(tanggal)", $bulan)->where("YEAR(tanggal)", $tahun)
                    ->get()->getResultArray();

                $presensi_map = [];
                foreach ($presensi_raw as $pr) $presensi_map[$pr['nip']][$pr['tanggal']] = $pr;
            }

            $hari_ini = date('Y-m-d');

            foreach ($pegawai as $pgw) {
                $opd_name = strtoupper($pgw['nama_opd']);
                if (!isset($matrix_data[$opd_name])) $matrix_data[$opd_name] = [];

                $row = [
                    'nama' => $pgw['nama_lengkap'],
                    'nip' => $pgw['nip'],
                    'jabatan' => $pgw['jabatan'] ?? '-',
                    'eselon' => $pgw['eselon'] ?? '-',
                    'absensi' => []
                ];

                for ($d = 1; $d <= $jml_hari; $d++) {
                    $tgl = $list_tanggal[$d]['tgl'];

                    $ada_jadwal_shift_hari_ini = isset($map_plotting[$pgw['id_pegawai']][$tgl]);
                    $punya_histori_shift_bulan_ini = isset($is_nakes_map[$pgw['id_pegawai']]);
                    $is_lepas_jaga = ($punya_histori_shift_bulan_ini && !$ada_jadwal_shift_hari_ini);

                    $status_color = '';
                    $tooltip = '';

                    $p = isset($presensi_map[$pgw['nip']][$tgl]) ? $presensi_map[$pgw['nip']][$tgl] : null;

                    $nama_hari_full = $this->get_nama_hari(date('D', strtotime($tgl)));
                    $active_id_jam_tmp = ($p && !empty($p['id_jam_kerja_snapshot'])) ? $p['id_jam_kerja_snapshot'] : ($ada_jadwal_shift_hari_ini ? $map_plotting[$pgw['id_pegawai']][$tgl] : ($pgw['id_jam_opd'] ?? 1));

                    // DETEKSI CITO
                    $nama_jk_aktif = strtolower($map_jam_name[$active_id_jam_tmp] ?? '');
                    $is_cito = (strpos($nama_jk_aktif, 'cito') !== false || strpos($nama_jk_aktif, 'standby') !== false);

                    $is_hari_libur_override = false;
                    $label_libur_override = '';
                    $is_shift_murni = isset($is_nakes_map[$pgw['id_pegawai']]);

                    if ($is_lepas_jaga) {
                        $is_hari_libur_override = true;
                        $label_libur_override = 'Lepas Jaga (Libur Shift)';
                    } elseif (in_array($tgl, $libur_nasional) && !$is_shift_murni) {
                        $is_hari_libur_override = true;
                        $label_libur_override = 'Libur Nasional';
                    } elseif (!isset($map_jadwal[$active_id_jam_tmp][$nama_hari_full]) && !$is_cito) {
                        // [FITUR BARU]: Nakes CITO tidak kena hukum "Bukan Hari Kerja"
                        $is_hari_libur_override = true;
                        $label_libur_override = $list_tanggal[$d]['is_weekend'] ? 'Libur / Akhir Pekan' : 'Bukan Hari Kerja';
                    }

                    $is_cuti = isset($map_cuti[$pgw['id_pegawai']][$tgl]);
                    $is_izin_full = isset($map_izin_full[$pgw['nip']][$tgl]);

                    // HIERARKI PENGECEKAN
                    if ($is_cuti) {
                        $status_color = 'bg-st-izin';
                        $tooltip = 'CUTI: ' . strtoupper($map_cuti[$pgw['id_pegawai']][$tgl]);
                    } elseif ($is_izin_full) {
                        $status_color = 'bg-st-izin';
                        $tooltip = $map_izin_full[$pgw['nip']][$tgl];
                    } elseif ($is_hari_libur_override) {
                        $status_color = 'bg-libur';
                        $tooltip = $label_libur_override;
                    } elseif ($p && ($p['kategori'] == 'Hadir' || strpos($p['kategori'], 'Apel') !== false)) {
                        if (!empty($p['jam_masuk_aktual']) && $p['jam_masuk_aktual'] > '04:00:00') {
                            $st_final = $p['status_masuk'];

                            if (empty($st_final)) {
                                if ($is_cito) {
                                    $st_final = 'Hadir (Standby)';
                                } else {
                                    $config = $map_jadwal[$active_id_jam_tmp][$nama_hari_full] ?? null;
                                    if ($config) {
                                        $diff = (strtotime($p['jam_masuk_aktual']) - strtotime(str_replace('.', ':', $config['jam_masuk']))) / 60;
                                        if ($diff <= 0) $st_final = 'Sangat Baik / Istimewa';
                                        elseif ($diff <= ($config['batas_baik_menit'] ?? 15)) $st_final = 'Baik';
                                        elseif ($diff <= ($config['batas_cukup_menit'] ?? 45)) $st_final = 'Cukup Baik';
                                        elseif ($diff <= ($config['batas_telat_menit'] ?? 90)) $st_final = 'Terlambat';
                                        elseif ($diff <= ($config['batas_bina_menit'] ?? 150)) $st_final = 'Pembinaan';
                                        else $st_final = 'Teguran';
                                    } else {
                                        $st_final = 'Hadir';
                                    }
                                }
                            }

                            $lbl_m = strtolower($st_final);
                            if (strpos($lbl_m, 'istimewa') !== false || strpos($lbl_m, 'sangat baik') !== false) $status_color = 'bg-st-istimewa';
                            elseif ($lbl_m == 'baik') $status_color = 'bg-st-baik';
                            elseif (strpos($lbl_m, 'cukup') !== false) $status_color = 'bg-st-cukup';
                            elseif (strpos($lbl_m, 'terlambat') !== false) $status_color = 'bg-st-telat';
                            elseif (strpos($lbl_m, 'pembinaan') !== false) $status_color = 'bg-st-bina';
                            elseif (strpos($lbl_m, 'teguran') !== false) $status_color = 'bg-st-teguran';
                            elseif (strpos($lbl_m, 'apel') !== false) $status_color = 'bg-st-apel';
                            elseif (strpos($lbl_m, 'standby') !== false) $status_color = 'bg-info text-dark border-info';
                            else $status_color = 'bg-st-baik';

                            $tooltip = ucwords($st_final) . ' (' . date('H:i', strtotime($p['jam_masuk_aktual'])) . ')';
                        } else {
                            $status_color = 'bg-alpa';
                            $tooltip = 'Masuk Kosong';
                        }
                    } elseif ($p && !empty($p['kategori'])) {
                        // Backup jika terisi manual di presensi
                        $status_color = 'bg-st-izin';
                        $tooltip = strtoupper($p['kategori']);
                    } else {
                        // TIDAK ADA REKAM JEJAK SAMA SEKALI
                        if ($tgl > $hari_ini) {
                            $status_color = '';
                            $tooltip = 'Belum Ada Data';
                        } elseif ($is_cito) {
                            $status_color = 'bg-info text-dark border-info';
                            $tooltip = 'Standby (Bebas Alpa)';
                        } else {
                            $status_color = 'bg-alpa';
                            $tooltip = 'Alpa / Tanpa Keterangan';
                        }
                    }
                    $is_late = in_array($status_color, ['bg-st-telat', 'bg-st-bina', 'bg-st-teguran']);
                    $row['absensi'][$d] = [
                        'color' => $status_color,
                        'title' => $tooltip,
                        'id_presensi' => $p ? $p['id_presensi'] : null,
                        'nip' => $pgw['nip'],
                        'tanggal' => $tgl,
                        'is_late' => $is_late
                    ];
                }
                $matrix_data[$opd_name][] = $row;
            }
        }

        return view('admin/rekap_bulanan_matriks', [
            'title'               => 'Monitoring Implementasi Role Model',
            'list_opd'            => $list_opd,
            'list_gabungan'       => $list_gabungan,
            'selected_opd'        => $id_opd,
            'selected_status_asn' => $status_asn,
            'bulan'               => $bulan,
            'tahun'               => $tahun,
            'list_tanggal'        => $list_tanggal,
            'matrix_data'         => $matrix_data,
            'is_induk_opd'        => $is_induk_opd,
            'child_opds'          => $child_opds,
            'admin_opd_name'      => $admin_opd_name
        ]);
    }

    public function suspend_presensi()
    {
        if (session()->get('level') !== 'kabupaten' && session()->get('level') !== 'Superadmin' && session()->get('level') !== 'opd') {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $id_presensi = $this->request->getPost('id_presensi');
        $alasan = $this->request->getPost('alasan_tangguh');

        if ($id_presensi) {
            $db = \Config\Database::connect();
            $db->table('presensi')->where('id_presensi', $id_presensi)->update([
                'status_verifikasi' => 'Ditangguhkan',
                'alasan_tangguh' => $alasan
            ]);
            return redirect()->back()->with('success', 'Presensi berhasil ditangguhkan.');
        }
        return redirect()->back()->with('error', 'Data tidak ditemukan.');
    }

    public function restore_presensi()
    {
        if (session()->get('level') !== 'kabupaten' && session()->get('level') !== 'Superadmin' && session()->get('level') !== 'opd') {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $id_presensi = $this->request->getPost('id_presensi');
        if ($id_presensi) {
            $db = \Config\Database::connect();
            $db->table('presensi')->where('id_presensi', $id_presensi)->update([
                'status_verifikasi' => 'Valid',
                'alasan_tangguh' => null,
                'kategori' => 'Hadir'
            ]);
            return redirect()->back()->with('success', 'Presensi berhasil dipulihkan.');
        }
        return redirect()->back()->with('error', 'Data tidak ditemukan.');
    }

    public function anulir_permanen()
    {
        if (session()->get('level') !== 'kabupaten' && session()->get('level') !== 'Superadmin' && session()->get('level') !== 'opd') {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $id_presensi = $this->request->getPost('id_presensi');
        if ($id_presensi) {
            $db = \Config\Database::connect();
            $db->table('presensi')->where('id_presensi', $id_presensi)->update([
                'status_verifikasi' => 'Dianulir',
                'kategori' => 'Alpha'
            ]);
            return redirect()->back()->with('success', 'Presensi dianulir permanen (Alpha).');
        }
        return redirect()->back()->with('error', 'Data tidak ditemukan.');
    }
    // =========================================================================
    // FITUR SMART CORRECTION MATRIKS (AJAX)
    // =========================================================================
    public function ajax_koreksi_terlambat()
    {
        $session = session();
        if (trim(strtolower($session->get('username'))) !== 'superadmin') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Anda tidak memiliki akses (Khusus Username Superadmin).']);
        }

        $nip = $this->request->getPost('nip');
        $tanggal = $this->request->getPost('tanggal');
        $status_baru = $this->request->getPost('status_baru');
        $jam_baru = $this->request->getPost('jam_baru');
        $jam_pulang = $this->request->getPost('jam_pulang');

        if (empty($nip) || empty($tanggal) || empty($status_baru)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        }

        $db = \Config\Database::connect();

        $presensi = $db->table('presensi')->where('nip', $nip)->where('tanggal', $tanggal)->get()->getRow();
        if (!$presensi) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data presensi tidak ditemukan di database.']);
        }

        // Tentukan warna baru berdasarkan status
        $new_color = 'bg-st-baik'; // default
        $lbl_m = strtolower($status_baru);
        if (strpos($lbl_m, 'istimewa') !== false || strpos($lbl_m, 'sangat baik') !== false) $new_color = 'bg-st-istimewa';
        elseif (strpos($lbl_m, 'baik') !== false) $new_color = 'bg-st-baik';
        elseif (strpos($lbl_m, 'cukup') !== false) $new_color = 'bg-st-cukup';
        elseif (strpos($lbl_m, 'terlambat') !== false) $new_color = 'bg-st-telat';
        elseif (strpos($lbl_m, 'pembinaan') !== false) $new_color = 'bg-st-bina';
        elseif (strpos($lbl_m, 'teguran') !== false) $new_color = 'bg-st-teguran';

        $update_data = [
            'status_masuk' => $status_baru,
            'kategori' => 'Hadir' // Selalu pastikan kategori menjadi Hadir agar terhitung di TPP
        ];

        // --- PASTIKAN JAM MASUK TERISI (Untuk syarat TPP) ---
        if (!empty($jam_baru)) {
            $update_data['jam_masuk_aktual'] = $jam_baru . ':00';
        } else {
            if (empty($presensi->jam_masuk_aktual) || $presensi->jam_masuk_aktual == '00:00:00') {
                $update_data['jam_masuk_aktual'] = isset($presensi->jam_masuk_seharusnya) ? $presensi->jam_masuk_seharusnya : '07:30:00';
            }
        }

        // --- PASTIKAN JAM PULANG TERISI (Untuk syarat TPP) ---
        if (!empty($jam_pulang)) {
            $update_data['jam_pulang_aktual'] = $jam_pulang . ':00';
            $update_data['status_pulang'] = 'Selesai';
        } else {
            if (empty($presensi->jam_pulang_aktual) || $presensi->jam_pulang_aktual == '00:00:00' || $presensi->jam_pulang_aktual <= '04:00:00') {
                $update_data['jam_pulang_aktual'] = isset($presensi->jam_pulang_seharusnya) ? $presensi->jam_pulang_seharusnya : '16:00:00';
                $update_data['status_pulang'] = 'Selesai';
            }
        }

        // --- REKALKULASI MENIT KETERLAMBATAN UNTUK REKAP TPP ---
        if (in_array($new_color, ['bg-st-baik', 'bg-st-istimewa', 'bg-st-cukup'])) {
            // Jika tepat waktu / cepat, hapus denda keterlambatan
            $update_data['menit_keterlambatan'] = 0;
        } else {
            // Jika terlambat
            if (!empty($jam_baru)) {
                $jam_seharusnya = isset($presensi->jam_masuk_seharusnya) ? $presensi->jam_masuk_seharusnya : '07:30:00';
                $diff = strtotime($jam_baru . ':00') - strtotime($jam_seharusnya);
                $update_data['menit_keterlambatan'] = max(0, floor($diff / 60));
            } elseif ($presensi->menit_keterlambatan == 0) {
                $update_data['menit_keterlambatan'] = 60; // denda default 1 jam jika jam_baru kosong
            }
        }

        try {
            $db->table('presensi')->where('id_presensi', $presensi->id_presensi)->update($update_data);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Status kehadiran berhasil dikoreksi.',
                'new_color' => $new_color
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function ajax_koreksi_terlambat_massal()
    {
        $session = session();
        if (trim(strtolower($session->get('username'))) !== 'superadmin') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Anda tidak memiliki akses (Khusus Username Superadmin).']);
        }

        $json_data = $this->request->getJSON();
        if (!$json_data || empty($json_data->data_koreksi) || empty($json_data->status_baru)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        }

        $db = \Config\Database::connect();
        $status_baru = $json_data->status_baru;
        $jam_baru = isset($json_data->jam_baru) ? $json_data->jam_baru : '';
        $jam_pulang = isset($json_data->jam_pulang) ? $json_data->jam_pulang : '';

        // Tentukan warna baru berdasarkan status
        $new_color = 'bg-st-baik'; // default
        $lbl_m = strtolower($status_baru);
        if (strpos($lbl_m, 'istimewa') !== false || strpos($lbl_m, 'sangat baik') !== false) $new_color = 'bg-st-istimewa';
        elseif (strpos($lbl_m, 'baik') !== false) $new_color = 'bg-st-baik';
        elseif (strpos($lbl_m, 'cukup') !== false) $new_color = 'bg-st-cukup';
        elseif (strpos($lbl_m, 'terlambat') !== false) $new_color = 'bg-st-telat';
        elseif (strpos($lbl_m, 'pembinaan') !== false) $new_color = 'bg-st-bina';
        elseif (strpos($lbl_m, 'teguran') !== false) $new_color = 'bg-st-teguran';

        $success_count = 0;

        $db->transStart();
        foreach ($json_data->data_koreksi as $item) {
            $nip = $item->nip;
            $tanggal = $item->tanggal;

            $presensi = $db->table('presensi')->where('nip', $nip)->where('tanggal', $tanggal)->get()->getRow();
            if ($presensi) {
                $update_data = [
                    'status_masuk' => $status_baru,
                    'kategori' => 'Hadir'
                ];

                if (!empty($jam_baru)) {
                    $update_data['jam_masuk_aktual'] = $jam_baru . ':00';
                } else {
                    if (empty($presensi->jam_masuk_aktual) || $presensi->jam_masuk_aktual == '00:00:00') {
                        $update_data['jam_masuk_aktual'] = isset($presensi->jam_masuk_seharusnya) ? $presensi->jam_masuk_seharusnya : '07:30:00';
                    }
                }

                if (!empty($jam_pulang)) {
                    $update_data['jam_pulang_aktual'] = $jam_pulang . ':00';
                    $update_data['status_pulang'] = 'Selesai';
                } else {
                    if (empty($presensi->jam_pulang_aktual) || $presensi->jam_pulang_aktual == '00:00:00' || $presensi->jam_pulang_aktual <= '04:00:00') {
                        $update_data['jam_pulang_aktual'] = isset($presensi->jam_pulang_seharusnya) ? $presensi->jam_pulang_seharusnya : '16:00:00';
                        $update_data['status_pulang'] = 'Selesai';
                    }
                }

                if (in_array($new_color, ['bg-st-baik', 'bg-st-istimewa', 'bg-st-cukup'])) {
                    $update_data['menit_keterlambatan'] = 0;
                } else {
                    if (!empty($jam_baru)) {
                        $jam_seharusnya = isset($presensi->jam_masuk_seharusnya) ? $presensi->jam_masuk_seharusnya : '07:30:00';
                        $diff = strtotime($jam_baru . ':00') - strtotime($jam_seharusnya);
                        $update_data['menit_keterlambatan'] = max(0, floor($diff / 60));
                    } elseif ($presensi->menit_keterlambatan == 0) {
                        $update_data['menit_keterlambatan'] = 60;
                    }
                }

                $db->table('presensi')->where('id_presensi', $presensi->id_presensi)->update($update_data);
                $success_count++;
            }
        }
        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data.'
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => $success_count . ' data kehadiran berhasil dikoreksi.',
            'new_color' => $new_color
        ]);
    }
}

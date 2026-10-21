<?php

namespace App\Controllers;

class Pwa extends BaseController
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
    // [FUNGSI BARU]: KIRIM PUSH NOTIF CEPAT (ANTI-LEMOT UNTUK FRONTEND PWA)
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
    // HELPER OPTIMASI CPU SERVER (Mencegah Database Overload 1500%)
    // ========================================================================
    private function checkFieldPlotting()
    {
        $status = cache('db_has_tgl_mulai');
        if ($status === null) {
            $status = $this->db->fieldExists('tanggal_mulai', 'plotting_shift');
            cache()->save('db_has_tgl_mulai', $status, 86400 * 30); // Simpan 30 hari di RAM
        }
        return $status;
    }

    private function getPlottingShiftPegawai($id_pegawai, $tanggal)
    {
        try {
            $has_tanggal_mulai = $this->checkFieldPlotting();
            $res = $this->db->table('plotting_shift')->where(['id_pegawai' => $id_pegawai, 'tanggal' => $tanggal])->get();
            $row = $res ? $res->getRow() : null;
            if (!$row && $has_tanggal_mulai) {
                $res2 = $this->db->table('plotting_shift')
                    ->where('id_pegawai', $id_pegawai)
                    ->where('tanggal_mulai <=', $tanggal)
                    ->where('tanggal_selesai >=', $tanggal)
                    ->get();
                $row = $res2 ? $res2->getRow() : null;
            }
            return $row;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * [SMART AUTO-HEAL]: Otomatis Memulihkan Anomali Shift Tertukar (RSUD / Nakes)
     * Kasus: Tanggal D-1 masuk malam (>= 17:30) tapi pulang kosong.
     * Lalu Tanggal D masuk pagi (< 13:00) dan pulang malam (>= 17:30) karena tertukar.
     */
    private function checkAndHealShiftAnomaly($id_pegawai)
    {
        try {
            $startDate = date('Y-m-d', strtotime('-7 days'));
            $rows = $this->db->table('presensi')
                ->where('id_pegawai', $id_pegawai)
                ->where('tanggal >=', $startDate)
                ->orderBy('tanggal', 'ASC')
                ->get()->getResultArray();

            if (count($rows) < 2) return false;

            $healed = false;
            for ($i = 0; $i < count($rows) - 1; $i++) {
                $cur = $rows[$i];
                $next = $rows[$i + 1];

                $diffDays = (strtotime($next['tanggal']) - strtotime($cur['tanggal'])) / 86400;
                if ($diffDays != 1) continue;

                $jp_cur = trim((string)($cur['jam_pulang_aktual'] ?? ''));
                $is_cur_unclosed = (empty($jp_cur) || $jp_cur === '00:00:00' || $jp_cur === '-' || $jp_cur === 'NULL');

                $jm_cur = trim((string)($cur['jam_masuk_aktual'] ?? ''));
                $jm_cur_time = (strlen($jm_cur) > 8) ? date('H:i:s', strtotime($jm_cur)) : $jm_cur;
                $is_cur_night_in = (!empty($jm_cur_time) && ($jm_cur_time >= '17:30:00' || $jm_cur_time <= '04:00:00'));

                $jm_next = trim((string)($next['jam_masuk_aktual'] ?? ''));
                $jm_next_time = (strlen($jm_next) > 8) ? date('H:i:s', strtotime($jm_next)) : $jm_next;
                $is_next_morning_in = (!empty($jm_next_time) && $jm_next_time >= '05:00:00' && $jm_next_time <= '13:00:00');

                $jp_next = trim((string)($next['jam_pulang_aktual'] ?? ''));
                $jp_next_time = (strlen($jp_next) > 8) ? date('H:i:s', strtotime($jp_next)) : $jp_next;
                $is_next_night_out = (!empty($jp_next_time) && ($jp_next_time >= '17:30:00' || $jp_next_time <= '04:00:00'));

                if ($is_cur_unclosed && $is_cur_night_in && $is_next_morning_in && $is_next_night_out) {
                    $this->db->table('presensi')->where('id_presensi', $cur['id_presensi'])->update([
                        'jam_pulang_aktual' => $jm_next,
                        'status_pulang'     => 'Selesai',
                        'updated_at'        => date('Y-m-d H:i:s')
                    ]);

                    $this->db->table('presensi')->where('id_presensi', $next['id_presensi'])->update([
                        'jam_masuk_aktual'  => $jp_next,
                        'jam_pulang_aktual' => null,
                        'status_masuk'      => 'Sangat Baik / Istimewa',
                        'status_pulang'     => null,
                        'updated_at'        => date('Y-m-d H:i:s')
                    ]);

                    $healed = true;
                }
            }
            return $healed;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function index()
    {
        $id_pegawai = session()->get('id_pegawai');

        if (!$id_pegawai || !session()->get('logged_in')) {
            session()->destroy();
            return redirect()->to('/authpegawai');
        }

        session_write_close();

        // [FITUR BARU]: Tambahkan select latitude, longitude, dan radius_opd untuk ditarik ke Frontend
        $pegawai = $this->db->table('pegawai')
            ->select('pegawai.*, opd.id_jam_kerja as id_jam_opd, opd.nama_opd, opd.latitude as lat_pusat, opd.longitude as lng_pusat, opd.radius as radius_opd')
            ->join('opd', 'pegawai.id_opd = opd.id_opd', 'left')
            ->where('id_pegawai', $id_pegawai)
            ->get()->getRowArray();

        if (!$pegawai) {
            return redirect()->to('/authpegawai');
        }

        $tanggal_hari_ini = date('Y-m-d');
        $nama_hari_indo = $this->get_nama_hari(date('D'));
        $has_tanggal_mulai = $this->checkFieldPlotting();

        if (!$libur_data = cache('libur_global_cache')) {
            $libur_data = [
                'hari_ini' => $this->db->table('hari_libur')->where('tanggal', $tanggal_hari_ini)->get()->getRowArray(),
                'mendatang' => $this->db->table('hari_libur')->where('tanggal >', $tanggal_hari_ini)->limit(5)->get()->getResultArray()
            ];
            cache()->save('libur_global_cache', $libur_data, 3600);
        }

        $cek_plotting = $this->db->table('plotting_shift')->where(['id_pegawai' => $id_pegawai, 'tanggal' => $tanggal_hari_ini])->get()->getRow();
        if (!$cek_plotting && $has_tanggal_mulai) {
            $cek_plotting = $this->db->table('plotting_shift')
                ->where('id_pegawai', $id_pegawai)
                ->where('tanggal_mulai <=', $tanggal_hari_ini)
                ->where('tanggal_selesai >=', $tanggal_hari_ini)
                ->get()->getRow();
        }

        $is_lepas_jaga = false;
        if (!$cek_plotting) {
            $bulan_ini = date('m');
            $tahun_ini = date('Y');

            $builder_shift = $this->db->table('plotting_shift')
                ->join('jam_kerja', 'jam_kerja.id_jam_kerja = plotting_shift.id_jam_kerja', 'left')
                ->where('plotting_shift.id_pegawai', $id_pegawai)
                ->where('jam_kerja.nama_jadwal NOT LIKE', '%ramadhan%')
                ->where('jam_kerja.nama_jadwal NOT LIKE', '%puasa%')
                ->where('jam_kerja.nama_jadwal NOT LIKE', '%umum%')
                ->where('jam_kerja.nama_jadwal NOT LIKE', '%hari kerja%');

            $start_date_month = sprintf('%04d-%02d-01', $tahun_ini, $bulan_ini);
            $end_date_month = date('Y-m-t', strtotime($start_date_month));

            if ($has_tanggal_mulai) {
                $builder_shift->where('tanggal_mulai >=', $start_date_month)
                    ->where('tanggal_mulai <=', $end_date_month);
            } else {
                $builder_shift->where('tanggal >=', $start_date_month)
                    ->where('tanggal <=', $end_date_month);
            }

            if ($builder_shift->countAllResults() > 0) {
                $is_lepas_jaga = true;
            }
        }

        $filter_bulan = $this->request->getGet('bulan') ?? date('m');
        $filter_tahun = $this->request->getGet('tahun') ?? date('Y');

        // [SMART AUTO-HEAL]: Periksa dan pulihkan anomali shift tertukar otomatis sebelum render
        $this->checkAndHealShiftAnomaly($id_pegawai);

        $start_date = sprintf('%04d-%02d-01', $filter_tahun, $filter_bulan);
        $end_date   = date('Y-m-t', strtotime($start_date));

        $riwayat = $this->db->table('presensi')
            ->where('id_pegawai', $id_pegawai)
            ->where('tanggal >=', $start_date)
            ->where('tanggal <=', $end_date)
            ->orderBy('tanggal', 'DESC')
            ->get()->getResultArray();

        $terakhir = null;
        foreach ($riwayat as $r) {
            if ($r['tanggal'] == $tanggal_hari_ini) {
                $terakhir = $r;
                break;
            }
        }

        $is_pending_shift_malam = false;
        $jam_sekarang_dash = date('H:i:s');
        $today_has_real_masuk = (!empty($terakhir['jam_masuk_aktual']) && $terakhir['jam_masuk_aktual'] !== '00:00:00' && $terakhir['jam_masuk_aktual'] !== '-');

        if (!$today_has_real_masuk && $jam_sekarang_dash <= '14:30:00') {
            $cek_kemarin = $this->db->table('presensi')->where(['id_pegawai' => $id_pegawai, 'tanggal' => date('Y-m-d', strtotime('-1 days'))])->get()->getRowArray();

            if ($cek_kemarin) {
                $jp_aktual = trim((string)($cek_kemarin['jam_pulang_aktual'] ?? ''));
                $is_unclosed = (empty($jp_aktual) || $jp_aktual === '00:00:00' || $jp_aktual === '-' || $jp_aktual === 'NULL');

                $jm_kemarin_time = trim((string)($cek_kemarin['jam_masuk_aktual'] ?? ''));
                if (strlen($jm_kemarin_time) > 8) $jm_kemarin_time = date('H:i:s', strtotime($jm_kemarin_time));
                $is_masuk_malam_kemarin = (!empty($jm_kemarin_time) && ($jm_kemarin_time >= '17:30:00' || $jm_kemarin_time <= '04:00:00'));

                $jm_target_kemarin = trim((string)($cek_kemarin['jam_masuk_seharusnya'] ?? ''));
                $is_jadwal_malam_kemarin = (!empty($jm_target_kemarin) && $jm_target_kemarin >= '17:30:00');

                $jp_target_kemarin = trim((string)($cek_kemarin['jam_pulang_seharusnya'] ?? ''));
                $is_cross_midnight = (!empty($jp_target_kemarin) && !empty($jm_target_kemarin) && strtotime(substr($jp_target_kemarin, -8)) <= strtotime(substr($jm_target_kemarin, -8)));

                $is_dinas_malam_kemarin = ($is_masuk_malam_kemarin || $is_jadwal_malam_kemarin || $is_cross_midnight);

                if ($is_unclosed && $is_dinas_malam_kemarin) {
                    $terakhir = $cek_kemarin;
                    $is_pending_shift_malam = true;
                }
            }
        }

        if ($is_pending_shift_malam) {
            $jadwal_hari_ini = [
                'jam_masuk'  => $terakhir['jam_masuk_seharusnya'],
                'jam_pulang' => $terakhir['jam_pulang_seharusnya'],
                'hari'       => 'Penyelesaian Shift Kemarin'
            ];
            $is_lepas_jaga = false;
        } else {
            if ($is_lepas_jaga) {
                $jadwal_hari_ini = [
                    'jam_masuk'  => 'LEPAS',
                    'jam_pulang' => 'JAGA',
                    'hari'       => $nama_hari_indo
                ];
            } else {
                $id_final_jam = ($cek_plotting) ? $cek_plotting->id_jam_kerja : ($pegawai['id_jam_opd'] ?? 1);
                $jadwal_hari_ini = $this->db->table('jam_kerja_detail')
                    ->where(['id_jam_kerja' => $id_final_jam, 'hari' => $nama_hari_indo])
                    ->get()->getRowArray();
            }
        }

        $status_libur_aktif = $libur_data['hari_ini'];

        if ($is_pending_shift_malam) {
            $status_libur_aktif = null;
        } elseif ($status_libur_aktif && $cek_plotting) {
            $jam_kerja_info = $this->db->table('jam_kerja')->select('nama_jadwal')->where('id_jam_kerja', $cek_plotting->id_jam_kerja)->get()->getRow();
            if ($jam_kerja_info) {
                $nama_j = strtolower($jam_kerja_info->nama_jadwal);
                if (strpos($nama_j, 'ramadhan') === false && strpos($nama_j, 'puasa') === false && strpos($nama_j, 'umum') === false && strpos($nama_j, 'hari kerja') === false) {
                    $status_libur_aktif = null;
                }
            }
        }

        $tugas_apel = $this->db->table('apel_peserta')
            ->select('apel.*, apel_peserta.jam_absen as sudah_absen_apel')
            ->join('apel', 'apel.id_apel = apel_peserta.id_apel')
            ->where('apel_peserta.id_pegawai', $id_pegawai)
            ->where('apel.tanggal', $tanggal_hari_ini)
            ->get()->getRowArray();

        // AUTO-SYNC KEHADIRAN APEL KE PRESENSI HARIAN (UNTUK PWA & REKAP)
        if ($tugas_apel && !empty($tugas_apel['sudah_absen_apel'])) {
            $jam_apel_clean = (strlen($tugas_apel['sudah_absen_apel']) > 8) ? date('H:i:s', strtotime($tugas_apel['sudah_absen_apel'])) : $tugas_apel['sudah_absen_apel'];
            $jam_mulai_apel = !empty($tugas_apel['jam_mulai']) ? $tugas_apel['jam_mulai'] : '07:30:00';
            $jam_pulang_default = (!empty($jadwal_hari_ini['jam_pulang']) && $jadwal_hari_ini['jam_pulang'] !== 'JAGA') ? $jadwal_hari_ini['jam_pulang'] : '16:00:00';
            $id_final_jam_apel = ($cek_plotting) ? $cek_plotting->id_jam_kerja : ($pegawai['id_jam_opd'] ?? 1);

            $cek_presensi_today = $this->db->table('presensi')
                ->where('id_pegawai', $id_pegawai)
                ->where('tanggal', $tanggal_hari_ini)
                ->get()->getRowArray();

            if (!$cek_presensi_today) {
                $data_apel_insert = [
                    'id_pegawai'            => $id_pegawai,
                    'nip'                   => $pegawai['nip'],
                    'tanggal'               => $tanggal_hari_ini,
                    'jam_masuk_aktual'      => $jam_apel_clean,
                    'jam_masuk_seharusnya'  => $jam_mulai_apel,
                    'jam_pulang_seharusnya' => $jam_pulang_default,
                    'menit_keterlambatan'   => 0,
                    'status_masuk'          => 'Hadir (Apel)',
                    'kategori'              => 'Hadir',
                    'device_info'           => 'Sinkronisasi Kehadiran Apel PWA',
                    'id_jam_kerja_snapshot' => $id_final_jam_apel,
                    'deskripsi'             => "Hadir apel pada kegiatan " . $tugas_apel['nama_apel'],
                    'created_at'            => date('Y-m-d H:i:s')
                ];
                $this->db->table('presensi')->insert($data_apel_insert);
                $terakhir = $this->db->table('presensi')->where('id_pegawai', $id_pegawai)->where('tanggal', $tanggal_hari_ini)->get()->getRowArray();
                array_unshift($riwayat, $terakhir);
            } else {
                $needs_update = false;
                $update_apel_presensi = [];

                if (empty($cek_presensi_today['jam_masuk_aktual']) || $cek_presensi_today['jam_masuk_aktual'] == '00:00:00') {
                    $update_apel_presensi['jam_masuk_aktual'] = $jam_apel_clean;
                    $needs_update = true;
                }
                if ($cek_presensi_today['status_masuk'] !== 'Hadir (Apel)') {
                    $update_apel_presensi['status_masuk'] = 'Hadir (Apel)';
                    $update_apel_presensi['kategori'] = 'Hadir';
                    $update_apel_presensi['menit_keterlambatan'] = 0;
                    $needs_update = true;
                }
                if (empty($cek_presensi_today['jam_masuk_seharusnya']) || $cek_presensi_today['jam_masuk_seharusnya'] == '00:00:00') {
                    $update_apel_presensi['jam_masuk_seharusnya'] = $jam_mulai_apel;
                    $needs_update = true;
                }
                if (empty($cek_presensi_today['jam_pulang_seharusnya']) || $cek_presensi_today['jam_pulang_seharusnya'] == '00:00:00') {
                    $update_apel_presensi['jam_pulang_seharusnya'] = $jam_pulang_default;
                    $needs_update = true;
                }

                if ($needs_update) {
                    $update_apel_presensi['updated_at'] = date('Y-m-d H:i:s');
                    $this->db->table('presensi')->where('id_presensi', $cek_presensi_today['id_presensi'])->update($update_apel_presensi);
                    $terakhir = $this->db->table('presensi')->where('id_presensi', $cek_presensi_today['id_presensi'])->get()->getRowArray();
                    foreach ($riwayat as $k => $rw) {
                        if ($rw['id_presensi'] == $terakhir['id_presensi']) {
                            $riwayat[$k] = $terakhir;
                            break;
                        }
                    }
                } else {
                    $terakhir = $cek_presensi_today;
                }
            }
        }

        $pengajuan_aktif = $this->db->table('pengajuan_absen')
            ->where('nip', $pegawai['nip'])
            ->where('tanggal_mulai <=', $tanggal_hari_ini)
            ->where('tanggal_selesai >=', $tanggal_hari_ini)
            ->orderBy('created_at', 'DESC')
            ->get()->getRowArray();

        $cito_aktif = null;
        if (isset($pegawai['is_cito']) && $pegawai['is_cito'] == 1) {
            $cito_aktif = $this->db->table('presensi_darurat')
                ->where('id_pegawai', $id_pegawai)
                ->where('tanggal', $tanggal_hari_ini)
                ->groupStart()
                ->where('jam_selesai', '00:00:00')
                ->orWhere('jam_selesai', null)
                ->orWhere('jam_selesai', '')
                ->groupEnd()
                ->get()->getRowArray();
        }

        // =========================================================================
        // [FITUR BARU]: PERSIAPAN DATA LOKASI UNTUK LIVE DISTANCE DI FRONTEND
        // =========================================================================
        $vip_nips = [
            '199006182011011002',
            '199003192019022001',
            '198908082010011001',
            '198401072002121002',
            '197502152005021002',
            '198712302025211129',
            '198011302006042020',
            '197608312005021002',
            '198403232025211119',
            '198808102025211214',
            '198006212009011005',
            '197812312007011082',
            '199602252020121001',
            '198904122020121001',
            '7604040505970004',
            '7604086611020001',
            '198310042009012005',
            '199002062015032012',
            '7604084203980002',
            '197507172007012030',
            '7604064401900004',
            '198212252014122002',
            '198107212010011001',
            '199612052025211080',
            '198307172006042030',
            '197709292002122005',
            '197406242007011021',
            '198509292025212073',
            '198712172007012001',
            '199009212025212085',
            '198212252014122002',
            '199007012025211126',
            '198911272025211100',
            '197910052002122007'
        ];

        $bebas_radius = false;
        if (in_array($pegawai['nip'], $vip_nips)) {
            $bebas_radius = true;
        } else if (isset($pegawai['is_wfa']) && $pegawai['is_wfa'] == 1) {
            if (!empty($pegawai['wfa_mulai']) && !empty($pegawai['wfa_selesai'])) {
                if ($tanggal_hari_ini >= $pegawai['wfa_mulai'] && $tanggal_hari_ini <= $pegawai['wfa_selesai']) $bebas_radius = true;
            } else {
                $bebas_radius = true;
            }
        }

        $multi_lokasi = [];
        if (!empty($pegawai['id_lokasi'])) {
            $ids = array_filter(array_map('trim', explode(',', $pegawai['id_lokasi'])));
            $daftar_lokasi = $this->db->table('opd_lokasi')->get()->getResultArray();
            foreach ($daftar_lokasi as $lok) {
                if (in_array((string)$lok['id_lokasi'], $ids)) {
                    $multi_lokasi[] = [
                        'nama' => $lok['nama_lokasi'],
                        'lat'  => (float)$lok['latitude'],
                        'lng'  => (float)$lok['longitude'],
                        'rad'  => (float)($lok['radius_meter'] ?? 60)
                    ];
                }
            }
        }
        if (!empty($pegawai['lat_pusat']) && $pegawai['lat_pusat'] != 0) {
            $multi_lokasi[] = [
                'nama' => 'Kantor Induk',
                'lat'  => (float)$pegawai['lat_pusat'],
                'lng'  => (float)$pegawai['lng_pusat'],
                'rad'  => (float)($pegawai['radius_opd'] ?? 60)
            ];
        }

        // =========================================================================
        // [RADAR CITO]: CEK APAKAH HARI INI JADWALNYA STANDBY / CITO
        // =========================================================================
        $is_hari_ini_cito = false;
        $id_final_jam_cek = ($cek_plotting) ? $cek_plotting->id_jam_kerja : ($pegawai['id_jam_opd'] ?? 1);
        $jam_kerja_aktif = $this->db->table('jam_kerja')->where('id_jam_kerja', $id_final_jam_cek)->get()->getRow();

        if ($jam_kerja_aktif && (strpos(strtolower($jam_kerja_aktif->nama_jadwal), 'cito') !== false || strpos(strtolower($jam_kerja_aktif->nama_jadwal), 'standby') !== false)) {
            $is_hari_ini_cito = true;
        }

        // [BUG FIX]: Mencegah Teguran di UI jika jadwalnya CITO, ubah tampilan menjadi Hadir (Standby)
        if ($is_hari_ini_cito && $terakhir) {
            if (!empty($terakhir['jam_masuk_aktual'])) {
                $terakhir['status_masuk'] = 'Hadir (Standby)';
                $terakhir['menit_keterlambatan'] = 0;
            }
            if (!empty($terakhir['jam_pulang_aktual'])) {
                $terakhir['status_pulang'] = 'Selesai';
            }
        }

        // Hitung estimasi TPP Dinamis (Beban Kerja 60% & Disiplin 40%) jika OPD mengaktifkan prototype
        $tpp_info = null;
        try {
            $tpp_info = \App\Controllers\TppDinamis::get_tpp_pegawai($id_pegawai, $filter_bulan, $filter_tahun);
        } catch (\Throwable $t) {
            log_message('error', 'PWA Dashboard TPP error: ' . $t->getMessage());
        }

        return view('pwa/index', [
            'pegawai'         => $pegawai,
            'jadwal_hari_ini' => $jadwal_hari_ini,
            'libur'           => $status_libur_aktif,
            'libur_mendatang' => $libur_data['mendatang'],
            'terakhir'        => $terakhir,
            'riwayat'         => $riwayat,
            'bulan_aktif'     => $filter_bulan,
            'tahun_aktif'     => $filter_tahun,
            'face_data'       => $pegawai['face_vector'] ?? null,
            'tugas_apel'      => $tugas_apel,
            'pengajuan_aktif' => $pengajuan_aktif,
            'nama_hari_indo'  => $nama_hari_indo,
            'cito_aktif'      => $cito_aktif,
            'is_hari_ini_cito' => $is_hari_ini_cito,
            'bebas_radius'    => $bebas_radius,
            'multi_lokasi'    => $multi_lokasi,
            'tpp_info'        => $tpp_info
        ]);
    }

    public function submit_cito()
    {
        $id_pegawai = session()->get('id_pegawai');

        // Pengecekan Khusus Jika dari Android tapi Session Kosong (Fallback NIP)
        if (!$id_pegawai && $this->request->getPost('nip')) {
            $nip_android = $this->request->getPost('nip');
            $cek_user = $this->db->table('pegawai')->where('nip', $nip_android)->get()->getRow();
            if ($cek_user) {
                $id_pegawai = $cek_user->id_pegawai;
            }
        }

        if (!$id_pegawai) return $this->response->setJSON(['status' => 'error', 'message' => 'Sesi berakhir.']);

        $keterangan = $this->request->getPost('keterangan');
        $lat = $this->request->getPost('latitude');
        $lng = $this->request->getPost('longitude');

        $tanggal = date('Y-m-d');
        $jam = date('H:i:s');

        $pegawai = $this->db->table('pegawai')->where('id_pegawai', $id_pegawai)->get()->getRowArray();
        if ($pegawai['is_cito'] != 1) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Anda tidak memiliki akses fitur Cito/Darurat.']);
        }

        $aktif = $this->db->table('presensi_darurat')
            ->where('id_pegawai', $id_pegawai)
            ->groupStart()
            ->where('jam_selesai', '00:00:00')
            ->orWhere('jam_selesai', null)
            ->orWhere('jam_selesai', '')
            ->groupEnd()
            ->get()->getRowArray();

        $this->db->transStart();
        $notif_judul = "";
        $notif_pesan = "";

        if ($aktif) {
            $this->db->table('presensi_darurat')->where('id_darurat', $aktif['id_darurat'])->update([
                'jam_selesai' => $jam
            ]);
            $msg = 'Absen Selesai Cito / Operasi berhasil dicatat!';

            $notif_judul = "Darurat/Cito Selesai 🟢";
            $notif_pesan = "Terima kasih, waktu tindakan darurat Anda telah diakhiri pukul " . date('H:i', strtotime($jam)) . " WITA.";
        } else {
            if (empty($keterangan)) return $this->response->setJSON(['status' => 'error', 'message' => 'Keterangan wajib diisi.']);
            $this->db->table('presensi_darurat')->insert([
                'id_pegawai'      => $id_pegawai,
                'nip'             => $pegawai['nip'],
                'id_opd'          => $pegawai['id_opd'],
                'tanggal'         => $tanggal,
                'jam_mulai'       => $jam,
                'keterangan'      => $keterangan,
                'status_approval' => 'Disetujui',
                'created_at'      => date('Y-m-d H:i:s')
            ]);
            $msg = 'Absen Mulai Cito / Panggilan Darurat berhasil dicatat!';

            $notif_judul = "Darurat/Cito Dimulai 🔴";
            $notif_pesan = "Harap berhati-hati dalam menjalankan tugas Anda. Jangan lupa akhiri jika sudah selesai.";
        }
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan data ke database.']);
        }

        // KIRIM NOTIFIKASI
        $this->sendFastNotif([$pegawai['nip']], $notif_judul, $notif_pesan);

        return $this->response->setJSON(['status' => 'success', 'message' => $msg, 'notif_title' => $notif_judul, 'notif_content' => $notif_pesan]);
    }

    public function simpan_wajah_pwa()
    {
        $id = session()->get('id_pegawai');
        $v = $this->request->getPost('face_vector');

        if ($v && $this->db->table('pegawai')->where('id_pegawai', $id)->update(['face_vector' => $v])) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Wajah terdaftar!']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal daftar wajah.']);
    }

    public function submit_absen_apel()
    {
        $id_pegawai = session()->get('id_pegawai');

        // Pengecekan Khusus Jika dari Android tapi Session Kosong (Fallback NIP)
        if (!$id_pegawai && $this->request->getPost('nip')) {
            $nip_android = trim($this->request->getPost('nip'));
            $cek_user = $this->db->table('pegawai')->where('nip', $nip_android)->get()->getRow();
            if ($cek_user) {
                $id_pegawai = $cek_user->id_pegawai;
            }
        }

        if (!$id_pegawai) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Sesi login tidak terdeteksi. Silakan muat ulang aplikasi Anda.'
            ]);
        }

        session_write_close();

        $lat_user   = (float) ($this->request->getPost('latitude') ?? $this->request->getPost('lat'));
        $lng_user   = (float) ($this->request->getPost('longitude') ?? $this->request->getPost('lng'));
        $id_apel    = $this->request->getPost('id_apel');

        // Sanitasi id_apel jika Android mengirim string null, 0, dsb
        if (empty($id_apel) || $id_apel === 'null' || $id_apel === 'undefined' || $id_apel === '0' || !is_numeric($id_apel)) {
            $id_apel = null;
        }

        $tanggal_hari_ini = date('Y-m-d');

        // Fallback jika id_apel tidak terkirim atau tidak valid dari APK
        if (empty($id_apel)) {
            $res_tugas = $this->db->table('apel_peserta')
                ->join('apel', 'apel.id_apel = apel_peserta.id_apel')
                ->where('apel_peserta.id_pegawai', $id_pegawai)
                ->where('apel.tanggal', $tanggal_hari_ini)
                ->orderBy('apel.id_apel', 'DESC')
                ->get();
            $cek_tugas = $res_tugas ? $res_tugas->getRow() : null;
            if ($cek_tugas) {
                $id_apel = $cek_tugas->id_apel;
            } else {
                // Cari jadwal apel apa saja yang aktif hari ini
                $res_event_ini = $this->db->table('apel')->where('tanggal', $tanggal_hari_ini)->orderBy('id_apel', 'DESC')->get();
                $event_hari_ini = $res_event_ini ? $res_event_ini->getRow() : null;
                if ($event_hari_ini) {
                    $id_apel = $event_hari_ini->id_apel;
                }
            }
        }

        $this->db->transStart();
        try {
            $tanggal      = date('Y-m-d');
            $jam_sekarang = date('H:i:s');

            $event = null;
            if (!empty($id_apel)) {
                $res_ev = $this->db->table('apel')->where('id_apel', $id_apel)->get();
                $event = $res_ev ? $res_ev->getRow() : null;
            }
            if (!$event) {
                // Coba ambil event apel hari ini
                $res_ev = $this->db->table('apel')->where('tanggal', $tanggal)->orderBy('id_apel', 'DESC')->get();
                $event = $res_ev ? $res_ev->getRow() : null;
                if ($event) {
                    $id_apel = $event->id_apel;
                }
            }

            if (!$event) {
                throw new \Exception('Jadwal apel hari ini tidak ditemukan atau belum diagendakan.');
            }

            if (empty($lat_user) || empty($lng_user) || $lat_user == 0 || $lng_user == 0) {
                throw new \Exception('Gagal mendeteksi lokasi GPS. Pastikan GPS/Lokasi di ponsel aktif dalam akurasi tinggi.');
            }

            // Validasi Jam Buka Apel (Maksimal 120 Menit sebelum jam_mulai)
            if (!empty($event->jam_mulai)) {
                $batas_buka_apel = date('H:i:s', strtotime('-120 minutes', strtotime($event->jam_mulai)));
                if ($jam_sekarang < $batas_buka_apel) {
                    throw new \Exception("Sistem Absen Apel belum dibuka. Anda baru bisa absen paling cepat mulai pukul " . date('H:i', strtotime($batas_buka_apel)) . " WITA.");
                }
            }

            // Validasi Jam Berakhir Apel (Beri toleransi ekstra 60 Menit setelah jam_akhir)
            if (!empty($event->jam_akhir)) {
                $batas_tutup_apel = date('H:i:s', strtotime('+60 minutes', strtotime($event->jam_akhir)));
                if ($jam_sekarang > $batas_tutup_apel) {
                    throw new \Exception("Waktu absen apel telah berakhir pada pukul " . date('H:i', strtotime($batas_tutup_apel)) . " WITA. Silakan lakukan absen reguler.");
                }
            }

            // AMBIL DATA PEGAWAI UNTUK CEK VIP & JADWAL KERJA
            $res_pgw = $this->db->table('pegawai')
                ->select('pegawai.nip, pegawai.id_opd, opd.id_jam_kerja as jam_opd')
                ->join('opd', 'pegawai.id_opd = opd.id_opd', 'left')
                ->where('id_pegawai', $id_pegawai)
                ->get();
            $pegawai = $res_pgw ? $res_pgw->getRow() : null;

            if (!$pegawai) {
                throw new \Exception('Data pegawai tidak ditemukan.');
            }

            // =========================================================
            // [FITUR VIP]: BYPASS RADIUS APEL
            // =========================================================
            $vip_nips = [
                '199006182011011002',
                '199003192019022001',
                '198908082010011001',
                '198401072002121002',
                '197502152005021002',
                '197812312007011082',
                '199003192019022001',
                '199602252020121001',
                '198904122020121001',
                '7604040505970004',
                '7604086611020001',
                '198310042009012005',
                '199002062015032012',
                '7604084203980002',
                '7604064401900004',
                '198212252014122002',
                '198107212010011001',
                '199612052025211080',
                '198307172006042030',
                '197709292002122005',
                '197406242007011021',
                '198509292025212073',
                '198712172007012001',
                '199009212025212085',
                '198212252014122002',
                '199007012025211126',
                '198911272025211100',
                '197910052002122007'
            ];
            $is_vip = in_array($pegawai->nip, $vip_nips);

            if (!$is_vip) {
                // JIKA BUKAN VIP, CEK RADIUS SEPERTI BIASA
                $jarak = $this->calculate_distance($lat_user, $lng_user, (float)$event->latitude, (float)$event->longitude);

                // Radius Toleransi kompensasi GPS Drift Android di lapangan terbuka (Minimal 250m)
                $radius_dasar = (float)($event->radius ?? 150);
                $batas_radius = max($radius_dasar + 100, 250);

                if ($jarak > $batas_radius) {
                    throw new \Exception("Di luar radius upacara! Jarak Anda: " . round($jarak) . " meter dari titik kumpul (Maks: " . round($batas_radius) . "m).");
                }
            }

            // PASTIKAN DATA PESERTA APEL ADA (UPSERT)
            $res_peserta = $this->db->table('apel_peserta')
                ->where(['id_apel' => $id_apel, 'id_pegawai' => $id_pegawai])
                ->get();
            $cek_peserta_ada = $res_peserta ? $res_peserta->getRow() : null;

            if ($cek_peserta_ada) {
                $this->db->table('apel_peserta')
                    ->where(['id_apel' => $id_apel, 'id_pegawai' => $id_pegawai])
                    ->update(['jam_absen' => $jam_sekarang]);
            } else {
                $this->db->table('apel_peserta')->insert([
                    'id_apel'    => $id_apel,
                    'id_pegawai' => $id_pegawai,
                    'jam_absen'  => $jam_sekarang
                ]);
            }

            // PERSIAPKAN DATA JADWAL KERJA (Didefinisikan di awal agar tidak undefined)
            $nama_hari_indo = $this->get_nama_hari(date('D'));
            $cek_plotting = $this->getPlottingShiftPegawai($id_pegawai, $tanggal);
            $id_final_jam = ($cek_plotting) ? $cek_plotting->id_jam_kerja : ($pegawai->jam_opd ?? 1);
            $res_jadwal = $this->db->table('jam_kerja_detail')->where(['id_jam_kerja' => $id_final_jam, 'hari' => $nama_hari_indo])->get();
            $jadwal = $res_jadwal ? $res_jadwal->getRowArray() : [];
            $jam_pulang_seharusnya = $jadwal['jam_pulang'] ?? '16:00:00';
            $jam_seharusnya = !empty($event->jam_mulai) ? $event->jam_mulai : '07:30:00';

            $res_harian = $this->db->table('presensi')->where(['id_pegawai' => $id_pegawai, 'tanggal' => $tanggal])->get();
            $cek_harian = $res_harian ? $res_harian->getRow() : null;

            if (!$cek_harian) {
                $this->db->table('presensi')->insert([
                    'id_pegawai'            => $id_pegawai,
                    'nip'                   => $pegawai->nip,
                    'tanggal'               => $tanggal,
                    'jam_masuk_aktual'      => $jam_sekarang,
                    'jam_masuk_seharusnya'  => $jam_seharusnya,
                    'jam_pulang_seharusnya' => $jam_pulang_seharusnya,
                    'menit_keterlambatan'   => 0,
                    'status_masuk'          => 'Hadir (Apel)',
                    'kategori'              => 'Hadir',
                    'latitude'              => $lat_user,
                    'longitude'             => $lng_user,
                    'device_info'           => $this->request->getPost('device_info') ?? 'Web Browser PWA',
                    'id_jam_kerja_snapshot' => $id_final_jam,
                    'deskripsi'             => "Hadir apel pagi di " . $event->nama_apel . ($is_vip ? " (Akses VIP)" : " (Sistem Otomatis)"),
                    'created_at'            => date('Y-m-d H:i:s')
                ]);
            } else {
                $update_apel_existing = [
                    'status_masuk'          => 'Hadir (Apel)',
                    'kategori'              => 'Hadir',
                    'jam_masuk_aktual'      => $jam_sekarang,
                    'menit_keterlambatan'   => 0,
                    'deskripsi'             => ($cek_harian->deskripsi ? $cek_harian->deskripsi . " | " : "") . "Disinkronkan otomatis dengan kehadiran Apel di " . $event->nama_apel,
                    'updated_at'            => date('Y-m-d H:i:s')
                ];
                if (empty($cek_harian->jam_masuk_seharusnya) || $cek_harian->jam_masuk_seharusnya == '00:00:00') {
                    $update_apel_existing['jam_masuk_seharusnya'] = $jam_seharusnya;
                }
                if (empty($cek_harian->jam_pulang_seharusnya) || $cek_harian->jam_pulang_seharusnya == '00:00:00') {
                    $update_apel_existing['jam_pulang_seharusnya'] = $jam_pulang_seharusnya;
                }
                if (empty($cek_harian->id_jam_kerja_snapshot)) {
                    $update_apel_existing['id_jam_kerja_snapshot'] = $id_final_jam;
                }
                if (!empty($lat_user) && !empty($lng_user)) {
                    $update_apel_existing['latitude'] = $lat_user;
                    $update_apel_existing['longitude'] = $lng_user;
                }
                $this->db->table('presensi')->where('id_presensi', $cek_harian->id_presensi)->update($update_apel_existing);
            }

            $this->db->transComplete();

            // KIRIM NOTIFIKASI APEL BERHASIL
            $this->sendFastNotif([$pegawai->nip], "Absen Apel Berhasil ✅", "Terima kasih, kehadiran Apel Anda telah tercatat dan otomatis terisi sebagai Hadir Pagi.");

            return $this->response->setJSON([
                'status'        => 'success',
                'message'       => "<b>Absen Apel Berhasil!</b><br>Kehadiran Apel Anda tercatat pukul " . substr($jam_sekarang, 0, 5) . " WITA dan otomatis terisi sebagai <b>Hadir Pagi (Masuk)</b>.",
                'notif_title'   => "Presensi Apel Berhasil 🎉",
                'notif_content' => "Kehadiran apel Anda telah tercatat dan dihitung sebagai Hadir Pagi."
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function submit_absen()
    {
        $id_pegawai = session()->get('id_pegawai');
        session_write_close();

        $confirm_double = $this->request->getPost('confirm_double');
        $confirm_pulang = $this->request->getPost('confirm_pulang');

        $lat_user    = (float) ($this->request->getPost('latitude') ?? $this->request->getPost('lat'));
        $lng_user    = (float) ($this->request->getPost('longitude') ?? $this->request->getPost('lng'));
        $device_info = $this->request->getPost('device_info');
        $nip_user    = $this->request->getPost('nip');

        if (empty($lat_user) || empty($lng_user) || $lat_user == 0 || $lng_user == 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mendeteksi lokasi. Pastikan GPS aktif.']);
        }

        if (cache("lock_absen_$id_pegawai")) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Sedang memproses, mohon tunggu...']);
        }
        cache()->save("lock_absen_$id_pegawai", true, 5);

        $this->db->transStart();
        try {
            $tanggal         = date('Y-m-d');
            $jam_sekarang    = date('H:i:s');
            $hari_angka      = date('N');
            $nama_hari_indo  = $this->get_nama_hari(date('D'));
            $jam_mulai_siang = ($hari_angka == 5) ? '11:30:00' : '12:00:00';
            $has_tanggal_mulai = $this->checkFieldPlotting(); // Optimasi CPU

            $cache_user = "user_info_v3_$id_pegawai";
            if (!$user_info = cache($cache_user)) {
                $user_info = $this->db->table('pegawai')
                    ->select('pegawai.*, opd.latitude as lat_pusat, opd.longitude as lng_pusat, opd.radius as radius_opd, opd.id_jam_kerja as jam_opd, opd.nama_opd')
                    ->join('opd', 'pegawai.id_opd = opd.id_opd', 'left')
                    ->where('id_pegawai', $id_pegawai)
                    ->get()->getRow();
                if ($user_info) cache()->save($cache_user, $user_info, 300); // Tahan 5 menit di RAM
            }
            if (!$user_info) throw new \Exception('Data pegawai tidak ditemukan.');

            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $is_iphone = (strpos($user_agent, 'iPhone') !== false || strpos($user_agent, 'iPad') !== false || strpos($user_agent, 'Mac OS') !== false);

            if (!$is_iphone) {
                if (empty($device_info)) throw new \Exception('Sistem gagal membaca KTP Digital perangkat. Gunakan PWA resmi.');

                $legacy_device_token = md5($device_info);
                $new_device_token    = md5($device_info . '_' . $id_pegawai);

                if (empty($user_info->device_token)) {
                    // Pakai formula baru yang digabung ID Pegawai (Pasti Unik per Orang, Mencegah Bentrok Database)
                    $this->db->table('pegawai')->where('id_pegawai', $id_pegawai)->update(['device_token' => $new_device_token]);
                    cache()->delete($cache_user);
                } else {
                    // Izinkan jika cocok dengan formula lama (Backwards Compatible) ATAU formula baru
                    if ($user_info->device_token !== $legacy_device_token && $user_info->device_token !== $new_device_token) {
                        throw new \Exception("<b>Gagal! Perangkat Tidak Dikenali.</b> Hubungi Admin OPD.");
                    }

                    // Auto-migrate (Diam-diam) ke formula baru jika pegawai masih pakai formula lama
                    if ($user_info->device_token === $legacy_device_token) {
                        $this->db->table('pegawai')->where('id_pegawai', $id_pegawai)->update(['device_token' => $new_device_token]);
                        cache()->delete($cache_user);
                    }
                }
            }

            // =========================================================
            // VALIDASI WAJIB APEL (AUTO-PROCESS JIKA DALAM RADIUS)
            // =========================================================
            $keterangan_bukan_apel = null;
            $cek_apel = $this->db->table('apel_peserta')
                ->select('apel_peserta.*, apel.jam_mulai, apel.jam_akhir, apel.latitude, apel.longitude, apel.radius, apel.nama_apel')
                ->join('apel', 'apel.id_apel = apel_peserta.id_apel')
                ->where('apel_peserta.id_pegawai', $id_pegawai)
                ->where('apel.tanggal', $tanggal)
                ->get()->getRow();

            $cek_presensi_apel_prev = $this->db->table('presensi')->where(['id_pegawai' => $id_pegawai, 'tanggal' => $tanggal])->get()->getRow();
            $sudah_hadir_apel_hari_ini = ($cek_presensi_apel_prev && strpos($cek_presensi_apel_prev->status_masuk ?? '', 'Apel') !== false && !empty($cek_presensi_apel_prev->jam_masuk_aktual) && $cek_presensi_apel_prev->jam_masuk_aktual !== '00:00:00');

            if ($cek_apel && empty($cek_apel->jam_absen) && !$sudah_hadir_apel_hari_ini) {
                $batas_jam_akhir_apel = !empty($cek_apel->jam_akhir) ? date('H:i:s', strtotime('+60 minutes', strtotime($cek_apel->jam_akhir))) : '11:00:00';
                if ($jam_sekarang <= $batas_jam_akhir_apel) {

                    // Cek Jarak ke Titik Apel
                    $jarak_apel = $this->calculate_distance($lat_user, $lng_user, (float)$cek_apel->latitude, (float)$cek_apel->longitude);
                    $radius_dasar_apel = (float)($cek_apel->radius ?? 150);
                    $batas_radius_apel = max($radius_dasar_apel + 100, 250);

                    // Cek VIP
                    $vip_nips = ['199006182011011002', '199003192019022001', '198908082010011001', '198401072002121002', '197502152005021002', '197812312007011082', '199602252020121001', '198904122020121001', '7604040505970004', '7604086611020001', '198310042009012005', '199002062015032012', '7604084203980002', '7604064401900004', '198212252014122002', '198107212010011001', '199612052025211080', '198307172006042030', '197709292002122005', '197406242007011021', '198509292025212073', '198712172007012001', '199009212025212085', '199007012025211126'];
                    $is_vip_apel = in_array($user_info->nip, $vip_nips);

                    if ($jarak_apel <= $batas_radius_apel || $is_vip_apel) {
                        // AUTO-PROCESS ABSEN APEL
                        $this->db->table('apel_peserta')->where(['id_apel' => $cek_apel->id_apel, 'id_pegawai' => $id_pegawai])->update(['jam_absen' => $jam_sekarang]);

                        $cek_plotting = $this->getPlottingShiftPegawai($id_pegawai, $tanggal);
                        $id_final_jam = ($cek_plotting) ? $cek_plotting->id_jam_kerja : ($user_info->jam_opd ?? 1);
                        $res_jadwal_apel = $this->db->table('jam_kerja_detail')->where(['id_jam_kerja' => $id_final_jam, 'hari' => $nama_hari_indo])->get();
                        $jadwal_apel = $res_jadwal_apel ? $res_jadwal_apel->getRowArray() : [];
                        $jam_pulang_seharusnya = $jadwal_apel['jam_pulang'] ?? '16:00:00';
                        $res_harian = $this->db->table('presensi')->where(['id_pegawai' => $id_pegawai, 'tanggal' => $tanggal])->get();
                        $cek_harian = $res_harian ? $res_harian->getRow() : null;

                        if (!$cek_harian) {
                            $this->db->table('presensi')->insert([
                                'id_pegawai'            => $id_pegawai,
                                'nip'                   => $user_info->nip,
                                'tanggal'               => $tanggal,
                                'jam_masuk_aktual'      => $jam_sekarang,
                                'jam_masuk_seharusnya'  => $cek_apel->jam_mulai,
                                'jam_pulang_seharusnya' => $jam_pulang_seharusnya,
                                'status_masuk'          => 'Hadir (Apel)',
                                'kategori'              => 'Hadir',
                                'latitude'              => $lat_user,
                                'longitude'             => $lng_user,
                                'device_info'           => $device_info,
                                'id_jam_kerja_snapshot' => $id_final_jam,
                                'deskripsi'             => "Hadir melalui sistem absen otomatis di " . $cek_apel->nama_apel,
                                'created_at'            => date('Y-m-d H:i:s')
                            ]);
                        } else {
                            // Jika sudah punya presensi, update waktu jam masuk dan status menjadi Hadir (Apel)
                            $update_auto_apel = [
                                'status_masuk'          => 'Hadir (Apel)',
                                'kategori'              => 'Hadir',
                                'jam_masuk_aktual'      => $jam_sekarang,
                                'deskripsi'             => ($cek_harian->deskripsi ? $cek_harian->deskripsi . " | " : "") . "Disinkronkan otomatis dengan kehadiran Apel di " . $cek_apel->nama_apel,
                                'updated_at'            => date('Y-m-d H:i:s')
                            ];
                            if (empty($cek_harian->jam_masuk_seharusnya) || $cek_harian->jam_masuk_seharusnya == '00:00:00') {
                                $update_auto_apel['jam_masuk_seharusnya'] = $cek_apel->jam_mulai;
                            }
                            if (empty($cek_harian->jam_pulang_seharusnya) || $cek_harian->jam_pulang_seharusnya == '00:00:00') {
                                $update_auto_apel['jam_pulang_seharusnya'] = $jam_pulang_seharusnya;
                            }
                            if (empty($cek_harian->id_jam_kerja_snapshot)) {
                                $update_auto_apel['id_jam_kerja_snapshot'] = $id_final_jam;
                            }
                            $this->db->table('presensi')->where('id_presensi', $cek_harian->id_presensi)->update($update_auto_apel);
                        }

                        $this->db->transComplete();
                        $this->sendFastNotif([$user_info->nip], "Absen Apel Berhasil ✅", "Terima kasih, data kehadiran apel Anda telah tercatat.");

                        return $this->response->setJSON([
                            'status'        => 'success',
                            'message'       => "<b>Absen Apel Berhasil!</b><br>Sistem mendeteksi Anda di lokasi Apel dan mencatat kehadiran Anda secara otomatis.",
                            'notif_title'   => "Presensi Apel Berhasil 🎉",
                            'notif_content' => "Kehadiran Anda pada upacara apel telah tercatat otomatis."
                        ]);
                    } else {
                        // Jika di luar radius apel (misal pegawai bertugas di kantor), jangan blokir kehadiran kerja mereka!
                        // Izinkan alur berlanjut ke pengecekan lokasi kantor/WFA di bawah.
                        $keterangan_bukan_apel = "Tidak mengikuti Apel di " . $cek_apel->nama_apel . " (Jarak " . round($jarak_apel) . "m)";
                    }
                }
            }

            // LOGIKA RADIUS VIP & UMUM
            $is_dalam_radius = false;
            $nama_lokasi_absen = "";
            $jarak_terdekat = 9999999;
            $wfa_valid = false;

            // =========================================================
            // [FITUR VIP]: BYPASS LOKASI UNTUK NIP TERTENTU
            // =========================================================
            $vip_nips = [
                '199006182011011002',
                '199003192019022001',
                '198908082010011001',
                '198401072002121002',
                '197502152005021002',
                '198712302025211129',
                '198011302006042020',
                '197608312005021002',
                '198403232025211119',
                '198808102025211214',
                '198006212009011005',
                '197812312007011082',
                '199003192019022001',
                '199602252020121001',
                '198904122020121001',
                '7604040505970004',
                '7604086611020001',
                '198310042009012005',
                '199002062015032012',
                '7604084203980002',
                '197507172007012030',
                '7604064401900004',
                '198107212010011001',
                '199612052025211080',
                '198307172006042030',
                '197709292002122005',
                '197406242007011021',
                '198509292025212073',
                '198712172007012001',
                '199009212025212085',
                '198212252014122002',
                '199007012025211126',
                '198911272025211100',
                '197910052002122007'
            ];
            if (in_array($user_info->nip, $vip_nips)) {
                $wfa_valid = true;
                $nama_lokasi_absen = "Absen Berhasil di Kantor Induk !";
            }

            if (!$wfa_valid && isset($user_info->is_wfa) && $user_info->is_wfa == 1) {
                if (!empty($user_info->wfa_mulai) && !empty($user_info->wfa_selesai)) {
                    if ($tanggal >= $user_info->wfa_mulai && $tanggal <= $user_info->wfa_selesai) $wfa_valid = true;
                } else {
                    $wfa_valid = true;
                }
            }

            if ($wfa_valid) {
                $is_dalam_radius = true;
                if (empty($nama_lokasi_absen)) {
                    $nama_lokasi_absen = "Lokasi WFA / Remote";
                }
            } else {
                if (!empty($user_info->id_lokasi)) {
                    $ids = array_filter(array_map('trim', explode(',', $user_info->id_lokasi)));
                    if (!$daftar_lokasi = cache('global_opd_lokasi_v2')) {
                        $daftar_lokasi = $this->db->table('opd_lokasi')->get()->getResultArray();
                        cache()->save('global_opd_lokasi_v2', $daftar_lokasi, 60);
                    }
                    foreach ($daftar_lokasi as $lok) {
                        if (in_array((string)$lok['id_lokasi'], $ids)) {
                            $jarak = $this->calculate_distance($lat_user, $lng_user, (float)$lok['latitude'], (float)$lok['longitude']);
                            if ($jarak < $jarak_terdekat) $jarak_terdekat = $jarak;

                            // TAMBAHAN TOLERANSI 20 METER UNTUK MENGAKOMODASI GPS DRIFT HP ANDROID
                            $batas_radius = (float)($lok['radius_meter'] ?? 60) + 20;
                            if ($jarak <= $batas_radius) {
                                $is_dalam_radius = true;
                                $nama_lokasi_absen = $lok['nama_lokasi'];
                                break;
                            }
                        }
                    }
                }
                if (!$is_dalam_radius && $user_info->lat_pusat != 0) {
                    $jarak = $this->calculate_distance($lat_user, $lng_user, (float)$user_info->lat_pusat, (float)$user_info->lng_pusat);
                    if ($jarak < $jarak_terdekat) $jarak_terdekat = $jarak;

                    // TAMBAHAN TOLERANSI 20 METER UNTUK MENGAKOMODASI GPS DRIFT HP ANDROID
                    $batas_radius = (float)($user_info->radius_opd ?? 60) + 20;
                    if ($jarak <= $batas_radius) {
                        $is_dalam_radius = true;
                        $nama_lokasi_absen = "Kantor Induk";
                    }
                }
                if (!$is_dalam_radius) {
                    $pesan_jarak = ($jarak_terdekat > 1000000) ? "Lokasi tidak valid." : round($jarak_terdekat) . " meter";
                    throw new \Exception("Di luar radius! Jarak Anda: " . $pesan_jarak);
                }
            }

            // [SMART AUTO-HEAL]: Pulihkan anomali shift tertukar sebelum memproses scan baru
            $this->checkAndHealShiftAnomaly($id_pegawai);

            $cek = $this->db->table('presensi')->where(['id_pegawai' => $id_pegawai, 'tanggal' => $tanggal])->get()->getRowArray();
            $cek_kemarin = $this->db->table('presensi')->where(['id_pegawai' => $id_pegawai, 'tanggal' => date('Y-m-d', strtotime('-1 days'))])->get()->getRowArray();

            $is_checkout_shift_malam = false;
            $is_checkout_shift2_malam = false;

            if ($cek_kemarin) {
                $jp_aktual = trim((string)($cek_kemarin['jam_pulang_aktual'] ?? ''));
                $is_unclosed_1 = (empty($jp_aktual) || $jp_aktual === '00:00:00' || $jp_aktual === '-' || $jp_aktual === 'NULL');

                // Deteksi dinas malam kemarin berbasis fakta riil:
                $jm_kemarin_time = trim((string)($cek_kemarin['jam_masuk_aktual'] ?? ''));
                if (strlen($jm_kemarin_time) > 8) $jm_kemarin_time = date('H:i:s', strtotime($jm_kemarin_time));
                $is_masuk_malam_kemarin = (!empty($jm_kemarin_time) && ($jm_kemarin_time >= '17:30:00' || $jm_kemarin_time <= '04:00:00'));

                $jm_target_kemarin = trim((string)($cek_kemarin['jam_masuk_seharusnya'] ?? ''));
                $is_jadwal_malam_kemarin = (!empty($jm_target_kemarin) && $jm_target_kemarin >= '17:30:00');

                $jp_target_kemarin = trim((string)($cek_kemarin['jam_pulang_seharusnya'] ?? ''));
                $is_cross_midnight = (!empty($jp_target_kemarin) && !empty($jm_target_kemarin) && strtotime(substr($jp_target_kemarin, -8)) <= strtotime(substr($jm_target_kemarin, -8)));

                $is_dinas_malam_kemarin = ($is_masuk_malam_kemarin || $is_jadwal_malam_kemarin || $is_cross_midnight);

                $jm_2 = trim((string)($cek_kemarin['jam_masuk_shift2'] ?? ''));
                $jp_2 = trim((string)($cek_kemarin['jam_pulang_shift2'] ?? ''));
                $is_unclosed_2 = (!empty($jm_2) && (empty($jp_2) || $jp_2 === '00:00:00' || $jp_2 === 'NULL'));

                $is_belum_kedaluwarsa = ($jam_sekarang <= '14:30:00');

                // Prioritaskan kepulangan shift kemarin jika hari ini belum ada absen masuk riil
                $today_has_real_masuk = (!empty($cek['jam_masuk_aktual']) && $cek['jam_masuk_aktual'] !== '00:00:00' && $cek['jam_masuk_aktual'] !== '-');

                if ($is_unclosed_1 && $is_dinas_malam_kemarin && $is_belum_kedaluwarsa && !$today_has_real_masuk) {
                    $is_checkout_shift_malam = true;
                    $cek = $cek_kemarin;
                } elseif ($is_unclosed_2 && $is_belum_kedaluwarsa && !$today_has_real_masuk) {
                    $is_checkout_shift2_malam = true;
                    $cek = $cek_kemarin;
                }
            }

            $cek_plotting = $this->db->table('plotting_shift')->where(['id_pegawai' => $id_pegawai, 'tanggal' => $tanggal])->get()->getRow();

            // =========================================================================
            // VALIDASI PENANGGUHAN (TILANG FISIK) UNTUK APK & PWA
            // =========================================================================
            if ($cek && isset($cek['status_verifikasi']) && $cek['status_verifikasi'] === 'Ditangguhkan') {
                $alasan = !empty($cek['alasan_tangguh']) ? $cek['alasan_tangguh'] : "Tanpa keterangan.";
                throw new \Exception("<div style='text-align:left;'><b>PRESENSI DITANGGUHKAN 🚫</b><br><br>Alasan: <i>$alasan</i><br><br><span style='color:red;'>Harap segera menghadap pimpinan Anda untuk klarifikasi!</span></div>");
            }

            // =========================================================================
            // [SUPER FIX]: TARIK VARIABEL CITO LEBIH AWAL UNTUK MENGUNCI LOGIKA LIBUR
            // =========================================================================
            $id_jam_untuk_cek = ($cek) ? ($cek['id_jam_kerja_snapshot'] ?? 1) : ($cek_plotting ? $cek_plotting->id_jam_kerja : ($user_info->jam_opd ?? 1));

            $cache_jk_name = "jk_name_v4_$id_jam_untuk_cek";
            if (!$nama_jk = cache($cache_jk_name)) {
                $jk_row = $this->db->table('jam_kerja')->select('nama_jadwal')->where('id_jam_kerja', $id_jam_untuk_cek)->get()->getRow();
                $nama_jk = $jk_row ? strtolower($jk_row->nama_jadwal) : '';
                cache()->save($cache_jk_name, $nama_jk, 3600);
            }
            $is_jadwal_cito = (strpos($nama_jk, 'cito') !== false || strpos($nama_jk, 'standby') !== false);
            // =========================================================================

            if (!$is_checkout_shift_malam && !$is_checkout_shift2_malam) {
                if (!$cek_plotting && $has_tanggal_mulai) {
                    $cek_plotting = $this->db->table('plotting_shift')
                        ->where('id_pegawai', $id_pegawai)
                        ->where('tanggal_mulai <=', $tanggal)
                        ->where('tanggal_selesai >=', $tanggal)
                        ->get()->getRow();
                }

                $hari_libur = $this->db->table('hari_libur')->where('tanggal', $tanggal)->get()->getRow();
                if ($hari_libur) {
                    $izin_absen = false;
                    if ($cek_plotting) {
                        $jam_kerja_info = $this->db->table('jam_kerja')->select('nama_jadwal')->where('id_jam_kerja', $cek_plotting->id_jam_kerja)->get()->getRow();
                        if ($jam_kerja_info) {
                            $nama_j = strtolower($jam_kerja_info->nama_jadwal);
                            if (strpos($nama_j, 'ramadhan') === false && strpos($nama_j, 'puasa') === false && strpos($nama_j, 'umum') === false && strpos($nama_j, 'hari kerja') === false) {
                                $izin_absen = true;
                            }
                        }
                    }
                    if (!$izin_absen) throw new \Exception("<b>Sistem Ditutup!</b><br>Hari ini adalah Libur/Cuti Bersama. Anda tidak perlu melakukan presensi.");
                }

                if (!$cek_plotting) {
                    $bulan_ini = date('m', strtotime($tanggal));
                    $tahun_ini = date('Y', strtotime($tanggal));

                    $builder_shift = $this->db->table('plotting_shift')
                        ->join('jam_kerja', 'jam_kerja.id_jam_kerja = plotting_shift.id_jam_kerja', 'left')
                        ->where('plotting_shift.id_pegawai', $id_pegawai)
                        ->where('jam_kerja.nama_jadwal NOT LIKE', '%ramadhan%')
                        ->where('jam_kerja.nama_jadwal NOT LIKE', '%puasa%')
                        ->where('jam_kerja.nama_jadwal NOT LIKE', '%umum%')
                        ->where('jam_kerja.nama_jadwal NOT LIKE', '%hari kerja%');

                    $start_date_month = sprintf('%04d-%02d-01', $tahun_ini, $bulan_ini);
                    $end_date_month = date('Y-m-t', strtotime($start_date_month));

                    if ($has_tanggal_mulai) {
                        $builder_shift->where('tanggal_mulai >=', $start_date_month)
                            ->where('tanggal_mulai <=', $end_date_month);
                    } else {
                        $builder_shift->where('tanggal >=', $start_date_month)
                            ->where('tanggal <=', $end_date_month);
                    }

                    if ($builder_shift->countAllResults() > 0) {
                        throw new \Exception("Hari ini Anda berstatus LEPAS JAGA (Libur Shift). Tidak perlu absen masuk hari ini.");
                    }
                }

                $id_final_jam = ($cek_plotting) ? $cek_plotting->id_jam_kerja : ($user_info->jam_opd ?? 1);
                $cache_jadwal = "jadwal_{$id_final_jam}_{$nama_hari_indo}";
                if (!$jadwal = cache($cache_jadwal)) {
                    $jadwal = $this->db->table('jam_kerja_detail')->where(['id_jam_kerja' => $id_final_jam, 'hari' => $nama_hari_indo])->get()->getRowArray();
                    if ($jadwal) cache()->save($cache_jadwal, $jadwal, 60);
                }

                // =================================================================
                // [PENGAMAN KETAT SABTU/MINGGU]: Tendang Reguler, Loloskan CITO
                // =================================================================
                if (!$jadwal) {
                    if ($is_jadwal_cito) {
                        // Nakes Cito butuh jam dummy agar tidak error, copy dari jadwal hari Senin/pertamanya
                        $jadwal = $this->db->table('jam_kerja_detail')->where('id_jam_kerja', $id_final_jam)->get()->getRowArray();
                    } else {
                        // Reguler yang coba absen di Sabtu/Minggu, langsung TENDANG!
                        throw new \Exception("<b>Akses Ditolak!</b><br>Hari ini ($nama_hari_indo) adalah hari libur (Bukan Hari Kerja). Anda tidak perlu melakukan presensi.");
                    }
                }

                if (!$jadwal) throw new \Exception("Jadwal kerja belum disetting sama sekali oleh Admin.");

                $jam_target = !empty($jadwal['jam_masuk']) ? str_replace('.', ':', $jadwal['jam_masuk']) : '07:30:00';
                $jam_pulang_target = !empty($jadwal['jam_pulang']) ? str_replace('.', ':', $jadwal['jam_pulang']) : '16:00:00';
                if (strlen($jam_target) == 5) $jam_target .= ':00';
                if (strlen($jam_pulang_target) == 5) $jam_pulang_target .= ':00';

                $batas_buka_absen = date('H:i:s', strtotime('-120 minutes', strtotime($jam_target)));

                // Aturan baru: Absen pagi tutup dari jam 12 malam sampai jam 7 pagi.
                $batas_buka_absen = max('07:00:00', $batas_buka_absen);

                if (!$cek && $jam_sekarang < $batas_buka_absen) {
                    if (!$is_jadwal_cito) { // CITO bebas masuk jam berapapun karena sifatnya 24/7
                        if ($jam_sekarang < '07:00:00') {
                            throw new \Exception("Sistem Presensi ditutup pada tengah malam dan baru akan dibuka pukul 07:00 WITA.");
                        } else {
                            throw new \Exception("Sistem belum dibuka. Silakan absen masuk maksimal 2 jam sebelum jadwal Anda (" . date('H:i', strtotime($jam_target)) . " WITA).");
                        }
                    } else {
                        // [PROTEKSI NAKES SHIFT MALAM]: Tolak jika jadwal hari ini adalah Shift Malam (>= 17:30) tapi coba absen masuk di pagi hari (< 15:00)
                        if ($jam_target >= '17:30:00' && $jam_sekarang < '15:00:00') {
                            throw new \Exception("<b>Jadwal Anda Shift Malam!</b><br>Jadwal masuk Anda adalah pukul " . date('H:i', strtotime($jam_target)) . " WITA.<br><br>Sistem presensi masuk malam baru akan dibuka pukul 17:00 WITA.");
                        }
                    }
                }
            } else {
                $jam_target = $cek['jam_masuk_seharusnya'];
                $jam_pulang_target = $cek['jam_pulang_seharusnya'];
            }

            $msg = "";
            $notif_judul = "Presensi Berhasil ✅";
            $notif_pesan = "Data kehadiran Anda telah tersimpan.";

            if (!$cek) {
                // [SUPER FIX]: Cegah double-tap lintas hari / bulan (Shift Malam)
                // Jika pegawai baru saja menyelesaikan shift malam (kurang dari 5 menit lalu), blokir percobaan absen masuk baru
                if ($cek_kemarin) {
                    $is_cross_m = (strtotime($cek_kemarin['jam_pulang_seharusnya']) <= strtotime($cek_kemarin['jam_masuk_seharusnya']));
                    $jp_aktual = trim((string)($cek_kemarin['jam_pulang_aktual'] ?? ''));
                    $is_closed_kemarin = (!empty($jp_aktual) && $jp_aktual !== '00:00:00' && $jp_aktual !== '-' && $jp_aktual !== 'NULL');

                    if ($is_cross_m && $is_closed_kemarin) {
                        $waktu_terakhir = !empty($cek_kemarin['updated_at']) ? $cek_kemarin['updated_at'] : $cek_kemarin['created_at'];
                        if ($waktu_terakhir && (strtotime(date('Y-m-d H:i:s')) - strtotime($waktu_terakhir) < 300)) {
                            throw new \Exception("<div style='text-align:left;'><b>Shift Malam Selesai! ✅</b><br><br>Absen pulang Anda sudah berhasil tersimpan di riwayat bulan kemarin.<br><br><i>Sistem mengunci tombol absen selama 5 menit untuk mencegah klik ganda.</i></div>");
                        }
                    }
                }

                // ABSEN MASUK PERTAMA KALI
                $waktu_aktual = strtotime($jam_sekarang);
                $waktu_target = strtotime($jam_target);
                $menit_keterlambatan = ($waktu_aktual > $waktu_target) ? ceil(($waktu_aktual - $waktu_target) / 60) : 0;

                $batas_baik  = $jadwal['batas_baik_menit'] ?? 15;
                $batas_cukup = $jadwal['batas_cukup_menit'] ?? 45;
                $batas_telat = $jadwal['batas_telat_menit'] ?? 90;
                $batas_bina  = $jadwal['batas_bina_menit'] ?? 150;

                if ($is_jadwal_cito) {
                    $status_m = 'Hadir (Standby)';
                    $menit_keterlambatan = 0;
                } else {
                    if ($waktu_aktual <= $waktu_target) {
                        $menit_awal = floor(($waktu_target - $waktu_aktual) / 60);
                        if ($menit_awal >= 30) {
                            $status_m = 'Istimewa (Datang Awal 🎖️)';
                        } else {
                            $status_m = 'Sangat Baik / Istimewa';
                        }
                    } elseif ($menit_keterlambatan <= $batas_baik) $status_m = 'Baik';
                    elseif ($menit_keterlambatan <= $batas_cukup) $status_m = 'Cukup Baik';
                    elseif ($menit_keterlambatan <= $batas_telat) $status_m = 'Terlambat';
                    elseif ($menit_keterlambatan <= $batas_bina) $status_m = 'Pembinaan';
                    else $status_m = 'Teguran';
                }

                $this->db->table('presensi')->insert([
                    'id_pegawai'            => $id_pegawai,
                    'tanggal'               => $tanggal,
                    'jam_masuk_aktual'      => $jam_sekarang,
                    'jam_masuk_seharusnya'  => $jam_target,
                    'jam_pulang_seharusnya' => $jam_pulang_target,
                    'status_masuk'          => $status_m,
                    'menit_keterlambatan'   => $menit_keterlambatan,
                    'id_jam_kerja_snapshot' => $id_jam_untuk_cek,
                    'latitude'              => $lat_user,
                    'longitude'             => $lng_user,
                    'device_info'           => $device_info,
                    'nip'                   => $nip_user,
                    'kategori'              => 'Hadir',
                    'deskripsi'             => $keterangan_bukan_apel,
                    'created_at'            => date('Y-m-d H:i:s')
                ]);

                $pantun = $this->getRandomPantun('Masuk', $user_info->nama_lengkap);
                $catatan_apel_teks = $keterangan_bukan_apel ? "<div class='small text-muted mb-2'><i>Catatan: {$keterangan_bukan_apel}</i></div>" : "";
                $msg = "Absen Masuk ($status_m) Berhasil di $nama_lokasi_absen!<br>{$catatan_apel_teks}<br>$pantun";

                $notif_judul = "Absen Masuk Berhasil ✅";
                $notif_pesan = "Terima kasih! Absen masuk Anda pukul " . date('H:i', strtotime($jam_sekarang)) . " telah tercatat. Semangat bekerja!";
            } else {
                $waktu_terakhir = !empty($cek['updated_at']) ? $cek['updated_at'] : $cek['created_at'];
                if ($waktu_terakhir && (strtotime(date('Y-m-d H:i:s')) - strtotime($waktu_terakhir) < 120)) {
                    $this->db->transRollback();
                    cache()->delete("lock_absen_$id_pegawai");
                    return $this->response->setJSON(['status' => 'info', 'message' => 'Terlalu cepat! Mohon tunggu 2 menit sebelum tap tombol absen lagi.']);
                }

                $nama_opd_user = strtoupper($user_info->nama_opd ?? '');
                $is_kesehatan_opd = (strpos($nama_opd_user, 'RSUD') !== false || strpos($nama_opd_user, 'PUSKESMAS') !== false || strpos($nama_opd_user, 'KESEHATAN') !== false);

                $is_jadwal_reguler = (strpos($nama_jk, 'reguler') !== false || strpos($nama_jk, 'umum') !== false || strpos($nama_jk, 'hari kerja') !== false);
                $pakai_jalur_reguler = (!$is_kesehatan_opd || $is_jadwal_reguler);

                if ($is_checkout_shift_malam) {
                    $jam_pulang_seharusnya = $cek['jam_pulang_seharusnya'];
                    $status_p = 'Selesai';
                    if (!empty($jam_pulang_seharusnya) && (strtotime($jam_sekarang) < strtotime($jam_pulang_seharusnya))) {
                        $status_p = 'Cepat Pulang';
                    }
                    if ($is_jadwal_cito) $status_p = 'Selesai';

                    if ($status_p == 'Cepat Pulang' && (empty($confirm_pulang) || $confirm_pulang != '1')) {
                        $this->db->transRollback();
                        cache()->delete("lock_absen_$id_pegawai");
                        return $this->response->setJSON([
                            'status' => 'confirm_pulang_cepat',
                            'message' => 'Jam kerja Anda belum selesai (Jadwal pulang: ' . date('H:i', strtotime($jam_pulang_seharusnya)) . ' WITA).<br><br>Apakah Anda yakin ingin <b>Pulang Cepat</b> sekarang?'
                        ]);
                    }

                    $this->db->table('presensi')->where('id_presensi', $cek['id_presensi'])->update([
                        'jam_pulang_aktual' => $jam_sekarang,
                        'status_pulang'     => $status_p,
                        'updated_at'        => date('Y-m-d H:i:s')
                    ]);
                    $pantun = $this->getRandomPantun('Pulang', $user_info->nama_lengkap);
                    $msg = "<b>Penyelesaian Shift Malam Berhasil!</b><br>Jam diperbarui: " . date('H:i', strtotime($jam_sekarang)) . " ($status_p)<br><br>$pantun";

                    $notif_judul = "Absen Pulang Berhasil 🏠";
                    $notif_pesan = "Terima kasih atas kerja keras Anda. Hati-hati di jalan pulang!";
                } elseif ($is_checkout_shift2_malam) {
                    $this->db->table('presensi')->where('id_presensi', $cek['id_presensi'])->update([
                        'jam_pulang_shift2' => $jam_sekarang,
                        'updated_at'        => date('Y-m-d H:i:s')
                    ]);
                    $pantun = $this->getRandomPantun('Pulang', $user_info->nama_lengkap);
                    $msg = "<b>Penyelesaian Shift Sambung (Malam) Berhasil!</b><br>Jam diperbarui: " . date('H:i', strtotime($jam_sekarang)) . "<br><br>$pantun";

                    $notif_judul = "Absen Pulang Berhasil 🏠";
                    $notif_pesan = "Terima kasih atas kerja keras Anda. Hati-hati di jalan pulang!";
                } else {

                    if ($pakai_jalur_reguler) {
                        if (empty($cek['jam_pulang_aktual'])) {

                            // [SUPER FIX]: Dinamisasi Batas Waktu Pulang
                            // Jika jadwal pulang adalah setengah hari (<= 13:30) seperti hari Jumat, maka tidak ada Ishoma, langsung bisa Pulang.
                            $batas_mulai_pulang = ($jam_pulang_target <= '13:30:00') ? date('H:i:s', strtotime('-120 minutes', strtotime($jam_pulang_target))) : '13:30:01';

                            if ($jam_sekarang >= $batas_mulai_pulang) {
                                $status_p = (strtotime($jam_sekarang) < strtotime($jam_pulang_target)) ? 'Cepat Pulang' : 'Selesai';
                                if ($is_jadwal_cito) $status_p = 'Selesai';

                                if ($status_p == 'Cepat Pulang' && (empty($confirm_pulang) || $confirm_pulang != '1')) {
                                    $this->db->transRollback();
                                    cache()->delete("lock_absen_$id_pegawai");
                                    return $this->response->setJSON([
                                        'status' => 'confirm_pulang_cepat',
                                        'message' => 'Jam kerja Anda belum selesai (Jadwal pulang: ' . date('H:i', strtotime($jam_pulang_target)) . ' WITA).<br><br>Apakah Anda yakin ingin <b>Pulang Cepat</b> sekarang?'
                                    ]);
                                }

                                $update_pulang_data = [
                                    'jam_pulang_aktual'     => $jam_sekarang,
                                    'jam_pulang_seharusnya' => $jam_pulang_target,
                                    'status_pulang'         => $status_p,
                                    'updated_at'            => date('Y-m-d H:i:s')
                                ];

                                // Pengaman ekstra: Jika paginya absen apel tapi jam_masuk_aktual belum terisi
                                if (empty($cek['jam_masuk_aktual']) || $cek['jam_masuk_aktual'] === '00:00:00' || $cek['jam_masuk_aktual'] === '-') {
                                    $cek_apel_pagi = $this->db->table('apel_peserta')
                                        ->select('jam_absen')
                                        ->where('id_pegawai', $id_pegawai)
                                        ->where('jam_absen IS NOT NULL')
                                        ->where("jam_absen != ''")
                                        ->orderBy('id_apel', 'DESC')
                                        ->get()->getRow();
                                    $update_pulang_data['jam_masuk_aktual'] = (!empty($cek_apel_pagi->jam_absen)) ? $cek_apel_pagi->jam_absen : (!empty($cek['jam_masuk_seharusnya']) ? $cek['jam_masuk_seharusnya'] : '07:30:00');
                                    if (empty($cek['status_masuk']) || $cek['status_masuk'] === 'Alpha' || $cek['status_masuk'] === 'Teguran') {
                                        $update_pulang_data['status_masuk'] = 'Hadir (Apel)';
                                        $update_pulang_data['kategori'] = 'Hadir';
                                    }
                                }

                                $this->db->table('presensi')->where('id_presensi', $cek['id_presensi'])->update($update_pulang_data);

                                $pantun = $this->getRandomPantun('Pulang', $user_info->nama_lengkap);
                                $kata_awal = empty($cek['jam_pulang_aktual']) ? "Absen Pulang Berhasil!" : "Update Jam Pulang Berhasil!";
                                $msg = "$kata_awal Jam diperbarui: " . date('H:i', strtotime($jam_sekarang)) . " ($status_p)<br><br>$pantun";

                                $notif_judul = "Absen Pulang Berhasil 🏠";
                                $notif_pesan = "Terima kasih atas dedikasi Anda hari ini. Hati-hati di jalan pulang!";
                            } elseif ($jam_sekarang >= $jam_mulai_siang && $jam_sekarang < $batas_mulai_pulang) {
                                if (empty($cek['jam_siang'])) {
                                    $this->db->table('presensi')->where('id_presensi', $cek['id_presensi'])->update([
                                        'jam_siang'  => $jam_sekarang,
                                        'updated_at' => date('Y-m-d H:i:s')
                                    ]);
                                    $pantun = $this->getRandomPantun('Siang', $user_info->nama_lengkap);
                                    $msg = "Absen Siang Berhasil!<br><br>$pantun";

                                    $notif_judul = "Absen Siang Berhasil ☕";
                                    $notif_pesan = "Terima kasih, selamat menikmati waktu istirahat siang Anda.";
                                } else {
                                    $this->db->transRollback();
                                    cache()->delete("lock_absen_$id_pegawai");
                                    return $this->response->setJSON(['status' => 'info', 'message' => 'Anda sudah melakukan absen istirahat siang. Belum waktunya absen pulang.']);
                                }
                            } else {
                                throw new \Exception('Belum waktunya absen siang atau pulang.');
                            }
                        } else {
                            $this->db->transRollback();
                            cache()->delete("lock_absen_$id_pegawai");
                            return $this->response->setJSON(['status' => 'info', 'message' => 'Jadwal kerja Anda hari ini sudah selesai.']);
                        }
                    } else {
                        // LOGIKA NAKES SHIFT
                        if (empty($cek['jam_pulang_aktual'])) {

                            $status_p = (strtotime($jam_sekarang) < strtotime($jam_pulang_target)) ? 'Cepat Pulang' : 'Selesai';
                            if ($is_jadwal_cito) $status_p = 'Selesai';

                            if ($status_p == 'Cepat Pulang' && (empty($confirm_pulang) || $confirm_pulang != '1')) {
                                $this->db->transRollback();
                                cache()->delete("lock_absen_$id_pegawai");
                                return $this->response->setJSON([
                                    'status' => 'confirm_pulang_cepat',
                                    'message' => 'Jam kerja Anda belum selesai (Jadwal pulang: ' . date('H:i', strtotime($jam_pulang_target)) . ' WITA).<br><br>Apakah Anda yakin ingin <b>Pulang Cepat</b> sekarang?'
                                ]);
                            }

                            $this->db->table('presensi')->where('id_presensi', $cek['id_presensi'])->update([
                                'jam_pulang_aktual'     => $jam_sekarang,
                                'status_pulang'         => $status_p,
                                'updated_at'            => date('Y-m-d H:i:s')
                            ]);
                            $pantun = $this->getRandomPantun('Pulang', $user_info->nama_lengkap);
                            $msg = "Absen Pulang Shift Pertama Berhasil!<br>Jam diperbarui: " . date('H:i', strtotime($jam_sekarang)) . "<br><br>$pantun";

                            $notif_judul = "Absen Pulang Berhasil 🏠";
                            $notif_pesan = "Terima kasih atas dedikasi Anda hari ini. Hati-hati di jalan pulang!";
                        } elseif (!empty($cek['jam_pulang_aktual']) && empty($cek['jam_masuk_shift2'])) {

                            if (empty($confirm_double) || $confirm_double != '1') {
                                $this->db->transRollback();
                                cache()->delete("lock_absen_$id_pegawai");
                                return $this->response->setJSON([
                                    'status' => 'confirm_double',
                                    'message' => 'Anda sudah menyelesaikan jadwal Shift Pertama Anda hari ini.<br><br>Apakah Anda yakin ingin memulai <b>Shift Sambung (Double Shift)</b> sekarang?'
                                ]);
                            }

                            $this->db->table('presensi')->where('id_presensi', $cek['id_presensi'])->update([
                                'jam_masuk_shift2' => $jam_sekarang,
                                'deskripsi'        => 'Pegawai mengambil Shift Ganda (Double Shift)',
                                'updated_at'       => date('Y-m-d H:i:s')
                            ]);
                            $pantun = $this->getRandomPantun('Masuk', $user_info->nama_lengkap);
                            $msg = "<b>Absen Shift Sambung Berhasil!</b><br>Selamat bertugas kembali.<br><br>$pantun";

                            $notif_judul = "Absen Shift Sambung Berhasil 🏥";
                            $notif_pesan = "Semangat bertugas kembali untuk Shift ke-2 Anda hari ini!";
                        } elseif (!empty($cek['jam_masuk_shift2']) && empty($cek['jam_pulang_shift2'])) {
                            $this->db->table('presensi')->where('id_presensi', $cek['id_presensi'])->update([
                                'jam_pulang_shift2' => $jam_sekarang,
                                'updated_at'        => date('Y-m-d H:i:s')
                            ]);
                            $pantun = $this->getRandomPantun('Pulang', $user_info->nama_lengkap);
                            $msg = "<b>Absen Pulang Shift Sambung Berhasil!</b><br>Terima kasih atas kerja keras Anda.<br><br>$pantun";

                            $notif_judul = "Absen Pulang Berhasil 🏠";
                            $notif_pesan = "Terima kasih atas kerja keras Anda. Hati-hati di jalan pulang!";
                        } else {
                            $this->db->transRollback();
                            cache()->delete("lock_absen_$id_pegawai");
                            return $this->response->setJSON(['status' => 'info', 'message' => 'Seluruh jadwal shift Anda hari ini telah diselesaikan.']);
                        }
                    }
                }
            }

            $this->db->transComplete();
            cache()->delete("lock_absen_$id_pegawai");

            // =========================================================
            // KIRIM PUSH NOTIFIKASI ONESIGNAL SETELAH PROSES SELESAI
            // =========================================================
            $this->sendFastNotif([$nip_user], $notif_judul, $notif_pesan);

            return $this->response->setJSON(['status' => 'success', 'message' => "$msg", 'notif_title' => $notif_judul, 'notif_content' => $notif_pesan]);
        } catch (\Exception $e) {
            $this->db->transRollback();
            cache()->delete("lock_absen_$id_pegawai");
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function submit_izin_pwa()
    {
        $nip = session()->get('nip');
        if (!$nip) $nip = $this->request->getPost('nip_manual');

        if (!$nip || $nip == "") {
            return $this->response->setJSON(['status'  => 'error', 'message' => 'Sesi Identitas (NIP) tidak ditemukan. Silakan refresh halaman.']);
        }

        $kategori     = $this->request->getPost('kategori');
        if ($kategori === 'Izin Absen') {
            $kategori = 'Izin';
        }
        $sub_kategori = $this->request->getPost('sub_kategori');
        $slot_waktu   = $this->request->getPost('slot_waktu') ?? 'Penuh';

        $tgl_mulai = $this->request->getPost('tgl_mulai');
        if (empty($tgl_mulai)) $tgl_mulai = date('Y-m-d');

        $tgl_selesai = $this->request->getPost('tgl_selesai');
        if (empty($tgl_selesai)) $tgl_selesai = $tgl_mulai;

        $file = $this->request->getFile('file_bukti');
        $namaFile = "";

        if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            if ($file->getError() === UPLOAD_ERR_INI_SIZE) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => "Gagal! Ukuran foto melebih batas maksimal server. Silakan kompres foto Anda atau turunkan resolusi kamera HP."
                ]);
            }

            if (!$file->isValid()) {
                $error_code = $file->getError();
                $error_msg  = $file->getErrorString();
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => "Server menolak file (Kode: $error_code). Alasan: $error_msg"
                ]);
            }

            $ukuran_bytes = $file->getSize();
            if ($ukuran_bytes > 10485760) {
                $ukuran_mb = round($ukuran_bytes / 1048576, 2);
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => "Gagal! Ukuran file Anda: $ukuran_mb MB. Maksimal yang diizinkan adalah 10 MB."
                ]);
            }

            $ext = strtolower($file->getClientExtension());
            $mimeType = strtolower($file->getMimeType() ?? '');

            // Deteksi apakah file sebenarnya adalah dokumen PDF
            $isPdf = ($ext === 'pdf' || $mimeType === 'application/pdf');
            $isImage = in_array($ext, ['jpg', 'jpeg', 'png']) || strpos($mimeType, 'image/') === 0;

            if (!$isPdf && !$isImage) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => "Gagal! Format file .$ext tidak diizinkan. Harap upload JPG, PNG, atau PDF."
                ]);
            }

            try {
                $namaFile = $file->getRandomName();

                if ($isPdf) {
                    // Pastikan file berekstensi .pdf dan langsung simpan tanpa diproses GD Image
                    if (!str_ends_with(strtolower($namaFile), '.pdf')) {
                        $namaFile = pathinfo($namaFile, PATHINFO_FILENAME) . '.pdf';
                    }
                    $file->move(FCPATH . 'uploads/bukti_absen/', $namaFile);
                } else {
                    // File Gambar
                    if ($ukuran_bytes > 1048576) {
                        $file->move(WRITEPATH . 'uploads/', $namaFile);
                        $tempPath = WRITEPATH . 'uploads/' . $namaFile;

                        try {
                            \Config\Services::image()
                                ->withFile($tempPath)
                                ->resize(1200, 1200, true, 'auto')
                                ->save(FCPATH . 'uploads/bukti_absen/' . $namaFile, 65);
                        } catch (\Throwable $imgEx) {
                            // Fallback jika library gambar gagal memproses, pindahkan langsung
                            copy($tempPath, FCPATH . 'uploads/bukti_absen/' . $namaFile);
                        }

                        if (file_exists($tempPath)) {
                            unlink($tempPath);
                        }
                    } else {
                        $file->move(FCPATH . 'uploads/bukti_absen/', $namaFile);
                    }
                }
            } catch (\Exception $e) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => "Gagal menyimpan file ke server. Pastikan folder memiliki izin tulis. Error: " . $e->getMessage()
                ]);
            }
        }

        $data = [
            'nip'             => $nip,
            'kategori'        => $kategori,
            'sub_kategori'    => $sub_kategori,
            'slot_waktu'      => $slot_waktu,
            'tanggal_mulai'   => $tgl_mulai,
            'tanggal_selesai' => $tgl_selesai,
            'keterangan'      => $this->request->getPost('alasan') ?: $this->request->getPost('keterangan'),
            'file_bukti'      => $namaFile,
            'status_approval' => 'Pending',
            'created_at'      => date('Y-m-d H:i:s')
        ];

        try {
            $this->db->table('pengajuan_absen')->insert($data);
            $notif_judul = "Pengajuan Terkirim";
            $notif_pesan = "Pengajuan $kategori Anda berhasil terkirim dan menunggu persetujuan.";
            return $this->response->setJSON(['status' => 'success', 'message' => 'Pengajuan berhasil terkirim!', 'notif_title' => $notif_judul, 'notif_content' => $notif_pesan]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal simpan ke database.']);
        }
    }

    private function calculate_distance($lat1, $lon1, $lat2, $lon2)
    {
        $earth_radius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earth_radius * $c;
    }

    private function get_nama_hari($day)
    {
        $h = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];
        return $h[$day] ?? 'Minggu';
    }

    public function update_password()
    {
        $id = session()->get('id_pegawai');
        $user = $this->db->table('pegawai')->where('id_pegawai', $id)->get()->getRowArray();
        if (password_verify($this->request->getPost('password_lama'), $user['password'])) {
            if ($this->request->getPost('password_baru') === $this->request->getPost('konfirmasi_password')) {
                $this->db->table('pegawai')->where('id_pegawai', $id)->update(['password' => password_hash($this->request->getPost('password_baru'), PASSWORD_DEFAULT)]);
                return $this->response->setJSON(['status' => 'success', 'message' => 'Password diubah.']);
            }
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal ganti password.']);
    }

    private function getRandomPantun($tipe, $nama_pegawai)
    {
        $pesan = $this->db->table('pesan_absen')
            ->where('tipe', $tipe)
            ->where('is_active', 1)
            ->orderBy('RAND()')
            ->limit(1)
            ->get()->getRow();

        if ($pesan) {
            return str_replace('{nama}', "<b>" . $nama_pegawai . "</b>", $pesan->pesan);
        }

        $defaults = [
            'Masuk'  => "Selamat bekerja, <b>$nama_pegawai</b>!",
            'Siang'  => "Selamat beristirahat sejenak, <b>$nama_pegawai</b>!",
            'Pulang' => "Hati-hati di jalan pulang, <b>$nama_pegawai</b>!"
        ];

        return $defaults[$tipe] ?? "Semangat, <b>$nama_pegawai</b>!";
    }

    public function update_profile()
    {
        $id = session()->get('id_pegawai');
        $nomor_hp = $this->request->getPost('nomor_hp');

        $lama = $this->request->getPost('lama');
        $baru = $this->request->getPost('baru');
        $konfirmasi = $this->request->getPost('konfirmasi');

        $updateData = ['nomor_hp' => $nomor_hp];
        $msg = "Nomor WhatsApp berhasil diperbarui.";

        if (!empty($lama) || !empty($baru)) {
            $user = $this->db->table('pegawai')->where('id_pegawai', $id)->get()->getRowArray();
            if (!password_verify($lama, $user['password'])) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Sandi saat ini yang Anda masukkan salah.']);
            }
            if ($baru !== $konfirmasi) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Konfirmasi sandi baru tidak cocok.']);
            }
            $updateData['password'] = password_hash($baru, PASSWORD_DEFAULT);
            $msg = "Profil dan Sandi berhasil diperbarui.";
        }

        $this->db->table('pegawai')->where('id_pegawai', $id)->update($updateData);
        return $this->response->setJSON(['status' => 'success', 'message' => $msg]);
    }
}

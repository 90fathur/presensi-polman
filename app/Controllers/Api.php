<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Api extends ResourceController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        date_default_timezone_set('Asia/Makassar');
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

    private function get_nama_hari($day_en)
    {
        $days = [
            'Sun' => 'Minggu',
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu'
        ];
        return $days[$day_en] ?? $day_en;
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

    /**
     * ENDPOINT: /api/login
     * METHOD: POST
     * Params: username (NIP), password
     */
    public function login()
    {
        $session  = session();
        $nip      = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        if (empty($nip) || empty($password)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'NIP dan Password wajib diisi.']);
        }

        $pegawai = $this->db->table('pegawai')->where('nip', $nip)->get()->getRowArray();

        if ($pegawai && password_verify($password, $pegawai['password'])) {
            $session->set([
                'id_pegawai'   => $pegawai['id_pegawai'],
                'username'     => $pegawai['nip'],
                'nama_lengkap' => $pegawai['nama_lengkap'],
                'id_opd'       => $pegawai['id_opd'],
                'level'        => 'pegawai',
                'logged_in'    => TRUE
            ]);

            // Jangan kirim password di JSON response
            unset($pegawai['password']);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Login Berhasil',
                'data'    => $pegawai,
                'session_id' => session_id() // Informasi tambahan untuk developer
            ]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'NIP atau Password Salah']);
    }

    /**
     * ENDPOINT: /api/logout
     * METHOD: POST / GET
     */
    public function logout()
    {
        session()->destroy();
        return $this->response->setJSON(['status' => 'success', 'message' => 'Logout Berhasil']);
    }

    public function index()
    {
        return $this->response->setJSON(['status' => 'success', 'message' => 'API is running']);
    }

    public function test_query()
    {
        $db = \Config\Database::connect();
        $res = $db->query("SELECT p.nama_lengkap, o.nama_opd, COUNT(pr.id_presensi) as jml FROM presensi pr JOIN pegawai p ON pr.nip = p.nip JOIN opd o ON p.id_opd = o.id_opd WHERE (pr.status_masuk LIKE '%Hadir%' OR pr.status_masuk LIKE '%Istimewa%') AND MONTH(pr.tanggal) = 6 AND YEAR(pr.tanggal) = 2026 GROUP BY p.nip ORDER BY jml DESC LIMIT 15")->getResultArray();
        return $this->response->setJSON($res);
    }

    /**
     * ENDPOINT: /api/dashboard
     * METHOD: GET / POST
     * Wajib menyertakan Cookie Session
     */
    public function dashboard()
    {
        $id_pegawai = session()->get('id_pegawai');

        if (!$id_pegawai || !session()->get('logged_in')) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Sesi berakhir atau belum login.']);
        }

        session_write_close();

        // Ambil data mentah persis seperti Pwa::index()
        $pegawai = $this->db->table('pegawai')
            ->select('pegawai.*, opd.id_jam_kerja as id_jam_opd, opd.nama_opd, opd.latitude as lat_pusat, opd.longitude as lng_pusat, opd.radius as radius_opd')
            ->join('opd', 'pegawai.id_opd = opd.id_opd', 'left')
            ->where('id_pegawai', $id_pegawai)
            ->get()->getRowArray();

        if (!$pegawai) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Pegawai tidak ditemukan.']);
        }

        unset($pegawai['password']);

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

        // [SMART AUTO-HEAL]: Periksa dan pulihkan anomali shift tertukar otomatis sebelum kirim response ke APK
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

        // AUTO-SYNC KEHADIRAN APEL KE PRESENSI HARIAN (UNTUK APK ANDROID & REKAP)
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
                    'device_info'           => 'Sinkronisasi Kehadiran Apel',
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
            ->where('status_approval', 'Disetujui')
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

        // Lokasi
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

        $is_hari_ini_cito = false;
        $id_final_jam_cek = ($cek_plotting) ? $cek_plotting->id_jam_kerja : ($pegawai['id_jam_opd'] ?? 1);
        $jam_kerja_aktif = $this->db->table('jam_kerja')->where('id_jam_kerja', $id_final_jam_cek)->get()->getRow();

        if ($jam_kerja_aktif && (strpos(strtolower($jam_kerja_aktif->nama_jadwal), 'cito') !== false || strpos(strtolower($jam_kerja_aktif->nama_jadwal), 'standby') !== false)) {
            $is_hari_ini_cito = true;
        }

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
            log_message('error', 'API Dashboard TPP calculation error: ' . $t->getMessage());
        }

        $response_data = [
            'status'          => 'success',
            'pegawai'         => $pegawai,
            'jadwal_hari_ini' => $jadwal_hari_ini,
            'libur'           => $status_libur_aktif,
            'libur_mendatang' => $libur_data['mendatang'],
            'terakhir'        => $terakhir,
            'riwayat'         => $riwayat,
            'bulan_aktif'     => $filter_bulan,
            'tahun_aktif'     => $filter_tahun,
            'tugas_apel'      => $tugas_apel,
            'pengajuan_aktif' => $pengajuan_aktif,
            'nama_hari_indo'  => $nama_hari_indo,
            'cito_aktif'      => $cito_aktif,
            'is_hari_ini_cito' => $is_hari_ini_cito,
            'bebas_radius'    => $bebas_radius,
            'multi_lokasi'    => $multi_lokasi,
            'is_bercadar'     => $pegawai['is_bercadar'] ?? 0,
            'tpp_info'        => $tpp_info
        ];

        return $this->response->setJSON($response_data);
    }

    /**
     * ENDPOINT: /api/tpp_detail
     * METHOD: GET / POST
     * Detail Rincian TPP Dinamis Pegawai
     */
    public function tpp_detail()
    {
        $id_pegawai = session()->get('id_pegawai');
        if (!$id_pegawai || !session()->get('logged_in')) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Sesi berakhir atau belum login.']);
        }

        $bulan = (int)($this->request->getGet('bulan') ?? ($this->request->getPost('bulan') ?? date('m')));
        $tahun = (int)($this->request->getGet('tahun') ?? ($this->request->getPost('tahun') ?? date('Y')));

        $tpp = \App\Controllers\TppDinamis::get_tpp_pegawai($id_pegawai, $bulan, $tahun);
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $tpp
        ]);
    }

    /**
     * ENDPOINT: /api/submit_absen
     * Ini bisa langsung meneruskan/memanggil logic Pwa::submit_absen
     * Karena Pwa::submit_absen sudah mereturn JSON murni!
     */
    public function submit_absen()
    {
        $pwaController = new \App\Controllers\Pwa();
        $pwaController->initController($this->request, $this->response, $this->logger);
        return $this->_cleanJsonResponse($pwaController->submit_absen());
    }

    /**
     * ENDPOINT: /api/submit_cito
     */
    public function submit_cito()
    {
        $pwaController = new \App\Controllers\Pwa();
        $pwaController->initController($this->request, $this->response, $this->logger);
        return $this->_cleanJsonResponse($pwaController->submit_cito());
    }

    /**
     * ENDPOINT: /api/submit_absen_apel
     */
    public function submit_absen_apel()
    {
        try {
            $pwaController = new \App\Controllers\Pwa();
            $pwaController->initController($this->request, $this->response, $this->logger);
            return $this->_cleanJsonResponse($pwaController->submit_absen_apel());
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(200)->setJSON([
                'status'  => 'error',
                'message' => 'Terjadi kendala server: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * ENDPOINT: /api/submit_izin
     * Digunakan untuk pengajuan Cuti, Sakit, Izin, dan Tugas Luar
     */
    public function submit_izin()
    {
        $pwaController = new \App\Controllers\Pwa();
        $pwaController->initController($this->request, $this->response, $this->logger);
        return $this->_cleanJsonResponse($pwaController->submit_izin_pwa());
    }

    private function _cleanJsonResponse($responseObj)
    {
        try {
            $body = $responseObj->getBody();
            if ($body) {
                $data = json_decode($body, true);
                if (is_array($data)) {
                    // Pastikan selalu status code 200 agar Retrofit Android dapat mem-parse JSON dengan sempurna
                    return $this->response->setStatusCode(200)->setJSON($data);
                }
            }
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(200)->setJSON([
                'status'  => 'error',
                'message' => 'Gagal memproses respon: ' . $e->getMessage()
            ]);
        }
        return $responseObj;
    }

    public function update_fcm_token()
    {
        $nip = $this->request->getPost('nip');
        $token = $this->request->getPost('token');

        if (!$nip || !$token) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'NIP atau Token kosong']);
        }

        $db = \Config\Database::connect();
        if (!$db->fieldExists('fcm_token', 'pegawai')) {
            $forge = \Config\Database::forge();
            $fields = [
                'fcm_token' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]
            ];
            $forge->addColumn('pegawai', $fields);
        }

        $db->table('pegawai')->where('nip', $nip)->update(['fcm_token' => $token]);

        return $this->response->setJSON(['status' => 'success', 'message' => 'FCM Token berhasil diperbarui']);
    }
}

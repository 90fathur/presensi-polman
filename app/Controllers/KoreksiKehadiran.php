<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class KoreksiKehadiran extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        // Hanya bisa diakses oleh Admin OPD
        if (session()->get('level') !== 'opd') {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
        }

        $id_opd = session()->get('id_opd');

        // Cek apakah fitur koreksi dibuka untuk OPD ini
        $opdData = $db->table('opd')->where('id_opd', $id_opd)->get()->getRow();
        if (!$opdData || $opdData->is_koreksi_open == 0) {
            return redirect()->to('dashboard')->with('error', 'Fitur Koreksi Kehadiran sedang ditutup oleh Admin Kabupaten.');
        }

        // Ambil daftar pegawai di OPD ini
        $pegawai = $db->table('pegawai')->where('id_opd', $id_opd)->orderBy('nama_lengkap', 'ASC')->get()->getResultArray();

        $data = [
            'title'   => 'Pengajuan Koreksi Kehadiran',
            'pegawai' => $pegawai
        ];

        return view('koreksi/index_opd', $data);
    }

    public function get_dates()
    {
        try {
            $db = \Config\Database::connect();
            $id_pegawai_arr = $this->request->getPost('id_pegawai'); // Now an array
            $bulan = $this->request->getPost('bulan');
            $tahun = $this->request->getPost('tahun');

            if (empty($id_pegawai_arr) || !$bulan || !$tahun) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
            }

            if (!is_array($id_pegawai_arr)) {
                $id_pegawai_arr = [$id_pegawai_arr];
            }

            $id_opd = session()->get('id_opd');

            // Jumlah hari dalam bulan
            $jml_hari = (int)date('t', strtotime(sprintf("%04d-%02d-01", $tahun, $bulan)));

            $pegawai_data = [];

            $map_hari = [
                'Monday'    => 'senin',
                'Tuesday'   => 'selasa',
                'Wednesday' => 'rabu',
                'Thursday'  => 'kamis',
                'Friday'    => 'jumat',
                'Saturday'  => 'sabtu',
                'Sunday'    => 'minggu'
            ];

            // Ambil daftar libur nasional untuk bulan & tahun yang dipilih
            $libur_nasional = [];
            $hari_libur_db = $db->table('hari_libur')
                ->where('MONTH(tanggal)', $bulan)
                ->where('YEAR(tanggal)', $tahun)
                ->get()->getResultArray();
            foreach ($hari_libur_db as $hl) {
                $libur_nasional[$hl['tanggal']] = $hl['keterangan'];
            }

            foreach ($id_pegawai_arr as $id_pegawai) {
                $pegawai = $db->table('pegawai')->where('id_pegawai', $id_pegawai)->get()->getRow();
                if (!$pegawai) continue;

                $opd = $db->table('opd')->where('id_opd', $pegawai->id_opd)->get()->getRow();
                $id_jam_kerja = $opd ? $opd->id_jam_kerja : 0;

                // Ambil jadwal lengkap pegawai ini
                $jadwal_list = $db->table('jam_kerja_detail')->where('id_jam_kerja', $id_jam_kerja)->get()->getResultArray();
                $jadwal_map = [];
                foreach ($jadwal_list as $j) {
                    $jadwal_map[strtolower($j['hari'])] = $j;
                }

                $dates = [];
                for ($i = 1; $i <= $jml_hari; $i++) {
                    $tanggal = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);

                    $nama_hari_eng = date('l', strtotime($tanggal));
                    $hari_indo = $map_hari[$nama_hari_eng];

                    $is_holiday = true;
                    $holiday_name = 'Hari Libur / Bukan Hari Kerja';

                    // Cek libur nasional
                    if (isset($libur_nasional[$tanggal])) {
                        $is_holiday = true;
                        $holiday_name = 'Libur Nasional: ' . $libur_nasional[$tanggal];
                    } else {
                        // Cek apakah di jadwal hari ini masuk
                        if (isset($jadwal_map[$hari_indo])) {
                            $j_hari = $jadwal_map[$hari_indo];
                            if (!empty($j_hari['jam_masuk']) && $j_hari['jam_masuk'] !== '00:00:00') {
                                $is_holiday = false;
                            }
                        }
                    }

                    // Jika shift dinamis (biasanya jam_masuk kosong semua atau tidak spesifik hari), kita buka saja centangnya
                    // Namun untuk sementara, kita andalkan map_jadwal_full

                    // Cek presensi existing
                    $presensi = $db->table('presensi')
                        ->where('id_pegawai', $id_pegawai)
                        ->where('tanggal', $tanggal)
                        ->get()->getRow();

                    // Cek koreksi existing
                    $koreksi = $db->table('koreksi_presensi')
                        ->where('id_pegawai', $id_pegawai)
                        ->where('tanggal', $tanggal)
                        ->get()->getRow();

                    $is_future = ($tanggal > date('Y-m-d'));

                    $dates[$i] = [
                        'tanggal' => $tanggal,
                        'is_holiday' => $is_holiday,
                        'holiday_name' => $holiday_name,
                        'has_presensi' => ($presensi) ? true : false,
                        'presensi_info' => ($presensi) ? 'Masuk: ' . $presensi->jam_masuk_aktual : '',
                        'koreksi_status' => ($koreksi) ? $koreksi->status : null,
                        'koreksi_ket' => ($koreksi) ? $koreksi->keterangan : '',
                        'is_future' => $is_future
                    ];
                }

                $pegawai_data[] = [
                    'id_pegawai' => $pegawai->id_pegawai,
                    'nama_lengkap' => $pegawai->nama_lengkap,
                    'nip' => $pegawai->nip,
                    'dates' => $dates
                ];
            }

            return $this->response->setJSON(['status' => 'success', 'data' => $pegawai_data, 'jml_hari' => $jml_hari]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage() . ' di baris ' . $e->getLine()]);
        }
    }

    public function submit_koreksi()
    {
        $db = \Config\Database::connect();
        $koreksi_data = $this->request->getPost('koreksi'); // array: id_pegawai => [tgl1, tgl2]
        $keterangan = $this->request->getPost('keterangan');

        if (empty($koreksi_data)) {
            return redirect()->back()->with('error', 'Tidak ada tanggal yang dipilih pada matriks.');
        }

        $id_opd = session()->get('id_opd');
        $admin_id = session()->get('id_user');

        $builder = $db->table('koreksi_presensi');

        foreach ($koreksi_data as $id_pegawai => $tanggal_koreksi) {
            foreach ($tanggal_koreksi as $tgl) {
                // Cek jika sudah ada pending koreksi
                $exist = $builder->where('id_pegawai', $id_pegawai)->where('tanggal', $tgl)->get()->getRow();
                if ($exist && $exist->status == 'pending') {
                    continue; // lewati jika sudah diajukan
                }

                if ($exist && $exist->status == 'rejected') {
                    // Update ke pending lagi
                    $builder->where('id_koreksi', $exist->id_koreksi)->update([
                        'status' => 'pending',
                        'keterangan' => $keterangan,
                        'created_by' => $admin_id,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                } else if (!$exist) {
                    // Insert baru
                    $builder->insert([
                        'id_pegawai' => $id_pegawai,
                        'id_opd' => $id_opd,
                        'tanggal' => $tgl,
                        'keterangan' => $keterangan,
                        'status' => 'pending',
                        'created_by' => $admin_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Pengajuan koreksi massal berhasil dikirim. Menunggu persetujuan Admin Kabupaten.');
    }

    // =========================================================================
    // FUNGSI UNTUK ADMIN KABUPATEN
    // =========================================================================

    public function approval()
    {
        // Hanya Admin Kabupaten
        if (session()->get('level') !== 'kabupaten' && session()->get('level') !== 'Superadmin') {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
        }

        $db = \Config\Database::connect();

        $id_opd = $this->request->getGet('id_opd');

        // Fetch OPD list
        $opd_list = $db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        $builder = $db->table('koreksi_presensi k')
            ->select('k.*, p.nama_lengkap, p.nip, o.nama_opd')
            ->join('pegawai p', 'k.id_pegawai = p.id_pegawai')
            ->join('opd o', 'k.id_opd = o.id_opd')
            ->where('k.status', 'pending');

        if (!empty($id_opd)) {
            $builder->where('k.id_opd', $id_opd);
        }

        $pending_raw = $builder->orderBy('k.tanggal', 'ASC')->get()->getResultArray();

        $pending_grouped = [];
        foreach ($pending_raw as $row) {
            $id = $row['id_pegawai'];
            if (!isset($pending_grouped[$id])) {
                $pending_grouped[$id] = [
                    'id_pegawai' => $id,
                    'nama_lengkap' => $row['nama_lengkap'],
                    'nip' => $row['nip'],
                    'nama_opd' => $row['nama_opd'],
                    'koreksi_ids' => [],
                    'tanggal_list' => [],
                    'keterangan_list' => []
                ];
            }
            $pending_grouped[$id]['koreksi_ids'][] = $row['id_koreksi'];
            $pending_grouped[$id]['tanggal_list'][] = date('d M Y', strtotime($row['tanggal']));
            $pending_grouped[$id]['keterangan_list'][] = $row['keterangan'] ?: '-';
        }

        $data = [
            'title' => 'Approval Koreksi Kehadiran',
            'pending_grouped' => array_values($pending_grouped),
            'opd_list' => $opd_list,
            'selected_opd' => $id_opd
        ];

        return view('koreksi/approval', $data);
    }

    private function _do_approve($id_koreksi)
    {
        $db = \Config\Database::connect();
        // Ambil data koreksi
        $koreksi = $db->table('koreksi_presensi')->where('id_koreksi', $id_koreksi)->get()->getRow();
        if (!$koreksi || $koreksi->status !== 'pending') {
            return false;
        }

        $id_pegawai = $koreksi->id_pegawai;
        $tanggal = $koreksi->tanggal;

        // Cari jam kerja pegawai pada hari itu
        $pegawai = $db->table('pegawai')->where('id_pegawai', $id_pegawai)->get()->getRow();
        if (!$pegawai) {
            return false;
        }

        $opd = $db->table('opd')->where('id_opd', $pegawai->id_opd)->get()->getRow();
        $id_jam_kerja = $opd ? $opd->id_jam_kerja : 0;

        // Ambil jam kerja dari jam_kerja_detail berdasarkan hari
        $nama_hari = strtolower(date('l', strtotime($tanggal)));
        $map_hari = [
            'monday'    => 'senin',
            'tuesday'   => 'selasa',
            'wednesday' => 'rabu',
            'thursday'  => 'kamis',
            'friday'    => 'jumat',
            'saturday'  => 'sabtu',
            'sunday'    => 'minggu'
        ];
        $hari_indo = $map_hari[$nama_hari];

        $jadwal = $db->table('jam_kerja_detail')
            ->where('id_jam_kerja', $id_jam_kerja)
            ->where('hari', $hari_indo)
            ->get()->getRow();

        // Acak jam masuk dan jam pulang
        $jam_masuk_aktual = null;
        $jam_pulang_aktual = null;

        if ($jadwal && !empty($jadwal->jam_masuk) && $jadwal->jam_masuk !== '00:00:00') {
            // Jam masuk diacak 10 menit sebelum jam masuk
            $start_masuk = strtotime($tanggal . ' ' . $jadwal->jam_masuk) - (10 * 60);
            $end_masuk = strtotime($tanggal . ' ' . $jadwal->jam_masuk);
            $rand_masuk = rand($start_masuk, $end_masuk);
            $jam_masuk_aktual = date('H:i:s', $rand_masuk);
        } else {
            // Default kalau tidak ada jam kerja (hari libur)
            $jam_masuk_aktual = '07:30:00';
        }

        if ($jadwal && !empty($jadwal->jam_pulang) && $jadwal->jam_pulang !== '00:00:00') {
            // Jam pulang diacak 30 menit setelah jam pulang
            $start_pulang = strtotime($tanggal . ' ' . $jadwal->jam_pulang);
            $end_pulang = strtotime($tanggal . ' ' . $jadwal->jam_pulang) + (30 * 60);
            $rand_pulang = rand($start_pulang, $end_pulang);
            $jam_pulang_aktual = date('H:i:s', $rand_pulang);
        } else {
            $jam_pulang_aktual = '16:00:00';
        }

        // Cek apakah ada record presensi pada hari tersebut
        $presensi = $db->table('presensi')
            ->where('id_pegawai', $id_pegawai)
            ->where('tanggal', $tanggal)
            ->get()->getRow();

        $dataPresensi = [
            'id_pegawai'            => $id_pegawai,
            'nip'                   => $pegawai->nip,
            'jam_masuk_aktual'      => $jam_masuk_aktual,
            'jam_pulang_aktual'     => $jam_pulang_aktual,
            'status_masuk'          => 'Tepat Waktu',
            'status_pulang'         => 'Selesai',
            'kategori'              => 'Hadir',
            'id_jam_kerja_snapshot' => $id_jam_kerja,
            'updated_at'            => date('Y-m-d H:i:s')
        ];

        if ($presensi) {
            // Update
            $db->table('presensi')->where('id_presensi', $presensi->id_presensi)->update($dataPresensi);
        } else {
            // Insert
            $dataPresensi['tanggal'] = $tanggal;
            $dataPresensi['created_at'] = date('Y-m-d H:i:s');
            $db->table('presensi')->insert($dataPresensi);
        }

        // Update status koreksi
        $db->table('koreksi_presensi')->where('id_koreksi', $id_koreksi)->update([
            'status' => 'approved',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return true;
    }

    public function approve_action($id_koreksi)
    {
        if (session()->get('level') !== 'kabupaten' && session()->get('level') !== 'Superadmin') {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
        }

        $this->_do_approve($id_koreksi);

        return redirect()->back()->with('success', 'Koreksi berhasil di-approve.');
    }

    public function reject_action($id_koreksi)
    {
        if (session()->get('level') !== 'kabupaten' && session()->get('level') !== 'Superadmin') {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
        }

        $db = \Config\Database::connect();

        $db->table('koreksi_presensi')->where('id_koreksi', $id_koreksi)->update([
            'status' => 'rejected',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return redirect()->back()->with('success', 'Koreksi ditolak.');
    }

    public function approve_bulk()
    {
        if (session()->get('level') !== 'kabupaten' && session()->get('level') !== 'Superadmin') {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
        }

        // koreksi_ids bisa berupa array of IDs, atau array of comma-separated string (karena grouping)
        $koreksi_groups = $this->request->getPost('koreksi_ids');
        if (empty($koreksi_groups) || !is_array($koreksi_groups)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih.');
        }

        $count = 0;
        foreach ($koreksi_groups as $group) {
            $ids = explode(',', $group);
            foreach ($ids as $id) {
                if ($this->_do_approve($id)) {
                    $count++;
                }
            }
        }

        return redirect()->back()->with('success', $count . ' pengajuan koreksi berhasil di-approve.');
    }

    public function reject_bulk()
    {
        if (session()->get('level') !== 'kabupaten' && session()->get('level') !== 'Superadmin') {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
        }

        $koreksi_groups = $this->request->getPost('koreksi_ids');
        if (empty($koreksi_groups) || !is_array($koreksi_groups)) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih.');
        }

        $all_ids = [];
        foreach ($koreksi_groups as $group) {
            $ids = explode(',', $group);
            $all_ids = array_merge($all_ids, $ids);
        }

        if (count($all_ids) > 0) {
            $db = \Config\Database::connect();
            $db->table('koreksi_presensi')->whereIn('id_koreksi', $all_ids)->update([
                'status' => 'rejected',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        return redirect()->back()->with('success', count($all_ids) . ' pengajuan koreksi berhasil ditolak.');
    }

    public function fix_db()
    {
        if (session()->get('level') !== 'kabupaten' && session()->get('level') !== 'Superadmin') {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak.');
        }

        $db = \Config\Database::connect();

        $query1 = "UPDATE presensi p JOIN pegawai pg ON p.id_pegawai = pg.id_pegawai SET p.nip = pg.nip WHERE p.nip IS NULL OR p.nip = ''";
        $db->query($query1);
        $affected1 = $db->affectedRows();

        $query2 = "UPDATE presensi SET status_masuk = 'Tepat Waktu', status_pulang = 'Selesai' WHERE kategori = 'Hadir' AND (status_masuk IS NULL OR status_masuk = '')";
        $db->query($query2);
        $affected2 = $db->affectedRows();

        echo "<h3>Proses Perbaikan Database Selesai</h3>";
        echo "Berhasil menyuntikkan NIP yang hilang: <b>$affected1</b> data.<br>";
        echo "Berhasil melengkapi status kehadiran: <b>$affected2</b> data.<br>";
        echo "<br><a href='" . base_url('koreksikehadiran/approval') . "'>Kembali ke Halaman Approval</a>";
    }
}

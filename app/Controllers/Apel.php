<?php

namespace App\Controllers;

class Apel extends BaseController
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

    public function index()
    {
        $level = session()->get('level');
        $builder = $this->db->table('apel');

        $perPage = 20;
        $currentPage = (int)($this->request->getVar('page_apel') ?? 1);
        $totalCount = $builder->countAllResults(false);

        $list_apel = $builder->orderBy('tanggal', 'DESC')
            ->limit($perPage, ($currentPage - 1) * $perPage)
            ->get()->getResultArray();

        $pager = service('pager');
        $pager_links = $pager->makeLinks($currentPage, $perPage, $totalCount, 'bootstrap_pagination', 0, 'apel');

        return view('admin/apel_index', [
            'list_apel' => $list_apel,
            'pager'     => $pager_links,
            'level'     => $level,
            'title'     => 'Manajemen Jadwal Apel'
        ]);
    }

    public function store()
    {
        if (!in_array(session()->get('level'), ['Superadmin', 'kabupaten'])) return redirect()->back();

        $this->db->table('apel')->insert([
            'nama_apel'  => $this->request->getPost('nama_apel'),
            'tanggal'    => $this->request->getPost('tanggal'),
            'jam_mulai'  => $this->request->getPost('jam_mulai'),
            'jam_akhir'  => $this->request->getPost('jam_akhir'),
            'latitude'   => $this->request->getPost('latitude'),
            'longitude'  => $this->request->getPost('longitude'),
            'radius'     => $this->request->getPost('radius'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return redirect()->to(base_url('apel'))->with('success', 'Jadwal Apel Berhasil Diterbitkan!');
    }

    public function update($id)
    {
        if (!in_array(session()->get('level'), ['Superadmin', 'kabupaten'])) return redirect()->back();

        $this->db->table('apel')->where('id_apel', $id)->update([
            'nama_apel'  => $this->request->getPost('nama_apel'),
            'tanggal'    => $this->request->getPost('tanggal'),
            'jam_mulai'  => $this->request->getPost('jam_mulai'),
            'jam_akhir'  => $this->request->getPost('jam_akhir'),
            'latitude'   => $this->request->getPost('latitude'),
            'longitude'  => $this->request->getPost('longitude'),
            'radius'     => $this->request->getPost('radius')
        ]);
        return redirect()->to(base_url('apel'))->with('success', 'Jadwal Apel Berhasil Diperbarui!');
    }

    public function delete($id)
    {
        if (!in_array(session()->get('level'), ['Superadmin', 'kabupaten'])) return redirect()->back();

        $this->db->transStart();
        // Hapus utusan dulu agar tidak ada data sampah
        $this->db->table('apel_peserta')->where('id_apel', $id)->delete();
        // Hapus jadwal apel
        $this->db->table('apel')->where('id_apel', $id)->delete();
        $this->db->transComplete();

        return redirect()->to(base_url('apel'))->with('success', 'Jadwal Apel Berhasil Dihapus!');
    }

    // =======================================================================
    // [PERBAIKAN ENTERPRISE]: KELOLA UTUSAN (FILTER INDUK DAN UPTD ANAK)
    // =======================================================================
    public function kelola($id_apel)
    {
        $level = session()->get('level');
        $id_opd_admin = session()->get('id_opd');
        $apel = $this->db->table('apel')->where('id_apel', $id_apel)->get()->getRowArray();

        $builder_p = $this->db->table('pegawai');
        $list_opd = $this->db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();
        $filter_opd = $this->request->getGet('id_opd');

        // Deteksi Dinas Induk & Anak
        $admin_opd_info = $this->db->table('opd')->where('id_opd', $id_opd_admin)->get()->getRow();
        $admin_opd_name = $admin_opd_info ? strtoupper($admin_opd_info->nama_opd) : '';

        $child_opds = [];
        $is_induk_opd = false;

        foreach ($list_opd as $o) {
            $nama_upper = strtoupper($o['nama_opd']);
            $is_child = false;

            if (!in_array($level, ['Superadmin', 'kabupaten'])) {
                if (strpos($admin_opd_name, 'DINAS KESEHATAN') !== false && strpos($nama_upper, 'DINAS KESEHATAN') !== false) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'DINAS PENDIDIKAN') !== false && (strpos($nama_upper, 'SDN ') !== false || strpos($nama_upper, 'SMPN ') !== false || strpos($nama_upper, 'TK ') !== false)) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'RSUD HAJJAH ANDI DEPU') !== false && strpos($nama_upper, 'RSUD HAJJAH ANDI DEPU') !== false) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'RSUD WONOMULYO') !== false && strpos($nama_upper, 'RSUD WONOMULYO') !== false) {
                    $is_child = true;
                }

                if ($is_child && $o['id_opd'] != $id_opd_admin) {
                    $is_induk_opd = true;
                    $child_opds[] = $o;
                }
            }
        }

        // Tampilkan Pegawai sesuai Hak Akses Filter
        if (in_array($level, ['Superadmin', 'kabupaten'])) {
            if (!empty($filter_opd)) {
                $builder_p->where('id_opd', $filter_opd);
                $pegawai = $builder_p->orderBy('nama_lengkap', 'ASC')->get()->getResultArray();
            } else {
                $pegawai = [];
            }
        } else {
            if ($is_induk_opd && !empty($filter_opd)) {
                // Validasi Keamanan: Jika admin induk milih dropdown, pastikan itu anaknya
                $valid = false;
                foreach ($child_opds as $c) {
                    if ($c['id_opd'] == $filter_opd) $valid = true;
                }

                if ($valid || $filter_opd == $id_opd_admin) {
                    $builder_p->where('id_opd', $filter_opd);
                } else {
                    $builder_p->where('id_opd', $id_opd_admin); // Kunci ke dirinya sendiri
                }
                $pegawai = $builder_p->orderBy('nama_lengkap', 'ASC')->get()->getResultArray();
            } else {
                $builder_p->where('id_opd', $id_opd_admin);
                $pegawai = $builder_p->orderBy('nama_lengkap', 'ASC')->get()->getResultArray();
            }
        }

        // Ambil Utusan Sesuai Filter
        $utusan = $this->db->table('apel_peserta')->where('id_apel', $id_apel);
        if (in_array($level, ['Superadmin', 'kabupaten'])) {
            if (!empty($filter_opd)) $utusan->where('id_opd', $filter_opd);
        } else {
            if ($is_induk_opd && !empty($filter_opd)) {
                $valid = false;
                foreach ($child_opds as $c) {
                    if ($c['id_opd'] == $filter_opd) $valid = true;
                }
                if ($valid || $filter_opd == $id_opd_admin) {
                    $utusan->where('id_opd', $filter_opd);
                } else {
                    $utusan->where('id_opd', $id_opd_admin);
                }
            } else {
                $utusan->where('id_opd', $id_opd_admin);
            }
        }

        $utusan_ids = array_column($utusan->get()->getResultArray(), 'id_pegawai');

        return view('admin/apel_kelola_peserta', [
            'apel'         => $apel,
            'pegawai'      => $pegawai,
            'utusan_ids'   => $utusan_ids,
            'list_opd'     => $list_opd,
            'filter_opd'   => $filter_opd,
            'level'        => $level,
            'is_induk_opd' => $is_induk_opd,
            'child_opds'   => $child_opds,
            'title'        => 'Kelola Utusan Apel'
        ]);
    }

    // =======================================================================
    // [PERBAIKAN]: SIMPAN UTUSAN (MENERIMA TARGET DARI INDUK JUGA)
    // =======================================================================
    public function rekap($id_apel)
    {
        $level = session()->get('level');
        $id_opd_admin = session()->get('id_opd');
        $apel = $this->db->table('apel')->where('id_apel', $id_apel)->get()->getRowArray();

        if (!$apel) {
            return redirect()->to('/apel')->with('error', 'Data apel tidak ditemukan.');
        }

        // AUTO-SYNC: Seluruh peserta yang sudah absen di apel otomatis masuk/update ke presensi harian
        $this->_sync_ke_presensi_internal($id_apel);

        // Ambil filter OPD jika ada
        $filter_opd = $this->request->getGet('id_opd');

        // Ambil data OPD admin untuk pengecekan hak akses bawahan
        $admin_opd_info = $this->db->table('opd')->where('id_opd', $id_opd_admin)->get()->getRow();
        $admin_opd_name = $admin_opd_info ? strtoupper($admin_opd_info->nama_opd) : '';
        $list_opd = $this->db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResultArray();

        $child_opds = [];
        $is_induk_opd = false;
        foreach ($list_opd as $o) {
            $nama_upper = strtoupper($o['nama_opd']);
            $is_child = false;
            if (!in_array($level, ['Superadmin', 'kabupaten'])) {
                if (strpos($admin_opd_name, 'DINAS KESEHATAN') !== false && strpos($nama_upper, 'DINAS KESEHATAN') !== false) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'DINAS PENDIDIKAN') !== false && (strpos($nama_upper, 'SDN ') !== false || strpos($nama_upper, 'SMPN ') !== false || strpos($nama_upper, 'TK ') !== false)) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'RSUD HAJJAH ANDI DEPU') !== false && strpos($nama_upper, 'RSUD HAJJAH ANDI DEPU') !== false) {
                    $is_child = true;
                } elseif (strpos($admin_opd_name, 'RSUD WONOMULYO') !== false && strpos($nama_upper, 'RSUD WONOMULYO') !== false) {
                    $is_child = true;
                }

                if ($is_child && $o['id_opd'] != $id_opd_admin) {
                    $is_induk_opd = true;
                    $child_opds[] = $o; // Simpan seluruh objek opd untuk view
                }
            }
        }

        $builder = $this->db->table('apel_peserta')
            ->select('apel_peserta.*, pegawai.nip, pegawai.nama_lengkap, opd.nama_opd')
            ->join('pegawai', 'pegawai.id_pegawai = apel_peserta.id_pegawai')
            ->join('opd', 'opd.id_opd = pegawai.id_opd', 'left')
            ->where('apel_peserta.id_apel', $id_apel)
            ->orderBy('opd.nama_opd', 'ASC')
            ->orderBy('pegawai.nama_lengkap', 'ASC');

        if (!in_array($level, ['Superadmin', 'kabupaten'])) {
            $allowed_opds = [$id_opd_admin];
            if (!empty($child_opds)) {
                $allowed_opds = array_merge($allowed_opds, array_column($child_opds, 'id_opd'));
            }
            $builder->whereIn('pegawai.id_opd', $allowed_opds);

            // Terapkan filter jika admin OPD induk memilih dropdown OPD spesifik
            if ($is_induk_opd && !empty($filter_opd)) {
                $valid = false;
                foreach ($child_opds as $c) {
                    if ($c['id_opd'] == $filter_opd) $valid = true;
                }
                if ($valid || $filter_opd == $id_opd_admin) {
                    $builder->where('pegawai.id_opd', $filter_opd);
                }
            }
        } else {
            // Superadmin / Kabupaten
            if (!empty($filter_opd)) {
                $builder->where('pegawai.id_opd', $filter_opd);
            }
        }

        $peserta = $builder->get()->getResultArray();

        $data = [
            'title'        => 'Rekap Apel Pegawai',
            'apel'         => $apel,
            'peserta'      => $peserta,
            'list_opd'     => $list_opd,
            'filter_opd'   => $filter_opd,
            'level'        => $level,
            'is_induk_opd' => $is_induk_opd,
            'child_opds'   => $child_opds,
            'id_opd_admin' => $id_opd_admin
        ];

        return view('admin/apel_rekap', $data);
    }

    public function simpan_utusan()
    {
        $id_apel = $this->request->getPost('id_apel');
        $level = session()->get('level');

        // TANGKAP TARGET OPD: Mencegah bug data utusan tertimpa ke OPD Superadmin atau Induk
        $id_opd_target = $this->request->getPost('id_opd_target');

        if (!empty($id_opd_target)) {
            $id_opd = $id_opd_target; // Pakai target dari dropdown jika ada
        } else {
            $id_opd = session()->get('id_opd');
        }

        $id_pegawai_pilihan = $this->request->getPost('id_pegawai');

        // 1. Ambil Informasi Detail Jadwal Apel untuk di WA
        $apel_info = $this->db->table('apel')->where('id_apel', $id_apel)->get()->getRow();

        $this->db->transStart();
        // Hanya hapus utusan pada OPD yang sedang difilter/dikelola saat ini
        $this->db->table('apel_peserta')->where(['id_apel' => $id_apel, 'id_opd' => $id_opd])->delete();

        $pegawai_data = [];

        if (!empty($id_pegawai_pilihan)) {
            $pegawai_data = $this->db->table('pegawai')
                ->select('id_pegawai, nip, nama_lengkap, nomor_hp')
                ->whereIn('id_pegawai', $id_pegawai_pilihan)
                ->get()->getResultArray();

            $map_nip = array_column($pegawai_data, 'nip', 'id_pegawai');

            foreach ($id_pegawai_pilihan as $idp) {
                $this->db->table('apel_peserta')->insert([
                    'id_apel'    => $id_apel,
                    'id_pegawai' => $idp,
                    'nip'        => $map_nip[$idp] ?? '',
                    'id_opd'     => $id_opd,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        $this->db->transComplete();

        // =======================================================================
        // [EKSEKUSI WHATSAPP GATEWAY & ONESIGNAL]: BROADCAST BULK
        // =======================================================================
        if ($this->db->transStatus() !== false && !empty($pegawai_data) && $apel_info) {
            helper('whatsapp');

            $tgl_apel = date('d/m/Y', strtotime($apel_info->tanggal));
            $jam_apel = date('H:i', strtotime($apel_info->jam_mulai));

            $target_numbers = [];
            $target_nips = [];

            foreach ($pegawai_data as $p) {
                if (!empty($p['nomor_hp'])) {
                    $target_numbers[] = $p['nomor_hp'];
                }
                if (!empty($p['nip'])) {
                    $target_nips[] = $p['nip'];
                }
            }

            // 1. KIRIM BROADCAST WHATSAPP
            if (!empty($target_numbers)) {
                $wa_msg = "*[PRESENSI POLMAN 2.0]*\n";
                $wa_msg .= "📢 *PANGGILAN TUGAS APEL*\n\n";
                $wa_msg .= "Halo Bapak/Ibu,\n";
                $wa_msg .= "Anda telah ditugaskan sebagai utusan OPD untuk mengikuti kegiatan:\n\n";
                $wa_msg .= "📌 *Nama Kegiatan:* {$apel_info->nama_apel}\n";
                $wa_msg .= "📅 *Tanggal:* $tgl_apel\n";
                $wa_msg .= "⏰ *Waktu:* $jam_apel WITA\n\n";
                $wa_msg .= "Buka Aplikasi Presensi Polman 2.0 dan klik tombol *APEL* jika Anda sudah berada di lokasi (Radius: {$apel_info->radius}m).\n\n";
                $wa_msg .= "_Pesan ini dikirim otomatis oleh Sistem Presensiku_";

                $nama_campaign = "Apel " . substr($apel_info->nama_apel, 0, 15);
                send_whatsapp_bulk($target_numbers, $wa_msg, $nama_campaign);
            }

            // 2. KIRIM PUSH NOTIFIKASI ONESIGNAL (Langsung ke HP)
            if (!empty($target_nips)) {
                $notif_judul = "Panggilan Tugas Apel 📢";
                $notif_pesan = "Anda ditugaskan mengikuti {$apel_info->nama_apel} tanggal $tgl_apel jam $jam_apel WITA. Buka aplikasi untuk absen.";

                $this->sendFastNotif($target_nips, $notif_judul, $notif_pesan);
            }
        }
        // =======================================================================

        // Redirect kembali ke halaman OPD yang sedang difilter
        $redirect_url = base_url('apel/kelola/' . $id_apel);
        if (!empty($id_opd_target)) {
            $redirect_url .= '?id_opd=' . $id_opd_target;
        }

        return redirect()->to($redirect_url)->with('success', 'Utusan Diperbarui, Broadcast WA & Push Notif Diproses!');
    }

    // =======================================================================
    // [FUNGSI BARU]: KIRIM PENGINGAT (REMINDER) KHUSUS UTUSAN APEL
    // =======================================================================
    public function remind_peserta()
    {
        if (!in_array(session()->get('level'), ['Superadmin', 'kabupaten'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak. Hanya Admin Kabupaten yang dapat mengirim pengingat.']);
        }

        $id_apel = $this->request->getPost('id_apel');

        if (!$id_apel) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID Apel tidak valid.']);
        }

        $apel_info = $this->db->table('apel')->where('id_apel', $id_apel)->get()->getRow();
        if (!$apel_info) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data jadwal Apel tidak ditemukan.']);
        }

        // Ambil data pegawai yang sudah ditandai sebagai peserta
        $peserta = $this->db->table('apel_peserta')
            ->select('pegawai.nip, pegawai.nomor_hp')
            ->join('pegawai', 'pegawai.id_pegawai = apel_peserta.id_pegawai')
            ->where('apel_peserta.id_apel', $id_apel)
            ->get()->getResultArray();

        if (empty($peserta)) {
            return $this->response->setJSON(['status' => 'warning', 'message' => 'Belum ada satu pun pegawai yang dipilih menjadi utusan pada jadwal Apel ini.']);
        }

        $target_numbers = [];
        $target_nips = [];

        foreach ($peserta as $p) {
            if (!empty($p['nomor_hp'])) {
                $target_numbers[] = $p['nomor_hp'];
            }
            if (!empty($p['nip'])) {
                $target_nips[] = $p['nip'];
            }
        }

        $tgl_apel = date('d/m/Y', strtotime($apel_info->tanggal));
        $jam_apel = date('H:i', strtotime($apel_info->jam_mulai));

        // 1. Eksekusi Broadcast WhatsApp
        if (!empty($target_numbers)) {
            helper('whatsapp');
            $wa_msg = "*[REMINDER APEL - PRESENSI POLMAN]*\n\n";
            $wa_msg .= "Halo Bapak/Ibu,\n";
            $wa_msg .= "Mengingatkan kembali tugas Anda sebagai utusan pada jadwal berikut:\n\n";
            $wa_msg .= "📌 *Kegiatan:* {$apel_info->nama_apel}\n";
            $wa_msg .= "📅 *Tanggal:* $tgl_apel\n";
            $wa_msg .= "⏰ *Waktu:* $jam_apel WITA\n\n";
            $wa_msg .= "Mohon hadir tepat waktu dan lakukan absen menggunakan Aplikasi Presensi 2.0 setelah berada di lokasi (Radius {$apel_info->radius}m).\n\n";
            $wa_msg .= "_Pesan ini dikirim otomatis oleh Sistem Presensiku_";

            $nama_campaign = "Remind " . substr($apel_info->nama_apel, 0, 10);
            send_whatsapp_bulk($target_numbers, $wa_msg, $nama_campaign);
        }

        // 2. Eksekusi Push Notifikasi OneSignal
        if (!empty($target_nips)) {
            $notif_judul = "Pengingat Apel 🔔";
            $notif_pesan = "Mengingatkan jadwal {$apel_info->nama_apel} tanggal $tgl_apel jam $jam_apel WITA. Jangan lupa bersiap dan lakukan absen!";

            $this->sendFastNotif($target_nips, $notif_judul, $notif_pesan);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Pengingat berhasil ditembakkan ke ' . count($peserta) . ' utusan (' . count($target_numbers) . ' via WA, ' . count($target_nips) . ' via Push Notifikasi).'
        ]);
    }

    /**
     * SINKRONISASI MANUAL VIA TOMBOL DI PANEL ADMIN
     */
    public function sinkron_ke_presensi($id_apel)
    {
        $total = $this->_sync_ke_presensi_internal($id_apel);
        return redirect()->to(base_url('apel/rekap/' . $id_apel))->with('success', "Sinkronisasi berhasil! Sebanyak {$total} peserta apel telah disinkronkan ke Presensi Harian (Masuk) & terbaca Hadir Pagi.");
    }

    /**
     * SINKRONISASI MASSAL INTERNAL:
     * Mengambil seluruh peserta pada kegiatan apel ini yang telah absen,
     * lalu meng-upsert ke tabel `presensi` agar di APK Android kotak MASUK terisi dan di REKAP terbaca Hadir Pagi.
     */
    private function _sync_ke_presensi_internal($id_apel)
    {
        $apel = $this->db->table('apel')->where('id_apel', $id_apel)->get()->getRowArray();
        if (!$apel) return 0;

        $peserta = $this->db->table('apel_peserta')
            ->select('apel_peserta.*, pegawai.nip, pegawai.id_opd, opd.id_jam_kerja as id_jam_opd')
            ->join('pegawai', 'pegawai.id_pegawai = apel_peserta.id_pegawai')
            ->join('opd', 'opd.id_opd = pegawai.id_opd', 'left')
            ->where('apel_peserta.id_apel', $id_apel)
            ->where('apel_peserta.jam_absen IS NOT NULL')
            ->where("apel_peserta.jam_absen != ''")
            ->get()->getResultArray();

        if (empty($peserta)) return 0;

        $tgl = $apel['tanggal'];
        $days = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];
        $nama_hari_indo = $days[date('D', strtotime($tgl))] ?? 'Senin';
        $jam_mulai_apel = !empty($apel['jam_mulai']) ? $apel['jam_mulai'] : '07:30:00';

        $synced_count = 0;

        foreach ($peserta as $p) {
            $jam_absen_clean = (strlen($p['jam_absen']) > 8) ? date('H:i:s', strtotime($p['jam_absen'])) : $p['jam_absen'];

            // Dapatkan info jadwal jam kerja pegawai
            $cek_plotting = null;
            try {
                $has_tm = $this->db->fieldExists('tanggal_mulai', 'plotting_shift');
                $b_plot = $this->db->table('plotting_shift')->where('id_pegawai', $p['id_pegawai']);
                if ($has_tm) {
                    $b_plot->where('tanggal_mulai <=', $tgl)->where('tanggal_selesai >=', $tgl);
                } else {
                    $b_plot->where('tanggal', $tgl);
                }
                $r_plot = $b_plot->get();
                $cek_plotting = $r_plot ? $r_plot->getRow() : null;
            } catch (\Throwable $e) {
                $cek_plotting = null;
            }

            $id_final_jam = ($cek_plotting) ? $cek_plotting->id_jam_kerja : ($p['id_jam_opd'] ?? 1);
            $r_jadwal = $this->db->table('jam_kerja_detail')->where(['id_jam_kerja' => $id_final_jam, 'hari' => $nama_hari_indo])->get();
            $jadwal = $r_jadwal ? $r_jadwal->getRowArray() : [];
            $jam_pulang_default = (!empty($jadwal['jam_pulang']) && $jadwal['jam_pulang'] !== 'JAGA') ? $jadwal['jam_pulang'] : '16:00:00';

            $cek_presensi = $this->db->table('presensi')
                ->where('id_pegawai', $p['id_pegawai'])
                ->where('tanggal', $tgl)
                ->get()->getRowArray();

            if (!$cek_presensi) {
                $this->db->table('presensi')->insert([
                    'id_pegawai'            => $p['id_pegawai'],
                    'nip'                   => $p['nip'],
                    'tanggal'               => $tgl,
                    'jam_masuk_aktual'      => $jam_absen_clean,
                    'jam_masuk_seharusnya'  => $jam_mulai_apel,
                    'jam_pulang_seharusnya' => $jam_pulang_default,
                    'menit_keterlambatan'   => 0,
                    'status_masuk'          => 'Hadir (Apel)',
                    'kategori'              => 'Hadir',
                    'device_info'           => 'Sinkronisasi Kehadiran Apel',
                    'id_jam_kerja_snapshot' => $id_final_jam,
                    'deskripsi'             => "Hadir apel pada kegiatan " . $apel['nama_apel'],
                    'created_at'            => date('Y-m-d H:i:s')
                ]);
                $synced_count++;
            } else {
                $needs_update = false;
                $update_fields = [];

                if (empty($cek_presensi['jam_masuk_aktual']) || $cek_presensi['jam_masuk_aktual'] == '00:00:00') {
                    $update_fields['jam_masuk_aktual'] = $jam_absen_clean;
                    $needs_update = true;
                }
                if ($cek_presensi['status_masuk'] !== 'Hadir (Apel)') {
                    $update_fields['status_masuk'] = 'Hadir (Apel)';
                    $update_fields['kategori'] = 'Hadir';
                    $update_fields['menit_keterlambatan'] = 0;
                    $needs_update = true;
                }
                if (empty($cek_presensi['jam_masuk_seharusnya']) || $cek_presensi['jam_masuk_seharusnya'] == '00:00:00') {
                    $update_fields['jam_masuk_seharusnya'] = $jam_mulai_apel;
                    $needs_update = true;
                }
                if (empty($cek_presensi['jam_pulang_seharusnya']) || $cek_presensi['jam_pulang_seharusnya'] == '00:00:00') {
                    $update_fields['jam_pulang_seharusnya'] = $jam_pulang_default;
                    $needs_update = true;
                }

                if ($needs_update) {
                    $update_fields['updated_at'] = date('Y-m-d H:i:s');
                    $this->db->table('presensi')->where('id_presensi', $cek_presensi['id_presensi'])->update($update_fields);
                    $synced_count++;
                }
            }
        }

        return $synced_count;
    }
}

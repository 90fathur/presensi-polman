<?php

namespace App\Controllers;

use App\Models\OpdModel;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Pegawai extends BaseController
{
    // ========================================================================
    // [FUNGSI BANTUAN]: AMBIL SELURUH OPD ANAK JIKA YANG LOGIN ADALAH INDUK
    // ========================================================================
    private function get_allowed_opd_ids($level, $id_opd_session)
    {
        if (in_array($level, ['Superadmin', 'kabupaten'])) return [];

        $db = \Config\Database::connect();
        $admin_opd_info = $db->table('opd')->where('id_opd', $id_opd_session)->get()->getRow();

        // Jika data OPD admin tidak ditemukan, kembalikan ID dia sendiri
        if (!$admin_opd_info) return [$id_opd_session];

        $admin_opd_name = strtoupper($admin_opd_info->nama_opd);
        $allowed_opd_ids = [$id_opd_session]; // Pasti bisa melihat dinasnya sendiri

        // Deteksi apakah OPD ini adalah Induk (Tidak mengandung spasi-strip-spasi " - ")
        $is_admin_induk = (strpos($admin_opd_name, ' - ') === false);

        $list_opd = $db->table('opd')->get()->getResultArray();

        foreach ($list_opd as $o) {
            $nama_upper = strtoupper($o['nama_opd']);
            $is_child = false;

            // Logika spesifik untuk Dinas/RSUD Besar
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

            // Pola umum: Nama Induk - Nama Anak (cth: RSUD HAJJAH ANDI DEPU - IGD)
            if (!$is_child && strpos($nama_upper, $admin_opd_name . ' - ') === 0) {
                $is_child = true;
            }

            // Jika valid sebagai anak, masukkan ke dalam array yang diizinkan
            if ($is_child && $o['id_opd'] != $id_opd_session) {
                $allowed_opd_ids[] = $o['id_opd'];
            }
        }
        return $allowed_opd_ids;
    }

    public function index()
    {
        $level = session()->get('level');
        $id_opd_session = session()->get('id_opd');
        $opdModel = new OpdModel();

        // Ambil info apakah OPD ini punya izin WFA (Jika login sebagai Admin OPD)
        $opd_wfa_allowed = 0;
        if ($level == 'opd') {
            $opd_info = $opdModel->where('id_opd', $id_opd_session)->first();
            $opd_wfa_allowed = $opd_info['wfa_kewenangan'] ?? 0;
        }

        $all_opd = $opdModel->orderBy('nama_opd', 'ASC')->findAll();
        $list_tampil = [];

        if (in_array($level, ['Superadmin', 'kabupaten'])) {
            $list_tampil = $all_opd;
        } else {
            // Ambil array ID OPD yang diizinkan (Induk + Anak)
            $allowed_ids = $this->get_allowed_opd_ids($level, $id_opd_session);

            // Filter list_tampil hanya untuk dropdown
            foreach ($all_opd as $o) {
                if (in_array($o['id_opd'], $allowed_ids)) {
                    $list_tampil[] = $o;
                }
            }
        }

        $data = [
            'title'           => 'Manajemen Pegawai',
            'level'           => $level,
            'opd_wfa_allowed' => $opd_wfa_allowed,
            'opd_list'        => $list_tampil
        ];

        return view('pegawai/index', $data);
    }

    public function get_lokasi_by_opd($id_opd = null)
    {
        $db = \Config\Database::connect();
        if (!$id_opd || $id_opd == "null") return $this->response->setJSON([]);
        $data = $db->table('opd_lokasi')->where('id_opd', $id_opd)->get()->getResultArray();
        return $this->response->setJSON($data);
    }

    public function update_lokasi()
    {
        $db = \Config\Database::connect();
        $id_pegawai = $this->request->getPost('id_pegawai');
        $id_lokasi_array = $this->request->getPost('id_lokasi');

        $lokasi_string = (!empty($id_lokasi_array)) ? implode(',', $id_lokasi_array) : null;

        $db->table('pegawai')->where('id_pegawai', $id_pegawai)->update(['id_lokasi' => $lokasi_string]);
        return $this->response->setJSON(['status' => 'success', 'message' => 'Multi-Lokasi berhasil diperbarui!']);
    }

    public function transfer_opd()
    {
        $db = \Config\Database::connect();
        $id_pegawai = $this->request->getPost('id_pegawai');
        $id_opd_baru = $this->request->getPost('id_opd_tujuan');
        $tanggal_hari_ini = date('Y-m-d');

        if (!$id_pegawai || !$id_opd_baru) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        }

        // 1. Tutup riwayat lama (jika ada)
        $db->table('riwayat_penempatan')
            ->where('id_pegawai', $id_pegawai)
            ->where('tmt_selesai IS NULL')
            ->update(['tmt_selesai' => $tanggal_hari_ini]);

        // 2. Buat riwayat baru
        $db->table('riwayat_penempatan')->insert([
            'id_pegawai'  => $id_pegawai,
            'id_opd'      => $id_opd_baru,
            'tmt_mulai'   => $tanggal_hari_ini,
            'tmt_selesai' => null
        ]);

        // 3. Update OPD di profil utama pegawai
        $db->table('pegawai')->where('id_pegawai', $id_pegawai)->update([
            'id_opd' => $id_opd_baru,
            'id_lokasi' => null
        ]);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Pegawai berhasil dipindahkan ke OPD baru beserta riwayatnya.']);
    }

    public function toggle_wfa_individu()
    {
        $db = \Config\Database::connect();
        $id_pegawai = $this->request->getPost('id_pegawai');
        $status_baru = $this->request->getPost('status_wfa');

        $data_update = ['is_wfa' => $status_baru];

        if ($status_baru == 1) {
            $data_update['wfa_mulai'] = $this->request->getPost('tgl_mulai');
            $data_update['wfa_selesai'] = $this->request->getPost('tgl_selesai');
            $msg = 'WFA Diaktifkan dari tanggal ' . date('d M Y', strtotime($data_update['wfa_mulai'])) . ' s/d ' . date('d M Y', strtotime($data_update['wfa_selesai']));
        } else {
            $data_update['wfa_mulai'] = null;
            $data_update['wfa_selesai'] = null;
            $msg = 'WFA Dinonaktifkan. Pegawai kembali diwajibkan absen di kantor.';
        }

        $db->table('pegawai')->where('id_pegawai', $id_pegawai)->update($data_update);
        return $this->response->setJSON(['status' => 'success', 'message' => $msg]);
    }

    public function toggle_bercadar()
    {
        if (session()->get('level') == 'pegawai') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Hanya Admin yang dapat mengatur jalur khusus ini.']);
        }

        $db = \Config\Database::connect();
        $id_pegawai = $this->request->getPost('id_pegawai');
        $status = $this->request->getPost('status_bercadar');

        $db->table('pegawai')->where('id_pegawai', $id_pegawai)->update(['is_bercadar' => $status]);

        $msg = ($status == 1) ? 'Pegawai telah dimasukkan ke Jalur Khusus (Bercadar).' : 'Pegawai dikembalikan ke jalur reguler (Kamera Wajah).';
        return $this->response->setJSON(['status' => 'success', 'message' => $msg]);
    }

    public function set_wfa_massal()
    {
        $level = session()->get('level');
        $id_opd_session = session()->get('id_opd');

        if ($level == 'pegawai') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak!']);
        }

        $db = \Config\Database::connect();

        $id_opd = ($level == 'kabupaten') ? $this->request->getPost('id_opd_wfa') : $id_opd_session;
        $status_wfa = $this->request->getPost('status_wfa_massal');

        $data_update = ['is_wfa' => $status_wfa];

        if ($status_wfa == 1) {
            $data_update['wfa_mulai'] = $this->request->getPost('tgl_mulai_massal');
            $data_update['wfa_selesai'] = $this->request->getPost('tgl_selesai_massal');
        } else {
            $data_update['wfa_mulai'] = null;
            $data_update['wfa_selesai'] = null;
        }

        $builder = $db->table('pegawai');

        if (!empty($id_opd)) {
            $builder->where('id_opd', $id_opd);
            $nama_target = "OPD terkait";
        } else {
            $nama_target = "SELURUH PEGAWAI KABUPATEN";
        }

        $builder->update($data_update);

        $status_txt = ($status_wfa == 1) ? "DIAKTIFKAN TERJADWAL" : "DINONAKTIFKAN";
        return $this->response->setJSON(['status' => 'success', 'message' => "Sistem WFA massal berhasil $status_txt untuk $nama_target."]);
    }

    public function set_wewenang_opd()
    {
        if (session()->get('level') != 'kabupaten') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak! Hanya Admin Kabupaten.']);
        }

        $db = \Config\Database::connect();
        $id_opd = $this->request->getPost('id_opd_wewenang');
        $status = $this->request->getPost('status_wewenang');

        if (empty($id_opd)) {
            $db->table('opd')->update(['wfa_kewenangan' => $status]);
            $msg = ($status == 1) ? 'Hak akses mengatur WFA DIBERIKAN ke SELURUH OPD.' : 'Hak akses WFA DICABUT dari SELURUH OPD.';
        } else {
            $db->table('opd')->where('id_opd', $id_opd)->update(['wfa_kewenangan' => $status]);
            $opd = $db->table('opd')->where('id_opd', $id_opd)->get()->getRow();
            $msg = ($status == 1) ? 'Hak akses WFA DIBERIKAN kepada ' . $opd->nama_opd : 'Hak akses WFA DICABUT dari ' . $opd->nama_opd;
        }

        return $this->response->setJSON(['status' => 'success', 'message' => $msg]);
    }

    public function ajax_list()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();
        $level = session()->get('level');
        $id_opd_session = session()->get('id_opd');
        $filter_opd = $request->getPost('filter_opd');

        $builder = $db->table('pegawai')
            ->select('pegawai.*, opd.nama_opd, opd.wfa_kewenangan')
            ->join('opd', 'pegawai.id_opd = opd.id_opd');

        // =========================================================================
        // [PERBAIKAN ENTERPRISE]: AJAX TABLE FILTER UNTUK DINAS INDUK & RSUD
        // =========================================================================
        if ($level == 'opd') {
            $allowed_opd_ids = $this->get_allowed_opd_ids($level, $id_opd_session);

            if (!empty($filter_opd)) {
                // Validasi Keamanan: Pastikan OPD yang direquest ada di dalam daftar anak/induknya
                if (in_array($filter_opd, $allowed_opd_ids)) {
                    $builder->where('pegawai.id_opd', $filter_opd);
                } else {
                    $builder->where('pegawai.id_opd', $id_opd_session); // Paksa ke dinas sendiri jika dicurangi via inspect element
                }
            } else {
                // Jika tidak ada dropdown yang dipilih, TAMPILKAN SEMUA (Induk + Anak)
                $builder->whereIn('pegawai.id_opd', $allowed_opd_ids);
            }
        } elseif (!empty($filter_opd)) {
            // Untuk Admin Kabupaten / Superadmin
            $builder->where('pegawai.id_opd', $filter_opd);
        }
        // =========================================================================

        $searchValue = $request->getPost('search')['value'];
        if ($searchValue) {
            $builder->groupStart()->like('pegawai.nama_lengkap', $searchValue)->orLike('pegawai.nip', $searchValue)->groupEnd();
        }

        $totalFiltered = $builder->countAllResults(false);
        $start = $request->getPost('start');
        $length = $request->getPost('length');

        $builder->orderBy("CASE 
            WHEN TRIM(eselon) = 'II.a' THEN 1 WHEN TRIM(eselon) = 'II.b' THEN 2
            WHEN TRIM(eselon) = 'III.a' THEN 3 WHEN TRIM(eselon) = 'III.b' THEN 4
            WHEN TRIM(eselon) = 'IV.a' THEN 5 WHEN TRIM(eselon) = 'IV.b' THEN 6
            WHEN level_jabatan LIKE '%Fungsional%' THEN 7
            WHEN level_jabatan LIKE '%Staf%' OR TRIM(eselon) = 'Non Eselon' THEN 8
            WHEN TRIM(eselon) = '-' THEN 9 ELSE 10 END", "ASC", false);

        $builder->orderBy('nama_lengkap', 'ASC');
        $result = $builder->limit($length, $start)->get()->getResult();

        $data = [];
        $no = $start + 1;
        foreach ($result as $p) {
            $nama_esc = addslashes((string)$p->nama_lengkap);

            $status_wajah = !empty($p->face_vector) ? '<span class="badge bg-success-subtle text-success px-3">AKTIF</span>' : '<span class="badge bg-danger-subtle text-danger px-3">BELUM ADA</span>';

            $is_wfa = isset($p->is_wfa) ? $p->is_wfa : 0;
            $wfa_mulai = $p->wfa_mulai ?? '';
            $wfa_selesai = $p->wfa_selesai ?? '';

            if ($is_wfa == 1 && !empty($wfa_mulai) && !empty($wfa_selesai)) {
                $badge_wfa = '<span class="badge bg-primary px-3 rounded-pill shadow-sm"><i class="fas fa-check-circle me-1"></i> Aktif<br><small style="font-size:0.6rem;">' . date('d/m/y', strtotime($wfa_mulai)) . ' - ' . date('d/m/y', strtotime($wfa_selesai)) . '</small></span>';
            } elseif ($is_wfa == 1) {
                $badge_wfa = '<span class="badge bg-primary px-3 rounded-pill shadow-sm"><i class="fas fa-check-circle me-1"></i> Aktif Permanen</span>';
            } else {
                $badge_wfa = '<span class="badge bg-secondary px-3 rounded-pill text-white-50"><i class="fas fa-times-circle me-1"></i> Tidak</span>';
            }

            $btnHapusWajah = '';
            // Admin OPD (Induk/Anak) TETAP TIDAK BISA HAPUS WAJAH (Hanya Admin Kab)
            if (!empty($p->face_vector) && $level == 'kabupaten') {
                $btnHapusWajah = '<a href="' . base_url('pegawai/hapus_wajah/' . $p->id_pegawai) . '" 
                            class="btn btn-sm btn-white border text-danger" 
                            onclick="return confirm(\'Hapus data biometrik wajah pegawai ini?\')" 
                            title="Hapus Wajah">
                            <i class="fas fa-user-slash"></i>
                          </a>';
            }

            $label_lokasi = "Kantor Induk";
            if ($level == 'kabupaten' && !empty($p->id_lokasi)) {
                $lokasi_arr = array_filter(explode(',', $p->id_lokasi), function ($v) {
                    return is_numeric(trim($v)) && (int)trim($v) > 0;
                });
                $count = count($lokasi_arr);
                if ($count > 0) {
                    $label_lokasi = ($count > 1) ? '<span class="badge bg-info"><i class="fas fa-map-marker-alt"></i> ' . $count . ' Lokasi Terdaftar</span>' : '<span class="text-primary small fw-bold"><i class="fas fa-map-marker-alt"></i> Lokasi Khusus</span>';
                }
            }

            $btnLokasi = '';
            $btnTransfer = '';
            $btnWfa = '';

            $wfa_next_status = ($is_wfa == 1) ? 0 : 1;
            $wfa_color = ($is_wfa == 1) ? 'text-primary bg-primary-subtle' : 'text-secondary';

            // Jika Admin OPD Induk Punya Wewenang WFA, dia juga bisa mengatur WFA anak UPTD-nya
            if ($level == 'kabupaten' || ($level == 'opd' && isset($p->wfa_kewenangan) && $p->wfa_kewenangan == 1)) {
                $btnWfa = '<button onclick="toggleWFA(\'' . $p->id_pegawai . '\', ' . $wfa_next_status . ', \'' . $nama_esc . '\', \'' . $wfa_mulai . '\', \'' . $wfa_selesai . '\')" class="btn btn-sm btn-white border ' . $wfa_color . '" title="Seting WFA Pegawai"><i class="fas fa-laptop-house"></i></button>';
            } else {
                $btnWfa = '<button class="btn btn-sm btn-white border text-muted opacity-50" title="Akses Atur WFA Dikunci Oleh Kabupaten" disabled><i class="fas fa-laptop-house"></i></button>';
            }

            if ($level == 'kabupaten') {
                $btnLokasi = '<button onclick="openLokasi(\'' . $p->id_pegawai . '\', \'' . $p->id_opd . '\', \'' . $nama_esc . '\', \'' . $p->id_lokasi . '\')" class="btn btn-sm btn-white border text-info" title="Penugasan Lokasi"><i class="fas fa-map-marker-alt"></i></button>';
                $btnTransfer = '<button onclick="openTransfer(\'' . $p->id_pegawai . '\', \'' . $nama_esc . '\', \'' . $p->id_opd . '\')" class="btn btn-sm btn-white border text-dark" title="Pindah OPD (Transfer)"><i class="fas fa-exchange-alt"></i></button>';
            } else {
                $btnLokasi = '<button class="btn btn-sm btn-white border text-muted opacity-50" title="Hanya Admin Kabupaten" disabled><i class="fas fa-map-marker-alt"></i></button>';
            }

            $btnResetDevice = '';
            if (!empty($p->device_token)) {
                $btnResetDevice = '<button onclick="resetDevice(\'' . $p->id_pegawai . '\', \'' . $nama_esc . '\')" class="btn btn-sm btn-white border text-primary" title="Reset Kunci HP (Device Binding)"><i class="fas fa-mobile-alt"></i></button>';
            } else {
                $btnResetDevice = '<button class="btn btn-sm btn-white border text-muted opacity-50" title="HP Belum Terkunci" disabled><i class="fas fa-mobile-alt"></i></button>';
            }

            $is_bercadar = isset($p->is_bercadar) ? $p->is_bercadar : 0;
            $bercadar_next_status = ($is_bercadar == 1) ? 0 : 1;
            $bercadar_color = ($is_bercadar == 1) ? 'text-primary bg-primary-subtle border-primary' : 'text-secondary opacity-50';
            $bercadar_title = ($is_bercadar == 1) ? 'Matikan Jalur Fingerprint (Bercadar)' : 'Aktifkan Jalur Fingerprint (Bercadar)';

            // Tampilkan tombol bercadar untuk semua Admin (termasuk Admin OPD)
            $btnBercadar = '<button onclick="toggleBercadar(\'' . $p->id_pegawai . '\', ' . $bercadar_next_status . ', \'' . $nama_esc . '\')" class="btn btn-sm btn-white border ' . $bercadar_color . '" title="' . $bercadar_title . '"><i class="fas fa-fingerprint"></i></button>';

            $opsi = '<div class="btn-group shadow-sm">' .
                $btnBercadar .
                $btnWfa .
                $btnHapusWajah .
                $btnResetDevice .
                '<button onclick="openEnroll(\'' . $p->id_pegawai . '\', \'' . $nama_esc . '\')" class="btn btn-sm btn-white border text-success" title="Registrasi Wajah"><i class="fas fa-camera"></i></button>' .
                $btnLokasi .
                $btnTransfer .
                '<button onclick="openEdit(\'' . $p->id_pegawai . '\')" class="btn btn-sm btn-white border text-warning" title="Edit Data"><i class="fas fa-edit"></i></button>
            <a href="' . base_url('pegawai/hapus/' . $p->id_pegawai) . '" class="btn btn-sm btn-white border text-danger" onclick="return confirm(\'Hapus?\')" title="Hapus Pegawai"><i class="fas fa-trash"></i></a>
            </div>';

            $row = [];
            $row[] = '<span class="ps-3 small text-muted">' . $no++ . '.</span>';
            $row[] = '<div><div class="fw-bold small">' . $p->nama_lengkap . '</div><div class="text-muted small" style="font-size: 10px;">NIP. ' . $p->nip . '</div></div>';

            $row[] = '<div>
                    <div class="small fw-bold text-dark">' . $p->jabatan . '</div>
                    <div class="text-muted" style="font-size: 10px;"><i class="fas fa-landmark me-1"></i>' . $p->nama_opd . '</div>
                    <div class="text-primary fw-bold" style="font-size: 9px;"><i class="fas fa-building me-1"></i>' . $label_lokasi . '</div>
                  </div>';

            $row[] = '<div class="text-center"><span class="badge bg-secondary">' . $p->level_jabatan . '</span></div>';
            $row[] = '<div class="text-center"><span class="badge bg-info-subtle text-info">' . ($p->status_asn ?? '-') . '</span></div>';
            $row[] = '<div class="text-center">' . $badge_wfa . '</div>';
            $row[] = '<div class="text-center">' . $status_wajah . '</div>';
            $row[] = '<div class="text-center">' . $p->nomor_hp . '</div>';
            $row[] = '<div class="text-center">' . $opsi . '</div>';
            $data[] = $row;
        }

        return $this->response->setJSON([
            "draw" => intval($request->getPost('draw')),
            "recordsTotal" => $db->table('pegawai')->countAllResults(),
            "recordsFiltered" => $totalFiltered,
            "data" => $data
        ]);
    }

    public function simpan()
    {
        $db = \Config\Database::connect();
        $nip_input = $this->request->getPost('nip');
        $cek_nip = $db->table('pegawai')->where('nip', $nip_input)->get()->getRow();

        if ($cek_nip) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan! NIP ' . $nip_input . ' sudah terdaftar di sistem.');
        }

        $data = [
            'id_opd'        => $this->request->getPost('id_opd'),
            'nip'           => $nip_input,
            'nama_lengkap'  => $this->request->getPost('nama_lengkap'),
            'jabatan'       => $this->request->getPost('jabatan'),
            'level_jabatan' => $this->request->getPost('level_jabatan'),
            'pangkat'       => $this->request->getPost('pangkat'),
            'eselon'        => $this->request->getPost('eselon'),
            'status_asn'    => $this->request->getPost('status_asn'),
            'is_bercadar'   => $this->request->getPost('is_bercadar') ?? 0,
            'password'      => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
        ];

        $db->table('pegawai')->insert($data);
        $insertId = $db->insertID();

        // Buat riwayat penempatan pertama kali
        $db->table('riwayat_penempatan')->insert([
            'id_pegawai'  => $insertId,
            'id_opd'      => $this->request->getPost('id_opd'),
            'tmt_mulai'   => date('Y-m-d'),
            'tmt_selesai' => null
        ]);

        return redirect()->to('pegawai')->with('success', 'Pegawai baru berhasil ditambahkan.');
    }

    public function update()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('id_pegawai');

        $data = [
            'id_opd'        => $this->request->getPost('id_opd'),
            'nip'           => $this->request->getPost('nip'),
            'nama_lengkap'  => $this->request->getPost('nama_lengkap'),
            'jabatan'       => $this->request->getPost('jabatan'),
            'level_jabatan' => $this->request->getPost('level_jabatan'),
            'pangkat'       => $this->request->getPost('pangkat'),
            'eselon'        => $this->request->getPost('eselon'),
            'status_asn'    => $this->request->getPost('status_asn'),
            'is_bercadar'   => $this->request->getPost('is_bercadar') ?? 0,
        ];

        if ($this->request->getPost('password')) {
            $data['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
        }

        $pegawai_lama = $db->table('pegawai')->where('id_pegawai', $id)->get()->getRow();

        $db->table('pegawai')->where('id_pegawai', $id)->update($data);

        // Cek jika OPD berubah (Mutasi via Edit Profil)
        $id_opd_baru = $this->request->getPost('id_opd');
        if ($pegawai_lama && $pegawai_lama->id_opd != $id_opd_baru) {
            $tanggal_hari_ini = date('Y-m-d');

            // Tutup riwayat lama
            $db->table('riwayat_penempatan')
                ->where('id_pegawai', $id)
                ->where('tmt_selesai IS NULL')
                ->update(['tmt_selesai' => $tanggal_hari_ini]);

            // Buka riwayat baru
            $db->table('riwayat_penempatan')->insert([
                'id_pegawai'  => $id,
                'id_opd'      => $id_opd_baru,
                'tmt_mulai'   => $tanggal_hari_ini,
                'tmt_selesai' => null
            ]);
        }

        return redirect()->to('pegawai')->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function edit_data($id)
    {
        $db = \Config\Database::connect();
        $data = $db->table('pegawai')->where('id_pegawai', $id)->get()->getRowArray();
        return $this->response->setJSON($data);
    }

    public function hapus($id)
    {
        $db = \Config\Database::connect();
        $db->table('pegawai')->where('id_pegawai', $id)->delete();
        return redirect()->to('pegawai')->with('success', 'Data pegawai berhasil dihapus.');
    }

    public function simpan_face_vector()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('id_pegawai');
        $vector = $this->request->getPost('face_vector');
        $db->table('pegawai')->where('id_pegawai', $id)->update(['face_vector' => $vector]);
        return $this->response->setJSON(['status' => 'success', 'msg' => 'Wajah Berhasil Disimpan!']);
    }

    public function ajax_generate_password()
    {
        if (session()->get('level') != 'kabupaten') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak!']);
        }

        $db = \Config\Database::connect();
        $limit = 50;
        $query_count = $db->table('pegawai')
            ->groupStart()
            ->where('CHAR_LENGTH(password) <', 60)
            ->orWhere('password', NULL)
            ->groupEnd();

        $total_sisa = $query_count->countAllResults(false);
        $pegawai = $query_count->limit($limit)->get()->getResultArray();

        if (empty($pegawai)) {
            return $this->response->setJSON(['status' => 'finish', 'message' => 'Semua password sudah aman.']);
        }

        $berhasil = 0;
        foreach ($pegawai as $p) {
            $hashPassword = password_hash($p['nip'], PASSWORD_DEFAULT);
            $db->table('pegawai')->where('id_pegawai', $p['id_pegawai'])->update(['password' => $hashPassword]);
            $berhasil++;
        }

        return $this->response->setJSON([
            'status' => 'process',
            'processed' => $berhasil,
            'remaining' => $total_sisa - $berhasil
        ]);
    }

    public function hapus_wajah($id)
    {
        $db = \Config\Database::connect();
        $db->table('pegawai')->where('id_pegawai', $id)->update(['face_vector' => null]);
        return redirect()->to('/pegawai')->with('success', 'Data biometrik wajah berhasil direset. Silakan scan ulang.');
    }

    public function import_excel()
    {
        $file = $this->request->getFile('file_excel');

        if (!$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid atau rusak.');
        }

        $ext = $file->getClientExtension();
        $reader = ($ext == 'xls') ? new Xls() : new Xlsx();

        try {
            $spreadsheet = $reader->load($file);
            $sheetData = $spreadsheet->getActiveSheet()->toArray();

            $db = \Config\Database::connect();
            $berhasil = 0;
            $skip = 0;

            foreach ($sheetData as $key => $row) {
                if ($key == 0) continue;
                $nip = str_replace("'", "", trim((string)$row[0]));
                if (empty($nip)) continue;

                $cek = $db->table('pegawai')->where('nip', $nip)->countAllResults();
                if ($cek > 0) {
                    $skip++;
                    continue;
                }

                $password_default = password_hash($nip, PASSWORD_DEFAULT);

                $data = [
                    'nip'           => $nip,
                    'nama_lengkap'  => $row[1] ?? '',
                    'id_lokasi'     => $row[2] ?? null,
                    'jabatan'       => $row[3] ?? '',
                    'nomor_hp'      => $row[4] ?? '',
                    'id_opd'        => $row[5] ?? null,
                    'eselon'        => $row[6] ?? '',
                    'status_asn'    => $row[7] ?? 'PNS',
                    'pangkat'       => $row[8] ?? '',
                    'level_jabatan' => 'Staf',
                    'password'      => $password_default,
                ];

                $db->table('pegawai')->insert($data);
                $berhasil++;
            }

            return redirect()->to('pegawai')->with('success', "Import Berhasil! $berhasil data masuk, $skip data NIP sudah ada (dilewati).");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }
    }

    public function init_import()
    {
        $file = $this->request->getFile('file_excel');
        if (!$file->isValid()) return $this->response->setJSON(['status' => 'error', 'msg' => 'File tidak valid.']);

        $newName = $file->getRandomName();
        $file->move(WRITEPATH . 'uploads', $newName);
        $filepath = WRITEPATH . 'uploads/' . $newName;

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filepath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filepath);
        $totalRows = $spreadsheet->getActiveSheet()->getHighestRow();

        return $this->response->setJSON([
            'status' => 'success',
            'temp_file' => $newName,
            'total_rows' => $totalRows - 1
        ]);
    }

    public function process_import_batch()
    {
        $tempFile = $this->request->getPost('temp_file');
        $offset = (int)$this->request->getPost('offset');
        $limit = 50;
        $filepath = WRITEPATH . 'uploads/' . $tempFile;

        if (!file_exists($filepath)) {
            return $this->response->setJSON(['status' => 'error', 'msg' => 'File sementara tidak ditemukan.']);
        }

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filepath);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filepath);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $db = \Config\Database::connect();
        $berhasil = 0;
        $skip = 0;

        for ($i = $offset + 1; $i < ($offset + 1 + $limit); $i++) {
            if (!isset($sheetData[$i])) break;
            $row = $sheetData[$i];
            $nip = str_replace("'", "", trim((string)($row[0] ?? '')));

            if (empty($nip)) continue;

            $cek = $db->table('pegawai')->where('nip', $nip)->countAllResults();
            if ($cek > 0) {
                $skip++;
                continue;
            }

            $db->table('pegawai')->insert([
                'nip'           => $nip,
                'nama_lengkap'  => $row[1] ?? '',
                'id_lokasi'     => $row[2] ?? null,
                'jabatan'       => $row[3] ?? '',
                'nomor_hp'      => $row[4] ?? '',
                'id_opd'        => $row[5] ?? null,
                'eselon'        => $row[6] ?? '',
                'status_asn'    => $row[7] ?? '',
                'pangkat'       => $row[8] ?? '',
                'level_jabatan' => 'Staf',
                'password'      => password_hash($nip, PASSWORD_DEFAULT)
            ]);
            $berhasil++;
        }

        $is_finished = ($offset + $limit >= count($sheetData) - 1);
        if ($is_finished) {
            @unlink($filepath);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'processed' => $berhasil + $skip,
            'is_finished' => $is_finished
        ]);
    }

    public function reset_device()
    {
        $db = \Config\Database::connect();
        $id_pegawai = $this->request->getPost('id_pegawai');

        if (!$id_pegawai) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID Pegawai tidak valid.']);
        }

        $update = $db->table('pegawai')->where('id_pegawai', $id_pegawai)->update(['device_token' => null]);

        if ($update) {
            cache()->delete("user_info_" . $id_pegawai);
            cache()->delete("user_info_v3_" . $id_pegawai);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Kunci perangkat berhasil direset. Pegawai kini dapat absen menggunakan HP barunya.']);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mereset perangkat dari database.']);
        }
    }
}

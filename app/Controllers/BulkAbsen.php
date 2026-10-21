<?php

namespace App\Controllers;

use App\Models\PegawaiModel;

class BulkAbsen extends BaseController
{
    public function index()
    {
        $username = session()->get('username');
        if ($username !== 'superadmin') {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak. Hanya user superadmin utama yang dapat mengakses fitur ini.');
        }

        $db = \Config\Database::connect();
        $pegawai = $db->table('pegawai')->select('id_pegawai, nama_lengkap as nama, nip')->orderBy('nama_lengkap', 'ASC')->get()->getResultArray();

        $data = [
            'title'   => 'Matriks Absen Bulanan',
            'pegawai' => $pegawai
        ];

        return view('superadmin/bulk_absen', $data);
    }

    public function save()
    {
        $username = session()->get('username');
        if ($username !== 'superadmin') {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $bulan = $this->request->getPost('bulan');
        $tahun = $this->request->getPost('tahun');
        $pegawai_ids = $this->request->getPost('id_pegawai'); // array

        if (empty($bulan) || empty($tahun) || empty($pegawai_ids)) {
            return redirect()->back()->with('error', 'Semua form harus diisi.');
        }

        $db = \Config\Database::connect();
        $inserted_count = 0;

        foreach ($pegawai_ids as $id_pegawai) {
            $pegawai = $db->table('pegawai')->where('id_pegawai', $id_pegawai)->get()->getRowArray();
            if (!$pegawai) continue;

            $jml_hari = date('t', strtotime(sprintf("%04d-%02d-01", $tahun, $bulan)));

            for ($i = 1; $i <= $jml_hari; $i++) {
                $tanggal = sprintf("%04d-%02d-%02d", $tahun, $bulan, $i);
                $dayOfWeek = date('N', strtotime($tanggal));

                // Lewati Sabtu (6) dan Minggu (7)
                if ($dayOfWeek >= 6) {
                    continue;
                }

                // Cek apakah sudah ada presensi
                $presensi = $db->table('presensi')->where([
                    'id_pegawai' => $id_pegawai,
                    'tanggal' => $tanggal
                ])->get()->getRowArray();

                // Cek apakah ada izin/sakit/TL yang disetujui pada tanggal ini
                $cek_izin = $db->table('pengajuan_absen')
                    ->where('nip', $pegawai['nip'])
                    ->where('status_approval', 'Disetujui')
                    ->where('tanggal_mulai <=', $tanggal)
                    ->groupStart()
                    ->where('tanggal_selesai >=', $tanggal)
                    ->orGroupStart()
                    ->where('tanggal_selesai', null)
                    ->where('tanggal_mulai', $tanggal) // Jika tgl selesai null, asumsikan 1 hari
                    ->groupEnd()
                    ->orWhere('tanggal_selesai', '0000-00-00')
                    ->groupEnd()
                    ->countAllResults();

                // Cek apakah ada cuti yang disetujui pada tanggal ini
                $cek_cuti = $db->table('pengajuan_cuti')
                    ->where('pegawai_id', $id_pegawai)
                    ->where("LOWER(TRIM(status))", 'disetujui')
                    ->where('tgl_mulai <=', $tanggal)
                    ->where("COALESCE(NULLIF(tgl_selesai, ''), tgl_mulai) >=", $tanggal)
                    ->countAllResults();

                if ($cek_izin == 0 && $cek_cuti == 0) {
                    if (!$presensi) {
                        // Belum ada absen sama sekali, lakukan INSERT
                        $insert_data = [
                            'id_pegawai'            => $id_pegawai,
                            'nip'                   => $pegawai['nip'] ?? '',
                            'tanggal'               => $tanggal,
                            'jam_masuk_aktual'      => '07:30:00',
                            'jam_pulang_aktual'     => '16:00:00',
                            'status_masuk'          => 'Tepat Waktu',
                            'status_pulang'         => 'Normal',
                            'kategori'              => 'Hadir',
                            'deskripsi'             => 'Disetujui Admin',
                            'created_at'            => date('Y-m-d H:i:s')
                        ];

                        $db->table('presensi')->insert($insert_data);
                        $inserted_count++;
                    } else {
                        // Sudah absen, cek apakah jam pulangnya kosong
                        if (empty($presensi['jam_pulang_aktual']) || $presensi['jam_pulang_aktual'] == '00:00:00') {
                            $db->table('presensi')->where('id_presensi', $presensi['id_presensi'])->update([
                                'jam_pulang_aktual' => '16:00:00',
                                'status_pulang'     => 'Normal',
                                'updated_at'        => date('Y-m-d H:i:s')
                            ]);
                            $inserted_count++; // Hitung sebagai tindakan sukses
                        }
                    }
                }
            }
        }

        return redirect()->back()->with('success', "Berhasil menambahkan $inserted_count data absensi.");
    }
}

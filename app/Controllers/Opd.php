<?php

namespace App\Controllers;

use App\Models\OpdModel;
use CodeIgniter\Controller;

class Opd extends BaseController
{
    protected $opdModel;

    public function __construct()
    {
        // Inisialisasi Model agar bisa dipakai di semua fungsi
        $this->opdModel = new OpdModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();
        $data = [
            'title' => 'Manajemen OPD',
            'opd'   => $this->opdModel->getOpdWithLocationCount(),
            'list_jam_kerja' => $db->table('jam_kerja')->get()->getResultArray()
        ];

        return view('opd/index', $data);
    }

    public function simpan()
    {
        // 1. Simpan Data Induk OPD ke tabel 'opd' (DITAMBAHKAN LAT & LONG PUSAT)
        $dataOpd = [
            'nama_opd'     => $this->request->getPost('nama_opd'),
            'kode_opd'     => $this->request->getPost('kode_opd'),
            'id_jam_kerja' => $this->request->getPost('id_jam_kerja'),
            'latitude'     => $this->request->getPost('lat_pusat'), // Kolom baru di tabel opd
            'longitude'    => $this->request->getPost('long_pusat'), // Kolom baru di tabel opd
            'radius'       => $this->request->getPost('radius')
        ];

        $this->opdModel->insert($dataOpd);
        $id_opd = $this->opdModel->getInsertID();

        // 2. Simpan Multi-Lokasi ke tabel 'opd_lokasi' (Tetap Sama)
        $db = \Config\Database::connect();
        $builder = $db->table('opd_lokasi');

        $nama_lokasi = $this->request->getPost('nama_lokasi');
        $lat = $this->request->getPost('lat');
        $long = $this->request->getPost('long');
        $radius = $this->request->getPost('radius');

        if ($nama_lokasi) {
            foreach ($nama_lokasi as $key => $val) {
                if (!empty($val) && !empty($lat[$key])) {
                    $builder->insert([
                        'id_opd'       => $id_opd,
                        'nama_lokasi'  => $val,
                        'latitude'     => $lat[$key],
                        'longitude'    => $long[$key],
                        'radius_meter' => $radius[$key],
                    ]);
                }
            }
        }

        return redirect()->to('/opd')->with('success', 'Data OPD dan Lokasi berhasil disimpan!');
    }

    // Fungsi Hapus OPD
    public function hapus($id)
    {
        $this->opdModel->delete($id);
        return redirect()->to('opd')->with('success', 'Data OPD berhasil dihapus');
    }

    // Fungsi Ambil Data untuk Edit (JSON)
    public function edit_data($id)
    {
        $data = $this->opdModel->find($id);
        return json_encode($data);
    }

    // Fungsi Update OPD
    public function update()
    {
        $id = $this->request->getPost('id_opd');
        $this->opdModel->update($id, [
            'nama_opd' => $this->request->getPost('nama_opd'),
            'kode_opd' => $this->request->getPost('kode_opd'),
            'id_jam_kerja' => $this->request->getPost('id_jam_kerja'), // <-- TAMBAHKAN BARIS INI
            'latitude'     => $this->request->getPost('lat_pusat'),  // Simpan Lat Pusat
            'longitude'    => $this->request->getPost('long_pusat'), // Simpan Long Pusat
            'radius'       => $this->request->getPost('radius')
        ]);
        return redirect()->to('opd')->with('success', 'Data OPD berhasil diperbarui');
    }

    // Halaman Kelola Lokasi Spesifik
    // Menampilkan halaman khusus kelola lokasi
    public function lokasi($id_opd)
    {
        $db = \Config\Database::connect();
        $data = [
            'title'   => 'Kelola Lokasi Kantor',
            'opd'     => $this->opdModel->find($id_opd),
            'lokasi'  => $db->table('opd_lokasi')->where('id_opd', $id_opd)->get()->getResultArray()
        ];
        return view('opd/lokasi', $data);
    }

    // Menambah 1 lokasi baru dari halaman Kelola Lokasi
    public function simpan_lokasi_tunggal()
    {
        $db = \Config\Database::connect();
        $id_opd = $this->request->getPost('id_opd');

        $db->table('opd_lokasi')->insert([
            'id_opd'       => $id_opd,
            'nama_lokasi'  => $this->request->getPost('nama_lokasi'),
            'latitude'     => $this->request->getPost('lat'),
            'longitude'    => $this->request->getPost('long'),
            'radius_meter' => $this->request->getPost('radius'),
        ]);

        return redirect()->to('opd/lokasi/' . $id_opd)->with('success', 'Lokasi baru berhasil ditambahkan');
    }

    // Menghapus hanya 1 titik lokasi
    public function hapus_lokasi($id_lokasi, $id_opd)
    {
        $db = \Config\Database::connect();
        $db->table('opd_lokasi')->where('id_lokasi', $id_lokasi)->delete();

        return redirect()->to('opd/lokasi/' . $id_opd)->with('success', 'Titik lokasi berhasil dihapus');
    }

    // Tambahkan ini di Controller
    public function kelola_pegawai_lokasi($id_lokasi, $id_opd)
    {
        $db = \Config\Database::connect();

        // Ambil data lokasi yang sedang dikelola
        $lokasi = $db->table('opd_lokasi')->where('id_lokasi', $id_lokasi)->get()->getRowArray();

        // Ambil semua pegawai di OPD tersebut
        $pegawai = $db->table('pegawai')
            ->where('id_opd', $id_opd)
            ->orderBy('nama_lengkap', 'ASC')
            ->get()->getResultArray();

        $data = [
            'title'     => 'Plotting Pegawai ke Lokasi',
            'lokasi'    => $lokasi,
            'pegawai'   => $pegawai,
            'id_opd'    => $id_opd
        ];

        return view('opd/plot_pegawai', $data);
    }

    public function simpan_plot_pegawai()
    {
        $id_lokasi = $this->request->getPost('id_lokasi');
        $id_opd    = $this->request->getPost('id_opd');
        $ids_pegawai = $this->request->getPost('pegawai') ?? []; // Array ID Pegawai yang dicentang

        $db = \Config\Database::connect();

        // 1. Ambil semua pegawai di OPD ini
        $all_pegawai = $db->table('pegawai')->where('id_opd', $id_opd)->get()->getResultArray();

        foreach ($all_pegawai as $p) {
            $current_locations = !empty($p['id_lokasi']) ? explode(',', $p['id_lokasi']) : [];

            if (in_array($p['id_pegawai'], $ids_pegawai)) {
                // Jika dicentang: Tambahkan id_lokasi jika belum ada
                if (!in_array($id_lokasi, $current_locations)) {
                    $current_locations[] = $id_lokasi;
                }
            } else {
                // Jika tidak dicentang: Hapus id_lokasi dari list
                $current_locations = array_diff($current_locations, [$id_lokasi]);
            }

            // Update ke database
            $new_val = !empty($current_locations) ? implode(',', array_unique($current_locations)) : null;
            $db->table('pegawai')->where('id_pegawai', $p['id_pegawai'])->update(['id_lokasi' => $new_val]);
        }

        return redirect()->to(base_url('opd/lokasi/' . $id_opd))->with('success', 'Plotting pegawai berhasil diperbarui.');
    }

    public function update_lokasi()
    {
        $db = \Config\Database::connect();

        $id_lokasi = $this->request->getPost('id_lokasi');
        $id_opd    = $this->request->getPost('id_opd');

        $db->table('opd_lokasi')->where('id_lokasi', $id_lokasi)->update([
            'nama_lokasi'  => $this->request->getPost('nama_lokasi'),
            'latitude'     => $this->request->getPost('lat'),
            'longitude'    => $this->request->getPost('long'),
            'radius_meter' => $this->request->getPost('radius'),
        ]);

        return redirect()->to('opd/lokasi/' . $id_opd)->with('success', 'Titik lokasi berhasil diperbarui');
    }

    // Fungsi Toggle Akses Koreksi Kehadiran
    public function toggle_koreksi($id_opd)
    {
        $db = \Config\Database::connect();

        $opd = $db->table('opd')->where('id_opd', $id_opd)->get()->getRow();
        if ($opd) {
            $new_status = ($opd->is_koreksi_open == 1) ? 0 : 1;
            $db->table('opd')->where('id_opd', $id_opd)->update(['is_koreksi_open' => $new_status]);

            $status_text = ($new_status == 1) ? 'dibuka' : 'ditutup';
            return redirect()->to('opd')->with('success', 'Akses Koreksi Kehadiran untuk OPD ' . $opd->nama_opd . ' berhasil ' . $status_text . '.');
        }

        return redirect()->to('opd')->with('error', 'OPD tidak ditemukan.');
    }
}

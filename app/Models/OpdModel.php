<?php

namespace App\Models;

use CodeIgniter\Model;

class OpdModel extends Model
{
    protected $table = 'opd';
    protected $primaryKey = 'id_opd';
    protected $allowedFields = [
    'nama_opd', 
    'kode_opd', 
    'id_jam_kerja', 
    'latitude', // WAJIB ADA
    'longitude', // WAJIB ADA
    'radius'

];

    public function getOpdWithLocationCount()
    {
        return $this->db->table('opd')
            ->select('opd.*, jam_kerja.nama_jadwal, COUNT(opd_lokasi.id_lokasi) as jumlah_lokasi')
            // Join ke tabel jam_kerja agar kita bisa ambil nama jadwalnya
            ->join('jam_kerja', 'opd.id_jam_kerja = jam_kerja.id_jam_kerja', 'left')
            // Join ke lokasi tetap dipertahankan
            ->join('opd_lokasi', 'opd.id_opd = opd_lokasi.id_opd', 'left')
            ->groupBy('opd.id_opd')
            ->get()->getResultArray();
    }
}

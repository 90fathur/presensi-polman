<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'system/bootstrap.php';
$db = \Config\Database::connect();

try {
    $builder = $db->table('pengajuan_absen')
            ->select('pengajuan_absen.*, pegawai.nama_lengkap, pegawai.nip, opd.nama_opd')
            ->join('pegawai', 'pegawai.nip = pengajuan_absen.nip')
            ->join('opd', 'opd.id_opd = pegawai.id_opd', 'left')
            ->where('pengajuan_absen.status_approval', 'Disetujui');
            
    $recordsFiltered = $builder->countAllResults(false);
    $builder->orderBy('pengajuan_absen.id_pengajuan', 'DESC');
    $builder->limit(10, 0);
    $query = $builder->get()->getResult();

    echo "OK: " . count($query);
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

<?php
namespace App\Controllers;

class MigrasiRiwayat extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // 1. Create table
        $sql = "CREATE TABLE IF NOT EXISTS `riwayat_penempatan` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_pegawai` varchar(50) NOT NULL,
            `id_opd` varchar(50) NOT NULL,
            `tmt_mulai` date NOT NULL,
            `tmt_selesai` date DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $db->query($sql);
        
        // 2. Clear existing (in case of rerun)
        $db->table('riwayat_penempatan')->truncate();
        
        // 3. Insert existing employees
        $pegawai = $db->table('pegawai')->get()->getResult();
        $batch = [];
        foreach ($pegawai as $p) {
            $batch[] = [
                'id_pegawai' => $p->id_pegawai,
                'id_opd' => $p->id_opd,
                'tmt_mulai' => '2000-01-01',
                'tmt_selesai' => null
            ];
        }
        
        if (count($batch) > 0) {
            $db->table('riwayat_penempatan')->insertBatch($batch);
        }
        
        return "Migrasi riwayat_penempatan selesai. " . count($batch) . " pegawai disinkronkan.";
    }
}

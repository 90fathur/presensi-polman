<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTppDinamisTables extends Migration
{
    public function up()
    {
        // 1. Tambah kolom tpp_dinamis_aktif pada tabel opd jika belum ada
        if (!$this->db->fieldExists('tpp_dinamis_aktif', 'opd')) {
            $this->forge->addColumn('opd', [
                'tpp_dinamis_aktif' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'nama_opd'
                ]
            ]);
        }

        // 2. Buat tabel tpp_ekin_data untuk menyimpan WKE, Capaian Kinerja, TPP BK, TPP KK
        if (!$this->db->tableExists('tpp_ekin_data')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true
                ],
                'id_opd' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0
                ],
                'nip' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50
                ],
                'nama_pegawai' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true
                ],
                'bulan' => [
                    'type'       => 'INT',
                    'constraint' => 2
                ],
                'tahun' => [
                    'type'       => 'INT',
                    'constraint' => 4
                ],
                'wke' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => '0',
                    'null'       => true
                ],
                'predikat_kinerja' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'default'    => 'BAIK',
                    'null'       => true
                ],
                'persen_kinerja' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '6,2',
                    'default'    => 100.00
                ],
                'tpp_bk' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00
                ],
                'tpp_kk' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ]
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['nip', 'bulan', 'tahun'], 'unique_pegawai_periode');
            $this->forge->addKey(['id_opd', 'bulan', 'tahun'], false, false, 'idx_opd_periode');
            $this->forge->createTable('tpp_ekin_data', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tpp_ekin_data')) {
            $this->forge->dropTable('tpp_ekin_data');
        }
        if ($this->db->fieldExists('tpp_dinamis_aktif', 'opd')) {
            $this->forge->dropColumn('opd', 'tpp_dinamis_aktif');
        }
    }
}

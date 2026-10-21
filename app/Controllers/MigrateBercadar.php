<?php

namespace App\Controllers;

class MigrateBercadar extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        try {
            $db->query("ALTER TABLE pegawai ADD COLUMN is_bercadar TINYINT(1) DEFAULT 0");
            echo "Success adding is_bercadar column!";
        } catch (\Exception $e) {
            echo "Failed or already exists: " . $e->getMessage();
        }
    }
}

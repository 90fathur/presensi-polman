<?php namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users'; // Pastikan nama tabel di aaPanel sama persis
    protected $primaryKey = 'id_user';
    protected $allowedFields = ['username', 'password', 'nama_user', 'level', 'id_opd'];
}
<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckDb extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'check:db';
    protected $description = 'Check database status.';

    public function run(array $params)
    {
        CLI::write("Check DB command executed.");
    }
}

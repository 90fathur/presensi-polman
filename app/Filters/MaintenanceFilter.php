<?php namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Cek apakah IP Anda ingin dikecualikan agar tetap bisa akses
        $allowedIPs = ['127.0.0.1', '::1', '125.162.220.193'];

        if (!in_array($request->getIPAddress(), $allowedIPs)) {
            echo view('maintenance'); // Tampilkan halaman maintenance
            exit;
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
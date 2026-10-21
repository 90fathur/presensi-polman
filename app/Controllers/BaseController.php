<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');

        // ========================================================================
        // [SMART FILTER]: BLOKIR APLIKASI WEBVIEW LAMA
        // ========================================================================
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isAndroidWebView = (stripos($userAgent, 'Android') !== false && stripos($userAgent, 'wv') !== false);

        $uri = $request->getUri()->getPath();
        $isApi = (strpos($uri, 'api/') === 0 || strpos($uri, '/api/') !== false);

        if ($isAndroidWebView && !$isApi) {
            echo '<div style="background-color: #C62828; color: white; padding: 30px; font-family: sans-serif; height: 100vh; text-align: center; box-sizing: border-box;">
                    <h1 style="font-size: 24px; margin-bottom: 20px;">⛔ APLIKASI KADALUWARSA ⛔</h1>
                    <p style="font-size: 16px; line-height: 1.5; margin-bottom: 30px;">
                        Aplikasi Presensi versi lama ini telah dinonaktifkan permanen.<br><br>
                        <b>Langkah Wajib:</b><br>
                        1. Tutup layar ini, tekan tahan ikon aplikasi di HP Anda, lalu pilih <b>UNINSTALL / HAPUS</b>.<br>
                        2. Setelah dihapus, download aplikasi versi 2.0 terbaru di browser Anda melalui link di bawah ini.
                    </p>
                    <a href="https://presensi.polmankab.go.id/download/presensinew.apk" style="display: inline-block; background-color: #fff; color: #C62828; padding: 15px 25px; text-decoration: none; font-weight: bold; border-radius: 8px; font-size: 16px;">📥 DOWNLOAD APLIKASI BARU</a>
                  </div>';
            exit;
        }
    }
}

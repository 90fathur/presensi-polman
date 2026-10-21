<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * Aliases for Filter classes.
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'authFilter'    => \App\Filters\AuthFilter::class, // Alias utama Anda
        'isLoggedIn'    => \App\Filters\AuthFilter::class, // Alias tambahan jika diperlukan
        // TAMBAHKAN BARIS INI:
        'auth'          => \App\Filters\AuthFilter::class,
        // 'maintenance'   => \App\Filters\MaintenanceFilter::class,
    ];

    /**
     * Special required filters.
     */
    public array $required = [
        'before' => [
            'forcehttps',
            'pagecache',
        ],
        'after' => [
            'pagecache',
            'performance',
            'toolbar',
        ],
    ];

    /**
     * Global filters applied before and after every request.
     */
    public array $globals = [
        'before' => [
            // 'maintenance',
            // Daftarkan authFilter secara global dengan pengecualian rute login
            'authFilter' => [
                'except' => [
                    '/',
                    'auth',
                    'auth/*',          // Pengecekan login Admin
                    'authpegawai',
                    'authpegawai/*', // Pengecekan login Pegawai PWA
                    'api',
                    'api/*',            // API Android Native (Login, Dashboard, Absen)
                    'presensi.apk',     // Shortcut download APK lama
                    'presensinew.apk',  // Shortcut download APK baru
                    'download/*',       // Folder/Route download APK
                    'cron/*',           // Eksekusi otomatis Cron Job
                    'apelweb',          // Web Check-in Apel
                    'apelweb/*'
                ]
            ],
        ],
        'after' => [
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    /**
     * Filter aliases that works on a particular HTTP method (GET, POST, etc.).
     */
    public array $methods = [];

    /**
     * Filter aliases that should run on any before or after URI patterns.
     */
    public array $filters = [];
}

<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// --- API NATIVE KOTLIN ---
$routes->group('api', function ($routes) {
    $routes->post('login', 'Api::login');
    $routes->post('logout', 'Api::logout');
    $routes->get('logout', 'Api::logout');
    $routes->post('dashboard', 'Api::dashboard');
    $routes->get('dashboard', 'Api::dashboard');
    $routes->post('submit_absen', 'Api::submit_absen');
    $routes->post('submit_cito', 'Api::submit_cito');
    $routes->post('submit_absen_apel', 'Api::submit_absen_apel');
    $routes->post('submit_izin', 'Api::submit_izin');
    $routes->post('update_fcm_token', 'Api::update_fcm_token');
});

// --- LOGIN & AUTH ---
$routes->get('/', 'AuthPegawai::index');
$routes->get('auth', 'Auth::index');
$routes->post('auth/login', 'Auth::login');
$routes->get('logout', 'Auth::logout');
$routes->get('logoutpwa', 'Auth::logoutpwa');
$routes->get('testnotif', 'TestNotif::index');
$routes->view('privacy-policy', 'privacy-policy');


$routes->get('cron/remind-masuk', 'Reminder::ingatkanMasuk');
$routes->get('cron/remind-siang', 'Reminder::ingatkanSiang');
$routes->get('cron/remind-pulang', 'Reminder::ingatkanPulang');
$routes->get('cron/remind-shift-masuk', 'Reminder::remindShiftDinamis');
$routes->get('cron/remind-shift-pulang', 'Reminder::remindShiftPulang');

$routes->get('cron/broadcast-welcome', 'Reminder::broadcastWelcome');

$routes->get('cron/remind-wfh', 'Reminder::ingatkanWfhBesok');

// --- ROUTE COMMAND CENTER PRESENSI POLMAN 2.0 (TanPA LOGIN) ---
$routes->get('live', 'Live::index');
$routes->post('live/ajax_stream', 'Live::ajax_stream');

// --- WEB CHECK-IN APEL (BYPASS APK) ---
$routes->get('apelweb/checkin/(:num)', 'ApelWeb::checkin/$1');
$routes->post('apelweb/process_checkin/(:num)', 'ApelWeb::process_checkin/$1');

// --- GROUP ADMIN (Kabupaten & OPD) ---
// Kita satukan karena keduanya menggunakan layout desktop yang mirip
$routes->group('', ['filter' => 'authFilter'], function ($routes) {

    $routes->get('dashboard', 'Home::index');
    $routes->get('rekap', 'Rekap::index');
    $routes->get('keterlambatan', 'Rekap::keterlambatan');
    $routes->get('cetaktpp', 'Rekap::tpp');
    $routes->get('cetaktpp/excel', 'Rekap::excel');
    
    // --- FITUR BARU: PROTOTYPE REKAP TPP DINAMIS (BK & KK 40% - 60%) ---
    $routes->get('tpp_dinamis', 'TppDinamis::index');
    $routes->get('tpp_dinamis/excel', 'TppDinamis::excel');
    $routes->get('tpp_dinamis/kelola_ekin', 'TppDinamis::kelola_ekin');
    $routes->post('tpp_dinamis/upload_ekin', 'TppDinamis::upload_ekin');
    $routes->post('tpp_dinamis/save_manual_ekin', 'TppDinamis::save_manual_ekin');
    $routes->get('tpp_dinamis/download_template', 'TppDinamis::download_template');
    $routes->get('tpp_dinamis/kelola_pagu', 'TppDinamis::kelola_pagu');
    $routes->post('tpp_dinamis/upload_pagu', 'TppDinamis::upload_pagu');
    $routes->post('tpp_dinamis/save_manual_pagu', 'TppDinamis::save_manual_pagu');
    $routes->get('tpp_dinamis/download_template_pagu', 'TppDinamis::download_template_pagu');
    $routes->get('tpp_dinamis/setting_opd', 'TppDinamis::setting_opd');
    $routes->post('tpp_dinamis/toggle_opd', 'TppDinamis::toggle_opd');
    // $routes->get('rekap/manual', 'Rekap::manual');
    // $routes->post('rekap/simpan_manual', 'Rekap::simpan_manual');
    $routes->get('rekap/harian', 'Rekap::harian');   // <--- TAMBAHKAN INI (Rekap Harian Baru)
    $routes->post('rekap/suspend_presensi', 'Rekap::suspend_presensi');
    $routes->post('rekap/restore_presensi', 'Rekap::restore_presensi');
    $routes->post('rekap/anulir_permanen', 'Rekap::anulir_permanen');
    $routes->get('users', 'Users::index');
    $routes->get('pegawai/generate_password', 'Pegawai::generate_password_nip');
    $routes->post('pegawai/ajax_generate_password', 'Pegawai::ajax_generate_password');
    $routes->get('pegawai/hapus_wajah/(:num)', 'Pegawai::hapus_wajah/$1');
    $routes->get('rekap/bulanan_matriks', 'Rekap::bulanan_matriks');
    $routes->post('rekap/ajax_koreksi_terlambat', 'Rekap::ajax_koreksi_terlambat');
    $routes->post('rekap/ajax_koreksi_terlambat_massal', 'Rekap::ajax_koreksi_terlambat_massal');


    $routes->get('bulkabsen', 'BulkAbsen::index');
    $routes->post('bulkabsen/save', 'BulkAbsen::save');

    $routes->post('pesan/hapus_massal', 'Pesan::hapus_massal');
    $routes->post('pesan/simpan_massal', 'Pesan::simpan_massal');

    // Pastikan rute ini ada agar JavaScript bisa memanggil data
    $routes->get('users/edit_data/(:num)', 'Users::edit_data/$1');
    $routes->post('users/update', 'Users::update');
    $routes->post('users/simpan', 'Users::simpan');
    $routes->get('users/hapus/(:num)', 'Users::hapus/$1');
    $routes->post('manajemenuser/update_password_self', 'ManajemenUser::update_password_self');

    $routes->get('pesan', 'Pesan::index');
    $routes->post('pesan/simpan', 'Pesan::simpan');
    $routes->post('pesan/update', 'Pesan::update');
    $routes->get('pesan/hapus/(:num)', 'Pesan::hapus/$1');




    // Manajemen OPD (Biasanya level Kabupaten)
    $routes->get('opd', 'Opd::index');
    $routes->post('opd/simpan', 'Opd::simpan');
    $routes->get('opd/lokasi/(:num)', 'Opd::lokasi/$1');
    $routes->post('opd/update', 'Opd::update');
    $routes->get('opd/hapus_lokasi/(:num)/(:num)', 'Opd::hapus_lokasi/$1/$2');
    $routes->get('opd/hapus/(:num)', 'Opd::hapus/$1');
    $routes->get('opd/toggle_koreksi/(:num)', 'Opd::toggle_koreksi/$1');
    $routes->post('opd/simpan_lokasi_tunggal', 'Opd::simpan_lokasi_tunggal');
    $routes->post('opd/update_lokasi', 'Opd::update_lokasi');

    // Manajemen Pegawai (Biasanya level OPD)
    $routes->get('pegawai', 'Pegawai::index');
    $routes->post('pegawai/simpan', 'Pegawai::simpan');
    $routes->get('pegawai/edit_data/(:num)', 'Pegawai::edit_data/$1');
    $routes->post('pegawai/update', 'Pegawai::update');
    $routes->get('pegawai/hapus/(:num)', 'Pegawai::hapus/$1');
    $routes->post('pegawai/simpan_face_vector', 'Pegawai::simpan_face_vector');
    $routes->get('pegawai/get_lokasi_by_opd/(:any)', 'Pegawai::get_lokasi_by_opd/$1');
    $routes->post('pegawai/transfer_opd', 'Pegawai::transfer_opd');
    $routes->post('pegawai/reset_device', 'Pegawai::reset_device');

    // 4. Halaman Pilih Pegawai (Plotting) - BARU
    $routes->get('opd/kelola_pegawai_lokasi/(:num)/(:num)', 'Opd::kelola_pegawai_lokasi/$1/$2');

    $routes->post('home/ajax_aktivitas', 'Home::ajax_aktivitas');

    // 5. Proses Simpan Centang Pegawai - BARU
    $routes->post('opd/simpan_plot_pegawai', 'Opd::simpan_plot_pegawai');

    // Route untuk menyimpan perubahan lokasi (saat klik Simpan Lokasi)
    $routes->post('pegawai/update_lokasi', 'Pegawai::update_lokasi');

    $routes->post('pegawai/import_excel', 'Pegawai::import_excel');

    // Route untuk inisialisasi (upload file & hitung total baris)
    $routes->post('pegawai/init_import', 'Pegawai::init_import');

    // Route untuk memproses data per 50 baris (Server-side batch)
    $routes->post('pegawai/process_import_batch', 'Pegawai::process_import_batch');

    // (Opsional) Jika nanti Bapak ingin membuat fitur download template
    $routes->get('pegawai/download_template', 'Pegawai::download_template');
    $routes->post('pegawai/toggle_wfa_individu', 'Pegawai::toggle_wfa_individu');
    $routes->post('pegawai/set_wfa_massal', 'Pegawai::set_wfa_massal');
    $routes->post('pegawai/set_wewenang_opd', 'Pegawai::set_wewenang_opd');
    $routes->post('pegawai/toggle_bercadar', 'Pegawai::toggle_bercadar');

    // Route Khusus Jam Ekstra / Lembur Nakes
    $routes->get('lemburnakes', 'LemburNakes::index');
    $routes->post('lemburnakes/simpan', 'LemburNakes::simpan');
    $routes->get('lemburnakes/hapus/(:num)', 'LemburNakes::hapus/$1');
    $routes->post('lemburnakes/set_akses_cito', 'LemburNakes::set_akses_cito');

    // Konfigurasi Kerja
    $routes->get('jamkerja', 'JamKerja::index');
    $routes->post('jamkerja/simpan_grup', 'JamKerja::simpan_grup');
    $routes->get('jamkerja/detail/(:num)', 'JamKerja::detail/$1');
    $routes->post('jamkerja/simpan_detail', 'JamKerja::simpan_detail');
    $routes->post('jamkerja/update_grup', 'JamKerja::update_grup');

    $routes->get('harilibur', 'HariLibur::index');
    $routes->post('harilibur/simpan', 'HariLibur::simpan');
    $routes->get('harilibur/hapus/(:num)', 'HariLibur::hapus/$1');

    // Plotting & User
    $routes->get('plotting', 'Plotting::index');
    $routes->post('plotting/simpan', 'Plotting::simpan');
    // $routes->get('plotting/hapus/(:num)', 'Plotting::hapus/$1');
    $routes->post('plotting/hapus_massal', 'Plotting::hapus_massal');
    $routes->post('plotting/ajax_list', 'Plotting::ajax_list');
    // Route untuk memproses Tukar Jaga Shift
    $routes->post('plotting/proses_tukar_jaga', 'Plotting::proses_tukar_jaga');
    // INI YANG BARU (WAJIB DITAMBAHKAN):
    $routes->post('plotting/simpan_massal', 'Plotting::simpan_massal');
    $routes->post('plotting/simpan_pola', 'Plotting::simpan_pola_shift');
    $routes->post('plotting/cek_shift_pegawai', 'Plotting::cek_shift_pegawai');
    $routes->post('plotting/ajax_kalender', 'Plotting::ajax_kalender');
    $routes->get('plotting/export_excel', 'Plotting::export_excel');
    $routes->get('plotting/export_pdf', 'Plotting::export_pdf');

    // $routes->get('plotting/hapus/(:num)', 'Plotting::hapus/$1');
    $routes->post('plotting/hapus', 'Plotting::hapus');

    $routes->get('manajemenuser', 'ManajemenUser::index');
    $routes->post('manajemenuser/simpan', 'ManajemenUser::simpan');
    $routes->get('manajemenuser/hapus/(:num)', 'ManajemenUser::hapus/$1');

    // Terminal Absensi (Mesin Scan di Kantor)
    $routes->get('scan', 'Presensi::scan');
    $routes->post('presensi/submit_absen', 'Presensi::submit_absen');

    // $routes->get('approval', 'Approval::index');
    // $routes->post('approval/proses', 'Approval::proses');

    $routes->get('approval', 'Approval::index');
    $routes->get('approval/riwayat', 'Approval::riwayat'); // Route Baru
    $routes->post('approval/proses', 'Approval::proses');
    // Route baru untuk memproses persetujuan MASSAL (Checkbox)
    $routes->post('approval/proses_massal', 'Approval::proses_massal');


    // Modul CITO RSUD
    $routes->get('cito', 'Cito::index');
    $routes->post('cito/update_status', 'Cito::update_status');
    $routes->post('cito/update_akses', 'Cito::update_akses');

    // --- MANAJEMEN CUTI OLEH ADMIN ---
    $routes->get('cuti', 'Cuti::index');
    $routes->post('cuti/simpan', 'Cuti::simpan');
    $routes->get('cuti/hapus/(:num)', 'Cuti::hapus/$1');
    // [TAMBAHAN BARU]: Route untuk DataTables Server-Side
    $routes->post('cuti/ajax_list', 'Cuti::ajax_list');

    // --- MANAJEMEN INPUT IZIN/SAKIT OLEH ADMIN ---
    $routes->get('inputizin', 'InputIzin::index');
    $routes->post('inputizin/simpan', 'InputIzin::simpan');
    $routes->get('inputizin/hapus/(:num)', 'InputIzin::hapus/$1');
    $routes->post('inputizin/ajax_list', 'InputIzin::ajax_list');

    // --- MANAJEMEN KOREKSI KEHADIRAN ---
    $routes->group('koreksikehadiran', function ($routes) {
        $routes->get('/', 'KoreksiKehadiran::index');
        $routes->post('get_dates', 'KoreksiKehadiran::get_dates');
        $routes->post('submit_koreksi', 'KoreksiKehadiran::submit_koreksi');
        $routes->get('approval', 'KoreksiKehadiran::approval');
        $routes->get('approve_action/(:num)', 'KoreksiKehadiran::approve_action/$1');
        $routes->get('reject_action/(:num)', 'KoreksiKehadiran::reject_action/$1');
        $routes->post('approve_bulk', 'KoreksiKehadiran::approve_bulk');
        $routes->post('reject_bulk', 'KoreksiKehadiran::reject_bulk');
        $routes->get('fix_db', 'KoreksiKehadiran::fix_db');
    });
});

$routes->group('approval', function ($routes) {
    $routes->get('/', 'Approval::index');                // Halaman Utama (Antrean Pending)
    $routes->get('riwayat', 'Approval::riwayat');        // Halaman Riwayat Selesai
    $routes->post('ajax_pending', 'Approval::ajax_pending'); // Ambil data tabel Pending (Server-side)
    $routes->post('ajax_riwayat', 'Approval::ajax_riwayat'); // Ambil data tabel Riwayat (Server-side)
    $routes->post('proses', 'Approval::proses');          // Eksekusi Approval Tunggal
    $routes->post('proses_massal', 'Approval::proses_massal'); // Eksekusi Approval Massal
    $routes->post('hapus/(:num)', 'Approval::hapus/$1');   // Hapus Riwayat Berdasarkan ID
    $routes->post('batal/(:num)', 'Approval::batal/$1');
});

// --- GROUP PWA (Pegawai Personal) ---
// Khusus untuk tampilan mobile pegawai di HP masing-masing
// Pintu masuk login Pegawai (PWA)
$routes->get('authpegawai', 'AuthPegawai::index');
$routes->post('authpegawai/login', 'AuthPegawai::login');





$routes->group('pwa', ['filter' => 'authFilter'], function ($routes) {
    $routes->get('dashboard', 'Pwa::index');
    $routes->post('submit_absen', 'Pwa::submit_absen');
    $routes->get('pengajuan/(:segment)', 'Pwa::pengajuan/$1');
    $routes->post('submit_izin', 'Pwa::submit_izin');
    $routes->post('submit_izin_pwa', 'Pwa::submit_izin_pwa');
});

// Route untuk halaman utama PWA Pegawai
$routes->get('pwa', 'Pwa::index');

// Route untuk proses Absen dan Izin (Wajib didaftarkan agar tombol berfungsi)
$routes->post('pwa/submit_absen', 'Pwa::submit_absen');
$routes->post('pwa/submit_izin', 'Pwa::submit_izin');
$routes->post('pegawai/ajax_list', 'Pegawai::ajax_list');
$routes->post('pwa/simpan_wajah_pwa', 'Pwa::simpan_wajah_pwa');
$routes->post('pwa/update_password', 'Pwa::update_password');
$routes->get('download/presensinew.apk', 'AuthPegawai::downloadApk');
$routes->get('presensi.apk', 'AuthPegawai::downloadApkOld');
$routes->post('pwa/submit_cito', 'Pwa::submit_cito');

// Route Cron Job Notifikasi Pengingat (Dengan Secret Key)
$routes->get('cron/reminders/(:segment)', 'Cron::reminders/$1');

// Laporan Tukin
$routes->get('laporan_tukin', 'LaporanTukin::index', ['filter' => 'authFilter']);
$routes->get('laporan_tukin/exportExcel', 'LaporanTukin::exportExcel', ['filter' => 'authFilter']);
$routes->post('laporan_tukin/ajax_list', 'LaporanTukin::ajax_list', ['filter' => 'authFilter']);

// Executive Dashboard
$routes->get('executive', 'Executive::index', ['filter' => 'authFilter']);





$routes->post('notifikasi/subscribe', 'Notifikasi::subscribe');

// Route untuk mengirim notifikasi secara massal (Blast)
// Bisa diakses via browser untuk tes: presensi.polmankab.go.id/notifikasi/sendReminder/pagi
$routes->get('notifikasi/sendReminder/(:any)', 'Notifikasi::sendReminder/$1');

// --- ROUTE MANAJEMEN APEL (SISI ADMIN) ---
$routes->group('apel', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Apel::index');                // Daftar Jadwal Apel
    $routes->post('store', 'Apel::store');           // Simpan Jadwal Baru (Kabupaten)
    $routes->get('kelola/(:num)', 'Apel::kelola/$1'); // Halaman Pilih Utusan (OPD)
    $routes->post('simpan_utusan', 'Apel::simpan_utusan'); // Simpan Utusan Massal
    $routes->get('delete/(:num)', 'Apel::delete/$1'); // Hapus Jadwal (Kabupaten)
    $routes->post('update/(:num)', 'Apel::update/$1');
    $routes->get('rekap/(:num)', 'Apel::rekap/$1');
    $routes->get('sinkron_ke_presensi/(:num)', 'Apel::sinkron_ke_presensi/$1');
    $routes->post('remind_peserta', 'Apel::remind_peserta');
});

// --- ROUTE ABSEN APEL (SISI PEGAWAI / PWA) ---
$routes->group('pwa', ['filter' => 'auth'], function ($routes) {
    // Tambahkan ini di dalam group PWA yang sudah ada
    $routes->post('submit_absen_apel', 'Pwa::submit_absen_apel');
    $routes->post('update_profile', 'Pwa::update_profile');
});


// ==========================================================
// ROUTE KHUSUS CRONJOB (HANYA BISA DIAKSES VIA TERMINAL/CLI)
// ==========================================================
$routes->cli('cron_reminder/cek_pulang', 'Cron_Reminder::cek_pulang');
$routes->cli('wacron/proses', 'WaCron::proses');



$routes->cli('migrate_bercadar', 'MigrateBercadar::index');
$routes->get('migrate_bercadar', 'MigrateBercadar::index');

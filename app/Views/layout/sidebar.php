<?php
// Ambil segment URI saat ini (Dirapikan agar tidak duplikat)
$uri = service('uri');
$segment = $uri->getSegment(1); // Mengambil kata pertama setelah base_url
$sub_segment = ($uri->getTotalSegments() >= 2) ? $uri->getSegment(2) : '';

// =========================================================================
// [FITUR BARU]: DETEKSI ADMIN NAKES UNTUK MENU LEMBUR DARURAT
// =========================================================================
$db_sidebar = \Config\Database::connect();
$myOpd = $db_sidebar->table('opd')->select('nama_opd')->where('id_opd', session()->get('id_opd'))->get()->getRow();
$nama_opd_upper = $myOpd ? strtoupper($myOpd->nama_opd) : '';
$is_nakes_admin = (strpos($nama_opd_upper, 'RSUD') !== false || strpos($nama_opd_upper, 'PUSKESMAS') !== false || strpos($nama_opd_upper, 'KESEHATAN') !== false || session()->get('level') == 'kabupaten');

// Deteksi Khusus RSUD Hajjah Andi Depu & Superadmin untuk Menu CITO
$is_rsud_depu_admin = (in_array(session()->get('level'), ['Superadmin', 'kabupaten']) || strpos($nama_opd_upper, 'RSUD HAJJAH ANDI DEPU') !== false);
// =========================================================================
?>

<style>
    /* Sembunyikan scrollbar bawaan agar sidebar terlihat premium */
    .sidebar::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.4);
    }
</style>
<div class="sidebar" style="width: 210px; height: 100vh; position: fixed; background: #111827; color: white; padding: 20px 10px; z-index: 1000; overflow-y: auto; overflow-x: hidden;">
    <div class="text-center mb-4 border-bottom border-secondary pb-3">
        <img src="https://polmankab.go.id//assets/uploads/summernote/SUMMER_0_1734504076.png" style="width: 50px; height: 50px; object-fit: contain; background: white; border-radius: 50%; padding: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
        <div class="fw-bold mt-2 small text-white">PRESENSI AI</div>
        <div class="badge <?= (session()->get('level') == 'kabupaten') ? 'bg-primary' : 'bg-success' ?> mt-1" style="font-size: 0.6rem;">
            <?= (session()->get('level') == 'kabupaten') ? 'ADMIN KABUPATEN' : 'ADMIN OPD' ?>
        </div>
    </div>

    <nav class="nav flex-column" id="nav-accordion">
        <a class="nav-link text-white mb-2 <?= ($segment == 'dashboard') ? 'active-link' : 'opacity-75' ?>" href="<?= base_url('dashboard') ?>">
            <i class="fas fa-th-large me-2"></i> Dashboard
        </a>

        <?php if (in_array(session()->get('level'), ['kabupaten', 'Superadmin', 'opd'])): ?>
            <a class="nav-link text-white mb-2 <?= ($segment == 'executive') ? 'active-link' : 'opacity-75' ?>" href="<?= base_url('executive') ?>">
                <i class="fas fa-crown me-2 text-warning"></i> Executive Dashboard
            </a>
        <?php endif; ?>

        <div class="nav-item">
            <?php $master_active = in_array($segment, ['opd', 'users', 'pegawai']); ?>
            <a class="nav-link text-white mb-2 d-flex justify-content-between align-items-center <?= $master_active ? '' : 'opacity-75' ?>"
                data-bs-toggle="collapse" href="#menuMaster" role="button" aria-expanded="<?= $master_active ? 'true' : 'false' ?>">
                <span><i class="fas fa-server me-2"></i> Master Data</span>
                <i class="fas fa-chevron-right small" style="font-size: 0.7rem;"></i>
            </a>
            <div class="collapse ps-3 <?= $master_active ? 'show' : '' ?>" id="menuMaster" data-bs-parent="#nav-accordion">
                <?php if (session()->get('level') == 'kabupaten'): ?>
                    <a class="nav-link text-white py-1 small <?= ($segment == 'opd') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('opd') ?>">
                        <i class="fas fa-university me-2"></i> Data OPD
                    </a>
                    <a class="nav-link text-white py-1 small <?= ($segment == 'users') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('users') ?>">
                        <i class="fas fa-user-tie me-2"></i> Kelola User
                    </a>
                <?php endif; ?>

                <a class="nav-link text-white py-1 small <?= ($segment == 'pegawai') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('pegawai') ?>">
                    <i class="fas fa-user-tie me-2"></i> Data Pegawai
                </a>
            </div>
        </div>

        <div class="nav-item">
            <?php $config_active = in_array($segment, ['jamkerja', 'harilibur', 'plotting', 'cuti', 'pesan', 'inputizin']); ?>
            <a class="nav-link text-white mb-2 d-flex justify-content-between align-items-center <?= $config_active ? '' : 'opacity-75' ?>"
                data-bs-toggle="collapse" href="#menuConfig" role="button" aria-expanded="<?= $config_active ? 'true' : 'false' ?>">
                <span><i class="fas fa-tools me-2"></i> Konfigurasi</span>
                <i class="fas fa-chevron-right small" style="font-size: 0.7rem;"></i>
            </a>
            <div class="collapse ps-3 <?= $config_active ? 'show' : '' ?>" id="menuConfig" data-bs-parent="#nav-accordion">
                <?php if (session()->get('level') == 'kabupaten'): ?>
                    <a class="nav-link text-white py-1 small <?= ($segment == 'jamkerja') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('jamkerja') ?>">
                        <i class="fas fa-business-time me-2"></i> Jam Kerja
                    </a>
                    <a class="nav-link text-white py-1 small <?= ($segment == 'harilibur') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('harilibur') ?>">
                        <i class="fas fa-calendar-day me-2"></i> Hari Libur
                    </a>
                    <a class="nav-link text-white py-1 small <?= ($segment == 'pesan') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('pesan') ?>">
                        <i class="fas fa-comment-dots me-2"></i> Pesan Sapaan
                    </a>
                <?php endif; ?>

                <a class="nav-link text-white py-1 small <?= ($segment == 'plotting') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('plotting') ?>">
                    <i class="fas fa-map-marked-alt me-2"></i> Plotting
                </a>

                <a class="nav-link text-white py-1 small <?= ($segment == 'cuti') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('cuti') ?>">
                    <i class="fas fa-user-clock me-2"></i> Cuti Pegawai
                </a>

                <a class="nav-link text-white py-1 small <?= ($segment == 'inputizin') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('inputizin') ?>">
                    <i class="fas fa-notes-medical me-2"></i> Izin & Tugas Luar
                </a>
            </div>
        </div>

        <a class="nav-link text-white mb-2 <?= ($segment == 'apel') ? 'active-link' : 'opacity-75' ?>" href="<?= base_url('apel') ?>">
            <i class="fas fa-bullhorn me-2 text-warning"></i> Manajemen Apel
        </a>

        <?php if (session()->get('username') == 'superadmin'): ?>
            <a class="nav-link text-white mb-2 <?= ($segment == 'bulkabsen') ? 'active-link' : 'opacity-75' ?>" href="<?= base_url('bulkabsen') ?>">
                <i class="fas fa-calendar-check me-2 text-info"></i> Matriks Absen (SA)
            </a>
        <?php endif; ?>


        <a class="nav-link text-white mb-2 <?= ($segment == 'approval') ? 'active-link' : 'opacity-75' ?>" href="<?= base_url('approval') ?>">
            <i class="fas fa-check-circle me-2"></i> Approval
        </a>

        <?php if (session()->get('level') == 'opd'): ?>
            <a class="nav-link text-white mb-2 <?= ($segment == 'koreksikehadiran') ? 'active-link' : 'opacity-75' ?>" href="<?= base_url('koreksikehadiran') ?>">
                <i class="fas fa-edit me-2 text-primary"></i> Koreksi Kehadiran
            </a>
        <?php endif; ?>

        <?php if (in_array(session()->get('level'), ['kabupaten', 'Superadmin'])): ?>
            <a class="nav-link text-white mb-2 <?= ($segment == 'koreksikehadiran' && $sub_segment == 'approval') ? 'active-link' : 'opacity-75' ?>" href="<?= base_url('koreksikehadiran/approval') ?>">
                <i class="fas fa-tasks me-2 text-primary"></i> Approval Koreksi
            </a>
        <?php endif; ?>

        <!-- <?php if ($is_nakes_admin): ?>
        <a class="nav-link text-white mb-2 <?= ($segment == 'lemburnakes') ? 'active-link' : 'opacity-75' ?>" href="<?= base_url('lemburnakes') ?>">
            <i class="fas fa-ambulance me-2 text-danger"></i> Lembur Nakes
        </a>
        <?php endif; ?> -->

        <?php if ($is_rsud_depu_admin): ?>
            <a class="nav-link text-white mb-2 <?= ($segment == 'cito') ? 'active-link' : 'opacity-75' ?>" href="<?= base_url('cito') ?>">
                <i class="fas fa-first-aid me-2 text-danger"></i> Manajemen CITO
            </a>
        <?php endif; ?>

        <div class="nav-item">
            <?php $rekap_active = in_array($segment, ['rekap', 'keterlambatan', 'cetaktpp', 'laporan_tukin', 'tpp_dinamis']); ?>
            <a class="nav-link text-white mb-2 d-flex justify-content-between align-items-center <?= $rekap_active ? '' : 'opacity-75' ?>"
                data-bs-toggle="collapse" href="#menuRekap" role="button" aria-expanded="<?= $rekap_active ? 'true' : 'false' ?>">
                <span><i class="fas fa-clipboard-list me-2"></i> Rekapitulasi</span>
                <i class="fas fa-chevron-right small" style="font-size: 0.7rem;"></i>
            </a>
            <div class="collapse ps-3 <?= ($segment == 'rekap' || $segment == 'keterlambatan' || $segment == 'cetaktpp' || $segment == 'laporan_tukin' || $segment == 'tpp_dinamis') ? 'show' : '' ?>" id="menuRekap" data-bs-parent="#nav-accordion">

                <a class="nav-link text-white py-1 small <?= ($uri->getSegment(2) == 'harian') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('rekap/harian') ?>">
                    <i class="fas fa-calendar-day me-2 text-warning"></i> <b>Rekap Harian</b>
                </a>

                <a class="nav-link text-white py-1 small <?= ($uri->getSegment(2) == 'bulanan_matriks') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('rekap/bulanan_matriks') ?>">
                    <i class="fas fa-th-list me-2"></i> Matriks Role Model
                </a>

                <a class="nav-link text-white py-1 small <?= ($segment == 'rekap' && empty($uri->getSegment(2))) ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('rekap') ?>">
                    <i class="fas fa-calendar-check me-2"></i> Rekap Kehadiran
                </a>

                <a class="nav-link text-white py-1 small <?= ($segment == 'keterlambatan') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('keterlambatan') ?>">
                    <i class="fas fa-user-clock me-2"></i> Rekap Telat
                </a>

                <a class="nav-link text-white py-1 small <?= ($segment == 'cetaktpp') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('cetaktpp') ?>">
                    <i class="fas fa-file-invoice-dollar me-2"></i> Rekap TPP
                </a>

                <a class="nav-link text-white py-1 small <?= ($segment == 'laporan_tukin') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('laporan_tukin') ?>">
                    <i class="fas fa-file-excel me-2 text-success"></i> Laporan Tukin
                </a>

                <!-- MENU BARU: REKAP TPP DINAMIS BK & KK (PROTOTYPE 40% - 60%) -->
                <a class="nav-link text-white py-1 small <?= ($segment == 'tpp_dinamis' && empty($sub_segment)) ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('tpp_dinamis') ?>">
                    <i class="fas fa-coins me-2 text-warning"></i> <b>Rekap TPP (BK & KK)</b>
                    <span class="badge bg-warning text-dark ms-1" style="font-size: 0.55rem;">BARU</span>
                </a>

                <a class="nav-link text-white py-1 small <?= ($segment == 'tpp_dinamis' && $sub_segment == 'kelola_ekin') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('tpp_dinamis/kelola_ekin') ?>">
                    <i class="fas fa-chart-line me-2 text-info"></i> Kelola E-Kinerja
                </a>

                <a class="nav-link text-white py-1 small <?= ($segment == 'tpp_dinamis' && $sub_segment == 'kelola_pagu') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('tpp_dinamis/kelola_pagu') ?>">
                    <i class="fas fa-file-invoice-dollar me-2 text-warning"></i> Kelola Pagu TPP
                </a>

                <?php if (in_array(session()->get('level'), ['Superadmin', 'kabupaten'])): ?>
                <a class="nav-link text-white py-1 small <?= ($segment == 'tpp_dinamis' && $sub_segment == 'setting_opd') ? 'active-sub' : 'opacity-50' ?>" href="<?= base_url('tpp_dinamis/setting_opd') ?>">
                    <i class="fas fa-sliders-h me-2 text-secondary"></i> Setting OPD Prototype
                </a>
                <?php endif; ?>

            </div>
        </div>
        <div class="mt-auto p-3">
            <button class="btn btn-outline-warning btn-sm w-100 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGantiPassword">
                <i class="fas fa-key me-2"></i> Ganti Password
            </button>
        </div>

        <a class="nav-link text-danger mt-4" href="<?= base_url('logout') ?>">
            <i class="fas fa-sign-out-alt me-2"></i> Keluar
        </a>
    </nav>
</div>

<style>
    /* Indikator link utama yang aktif (Dashboard) */
    .active-link {
        background: rgba(13, 110, 253, 0.2);
        border-left: 4px solid #0d6efd;
        border-radius: 4px;
        opacity: 1 !important;
        font-weight: bold;
    }

    /* Indikator sub-menu yang aktif */
    .active-sub {
        color: #3b82f6 !important;
        opacity: 1 !important;
        font-weight: bold;
    }

    /* Animasi panah */
    [data-bs-toggle="collapse"][aria-expanded="true"] .fa-chevron-right {
        transform: rotate(90deg);
        transition: 0.3s;
    }

    .nav-link:hover {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        transition: 0.2s;
    }

    .nav-link i {
        width: 20px;
        text-align: center;
    }
</style>
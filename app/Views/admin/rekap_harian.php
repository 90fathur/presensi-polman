<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
$bulan_list = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];
?>

<style>
    .bg-danger-subtle {
        background-color: #f8d7da !important;
    }

    .bg-success-subtle {
        background-color: #d1e7dd !important;
    }

    .table thead th {
        border: none !important;
        font-size: 0.65rem;
    }

    .table tbody td {
        border-color: #f0f0f0 !important;
    }

    @media print {
        .no-print {
            display: none !important;
        }
    }

    /* KELOMPOK WARNA BADGE STATUS MURNI CSS */
    .bg-st-istimewa {
        background-color: #155724 !important;
        color: white !important;
    }

    .bg-st-baik {
        background-color: #28a745 !important;
        color: white !important;
    }

    .bg-st-cukup {
        background-color: #8ce196 !important;
        color: #212529 !important;
    }

    .bg-st-telat {
        background-color: #ffda6a !important;
        color: #212529 !important;
    }

    .bg-st-bina {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }

    .bg-st-teguran {
        background-color: #dc3545 !important;
        color: white !important;
    }

    .bg-st-izin {
        background-color: #6f42c1 !important;
        color: white !important;
    }

    .bg-st-apel {
        background-color: #0d6efd !important;
        color: white !important;
    }

    .bg-st-cepul {
        background-color: #fd7e14 !important;
        color: white !important;
    }

    .bg-st-selesai {
        background-color: #0d6efd !important;
        color: white !important;
    }

    /* [FITUR BARU]: WARNA KHUSUS UNTUK STATUS STANDBY / CITO */
    .bg-st-standby {
        background-color: #0dcaf0 !important;
        color: #212529 !important;
    }

    .bg-st-default {
        background-color: #6c757d !important;
        color: white !important;
    }

    /* STYLE UNTUK BARIS GROUPING */
    .row-group-header {
        background-color: #e2e8f0 !important;
        color: #0f172a !important;
        font-weight: bold;
        text-align: left;
    }
</style>

<div class="card shadow-sm border-0 mb-4 no-print">
    <div class="card-body">
        <form action="" method="GET" class="row g-2 align-items-end">

            <?php if (session()->get('level') == 'kabupaten' || session()->get('level') == 'Superadmin' || (isset($is_induk_opd) && $is_induk_opd)): ?>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Pilih Unit Kerja / Kategori</label>
                    <select name="id_opd" class="form-select form-select-sm shadow-none border-secondary" required>

                        <?php if (session()->get('level') == 'kabupaten' || session()->get('level') == 'Superadmin'): ?>
                            <option value="">-- Pilih Instansi --</option>
                            <optgroup label="[ REKAP GABUNGAN MASSAL ]">
                                <?php if (!empty($list_gabungan)): foreach ($list_gabungan as $gabungan): ?>
                                        <option value="gabungan||<?= $gabungan ?>" <?= ($selected_opd == 'gabungan||' . $gabungan) ? 'selected' : '' ?>>GABUNGAN <?= $gabungan ?></option>
                                <?php endforeach;
                                endif; ?>
                            </optgroup>
                            <optgroup label="[ REKAP STRUKTURAL KABUPATEN ]">
                                <option value="rekap_eselon_2" <?= ($selected_opd == 'rekap_eselon_2') ? 'selected' : '' ?>>ESELON II (KEPALA DINAS)</option>
                                <option value="rekap_eselon_3" <?= ($selected_opd == 'rekap_eselon_3') ? 'selected' : '' ?>>ESELON III (KABID/SEKRETARIS)</option>
                                <option value="rekap_eselon_4" <?= ($selected_opd == 'rekap_eselon_4') ? 'selected' : '' ?>>ESELON IV (KASI/KASUBAG)</option>
                                <option value="rekap_eselon_gabungan" <?= ($selected_opd == 'rekap_eselon_gabungan') ? 'selected' : '' ?>>GABUNGAN ESELON II, III, & IV</option>
                            </optgroup>
                            <optgroup label="[ PER UNIT KERJA (OPD) ]">
                                <?php foreach ($list_opd as $o): ?>
                                    <option value="<?= $o['id_opd'] ?>" <?= ($selected_opd == $o['id_opd']) ? 'selected' : '' ?>><?= $o['nama_opd'] ?></option>
                                <?php endforeach; ?>
                            </optgroup>

                        <?php else: ?>
                            <option value="<?= session()->get('id_opd') ?>" <?= ($selected_opd == session()->get('id_opd')) ? 'selected' : '' ?>>-- REKAP KANTOR INDUK SAJA --</option>

                            <?php
                            $nama_gab_dinas = '';
                            foreach ($list_gabungan as $gab) {
                                if (strpos($admin_opd_name, $gab) !== false || (strpos($admin_opd_name, 'DINAS KESEHATAN') !== false && $gab == 'DINAS KESEHATAN')) {
                                    $nama_gab_dinas = $gab;
                                    break;
                                }
                            }
                            if ($nama_gab_dinas):
                            ?>
                                <option value="gabungan||<?= $nama_gab_dinas ?>" <?= ($selected_opd == 'gabungan||' . $nama_gab_dinas) ? 'selected' : '' ?>>-- REKAP GABUNGAN (INDUK + SELURUH UPTD) --</option>
                            <?php endif; ?>

                            <optgroup label="[ PILIH UPTD DI BAWAH ANDA ]">
                                <?php if (isset($child_opds)): foreach ($child_opds as $c): ?>
                                        <option value="<?= $c['id_opd'] ?>" <?= ($selected_opd == $c['id_opd']) ? 'selected' : '' ?>><?= $c['nama_opd'] ?></option>
                                <?php endforeach;
                                endif; ?>
                            </optgroup>
                        <?php endif; ?>

                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-2">
                <label class="small fw-bold text-muted">Shift / Jadwal</label>
                <select name="id_jam_kerja" class="form-select form-select-sm shadow-none">
                    <option value="">Semua Shift</option>
                    <option value="lepas_jaga" <?= (isset($selected_shift) && $selected_shift == 'lepas_jaga') ? 'selected' : '' ?>>Lepas Jaga / Libur Shift</option>
                    <?php if (isset($list_jam_kerja)): foreach ($list_jam_kerja as $jk): ?>
                            <option value="<?= $jk['id_jam_kerja'] ?>" <?= (isset($selected_shift) && $selected_shift == $jk['id_jam_kerja']) ? 'selected' : '' ?>>
                                <?= $jk['nama_jadwal'] ?>
                            </option>
                    <?php endforeach;
                    endif; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="small fw-bold text-muted">Status ASN</label>
                <select name="status_asn" class="form-select border-secondary">
                    <option value="">-- Semua Status ASN --</option>
                    <option value="PNS" <?= (isset($selected_status_asn) && $selected_status_asn == 'PNS') ? 'selected' : '' ?>>PNS</option>
                    <option value="PPPK" <?= (isset($selected_status_asn) && $selected_status_asn == 'PPPK') ? 'selected' : '' ?>>PPPK</option>
                    <option value="PPPKPW" <?= (isset($selected_status_asn) && $selected_status_asn == 'PPPKPW') ? 'selected' : '' ?>>PPPKPW</option>
                    <option value="TENAGA KONTRAK" <?= (isset($selected_status_asn) && $selected_status_asn == 'TENAGA KONTRAK') ? 'selected' : '' ?>>TENAGA KONTRAK</option>
                    <option value="KONTRAK BLUD" <?= (isset($selected_status_asn) && $selected_status_asn == 'KONTRAK BLUD') ? 'selected' : '' ?>>KONTRAK BLUD</option>
                    <option value="NON ASN LAINNYA" <?= (isset($selected_status_asn) && $selected_status_asn == 'NON ASN LAINNYA') ? 'selected' : '' ?>>NON ASN LAINNYA</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="small fw-bold text-muted">Tanggal</label>
                <input type="date" name="tanggal" class="form-control form-control-sm shadow-none" value="<?= $tanggal ?>">
            </div>

            <div class="col-md-1">
                <label class="small fw-bold text-muted">Sesi</label>
                <select name="sesi" class="form-select form-select-sm shadow-none">
                    <option value="semua" <?= ($sesi ?? 'semua') == 'semua' ? 'selected' : '' ?>>Lengkap</option>
                    <option value="pagi" <?= ($sesi ?? '') == 'pagi' ? 'selected' : '' ?>>Pagi</option>
                    <option value="siang" <?= ($sesi ?? '') == 'siang' ? 'selected' : '' ?>>Siang</option>
                    <option value="sore" <?= ($sesi ?? '') == 'sore' ? 'selected' : '' ?>>Sore</option>
                </select>
            </div>

            <div class="col-md-1">
                <label class="small fw-bold text-muted">Pencarian</label>
                <input type="text" name="keyword" class="form-control form-control-sm shadow-none" placeholder="Nama / NIP" value="<?= $keyword ?? '' ?>">
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 shadow-sm" title="Tampilkan Data">
                    <i class="fas fa-search"></i>
                </button>
                <button type="button" onclick="generatePDF()" class="btn btn-danger btn-sm w-100 shadow-sm" title="Cetak PDF">
                    <i class="fas fa-file-pdf"></i>
                </button>
            </div>

        </form>
    </div>
</div>

<?php if (!empty($selected_opd)): ?>

    <ul class="nav nav-pills mb-3 no-print" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill small fw-bold px-4" id="pills-standar-tab" data-bs-toggle="pill" data-bs-target="#pills-standar" type="button" role="tab">
                <i class="fas fa-table me-1"></i> Tampilan Normal
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill small fw-bold px-4 ms-2" id="pills-grup-tab" data-bs-toggle="pill" data-bs-target="#pills-grup" type="button" role="tab">
                <i class="fas fa-layer-group me-1"></i> Tampilan Hierarki
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">

        <div class="tab-pane fade show active" id="pills-standar" role="tabpanel">
            <?php if (!empty($rekap)): ?>
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm text-center small align-middle mb-0" id="tabelRekap" style="font-size: 0.75rem;">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th rowspan="2" width="30" class="align-middle border-0">No</th>
                                    <th rowspan="2" class="align-middle text-start ps-3 border-0" style="min-width: 200px;">Nama Pegawai / NIP</th>
                                    <th colspan="3" class="bg-primary py-2 border-0">Waktu Presensi (Harian)</th>
                                    <th rowspan="2" class="align-middle bg-secondary border-0 text-white" style="min-width: 130px;">Status Penilaian</th>
                                    <th rowspan="2" class="align-middle bg-danger border-0 text-white no-print" style="width: 80px;">Aksi</th>
                                </tr>
                                <tr>
                                    <th width="80" class="bg-light text-dark border-0">Masuk</th>
                                    <th width="80" class="bg-light text-dark border-0">Siang</th>
                                    <th width="80" class="bg-light text-dark border-0">Pulang</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                foreach ($rekap as $r): ?>
                                    <tr>
                                        <td class="text-muted"><?= $no++ ?></td>
                                        <td class="text-start ps-3">
                                            <div class="fw-bold text-dark"><?= $r['nama_lengkap'] ?></div>
                                            <div class="text-muted" style="font-size: 0.65rem;">NIP. <?= $r['nip'] ?></div>
                                            <div class="text-primary fw-bold" style="font-size: 0.6rem;"><?= $r['nama_opd'] ?></div>
                                            <div class="mt-1">
                                                <span class="badge bg-light text-dark border border-secondary border-opacity-25 rounded-pill" style="font-size: 0.55rem; padding: 3px 8px;">
                                                    <i class="fas fa-business-time text-warning me-1"></i> <?= $r['nama_shift'] ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="<?= ($r['jam_masuk_aktual']) ? 'fw-bold text-dark' : 'text-muted' ?>">
                                            <?= ($r['jam_masuk_aktual']) ? date('H:i', strtotime($r['jam_masuk_aktual'])) : '--:--' ?>
                                        </td>
                                        <td class="text-muted">
                                            <?= ($r['jam_siang']) ? date('H:i', strtotime($r['jam_siang'])) : '--:--' ?>
                                        </td>
                                        <td class="<?= ($r['jam_pulang_aktual']) ? 'fw-bold text-dark' : 'text-muted' ?>">
                                            <?= ($r['jam_pulang_aktual']) ? date('H:i', strtotime($r['jam_pulang_aktual'])) : '--:--' ?>
                                        </td>
                                        <td>
                                            <?php
                                            $lbl_m = strtolower($r['status_label'] ?? '');
                                            $is_absen_khusus = (strpos($lbl_m, 'izin') !== false || strpos($lbl_m, 'sakit') !== false || strpos($lbl_m, 'luar') !== false || strpos($lbl_m, 'cuti') !== false || strpos($lbl_m, 'standby') !== false || strpos($lbl_m, 'libur') !== false || strpos($lbl_m, 'alpa') !== false);

                                            $cls_m = 'bg-st-default';
                                            if (strpos($lbl_m, 'istimewa') !== false || strpos($lbl_m, 'sangat baik') !== false) $cls_m = 'bg-st-istimewa';
                                            elseif ($lbl_m == 'baik') $cls_m = 'bg-st-baik';
                                            elseif (strpos($lbl_m, 'cukup') !== false) $cls_m = 'bg-st-cukup';
                                            elseif (strpos($lbl_m, 'terlambat') !== false) $cls_m = 'bg-st-telat';
                                            elseif (strpos($lbl_m, 'pembinaan') !== false) $cls_m = 'bg-st-bina';
                                            elseif (strpos($lbl_m, 'teguran') !== false) $cls_m = 'bg-st-teguran';
                                            elseif (strpos($lbl_m, 'apel') !== false) $cls_m = 'bg-st-apel';
                                            elseif (strpos($lbl_m, 'standby') !== false) $cls_m = 'bg-st-standby border border-info'; // [FITUR BARU]
                                            elseif ($is_absen_khusus) $cls_m = 'bg-st-izin';

                                            $lbl_p = strtolower($r['status_pulang_label'] ?? '');
                                            $cls_p = 'bg-st-default';
                                            if ($lbl_p == 'cepat pulang') $cls_p = 'bg-st-cepul';
                                            elseif ($lbl_p == 'selesai') $cls_p = 'bg-st-selesai';
                                            ?>

                                            <?php if (empty($r['status_pulang_label']) || $is_absen_khusus): ?>
                                                <span class="badge <?= $cls_m ?> rounded-pill shadow-sm" style="font-size: 0.6rem; width: 100%; padding: 6px 0; white-space: normal;">
                                                    <?= strtoupper($r['status_label']) ?>
                                                </span>
                                            <?php else: ?>
                                                <div class="d-flex flex-column gap-1">
                                                    <span class="badge <?= $cls_m ?> rounded-pill shadow-sm" style="font-size: 0.6rem; width: 100%; padding: 4px 0;">M: <?= $r['status_label'] ?></span>
                                                    <span class="badge <?= $cls_p ?> rounded-pill shadow-sm" style="font-size: 0.6rem; width: 100%; padding: 4px 0;">P: <?= $r['status_pulang_label'] ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="no-print align-middle">
                                            <?php if (!empty($r['id_presensi']) && $r['kategori'] == 'Hadir'): ?>
                                                <?php if (empty($r['status_verifikasi']) || $r['status_verifikasi'] == 'Valid'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger shadow-sm rounded-pill" style="font-size: 0.6rem; padding: 4px 10px;" onclick="bukaModalSuspend('<?= $r['id_presensi'] ?>', '<?= esc($r['nama_lengkap'], 'js') ?>')">
                                                        <i class="fas fa-ban"></i> Anulir
                                                    </button>
                                                <?php else: ?>
                                                    <form action="<?= base_url('rekap/restore_presensi') ?>" method="POST" class="d-inline">
                                                        <input type="hidden" name="id_presensi" value="<?= $r['id_presensi'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-success shadow-sm rounded-pill mb-1" style="font-size: 0.6rem; padding: 4px 10px;" onclick="return confirm('Pulihkan presensi ini?')">
                                                            <i class="fas fa-undo"></i> Pulihkan
                                                        </button>
                                                    </form>
                                                    <?php if ($r['status_verifikasi'] == 'Ditangguhkan'): ?>
                                                        <form action="<?= base_url('rekap/anulir_permanen') ?>" method="POST" class="d-inline">
                                                            <input type="hidden" name="id_presensi" value="<?= $r['id_presensi'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-dark shadow-sm rounded-pill" style="font-size: 0.6rem; padding: 4px 10px;" onclick="return confirm('Anulir permanen menjadi Alpha?')">
                                                                <i class="fas fa-times"></i> Alpha
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning border-0 shadow-sm text-center py-4">
                    <i class="fas fa-exclamation-triangle me-2"></i> Tidak ada data presensi ditemukan.
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="pills-grup" role="tabpanel">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm text-center small align-middle mb-0" style="font-size: 0.75rem;">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th rowspan="2" width="30" class="align-middle border-0">No</th>
                                <th rowspan="2" class="align-middle text-start ps-3 border-0" style="min-width: 200px;">Nama Pegawai / NIP</th>
                                <?php $colspan_waktu = ($sesi == 'semua') ? 3 : 1; ?>
                                <th colspan="<?= $colspan_waktu ?>" class="bg-primary py-2 border-0">Waktu Presensi (<?= ucfirst($sesi) ?>)</th>
                                <th rowspan="2" class="align-middle bg-secondary border-0 text-white" style="min-width: 130px;">Status Penilaian</th>
                            </tr>
                            <tr>
                                <?php if ($sesi == 'semua' || $sesi == 'pagi'): ?><th width="80" class="bg-light text-dark border-0">Masuk</th><?php endif; ?>
                                <?php if ($sesi == 'semua' || $sesi == 'siang'): ?><th width="80" class="bg-light text-dark border-0">Siang</th><?php endif; ?>
                                <?php if ($sesi == 'semua' || $sesi == 'sore'): ?><th width="80" class="bg-light text-dark border-0">Pulang</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $data_found = false;

                            foreach ($rekap_grouped as $grup_nama => $pegawai_list):
                                if (count($pegawai_list) > 0):
                                    $data_found = true;
                            ?>
                                    <tr>
                                        <td colspan="<?= 3 + $colspan_waktu ?>" class="row-group-header ps-3 py-2">
                                            <i class="fas fa-layer-group me-1 text-primary"></i> <?= $grup_nama ?>
                                        </td>
                                    </tr>
                                    <?php foreach ($pegawai_list as $r): ?>
                                        <tr>
                                            <td class="text-muted"><?= $no++ ?></td>
                                            <td class="text-start ps-3">
                                                <div class="fw-bold text-dark"><?= $r['nama_lengkap'] ?></div>
                                                <div class="text-muted" style="font-size: 0.65rem;">NIP. <?= $r['nip'] ?></div>
                                                <div class="mt-1">
                                                    <span class="badge bg-light text-dark border border-secondary border-opacity-25 rounded-pill" style="font-size: 0.55rem; padding: 3px 8px;">
                                                        <i class="fas fa-business-time text-warning me-1"></i> <?= $r['nama_shift'] ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <?php if ($sesi == 'semua' || $sesi == 'pagi'): ?>
                                                <td class="<?= ($r['jam_masuk_aktual']) ? 'fw-bold text-dark' : 'text-muted' ?>">
                                                    <?= ($r['jam_masuk_aktual']) ? date('H:i', strtotime($r['jam_masuk_aktual'])) : '--:--' ?>
                                                </td>
                                            <?php endif; ?>
                                            <?php if ($sesi == 'semua' || $sesi == 'siang'): ?>
                                                <td class="text-muted">
                                                    <?= ($r['jam_siang']) ? date('H:i', strtotime($r['jam_siang'])) : '--:--' ?>
                                                </td>
                                            <?php endif; ?>
                                            <?php if ($sesi == 'semua' || $sesi == 'sore'): ?>
                                                <td class="<?= ($r['jam_pulang_aktual']) ? 'fw-bold text-dark' : 'text-muted' ?>">
                                                    <?= ($r['jam_pulang_aktual']) ? date('H:i', strtotime($r['jam_pulang_aktual'])) : '--:--' ?>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <?php
                                                $lbl_m = strtolower($r['status_label'] ?? '');
                                                $is_absen_khusus = (strpos($lbl_m, 'izin') !== false || strpos($lbl_m, 'sakit') !== false || strpos($lbl_m, 'luar') !== false || strpos($lbl_m, 'cuti') !== false || strpos($lbl_m, 'standby') !== false || strpos($lbl_m, 'libur') !== false || strpos($lbl_m, 'alpa') !== false);

                                                $cls_m = 'bg-st-default';
                                                if (strpos($lbl_m, 'istimewa') !== false || strpos($lbl_m, 'sangat baik') !== false) $cls_m = 'bg-st-istimewa';
                                                elseif ($lbl_m == 'baik') $cls_m = 'bg-st-baik';
                                                elseif (strpos($lbl_m, 'cukup') !== false) $cls_m = 'bg-st-cukup';
                                                elseif (strpos($lbl_m, 'terlambat') !== false) $cls_m = 'bg-st-telat';
                                                elseif (strpos($lbl_m, 'pembinaan') !== false) $cls_m = 'bg-st-bina';
                                                elseif (strpos($lbl_m, 'teguran') !== false) $cls_m = 'bg-st-teguran';
                                                elseif (strpos($lbl_m, 'apel') !== false) $cls_m = 'bg-st-apel';
                                                elseif (strpos($lbl_m, 'standby') !== false) $cls_m = 'bg-st-standby border border-info'; // [FITUR BARU]
                                                elseif ($is_absen_khusus) $cls_m = 'bg-st-izin';

                                                $lbl_p = strtolower($r['status_pulang_label'] ?? '');
                                                $cls_p = 'bg-st-default';
                                                if ($lbl_p == 'cepat pulang') $cls_p = 'bg-st-cepul';
                                                elseif ($lbl_p == 'selesai') $cls_p = 'bg-st-selesai';
                                                ?>

                                                <?php if (empty($r['status_pulang_label']) || $is_absen_khusus): ?>
                                                    <span class="badge <?= $cls_m ?> rounded-pill shadow-sm" style="font-size: 0.6rem; width: 100%; padding: 6px 0; white-space: normal;">
                                                        <?= strtoupper($r['status_label']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <div class="d-flex flex-column gap-1">
                                                        <?php if ($sesi == 'semua' || $sesi == 'pagi' || $sesi == 'siang'): ?>
                                                            <span class="badge <?= $cls_m ?> rounded-pill shadow-sm" style="font-size: 0.6rem; width: 100%; padding: 4px 0;">
                                                                M: <?= $r['status_label'] ?>
                                                            </span>
                                                        <?php endif; ?>

                                                        <?php if ($sesi == 'semua' || $sesi == 'sore'): ?>
                                                            <span class="badge <?= $cls_p ?> rounded-pill shadow-sm" style="font-size: 0.6rem; width: 100%; padding: 4px 0;">
                                                                P: <?= $r['status_pulang_label'] ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="no-print align-middle">
                                                <?php if (!empty($r['id_presensi']) && $r['kategori'] == 'Hadir'): ?>
                                                    <?php if (empty($r['status_verifikasi']) || $r['status_verifikasi'] == 'Valid'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger shadow-sm rounded-pill" style="font-size: 0.6rem; padding: 4px 10px;" onclick="bukaModalSuspend('<?= $r['id_presensi'] ?>', '<?= esc($r['nama_lengkap'], 'js') ?>')">
                                                            <i class="fas fa-ban"></i> Anulir
                                                        </button>
                                                    <?php else: ?>
                                                        <form action="<?= base_url('rekap/restore_presensi') ?>" method="POST" class="d-inline">
                                                            <input type="hidden" name="id_presensi" value="<?= $r['id_presensi'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-success shadow-sm rounded-pill mb-1" style="font-size: 0.6rem; padding: 4px 10px;" onclick="return confirm('Pulihkan presensi ini?')">
                                                                <i class="fas fa-undo"></i> Pulihkan
                                                            </button>
                                                        </form>
                                                        <?php if ($r['status_verifikasi'] == 'Ditangguhkan'): ?>
                                                            <form action="<?= base_url('rekap/anulir_permanen') ?>" method="POST" class="d-inline">
                                                                <input type="hidden" name="id_presensi" value="<?= $r['id_presensi'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-dark shadow-sm rounded-pill" style="font-size: 0.6rem; padding: 4px 10px;" onclick="return confirm('Anulir permanen menjadi Alpha?')">
                                                                    <i class="fas fa-times"></i> Alpha
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                            <?php
                                    endforeach;
                                endif;
                            endforeach;
                            ?>
                            <?php if (!$data_found): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Tidak ada data yang sesuai.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <i class="fas fa-search text-light mb-3" style="font-size: 3rem;"></i>
            <h6 class="fw-bold">Siap Menampilkan Data</h6>
            <p class="text-muted small">Pilih <b>Unit Kerja</b>, <b>Tanggal</b>, dan <b>Sesi</b> lalu klik <b>Tampilkan</b>.</p>
        </div>
    </div>
<?php endif; ?>

<!-- MODAL SUSPEND PRESENSI -->
<div class="modal fade" id="modalSuspend" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-ban me-2"></i>Tangguhkan Presensi</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('rekap/suspend_presensi') ?>" method="POST">
                <div class="modal-body">
                    <p class="small text-muted mb-3">Tangguhkan presensi untuk: <strong id="suspendPegawaiName" class="text-dark"></strong></p>
                    <input type="hidden" name="id_presensi" id="suspendIdPresensi">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Alasan Penangguhan</label>
                        <textarea name="alasan_tangguh" class="form-control" rows="3" placeholder="Contoh: Terpantau pulang setelah absen pagi, mohon menghadap pimpinan." required></textarea>
                        <small class="text-danger mt-1 d-block" style="font-size: 0.7rem;">Pesan ini akan muncul di aplikasi pegawai, dan mereka diwajibkan menghadap pimpinan.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light btn-sm shadow-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm shadow-sm"><i class="fas fa-gavel me-1"></i> Tangguhkan Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function bukaModalSuspend(id_presensi, nama) {
        document.getElementById('suspendIdPresensi').value = id_presensi;
        document.getElementById('suspendPegawaiName').innerText = nama;
        new bootstrap.Modal(document.getElementById('modalSuspend')).show();
    }

    function generatePDF() {
        const isStandar = document.getElementById('pills-standar-tab').classList.contains('active');

        Swal.fire({
            title: 'Memproses Laporan...',
            text: 'Mohon tunggu sebentar, file PDF sedang dibuat.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        setTimeout(() => {
            try {
                const {
                    jsPDF
                } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4');

                let namaSesi = "<?= strtoupper($sesi ?? 'SEMUA') ?>";
                let sesiStr = (namaSesi !== 'SEMUA') ? `SESI ${namaSesi}` : "LENGKAP";
                let tipeLaporan = isStandar ? "HARIAN" : "HARIAN HIERARKI";

                let judul = `REKAP ${tipeLaporan} PRESENSI ASN (${sesiStr})`;
                let tanggal = "<?= date('d-m-Y', strtotime($tanggal ?? date('Y-m-d'))) ?>";

                doc.setFontSize(14);
                doc.text(judul, 105, 15, {
                    align: "center"
                });
                doc.setFontSize(10);

                let subtitleText = `Tanggal: ${tanggal} | Kab. Polewali Mandar`;
                <?php if (!empty($selected_status_asn)): ?>
                    subtitleText += ` | Status ASN: <?= strtoupper($selected_status_asn) ?>`;
                <?php endif; ?>
                doc.text(subtitleText, 105, 22, {
                    align: "center"
                });

                const sesiFilter = "<?= $sesi ?? 'semua' ?>";
                let tableHeaders = [
                    ['No', 'Nama Pegawai / NIP']
                ];
                if (sesiFilter === 'semua') tableHeaders[0].push('Masuk', 'Siang', 'Pulang');
                else if (sesiFilter === 'pagi') tableHeaders[0].push('Masuk');
                else if (sesiFilter === 'siang') tableHeaders[0].push('Siang');
                else if (sesiFilter === 'sore') tableHeaders[0].push('Pulang');
                tableHeaders[0].push('Status Penilaian');

                let tableBody = [];
                let no = 1;

                if (isStandar) {
                    const rawDataStandar = <?= json_encode($rekap ?? []) ?>;
                    rawDataStandar.forEach(p => {
                        let row = [];
                        row.push(no++);
                        row.push(p.nama_lengkap + "\nNIP. " + p.nip + "\n" + (p.nama_opd) + "\n(" + (p.nama_shift || "Reguler") + ")");

                        if (sesiFilter === 'semua' || sesiFilter === 'pagi') row.push(p.jam_masuk_aktual ? p.jam_masuk_aktual.substring(0, 5) : '--:--');
                        if (sesiFilter === 'semua' || sesiFilter === 'siang') row.push(p.jam_siang ? p.jam_siang.substring(0, 5) : '--:--');
                        if (sesiFilter === 'semua' || sesiFilter === 'sore') row.push(p.jam_pulang_aktual ? p.jam_pulang_aktual.substring(0, 5) : '--:--');

                        let stLabel = p.status_label ? p.status_label.toUpperCase() : '-';
                        if (stLabel.toLowerCase().includes('izin') || stLabel.toLowerCase().includes('sakit') || stLabel.toLowerCase().includes('cuti') || stLabel.toLowerCase().includes('libur') || stLabel.toLowerCase().includes('alpa') || stLabel.toLowerCase().includes('standby')) {
                            row.push(stLabel);
                        } else {
                            row.push(`M: ${p.status_label || '-'}\nP: ${p.status_pulang_label || '-'}`);
                        }
                        tableBody.push(row);
                    });
                } else {
                    const selectedOpdId = "<?= $selected_opd ?>";
                    let rawDataGrup = {};

                    if (selectedOpdId.startsWith('gabungan||')) {
                        const rawDataSemua = <?= json_encode($rekap ?? []) ?>;
                        rawDataSemua.forEach(p => {
                            let namaR = p.nama_opd.toUpperCase();
                            if (!rawDataGrup[namaR]) rawDataGrup[namaR] = [];
                            rawDataGrup[namaR].push(p);
                        });
                    } else {
                        rawDataGrup = <?= json_encode($rekap_grouped ?? []) ?>;
                    }

                    let colSpanCount = tableHeaders[0].length;

                    for (const [groupName, pegawaiList] of Object.entries(rawDataGrup)) {
                        if (pegawaiList.length > 0) {
                            tableBody.push([{
                                content: groupName,
                                colSpan: colSpanCount,
                                styles: {
                                    fillColor: [226, 232, 240],
                                    textColor: [15, 23, 42],
                                    fontStyle: 'bold',
                                    halign: 'left'
                                }
                            }]);

                            pegawaiList.forEach(p => {
                                let row = [];
                                row.push(no++);
                                row.push(p.nama_lengkap + "\nNIP. " + p.nip + "\n(" + (p.nama_shift || "Reguler") + ")");

                                if (sesiFilter === 'semua' || sesiFilter === 'pagi') row.push(p.jam_masuk_aktual ? p.jam_masuk_aktual.substring(0, 5) : '--:--');
                                if (sesiFilter === 'semua' || sesiFilter === 'siang') row.push(p.jam_siang ? p.jam_siang.substring(0, 5) : '--:--');
                                if (sesiFilter === 'semua' || sesiFilter === 'sore') row.push(p.jam_pulang_aktual ? p.jam_pulang_aktual.substring(0, 5) : '--:--');

                                let stLabel = p.status_label ? p.status_label.toUpperCase() : '-';
                                let isKhusus = (stLabel.toLowerCase().includes('izin') || stLabel.toLowerCase().includes('sakit') || stLabel.toLowerCase().includes('cuti') || stLabel.toLowerCase().includes('libur') || stLabel.toLowerCase().includes('alpa') || stLabel.toLowerCase().includes('standby'));

                                if (isKhusus) {
                                    row.push(stLabel);
                                } else {
                                    if (sesiFilter === 'semua') row.push(`M: ${p.status_label || '-'}\nP: ${p.status_pulang_label || '-'}`);
                                    else if (sesiFilter === 'pagi' || sesiFilter === 'siang') row.push(p.status_label || '-');
                                    else if (sesiFilter === 'sore') row.push(p.status_pulang_label || '-');
                                }
                                tableBody.push(row);
                            });
                        }
                    }
                }

                doc.autoTable({
                    startY: 30,
                    head: tableHeaders,
                    body: tableBody,
                    theme: 'grid',
                    styles: {
                        fontSize: 7,
                        halign: 'center',
                        valign: 'middle',
                        textColor: [0, 0, 0]
                    },
                    headStyles: {
                        fillColor: [33, 37, 41],
                        textColor: [255, 255, 255]
                    },
                    columnStyles: {
                        1: {
                            halign: 'left'
                        }
                    },
                    margin: {
                        top: 30,
                        left: 10,
                        right: 10
                    }
                });

                doc.save(`Rekap_Harian_${tanggal}_${namaSesi}.pdf`);
                Swal.close();
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Terjadi kesalahan saat membuat PDF.', 'error');
            }
        }, 300);
    }
</script>
<?= $this->endSection() ?>
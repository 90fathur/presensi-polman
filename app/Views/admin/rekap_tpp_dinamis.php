<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<style>
    .tpp-stat-card {
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .tpp-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.08) !important;
    }
    .table-tpp thead th {
        vertical-align: middle;
        font-size: 0.68rem;
        letter-spacing: 0.3px;
        border: 1px solid #dee2e6;
    }
    .table-tpp tbody td {
        vertical-align: middle;
        font-size: 0.72rem;
        border: 1px solid #dee2e6;
        padding: 5px 8px;
    }
    .bg-kinerja {
        background-color: rgba(13, 110, 253, 0.06);
    }
    .bg-disiplin {
        background-color: rgba(25, 135, 84, 0.06);
    }
    .bg-potongan {
        background-color: rgba(220, 53, 69, 0.06);
    }
    .bg-bersih {
        background-color: rgba(255, 193, 7, 0.12);
        font-weight: bold;
    }
</style>

<div class="container-fluid px-0">

    <!-- HEADER TITLE & ACTION BUTTONS -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="fas fa-coins text-warning me-2"></i> Rekap TPP BK & KK (Beban Kerja 60% & Disiplin 40%)
            </h4>
            <p class="text-muted small mb-0">
                Perhitungan TPP dinamis terintegrasi kehadiran kedisiplinan 40% & capaian E-Kinerja 60% sesuai format resmi BKPSDM.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (in_array($level, ['Superadmin', 'kabupaten'])): ?>
                <a href="<?= base_url('tpp_dinamis/setting_opd') ?>" class="btn btn-outline-dark btn-sm rounded-pill shadow-xs px-3">
                    <i class="fas fa-sliders-h me-1 text-primary"></i> Setting OPD
                </a>
            <?php endif; ?>

            <!-- TOMBOL UPLOAD E-KINERJA -->
            <button type="button" class="btn btn-info text-white btn-sm rounded-pill shadow-sm px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalUploadEkin">
                <i class="fas fa-chart-line me-1"></i> Upload E-Kinerja
            </button>

            <!-- TOMBOL UPLOAD PAGU TPP -->
            <button type="button" class="btn btn-warning text-dark btn-sm rounded-pill shadow-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalUploadPagu">
                <i class="fas fa-coins me-1"></i> Upload Pagu TPP
            </button>

            <!-- DROPDOWN UNDUH TEMPLATE -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm rounded-pill shadow-xs px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-download me-1"></i> Unduh Template
                </button>
                <ul class="dropdown-menu shadow border-0" style="font-size: 0.82rem;">
                    <li>
                        <a class="dropdown-item py-2" href="<?= base_url('tpp_dinamis/download_template?id_opd=' . $selected_opd . '&bulan=' . $bulan . '&tahun=' . $tahun) ?>">
                            <i class="fas fa-file-excel me-2 text-info"></i> Template Capaian E-Kinerja (.xlsx)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="<?= base_url('tpp_dinamis/download_template_pagu?id_opd=' . $selected_opd) ?>">
                            <i class="fas fa-file-invoice-dollar me-2 text-warning"></i> Template Master Pagu TPP (.xlsx)
                        </a>
                    </li>
                </ul>
            </div>

            <?php if (!empty($pegawai)): ?>
                <a href="<?= base_url('tpp_dinamis/excel?id_opd=' . $selected_opd . '&bulan=' . $bulan . '&tahun=' . $tahun . '&status_asn=' . ($selected_status_asn ?? '')) ?>" class="btn btn-success btn-sm rounded-pill shadow-sm px-3">
                    <i class="fas fa-file-excel me-1"></i> Ekspor Excel Resmi
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- NOTIFIKASI JIKA OPD BELUM DIAKTIFKAN PROTOTIPE (ADMIN OPD) -->
    <?php if (!$is_opd_prototype_active && !in_array($level, ['Superadmin', 'kabupaten'])): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center mb-4" role="alert">
            <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
            <div>
                <h6 class="fw-bold mb-1">Status Prototype Belum Diaktifkan</h6>
                <p class="small mb-0">
                    Instansi Anda saat ini belum diaktifkan dalam daftar <b>Prototype TPP Dinamis (BK & KK 40/60)</b> oleh Admin Kabupaten. 
                    Silakan hubungi BKPSDM / Admin Kabupaten untuk mengaktifkan prototype ini.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- FILTER CARD -->
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-body p-3">
            <form action="<?= base_url('tpp_dinamis') ?>" method="GET" class="row g-2 align-items-end">

                <?php if (in_array($level, ['kabupaten', 'Superadmin'])): ?>
                    <div class="col-md-4">
                        <label class="small fw-bold text-muted mb-1">
                            Unit Kerja / OPD (Prototype Aktif)
                            <span class="badge bg-primary-subtle text-primary ms-1" style="font-size: 0.65rem;">
                                <?= count($list_opd_aktif) ?> OPD Aktif
                            </span>
                        </label>
                        <select name="id_opd" class="form-select form-select-sm shadow-none border-secondary" required>
                            <option value="">-- Pilih OPD Prototype --</option>
                            <optgroup label="[ OPD PROTOTYPE AKTIF ]">
                                <?php foreach ($list_opd_aktif as $oa): ?>
                                    <option value="<?= $oa['id_opd'] ?>" <?= ($selected_opd == $oa['id_opd']) ? 'selected' : '' ?>>
                                        <?= $oa['nama_opd'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="[ SEMUA OPD KABUPATEN ]">
                                <?php foreach ($list_opd_all as $o): ?>
                                    <?php if (empty($o['tpp_dinamis_aktif'])): ?>
                                        <option value="<?= $o['id_opd'] ?>" <?= ($selected_opd == $o['id_opd']) ? 'selected' : '' ?>>
                                            <?= $o['nama_opd'] ?> (Belum Aktif)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">Status ASN</label>
                    <select name="status_asn" class="form-select form-select-sm border-secondary shadow-none">
                        <option value="">-- Semua Status ASN --</option>
                        <option value="PNS" <?= ($selected_status_asn == 'PNS') ? 'selected' : '' ?>>PNS</option>
                        <option value="PPPK" <?= ($selected_status_asn == 'PPPK') ? 'selected' : '' ?>>PPPK</option>
                        <option value="PPPKPW" <?= ($selected_status_asn == 'PPPKPW') ? 'selected' : '' ?>>PPPKPW</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">Bulan</label>
                    <select name="bulan" class="form-select form-select-sm border-secondary shadow-none">
                        <?php 
                        $bln_indo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                        foreach ($bln_indo as $num => $nama_bln): ?>
                            <option value="<?= $num ?>" <?= ($bulan == $num) ? 'selected' : '' ?>><?= $nama_bln ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small fw-bold text-muted mb-1">Tahun</label>
                    <input type="number" name="tahun" class="form-control form-control-sm border-secondary shadow-none" value="<?= $tahun ?>" min="2020" max="2035">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill shadow-sm">
                        <i class="fas fa-search me-1"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <?php if (!empty($pegawai)): ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card tpp-stat-card border-0 shadow-sm bg-white p-3 text-center border-start border-primary border-4">
                    <span class="text-muted small fw-semibold text-uppercase">Total Pegawai</span>
                    <h4 class="fw-bold text-primary mb-0 mt-1"><?= number_format($summary['total_pegawai']) ?></h4>
                    <span class="small text-muted" style="font-size: 0.65rem;">Orang</span>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card tpp-stat-card border-0 shadow-sm bg-white p-3 text-center border-start border-info border-4">
                    <span class="text-muted small fw-semibold text-uppercase">TPP Kinerja (60%)</span>
                    <h5 class="fw-bold text-info mb-0 mt-1">Rp <?= number_format($summary['total_kinerja'], 0, ',', '.') ?></h5>
                    <span class="small text-muted" style="font-size: 0.65rem;">Capaian Ekin</span>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card tpp-stat-card border-0 shadow-sm bg-white p-3 text-center border-start border-success border-4">
                    <span class="text-muted small fw-semibold text-uppercase">TPP Disiplin (40%)</span>
                    <h5 class="fw-bold text-success mb-0 mt-1">Rp <?= number_format($summary['total_disiplin'], 0, ',', '.') ?></h5>
                    <span class="small text-muted" style="font-size: 0.65rem;">Kehadiran Presensi</span>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card tpp-stat-card border-0 shadow-sm bg-white p-3 text-center border-start border-secondary border-4">
                    <span class="text-muted small fw-semibold text-uppercase">Total TPP Kotor</span>
                    <h5 class="fw-bold text-dark mb-0 mt-1">Rp <?= number_format($summary['total_tpp_kotor'], 0, ',', '.') ?></h5>
                    <span class="small text-muted" style="font-size: 0.65rem;">Kinerja + Disiplin</span>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card tpp-stat-card border-0 shadow-sm bg-white p-3 text-center border-start border-danger border-4">
                    <span class="text-muted small fw-semibold text-uppercase">Potongan PPh 21</span>
                    <h5 class="fw-bold text-danger mb-0 mt-1">Rp <?= number_format($summary['total_pph21'], 0, ',', '.') ?></h5>
                    <span class="small text-muted" style="font-size: 0.65rem;">Pajak Penghasilan</span>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card tpp-stat-card border-0 shadow-sm bg-white p-3 text-center border-start border-warning border-4">
                    <span class="text-muted small fw-semibold text-uppercase">Total TPP Bersih</span>
                    <h5 class="fw-bold text-warning mb-0 mt-1">Rp <?= number_format($summary['total_tpp_bersih'], 0, ',', '.') ?></h5>
                    <span class="small text-muted" style="font-size: 0.65rem;">Siap Dibayarkan</span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- TABEL REKAP TPP DINAMIS -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fas fa-table me-2 text-primary"></i> Tabel Perhitungan TPP (BK & KK) - <?= strtoupper($nama_opd) ?>
            </h6>
            <span class="badge bg-light text-dark border">
                Periode: <?= $bln_indo[$bulan] ?? $bulan ?> <?= $tahun ?>
            </span>
        </div>

        <?php if (empty($pegawai)): ?>
            <div class="card-body p-5 text-center">
                <i class="fas fa-folder-open fa-3x text-muted mb-3 opacity-50"></i>
                <h6 class="fw-bold text-secondary">Data Pegawai Tidak Ditemukan</h6>
                <p class="small text-muted mb-0">Silakan pilih OPD atau pastikan OPD memiliki pegawai yang terdaftar.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-tpp mb-0 text-center align-middle" style="min-width: 1350px;">
                    <thead class="bg-light text-dark fw-bold">
                        <tr>
                            <th rowspan="3" style="width: 40px;">NO</th>
                            <th rowspan="3" style="width: 190px;" class="text-start ps-2">NAMA / NIP</th>
                            <th rowspan="3" style="width: 120px;">PANGKAT / GOL</th>
                            <th rowspan="3" style="width: 140px;" class="text-start ps-2">JABATAN</th>
                            <th rowspan="3" style="width: 70px;">JUMLAH<br>WKE</th>
                            <th colspan="2" class="bg-primary bg-opacity-10 text-primary">JENIS TPP</th>
                            <th rowspan="3" style="width: 95px;">JUMLAH TPP<br>(BK+KK)</th>
                            <th rowspan="3" style="width: 80px;">BPJS 1%</th>
                            <th rowspan="3" style="width: 95px;">TPP STLH<br>BPJS</th>
                            <th colspan="3" class="bg-info bg-opacity-10 text-info-emphasis">PREDIKAT KINERJA (60%)</th>
                            <th colspan="3" class="bg-success bg-opacity-10 text-success-emphasis">DISIPLIN KERJA (40%)</th>
                            <th rowspan="3" style="width: 95px;" class="bg-secondary bg-opacity-10">JUMLAH TPP<br>KOTOR</th>
                            <th colspan="2" class="bg-danger bg-opacity-10 text-danger-emphasis">PPh 21</th>
                            <th rowspan="3" style="width: 100px;" class="bg-warning bg-opacity-25 text-dark fw-bold">JUMLAH TPP<br>BERSIH</th>
                            <th rowspan="3" style="width: 60px;">AKSI</th>
                        </tr>
                        <tr>
                            <th style="width: 85px;">TPP BK</th>
                            <th style="width: 85px;">TPP KK</th>

                            <th style="width: 85px;">TPP (60%)</th>
                            <th style="width: 65px;">% KINERJA</th>
                            <th style="width: 85px;">JUMLAH</th>

                            <th style="width: 85px;">TPP (40%)</th>
                            <th style="width: 65px;">POT (%)</th>
                            <th style="width: 85px;">JUMLAH</th>

                            <th style="width: 45px;">%</th>
                            <th style="width: 80px;">NOMINAL</th>
                        </tr>
                        <tr class="bg-light text-muted" style="font-size: 0.6rem;">
                            <th>6</th>
                            <th>8</th>
                            <th>12</th>
                            <th>13</th>
                            <th>14</th>
                            <th>15</th>
                            <th>16</th>
                            <th>17</th>
                            <th>%</th>
                            <th>19</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pegawai as $p): ?>
                            <tr>
                                <td class="bg-light fw-bold"><?= $p['no'] ?></td>
                                <td class="text-start ps-2">
                                    <div class="fw-bold text-dark"><?= esc($p['nama']) ?></div>
                                    <div class="text-muted" style="font-size: 0.65rem;">NIP. <?= esc($p['nip']) ?></div>
                                </td>
                                <td>
                                    <span class="small"><?= esc($p['pangkat']) ?></span>
                                    <span class="badge bg-secondary-subtle text-secondary d-block mt-1" style="font-size: 0.6rem;"><?= esc($p['golongan']) ?></span>
                                </td>
                                <td class="text-start ps-2 small"><?= esc($p['jabatan']) ?></td>
                                <td class="fw-bold text-primary"><?= esc($p['wke']) ?></td>

                                <!-- Jenis TPP -->
                                <td class="text-end pe-2"><?= number_format($p['tpp_bk'], 0, ',', '.') ?></td>
                                <td class="text-end pe-2"><?= number_format($p['tpp_kk'], 0, ',', '.') ?></td>
                                <td class="text-end pe-2 fw-semibold"><?= number_format($p['jumlah_tpp'], 0, ',', '.') ?></td>
                                <td class="text-end pe-2 text-muted small"><?= number_format($p['bpjs_1pct'], 0, ',', '.') ?></td>
                                <td class="text-end pe-2 fw-semibold bg-light"><?= number_format($p['tpp_setelah_bpjs'], 0, ',', '.') ?></td>

                                <!-- Kinerja 60% -->
                                <td class="text-end pe-2 bg-kinerja"><?= number_format($p['tpp_60'], 0, ',', '.') ?></td>
                                <td class="bg-kinerja fw-bold text-primary">
                                    <div><?= number_format($p['persen_kinerja'], 0) ?>%</div>
                                    <span class="badge bg-white text-dark border px-1 mt-1" style="font-size: 0.62rem;"><?= esc($p['predikat_kinerja']) ?></span>
                                </td>
                                <td class="text-end pe-2 bg-kinerja fw-bold text-primary"><?= number_format($p['jumlah_tpp_kinerja'], 0, ',', '.') ?></td>

                                <!-- Disiplin 40% -->
                                <td class="text-end pe-2 bg-disiplin"><?= number_format($p['tpp_40'], 0, ',', '.') ?></td>
                                <td class="bg-disiplin fw-bold <?= ($p['persen_pot_disiplin'] > 0) ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format($p['persen_pot_disiplin'], 1) ?>%
                                </td>
                                <td class="text-end pe-2 bg-disiplin fw-bold text-success"><?= number_format($p['jumlah_tpp_disiplin'], 0, ',', '.') ?></td>

                                <!-- Total Kotor -->
                                <td class="text-end pe-2 fw-bold bg-light text-dark"><?= number_format($p['tpp_kotor'], 0, ',', '.') ?></td>

                                <!-- Pajak PPh 21 -->
                                <td class="small bg-potongan"><?= ($p['tarif_pph'] * 100) ?>%</td>
                                <td class="text-end pe-2 bg-potongan text-danger"><?= number_format($p['nominal_pph21'], 0, ',', '.') ?></td>

                                <!-- Total Bersih -->
                                <td class="text-end pe-2 bg-bersih text-dark"><?= number_format($p['tpp_bersih'], 0, ',', '.') ?></td>

                                <!-- Aksi Edit Cepat -->
                                <td>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-circle py-0 px-2"
                                            title="Edit Cepat Pagu & Ekin"
                                            onclick="openEditModal('<?= $p['nip'] ?>', '<?= addslashes($p['nama']) ?>', '<?= $p['wke'] ?>', '<?= $p['persen_kinerja'] ?>', '<?= $p['tpp_bk'] ?>', '<?= $p['tpp_kk'] ?>', '<?= $p['predikat_kinerja'] ?>')">
                                        <i class="fas fa-edit" style="font-size: 0.7rem;"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-light fw-bold text-center" style="font-size: 0.72rem;">
                        <tr>
                            <td colspan="5" class="text-end pe-3">TOTAL KESELURUHAN:</td>
                            <td class="text-end pe-2">-</td>
                            <td class="text-end pe-2">-</td>
                            <td class="text-end pe-2">-</td>
                            <td class="text-end pe-2 text-muted">Rp <?= number_format($summary['total_bpjs'], 0, ',', '.') ?></td>
                            <td class="text-end pe-2">-</td>
                            <td class="text-end pe-2">-</td>
                            <td class="text-center">-</td>
                            <td class="text-end pe-2 text-primary">Rp <?= number_format($summary['total_kinerja'], 0, ',', '.') ?></td>
                            <td class="text-end pe-2">-</td>
                            <td class="text-center">-</td>
                            <td class="text-end pe-2 text-success">Rp <?= number_format($summary['total_disiplin'], 0, ',', '.') ?></td>
                            <td class="text-end pe-2 text-dark bg-secondary bg-opacity-25">Rp <?= number_format($summary['total_tpp_kotor'], 0, ',', '.') ?></td>
                            <td class="text-center">-</td>
                            <td class="text-end pe-2 text-danger">Rp <?= number_format($summary['total_pph21'], 0, ',', '.') ?></td>
                            <td class="text-end pe-2 text-dark bg-warning bg-opacity-25 fs-6">Rp <?= number_format($summary['total_tpp_bersih'], 0, ',', '.') ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL UPLOAD EXCEL E-KINERJA -->
<div class="modal fade" id="modalUploadEkin" tabindex="-1" aria-labelledby="modalUploadEkinLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h6 class="modal-title fw-bold" id="modalUploadEkinLabel">
                    <i class="fas fa-cloud-upload-alt me-2"></i> Upload File E-Kinerja / TPP Excel
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formUploadEkin" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_opd" value="<?= $selected_opd ?>">

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label small fw-bold text-muted mb-1">Target Periode Bulan</label>
                            <select name="bulan" id="upload_target_bulan" class="form-select form-select-sm" required>
                                <?php foreach ($bln_indo as $num => $nama_bln): ?>
                                    <option value="<?= $num ?>" <?= ($bulan == $num) ? 'selected' : '' ?>><?= $nama_bln ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-bold text-muted mb-1">Target Tahun</label>
                            <input type="number" name="tahun" id="upload_target_tahun" class="form-control form-control-sm" value="<?= $tahun ?>" required>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 py-2 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        <b>Dukungan Otomatis Format File:</b>
                        <ul class="mb-0 ps-3 mt-1">
                            <li>Laporan Penilaian SKP Resmi SIASN / BKN (Contoh: <b>Laporan Penilaian SKP.xlsx</b>)</li>
                            <li>File rekap BKPSDM (Sheet <b>TPP REKAP BK&KK</b>)</li>
                            <li>File capaian E-Kinerja ASN (Sheet <b>PK PNS</b>)</li>
                            <li>Template Excel Resmi Presensi AI (.xlsx/.xls)</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Pilih File Excel (.xlsx / .xls)</label>
                        <input type="file" name="file_excel" class="form-control" accept=".xlsx, .xls, .csv" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <a href="<?= base_url('tpp_dinamis/download_template?id_opd=' . $selected_opd . '&bulan=' . $bulan . '&tahun=' . $tahun) ?>" class="small text-decoration-none">
                            <i class="fas fa-download me-1"></i> Unduh Format Template
                        </a>
                        <span class="small text-muted">Aktif: <?= $bln_indo[$bulan] ?? $bulan ?> <?= $tahun ?></span>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitUpload" class="btn btn-primary btn-sm rounded-pill px-4">
                        <i class="fas fa-upload me-1"></i> Mulai Proses
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL UPLOAD MASTER PAGU TPP -->
<div class="modal fade" id="modalUploadPagu" tabindex="-1" aria-labelledby="modalUploadPaguLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark py-3">
                <h6 class="modal-title fw-bold" id="modalUploadPaguLabel">
                    <i class="fas fa-coins me-2"></i> Upload Master Pagu TPP (Beban Kerja & Kondisi Kerja)
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUploadPagu" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_opd" value="<?= $selected_opd ?>">
                    <input type="hidden" name="bulan" value="<?= $bulan ?>">
                    <input type="hidden" name="tahun" value="<?= $tahun ?>">

                    <div class="alert alert-warning border-0 py-2 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        <b>Keterangan Master Pagu:</b>
                        <ul class="mb-0 ps-3 mt-1">
                            <li>Data pagu TPP BK & KK ini bersifat <b>tetap/permanen</b> untuk seluruh bulan.</li>
                            <li>Dukungan file: Sheet <b>TPP REKAP BK&KK</b> (file resmi BKPSDM) atau <b>Template Pagu TPP</b>.</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Pilih File Excel Pagu (.xlsx / .xls)</label>
                        <input type="file" name="file_excel" class="form-control" accept=".xlsx, .xls, .csv" required>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <a href="<?= base_url('tpp_dinamis/download_template_pagu?id_opd=' . $selected_opd) ?>" class="small text-decoration-none">
                            <i class="fas fa-download me-1"></i> Unduh Format Template Pagu
                        </a>
                        <span class="small text-muted">Master Nominal TPP</span>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitUploadPagu" class="btn btn-warning text-dark fw-bold btn-sm rounded-pill px-4">
                        <i class="fas fa-upload me-1"></i> Mulai Upload Pagu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT CEPAT PAGU & EKIN -->
<div class="modal fade" id="modalEditEkin" tabindex="-1" aria-labelledby="modalEditEkinLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-3">
                <h6 class="modal-title fw-bold" id="modalEditEkinLabel">
                    <i class="fas fa-user-edit me-2 text-warning"></i> Edit Pagu TPP & E-Kinerja Pegawai
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditEkin">
                <div class="modal-body p-4">
                    <input type="hidden" name="nip" id="edit_nip">
                    <input type="hidden" name="bulan" value="<?= $bulan ?>">
                    <input type="hidden" name="tahun" value="<?= $tahun ?>">

                    <div class="mb-3 p-2 bg-light rounded-3">
                        <div class="fw-bold text-dark" id="edit_nama">-</div>
                        <div class="small text-muted" id="edit_nip_display">-</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">Jumlah WKE</label>
                            <input type="number" name="wke" id="edit_wke" class="form-control form-control-sm" placeholder="Misal: 9020" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">% Kinerja (0 - 100)</label>
                            <input type="number" step="0.01" name="persen_kinerja" id="edit_persen_kinerja" class="form-control form-control-sm" placeholder="100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">TPP BK (Beban Kerja)</label>
                            <input type="number" step="0.01" name="tpp_bk" id="edit_tpp_bk" class="form-control form-control-sm" placeholder="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">TPP KK (Kondisi Kerja)</label>
                            <input type="number" step="0.01" name="tpp_kk" id="edit_tpp_kk" class="form-control form-control-sm" placeholder="0" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted mb-1">Rating / Predikat Kinerja</label>
                            <select name="predikat_kinerja" id="edit_predikat" class="form-select form-select-sm">
                                <option value="SANGAT BAIK">SANGAT BAIK (100%)</option>
                                <option value="BAIK">BAIK (100%)</option>
                                <option value="BUTUH PERBAIKAN">BUTUH PERBAIKAN (75%)</option>
                                <option value="KURANG">KURANG (50%)</option>
                                <option value="SANGAT KURANG">SANGAT KURANG (0%)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitEdit" class="btn btn-success btn-sm rounded-pill px-4">
                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function getPersenByPredikat(pred) {
        pred = (pred || '').toUpperCase().trim();
        if (pred === 'SANGAT BAIK' || pred === 'BAIK') return 100;
        if (pred === 'BUTUH PERBAIKAN') return 75;
        if (pred === 'KURANG') return 50;
        if (pred === 'SANGAT KURANG') return 0;
        return 100;
    }

    document.getElementById('edit_predikat').addEventListener('change', function() {
        document.getElementById('edit_persen_kinerja').value = getPersenByPredikat(this.value);
    });

    function openEditModal(nip, nama, wke, persenKinerja, tppBk, tppKk, predikat) {
        document.getElementById('edit_nip').value = nip;
        document.getElementById('edit_nama').innerText = nama;
        document.getElementById('edit_nip_display').innerText = 'NIP. ' + nip;
        document.getElementById('edit_wke').value = wke || '0';
        document.getElementById('edit_persen_kinerja').value = persenKinerja || '100';
        document.getElementById('edit_tpp_bk').value = tppBk || '0';
        document.getElementById('edit_tpp_kk').value = tppKk || '0';

        var predUpper = (predikat || 'BAIK').toUpperCase();
        var sel = document.getElementById('edit_predikat');
        sel.value = predUpper;
        if (!sel.value) sel.value = 'BAIK';

        var editModal = new bootstrap.Modal(document.getElementById('modalEditEkin'));
        editModal.show();
    }

    // Handle Upload Excel Ekin
    document.getElementById('formUploadEkin').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var btn = document.getElementById('btnSubmitUpload');
        var formData = new FormData(form);

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...';

        fetch('<?= base_url('tpp_dinamis/upload_ekin') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-1"></i> Mulai Proses';

            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 2500,
                    showConfirmButton: true
                }).then(() => {
                    const targetBln = res.bulan || formData.get('bulan') || '<?= $bulan ?>';
                    const targetThn = res.tahun || formData.get('tahun') || '<?= $tahun ?>';
                    window.location.href = '<?= base_url('tpp_dinamis') ?>?id_opd=<?= $selected_opd ?>&status_asn=<?= $selected_status_asn ?? '' ?>&bulan=' + encodeURIComponent(targetBln) + '&tahun=' + encodeURIComponent(targetThn);
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: res.message
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-1"></i> Mulai Proses';
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan Sistem',
                text: 'Gagal menghubungi server. Silakan coba kembali.'
            });
        });
    });

    // Form Upload Master Pagu TPP
    document.getElementById('formUploadPagu').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var btn = document.getElementById('btnSubmitUploadPagu');
        var formData = new FormData(form);

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Mengupload Pagu...';

        fetch('<?= base_url('tpp_dinamis/upload_pagu') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-1"></i> Mulai Upload Pagu';

            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 2500,
                    showConfirmButton: true
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: res.message
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload me-1"></i> Mulai Upload Pagu';
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan Sistem',
                text: 'Gagal menghubungi server. Silakan coba kembali.'
            });
        });
    });

    // Handle Edit Cepat Pagu & Ekin
    document.getElementById('formEditEkin').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var btn = document.getElementById('btnSubmitEdit');
        var formData = new FormData(form);

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menyimpan...';

        fetch('<?= base_url('tpp_dinamis/save_manual_ekin') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> Simpan Perubahan';

            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Tersimpan!',
                    text: res.message,
                    timer: 1800,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: res.message
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> Simpan Perubahan';
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan',
                text: 'Terjadi kesalahan pada sistem.'
            });
        });
    });
</script>

<?= $this->endSection() ?>

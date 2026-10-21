<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0">Rekapitulasi Kehadiran Apel</h5>
            <div class="text-muted small mt-1">Kegiatan: <strong class="text-primary"><?= $apel['nama_apel'] ?></strong> | Tanggal: <?= date('d M Y', strtotime($apel['tanggal'])) ?></div>
        </div>
        <div>
            <a href="<?= base_url('apel/sinkron_ke_presensi/' . $apel['id_apel']) ?>" class="btn btn-primary btn-sm rounded-pill px-3 me-1" title="Sinkronkan kehadiran apel ke presensi harian">
                <i class="fas fa-sync-alt me-1"></i>Sinkronkan ke Presensi
            </a>
            <a href="<?= base_url('apel') ?>" class="btn btn-secondary btn-sm rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
            <button onclick="window.print()" class="btn btn-success btn-sm rounded-pill px-4 ms-2">
                <i class="fas fa-print me-2"></i>Cetak
            </button>
        </div>
    </div>
    <div class="card-body">

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- FILTER OPD -->
        <?php if (in_array($level, ['Superadmin', 'kabupaten']) || $is_induk_opd): ?>
            <div class="row mb-4">
                <div class="col-md-6">
                    <form action="" method="GET">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-filter text-muted"></i></span>
                            <select name="id_opd" class="form-select border-start-0" onchange="this.form.submit()">
                                <option value="">Semua OPD yang Ditugaskan</option>
                                <?php if (in_array($level, ['Superadmin', 'kabupaten'])): ?>
                                    <?php foreach ($list_opd as $o): ?>
                                        <option value="<?= $o['id_opd'] ?>" <?= ($filter_opd == $o['id_opd']) ? 'selected' : '' ?>>
                                            <?= $o['nama_opd'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php
                                    // Untuk Admin Induk OPD
                                    $induk = array_filter($list_opd, function ($x) use ($id_opd_admin) {
                                        return $x['id_opd'] == $id_opd_admin;
                                    });
                                    $induk = reset($induk);
                                    if ($induk):
                                    ?>
                                        <option value="<?= $induk['id_opd'] ?>" <?= ($filter_opd == $induk['id_opd']) ? 'selected' : '' ?>>
                                            <?= $induk['nama_opd'] ?> (Induk)
                                        </option>
                                    <?php endif; ?>
                                    <?php foreach ($child_opds as $c): ?>
                                        <option value="<?= $c['id_opd'] ?>" <?= ($filter_opd == $c['id_opd']) ? 'selected' : '' ?>>
                                            -- <?= $c['nama_opd'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $hadir = 0;
        $tidak_hadir = 0;
        foreach ($peserta as $p) {
            if (!empty($p['jam_absen'])) $hadir++;
            else $tidak_hadir++;
        }
        ?>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-light border-0">
                    <div class="card-body text-center p-3">
                        <h6 class="text-muted fw-bold mb-1">Total Peserta</h6>
                        <h3 class="fw-bold text-dark mb-0"><?= count($peserta) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success bg-opacity-10 border-0">
                    <div class="card-body text-center p-3">
                        <h6 class="text-success fw-bold mb-1">Hadir Absen</h6>
                        <h3 class="fw-bold text-success mb-0"><?= $hadir ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger bg-opacity-10 border-0">
                    <div class="card-body text-center p-3">
                        <h6 class="text-danger fw-bold mb-1">Tidak Absen / Alpa Apel</h6>
                        <h3 class="fw-bold text-danger mb-0"><?= $tidak_hadir ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle table-bordered">
                <thead class="bg-light text-center">
                    <tr>
                        <th width="5%">No</th>
                        <th>Pegawai</th>
                        <th>Unit Kerja</th>
                        <th>Status Kehadiran</th>
                        <th>Waktu Absen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($peserta)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">Belum ada peserta yang ditugaskan untuk Apel ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1;
                        foreach ($peserta as $p): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= $p['nama_lengkap'] ?></div>
                                    <div class="small text-muted"><?= $p['nip'] ?></div>
                                </td>
                                <td><span class="small text-muted"><?= $p['nama_opd'] ?: '-' ?></span></td>
                                <td class="text-center">
                                    <?php if (!empty($p['jam_absen'])): ?>
                                        <span class="badge bg-success rounded-pill px-3">Hadir</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill px-3">Tidak Hadir (TA)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($p['jam_absen'])): ?>
                                        <div class="fw-bold text-primary"><?= $p['jam_absen'] ?> WITA</div>
                                        <?php if (!empty($p['latitude'])): ?>
                                            <div class="small text-muted"><i class="fas fa-map-marker-alt text-danger me-1"></i>Radius Valid</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }

        .card,
        .card * {
            visibility: visible;
        }

        .card {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none !important;
            box-shadow: none !important;
        }

        .btn,
        .btn-close,
        .btn-group {
            display: none !important;
        }
    }
</style>
<?= $this->endSection() ?>
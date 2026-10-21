<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="mb-4">
    <a href="<?= base_url('jamkerja') ?>" class="text-decoration-none small text-muted"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
    <h5 class="fw-bold mt-2">Setting Jam: <?= htmlspecialchars($grup['nama_jadwal']) ?></h5>
    <span class="badge bg-warning text-dark"><i class="fas fa-info-circle me-1"></i> Angka toleransi menggunakan satuan <b>Menit</b></span>
</div>

<div class="card border-0 shadow-sm">
    <form action="<?= base_url('jamkerja/simpan_detail') ?>" method="POST">
        <input type="hidden" name="id_jam_kerja" value="<?= $grup['id_jam_kerja'] ?>">
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-hover" style="min-width: 900px;">
                <thead class="bg-light small text-center align-middle">
                    <tr>
                        <th class="ps-4 text-start" rowspan="2" width="10%">HARI</th>
                        <th rowspan="2" width="12%">JAM MASUK</th>
                        <th rowspan="2" width="12%">JAM PULANG</th>
                        <th colspan="4" class="bg-warning-subtle border-bottom-0">BATAS TOLERANSI KETERLAMBATAN (MENIT)</th>
                        <th class="text-center" rowspan="2" width="10%">STATUS</th>
                    </tr>
                    <tr>
                        <th class="bg-success text-white" style="font-size: 0.7rem;" width="10%">Baik (Maks)</th>
                        <th class="bg-info text-dark" style="font-size: 0.7rem;" width="10%">Ckp Baik (Maks)</th>
                        <th class="bg-warning text-dark" style="font-size: 0.7rem;" width="10%">Telat (Maks)</th>
                        <th class="bg-danger text-white" style="font-size: 0.7rem;" width="10%">Bina (Maks)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($hari_list as $h): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="hari[]" value="<?= $h ?>" id="check<?= $h ?>" <?= isset($details[$h]) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold small" for="check<?= $h ?>"><?= $h ?></label>
                            </div>
                        </td>
                        <td>
                            <input type="time" name="jam_masuk[<?= $h ?>]" class="form-control form-control-sm" value="<?= $details[$h]['jam_masuk'] ?? '07:30' ?>">
                        </td>
                        <td>
                            <input type="time" name="jam_pulang[<?= $h ?>]" class="form-control form-control-sm" value="<?= $details[$h]['jam_pulang'] ?? '16:00' ?>">
                        </td>
                        
                        <td>
                            <input type="number" name="batas_baik_menit[<?= $h ?>]" class="form-control form-control-sm text-center" placeholder="15" value="<?= $details[$h]['batas_baik_menit'] ?? '15' ?>">
                        </td>
                        <td>
                            <input type="number" name="batas_cukup_menit[<?= $h ?>]" class="form-control form-control-sm text-center" placeholder="45" value="<?= $details[$h]['batas_cukup_menit'] ?? '45' ?>">
                        </td>
                        <td>
                            <input type="number" name="batas_telat_menit[<?= $h ?>]" class="form-control form-control-sm text-center" placeholder="90" value="<?= $details[$h]['batas_telat_menit'] ?? '90' ?>">
                        </td>
                        <td>
                            <input type="number" name="batas_bina_menit[<?= $h ?>]" class="form-control form-control-sm text-center" placeholder="150" value="<?= $details[$h]['batas_bina_menit'] ?? '150' ?>">
                        </td>

                        <td class="text-center">
                            <span class="badge <?= isset($details[$h]) ? 'bg-success' : 'bg-light text-muted border' ?> small w-100">
                                <?= isset($details[$h]) ? 'Aktif' : 'Libur' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 p-4 text-end">
            <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold">
                <i class="fas fa-save me-2"></i> Simpan Aturan & Toleransi
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
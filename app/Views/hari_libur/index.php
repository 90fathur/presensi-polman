<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Manajemen Hari Libur</h5>
    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalLibur">
        <i class="fas fa-plus me-1"></i> Tambah Hari Libur
    </button>
</div>

<?php if (session()->getFlashdata('success')) : ?>
    <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
        <?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small">
                    <tr>
                        <th class="ps-4">NO</th>
                        <th>TANGGAL</th>
                        <th>KETERANGAN</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($libur)) : ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted small">Belum ada data hari libur.</td>
                        </tr>
                    <?php endif; ?>
                    
                    <?php $no = 1; foreach ($libur as $l) : ?>
                        <tr>
                            <td class="ps-4 small text-muted"><?= $no++ ?>.</td>
                            <td class="fw-bold small"><?= date('d M Y', strtotime($l['tanggal'])) ?></td>
                            <td class="small"><?= $l['keterangan'] ?></td>
                            <td class="text-center">
                                <a href="<?= base_url('harilibur/hapus/' . $l['id_libur']) ?>" 
                                   class="btn btn-sm btn-light border text-danger" 
                                   onclick="return confirm('Hapus hari libur ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLibur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold small">Tambah Hari Libur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('harilibur/simpan') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control border-light bg-light" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control border-light bg-light" placeholder="Contoh: Libur Nasional / Cuti Bersama" required>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
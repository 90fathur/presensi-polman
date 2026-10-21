<?= view('layout/header') ?>
<?= view('layout/sidebar') ?>

<div class="main-content">
    <div class="d-md-none mb-3">
        <button class="btn btn-dark shadow-sm" id="toggleSidebar">
            <i class="fas fa-bars"></i> Menu
        </button>
    </div>
    <div class="modal fade" id="modalGantiPassword" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="<?= base_url('manajemenuser/update_password_self') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold"><i class="fas fa-lock me-2 text-warning"></i>Ganti Password Anda</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4">
                        <div class="alert alert-warning py-2 small border-0">
                            <i class="fas fa-info-circle me-1"></i> Pastikan password baru mudah diingat.
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold">Password Baru</label>
                            <input type="password" name="password_baru" class="form-control bg-light border-light" placeholder="Masukkan password baru" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold">Konfirmasi Password Baru</label>
                            <input type="password" name="konfirmasi_password" class="form-control bg-light border-light" placeholder="Ulangi password baru" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 shadow-sm text-dark fw-bold">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?= $this->renderSection('content') ?>

    <?= view('layout/footer') ?>
</div>
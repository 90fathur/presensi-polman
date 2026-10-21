<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Manajemen Akun OPD</h5>
    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUser">
        <i class="fas fa-user-shield me-1"></i> Daftarkan Akun OPD
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-muted small">
                <tr>
                    <th class="ps-4">USERNAME</th>
                    <th>NAMA ADMIN</th>
                    <th>UNIT KERJA (OPD)</th>
                    <th class="text-center">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u) : ?>
                    <tr>
                        <td class="ps-4 fw-bold small"><?= $u['username'] ?></td>
                        <td class="small"><?= $u['nama_user'] ?></td>
                        <td>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                <?= $u['nama_opd'] ?? 'Super Admin' ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if($u['level'] != 'admin'): ?>
                                <a href="<?= base_url('manajemenuser/hapus/' . $u['id_user']) ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('Hapus akun ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('manajemenuser/simpan') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Tambah Akun OPD</h5>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="small fw-bold">Pilih Instansi (OPD)</label>
                        <select name="id_opd" class="form-select bg-light border-light" required>
                            <option value="">-- Pilih OPD --</option>
                            <?php foreach($opd_list as $o): ?>
                                <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Username</label>
                        <input type="text" name="username" class="form-control bg-light border-light" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Nama Admin OPD</label>
                        <input type="text" name="nama_user" class="form-control bg-light border-light" placeholder="Contoh: Admin RSUD" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Password</label>
                        <input type="password" name="password" class="form-control bg-light border-light" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Daftarkan Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
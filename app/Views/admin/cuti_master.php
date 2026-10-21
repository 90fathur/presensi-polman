<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h5 class="fw-bold mt-2">Master Jenis Cuti</h5>
        <p class="small text-muted">Kelola kategori cuti dan jatah hari pegawai</p>
    </div>
    <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus me-1"></i> Tambah Cuti
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="bg-light small text-uppercase">
                <tr>
                    <th class="ps-4" width="80">No</th>
                    <th>Jenis Cuti</th>
                    <th>Jatah Hari</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; foreach($master_cuti as $m): ?>
                <tr>
                    <td class="ps-4 small text-muted"><?= $no++ ?></td>
                    <td><span class="fw-bold text-dark"><?= $m->nama_cuti ?></span></td>
                    <td><span class="badge bg-primary-subtle text-primary rounded-pill px-3"><?= $m->jatah_tahunan ?> Hari</span></td>
                    <td class="text-center">
                        <a href="<?= base_url('cuti/hapus_master/'.$m->id_cuti) ?>" class="btn btn-light btn-sm rounded-circle text-danger shadow-sm" onclick="return confirm('Hapus?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold">Tambah Jenis Cuti</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('cuti/simpan_master') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Cuti</label>
                        <input type="text" name="nama_cuti" class="form-control rounded-3" placeholder="Misal: Cuti Tahunan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jatah Hari</label>
                        <input type="number" name="jatah_tahunan" class="form-control rounded-3" placeholder="12" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary rounded-pill px-5">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
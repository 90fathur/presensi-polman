<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">Manajemen Grup Jadwal</h5>
    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGrup">
        <i class="fas fa-plus me-1"></i> Tambah Grup
    </button>
</div>

<div class="row">
    <?php foreach($jadwal as $j): ?>
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="badge bg-primary-subtle text-primary mb-2"><?= $j['tipe_jadwal'] ?></div>
                <h6 class="fw-bold text-dark"><?= $j['nama_jadwal'] ?></h6>
                <p class="small text-muted mb-3">Pengaturan jam kerja per hari (Senin-Minggu)</p>
                
                <div class="d-flex justify-content-between gap-2">
                    <a href="<?= base_url('jamkerja/detail/'.$j['id_jam_kerja']) ?>" class="btn btn-light border btn-sm w-100 rounded-pill">
                        <i class="fas fa-cog me-1"></i> Atur Jam
                    </a>
                    <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 text-dark fw-bold shadow-sm" onclick="bukaModalEdit(<?= $j['id_jam_kerja'] ?>, '<?= htmlspecialchars($j['nama_jadwal'], ENT_QUOTES) ?>', '<?= $j['tipe_jadwal'] ?>')">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="modalGrup" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('jamkerja/simpan_grup') ?>" method="POST">
                <div class="modal-header border-0 bg-light">
                    <h6 class="modal-title fw-bold">Buat Grup Jadwal</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Nama Grup / Shift</label>
                        <input type="text" name="nama_jadwal" class="form-control" placeholder="Contoh: Reguler 5 Hari" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Tipe</label>
                        <select name="tipe_jadwal" class="form-select">
                            <option value="Non-Shift">Non-Shift (Kantor)</option>
                            <option value="Shift">Shift (RS/Pelayanan)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Simpan Grup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditGrup" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= base_url('jamkerja/update_grup') ?>" method="POST">
                <input type="hidden" name="id_jam_kerja" id="edit_id_jam_kerja">
                <div class="modal-header border-0 bg-light">
                    <h6 class="modal-title fw-bold">Edit Grup Jadwal</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Nama Grup / Shift</label>
                        <input type="text" name="nama_jadwal" id="edit_nama_jadwal" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Tipe</label>
                        <select name="tipe_jadwal" id="edit_tipe_jadwal" class="form-select">
                            <option value="Non-Shift">Non-Shift (Kantor)</option>
                            <option value="Shift">Shift (RS/Pelayanan)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="submit" class="btn btn-warning text-dark fw-bold w-100 rounded-pill shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function bukaModalEdit(id, nama, tipe) {
        document.getElementById('edit_id_jam_kerja').value = id;
        document.getElementById('edit_nama_jadwal').value = nama;
        document.getElementById('edit_tipe_jadwal').value = tipe;
        
        var modal = new bootstrap.Modal(document.getElementById('modalEditGrup'));
        modal.show();
    }
</script>

<?= $this->endSection() ?>
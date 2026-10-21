<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="mb-4">
    <a href="<?= base_url('opd') ?>" class="btn btn-sm btn-secondary mb-2 rounded-pill shadow-sm text-white px-3">
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar OPD
    </a>
    <h5 class="fw-bold mt-2">📍 Lokasi Kantor: <?= $opd['nama_opd'] ?></h5>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm p-4">
            <h6 class="fw-bold mb-3">Tambah Lokasi Baru</h6>
            <form action="<?= base_url('opd/simpan_lokasi_tunggal') ?>" method="post">
                <input type="hidden" name="id_opd" value="<?= $opd['id_opd'] ?>">
                <div class="mb-2">
                    <label class="small fw-bold">Nama Gedung/Unit</label>
                    <input type="text" name="nama_lokasi" class="form-control form-control-sm" placeholder="Contoh: Gedung B / Kantor Cabang" required>
                </div>
                <div class="mb-2">
                    <label class="small fw-bold">Latitude</label>
                    <input type="text" name="lat" class="form-control form-control-sm" placeholder="-3.4251" required>
                </div>
                <div class="mb-2">
                    <label class="small fw-bold">Longitude</label>
                    <input type="text" name="long" class="form-control form-control-sm" placeholder="119.3241" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Radius (Meter)</label>
                    <input type="number" name="radius" class="form-control form-control-sm" value="50">
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill">Simpan Lokasi</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="ps-4">NAMA LOKASI</th>
                            <th>KOORDINAT</th>
                            <th>RADIUS</th>
                            <th>PEGAWAI</th>
                            <th class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lokasi)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">Belum ada lokasi kantor didaftarkan.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($lokasi as $l): ?>
                            <tr>
                                <td class="ps-4 fw-bold small text-primary"><?= $l['nama_lokasi'] ?></td>
                                <td class="small text-muted"><?= $l['latitude'] ?>, <?= $l['longitude'] ?></td>
                                <td class="small"><?= $l['radius_meter'] ?>m</td>
                                <td>
                                    <a href="<?= base_url('opd/kelola_pegawai_lokasi/' . $l['id_lokasi'] . '/' . $opd['id_opd']) ?>"
                                        class="btn btn-xs btn-info text-white rounded-pill px-3 shadow-sm" style="font-size: 0.7rem;">
                                        <i class="fas fa-users me-1"></i> Kelola Pegawai
                                    </a>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-sm btn-light text-primary border" 
                                            onclick="editLokasi('<?= $l['id_lokasi'] ?>', '<?= addslashes($l['nama_lokasi']) ?>', '<?= $l['latitude'] ?>', '<?= $l['longitude'] ?>', '<?= $l['radius_meter'] ?>')" 
                                            title="Edit Lokasi">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <a href="<?= base_url('opd/hapus_lokasi/' . $l['id_lokasi'] . '/' . $opd['id_opd']) ?>"
                                            class="btn btn-sm btn-light text-danger border"
                                            onclick="return confirm('Hapus titik lokasi ini?')" title="Hapus Lokasi">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditLokasi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h6 class="fw-bold mb-0">Edit Lokasi Kantor</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('opd/update_lokasi') ?>" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_opd" value="<?= $opd['id_opd'] ?>">
                    <input type="hidden" name="id_lokasi" id="edit_id_lokasi">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">Nama Gedung/Unit</label>
                        <input type="text" name="nama_lokasi" id="edit_nama_lokasi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Latitude</label>
                        <input type="text" name="lat" id="edit_lat" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Longitude</label>
                        <input type="text" name="long" id="edit_long" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Radius (Meter)</label>
                        <input type="number" name="radius" id="edit_radius" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editLokasi(id, nama, lat, lng, radius) {
        // Masukkan data ke dalam form modal
        document.getElementById('edit_id_lokasi').value = id;
        document.getElementById('edit_nama_lokasi').value = nama;
        document.getElementById('edit_lat').value = lat;
        document.getElementById('edit_long').value = lng;
        document.getElementById('edit_radius').value = radius;
        
        // Tampilkan Modal
        var myModal = new bootstrap.Modal(document.getElementById('modalEditLokasi'));
        myModal.show();
    }
</script>

<?= $this->endSection() ?>
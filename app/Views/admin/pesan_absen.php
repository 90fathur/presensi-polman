<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Manajemen Pesan Sapaan</h5>
        <p class="text-muted small mb-0">Atur pesan dinamis atau pantun saat pegawai absen</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" onclick="submitHapusMassal()">
            <i class="fas fa-trash-alt me-1"></i> Hapus Terpilih
        </button>
        <button class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPesanMassal">
            <i class="fas fa-paste me-1"></i> Input Massal
        </button>
        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPesan">
            <i class="fas fa-plus me-1"></i> Tambah 1 Pesan
        </button>
    </div>
</div>

<?php if (session()->getFlashdata('success')) : ?>
    <div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')) : ?>
    <div class="alert alert-danger border-0 shadow-sm"><i class="fas fa-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="alert alert-info border-0 shadow-sm small">
    <i class="fas fa-lightbulb me-2"></i> <b>Tips:</b> Gunakan teks <code>{nama}</code> di dalam pesan. Sistem akan otomatis menggantinya dengan nama pegawai yang sedang absen. Contoh: <i>"Semangat pagi, {nama}!"</i>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form action="<?= base_url('pesan/hapus_massal') ?>" method="POST" id="formHapusMassal">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th width="3%" class="text-center">
                                <input class="form-check-input shadow-none border-secondary" type="checkbox" id="checkAll">
                            </th>
                            <th width="5%">NO</th>
                            <th width="10%">TIPE</th>
                            <th width="55%">ISI PESAN / PANTUN</th>
                            <th width="12%" class="text-center">STATUS</th>
                            <th width="15%" class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($pesan_list as $row): ?>
                            <tr>
                                <td class="text-center">
                                    <input class="form-check-input check-item shadow-none border-secondary" type="checkbox" name="ids[]" value="<?= $row['id_pesan'] ?>">
                                </td>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td><span class="badge bg-dark"><?= $row['tipe'] ?></span></td>
                                <td><?= nl2br($row['pesan']) ?></td>
                                <td class="text-center">
                                    <?= $row['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Nonaktif</span>' ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group shadow-sm">
                                        <button type="button" class="btn btn-sm btn-white border text-warning" onclick="editPesan(<?= $row['id_pesan'] ?>, '<?= $row['tipe'] ?>', `<?= htmlspecialchars($row['pesan']) ?>`, <?= $row['is_active'] ?>)"><i class="fas fa-edit"></i></button>
                                        <a href="<?= base_url('pesan/hapus/'.$row['id_pesan']) ?>" class="btn btn-sm btn-white border text-danger" onclick="return confirm('Hapus pesan ini?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pesan_list)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada pesan sapaan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalPesan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Tambah Pesan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('pesan/simpan') ?>" method="POST" id="formPesan">
                <input type="hidden" name="id_pesan" id="id_pesan">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Momen / Tipe Absen</label>
                        <select name="tipe" id="tipe" class="form-select bg-light border-0" required>
                            <option value="Masuk">Absen Masuk (Pagi)</option>
                            <option value="Siang">Absen Siang (Istirahat)</option>
                            <option value="Pulang">Absen Pulang (Sore)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Isi Pesan (Bisa pakai HTML seperti &lt;br&gt;)</label>
                        <textarea name="pesan" id="pesan" rows="4" class="form-control bg-light border-0" placeholder="Contoh: Selamat pagi {nama}, semangat kerjanya!" required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="is_active" id="is_active" class="form-select bg-light border-0">
                            <option value="1">Aktif (Gunakan)</option>
                            <option value="0">Nonaktif (Sembunyikan)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPesanMassal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-paste me-2"></i>Input Pesan Massal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('pesan/simpan_massal') ?>" method="POST">
                <div class="modal-body p-4">
                    <div class="alert alert-warning small border-0 shadow-sm">
                        <i class="fas fa-info-circle me-1"></i> <b>Cara Penggunaan:</b><br>
                        Pilih tipe absen, lalu <i>Copy-Paste</i> puluhan pesan sekaligus ke dalam kotak di bawah ini. <b>Satu baris (Enter) akan dianggap sebagai 1 pesan baru.</b>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Momen / Tipe Absen</label>
                        <select name="tipe" class="form-select border-success shadow-none" required>
                            <option value="Masuk">Absen Masuk (Pagi)</option>
                            <option value="Siang">Absen Siang (Istirahat)</option>
                            <option value="Pulang" selected>Absen Pulang (Sore)</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Paste Pesan Disini</label>
                        <textarea name="pesan_massal" rows="10" class="form-control bg-light border-0" placeholder="Pesan 1...&#10;Pesan 2...&#10;Pesan 3..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow"><i class="fas fa-save me-1"></i> Simpan Massal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Script Edit Pesan (Asli)
    function editPesan(id, tipe, pesan, status) {
        document.getElementById('modalTitle').innerText = "Edit Pesan";
        document.getElementById('formPesan').action = "<?= base_url('pesan/update') ?>";
        document.getElementById('id_pesan').value = id;
        document.getElementById('tipe').value = tipe;
        document.getElementById('pesan').value = pesan;
        document.getElementById('is_active').value = status;
        new bootstrap.Modal(document.getElementById('modalPesan')).show();
    }

    // Reset form saat modal tambah ditutup
    document.getElementById('modalPesan').addEventListener('hidden.bs.modal', function () {
        document.getElementById('modalTitle').innerText = "Tambah Pesan";
        document.getElementById('formPesan').action = "<?= base_url('pesan/simpan') ?>";
        document.getElementById('id_pesan').value = "";
        document.getElementById('pesan').value = "";
    });

    // Script Checkbox Select All
    document.getElementById('checkAll').addEventListener('change', function(e) {
        let checkboxes = document.querySelectorAll('.check-item');
        checkboxes.forEach(cb => cb.checked = e.target.checked);
    });

    // Script Konfirmasi Hapus Massal
    function submitHapusMassal() {
        let checkedBoxes = document.querySelectorAll('.check-item:checked');
        if (checkedBoxes.length === 0) {
            alert('Silakan centang minimal satu pesan yang ingin dihapus!');
            return;
        }
        if (confirm('Anda yakin ingin menghapus ' + checkedBoxes.length + ' pesan terpilih secara permanen?')) {
            document.getElementById('formHapusMassal').submit();
        }
    }
</script>
<?= $this->endSection() ?>
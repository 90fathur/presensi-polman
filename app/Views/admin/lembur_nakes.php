<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-ambulance text-danger me-2"></i><?= $title ?></h4>
            <p class="text-muted small mb-0">Kelola riwayat jam panggilan darurat dan akses tombol Cito di aplikasi PWA Nakes.</p>
        </div>
        <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fas fa-plus me-2"></i> Input Manual
        </button>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <ul class="nav nav-pills mb-4 border-0" id="citoTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4 shadow-sm me-2" data-bs-toggle="pill" data-bs-target="#tab-riwayat" type="button" role="tab">Riwayat Jam Cito</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 shadow-sm" data-bs-toggle="pill" data-bs-target="#tab-akses" type="button" role="tab"><i class="fas fa-mobile-alt me-1"></i> Kelola Akses PWA</button>
        </li>
    </ul>

    <div class="tab-content" id="citoTabContent">
        <div class="tab-pane fade show active" id="tab-riwayat" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <form action="<?= base_url('lemburnakes') ?>" method="GET" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Bulan</label>
                            <select name="bulan" class="form-select shadow-none">
                                <?php
                                $bulan_indo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= sprintf('%02d', $i) ?>" <?= ($bulan_aktif == sprintf('%02d', $i)) ? 'selected' : '' ?>><?= $bulan_indo[$i] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Tahun</label>
                            <select name="tahun" class="form-select shadow-none">
                                <?php for ($i = date('Y'); $i >= date('Y') - 2; $i--): ?>
                                    <option value="<?= $i ?>" <?= ($tahun_aktif == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-dark w-100"><i class="fas fa-search me-2"></i> Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tgl & Waktu</th>
                                    <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama Pegawai</th>
                                    <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Keterangan / Tindakan</th>
                                    <th class="px-4 py-3 text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($riwayat_darurat)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Belum ada catatan jam darurat bulan ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($riwayat_darurat as $r): ?>
                                        <tr>
                                            <td class="px-4">
                                                <div class="fw-bold text-dark"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></div>
                                                <div class="text-danger fw-bold" style="font-size: 0.8rem;">
                                                    <i class="far fa-clock me-1"></i> <?= date('H:i', strtotime($r['jam_mulai'])) ?> - <?= ($r['jam_selesai'] == '00:00:00' || empty($r['jam_selesai'])) ? '<span class="badge bg-warning">Berjalan</span>' : date('H:i', strtotime($r['jam_selesai'])) ?>
                                                </div>
                                            </td>
                                            <td class="px-4">
                                                <div class="fw-bold text-dark"><?= $r['nama_lengkap'] ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-building me-1"></i> <?= $r['nama_opd'] ?? '-' ?></div>
                                            </td>
                                            <td class="px-4">
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-2 py-1 mb-1" style="font-size: 0.7rem;">Ekstra/Cito</span>
                                                <div class="text-muted" style="font-size: 0.8rem;"><?= $r['keterangan'] ?></div>
                                            </td>
                                            <td class="px-4 text-center">
                                                <a href="<?= base_url('lemburnakes/hapus/' . $r['id_darurat']) ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Hapus riwayat ini?')" title="Hapus"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-akses" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom pb-0">
                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-toggle-on text-primary me-2"></i> Izinkan Tombol Cito di PWA Pegawai</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 600px;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light position-sticky top-0 shadow-sm z-index-1">
                                <tr>
                                    <th class="px-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 w-50">Nama Pegawai</th>
                                    <th class="px-4 py-3 text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status Akses Cito</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($list_pegawai as $p): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="fw-bold text-dark"><?= $p['nama_lengkap'] ?></div>
                                            <div class="text-muted small">NIP. <?= $p['nip'] ?></div>
                                        </td>
                                        <td class="px-4 text-center">
                                            <div class="form-check form-switch d-flex justify-content-center m-0">
                                                <input class="form-check-input" type="checkbox" style="width: 2.5rem; height: 1.25rem; cursor:pointer;"
                                                    <?= ($p['is_cito'] == 1) ? 'checked' : '' ?>
                                                    onchange="toggleAksesCito(<?= $p['id_pegawai'] ?>, this.checked)">
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
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-plus-circle text-primary me-2"></i> Input Jam Ekstra Manual</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('lemburnakes/simpan') ?>" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Pilih Pegawai</label>
                        <select name="id_pegawai" class="form-select shadow-none" required>
                            <option value="">-- Pilih Pegawai --</option>
                            <?php foreach ($list_pegawai as $p): ?>
                                <option value="<?= $p['id_pegawai'] ?>"><?= $p['nama_lengkap'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Tanggal Panggilan</label>
                        <input type="date" name="tanggal" class="form-control shadow-none" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control shadow-none" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control shadow-none" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Keterangan / Tindakan</label>
                        <textarea name="keterangan" class="form-control shadow-none" rows="2" placeholder="Cth: Panggilan Operasi Cito..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Simpan Rekam</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function toggleAksesCito(id, isChecked) {
        let status = isChecked ? 1 : 0;

        // Mengambil Token CSRF bawaan CodeIgniter 4
        let csrfName = '<?= csrf_token() ?>';
        let csrfHash = '<?= csrf_hash() ?>';

        // Membungkus data + token keamanan
        let payload = {
            id_pegawai: id,
            status: status
        };
        payload[csrfName] = csrfHash;

        $.ajax({
            url: "<?= base_url('lemburnakes/set_akses_cito') ?>",
            type: "POST",
            data: payload,
            success: function(res) {
                // Berhasil mengubah status (Bisa tambahkan Toast/Notif di sini kalau mau)
                console.log(res.message);
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert("Gagal merubah akses! Cek koneksi atau refresh halaman.");
                // Kembalikan posisi tombol switch seperti semula jika gagal
                location.reload();
            }
        });
    }
</script>
<?= $this->endSection() ?>
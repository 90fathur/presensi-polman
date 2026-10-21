<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Manajemen OPD</h5>
        <small class="text-muted">Kelola instansi dan titik lokasi absensi pegawai.</small>
    </div>

    <div class="d-flex align-items-center gap-2">
        <div class="input-group input-group-sm shadow-sm" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Cari Nama Instansi...">
        </div>

        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalOPD">
            <i class="fas fa-plus me-1"></i> Tambah OPD
        </button>
    </div>
</div>

<?php if (session()->getFlashdata('success')) : ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableOPD">
                <thead class="bg-light text-muted small">
                    <tr>
                        <th class="ps-4" width="50">NO</th>
                        <th>NAMA INSTANSI</th>
                        <th>JADWAL KERJA</th>
                        <th>RADIUS</th>
                        <th>TITIK LOKASI</th>
                        <th class="text-center">KOREKSI</th>
                        <th class="text-center">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    foreach ($opd as $row): ?>
                        <tr>
                            <td class="ps-4 text-muted small"><?= $no++ ?></td>
                            <td class="searchable-opd">
                                <div class="fw-bold text-dark small"><?= $row['nama_opd'] ?></div>
                                <div class="text-muted" style="font-size: 0.65rem;">
                                    HQ: <?= $row['latitude'] ?>, <?= $row['longitude'] ?>
                                </div>
                            </td>
                            <td> <?php if (!empty($row['nama_jadwal'])): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">
                                        <i class="fas fa-clock me-1"></i> <?= $row['nama_jadwal'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border small">Belum diatur</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary-subtle text-secondary border"><?= $row['radius'] ?> Meter</span></td>
                            <td>
                                <span class="badge bg-info-subtle text-info border px-3">
                                    <i class="fas fa-map-marker-alt me-1"></i> <?= $row['jumlah_lokasi'] ?> Lokasi Kantor
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if (isset($row['is_koreksi_open']) && $row['is_koreksi_open'] == 1): ?>
                                    <span class="badge bg-success small">Terbuka</span>
                                <?php else: ?>
                                    <span class="badge bg-danger small">Tertutup</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="<?= base_url('opd/toggle_koreksi/' . $row['id_opd']) ?>" class="btn btn-sm btn-light border text-<?= (isset($row['is_koreksi_open']) && $row['is_koreksi_open'] == 1) ? 'danger' : 'success' ?>" title="<?= (isset($row['is_koreksi_open']) && $row['is_koreksi_open'] == 1) ? 'Tutup Akses Koreksi' : 'Buka Akses Koreksi' ?>" onclick="return confirm('Anda yakin mengubah status akses koreksi untuk OPD ini?')">
                                        <i class="fas fa-<?= (isset($row['is_koreksi_open']) && $row['is_koreksi_open'] == 1) ? 'lock' : 'unlock' ?>"></i>
                                    </a>
                                    <a href="<?= base_url('opd/lokasi/' . $row['id_opd']) ?>" class="btn btn-sm btn-light border text-primary" title="Kelola Lokasi">
                                        <i class="fas fa-search-location"></i>
                                    </a>
                                    <button class="btn btn-sm btn-light border text-dark btn-edit"
                                        data-id="<?= $row['id_opd'] ?>"
                                        data-nama="<?= $row['nama_opd'] ?>"
                                        data-kode="<?= $row['kode_opd'] ?>"
                                        data-jam="<?= $row['id_jam_kerja'] ?>"
                                        data-lat="<?= $row['latitude'] ?>"
                                        data-long="<?= $row['longitude'] ?>"
                                        title="Edit OPD">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="<?= base_url('opd/hapus/' . $row['id_opd']) ?>"
                                        class="btn btn-sm btn-light border text-danger"
                                        onclick="return confirm('Hapus OPD ini?')">
                                        <i class="fas fa-trash"></i>
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

<div class="modal fade" id="modalOPD" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="<?= base_url('opd/simpan') ?>" method="post" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Registrasi OPD & Multi-Lokasi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-5">
                        <label class="small fw-bold">Nama OPD</label>
                        <input type="text" name="nama_opd" class="form-control" placeholder="Nama Dinas" required>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Kode OPD</label>
                        <input type="text" name="kode_opd" class="form-control" placeholder="KODE" required>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold">Jadwal Kerja Default</label>
                        <select name="id_jam_kerja" class="form-select" required>
                            <option value="">-- Pilih Jadwal --</option>
                            <?php foreach ($list_jam_kerja as $jk): ?>
                                <option value="<?= $jk['id_jam_kerja'] ?>"><?= $jk['nama_jadwal'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="bg-warning-subtle p-3 rounded mb-3 border border-warning">
                    <h6 class="fw-bold small text-warning-emphasis mb-2"><i class="fas fa-building me-2"></i>Titik Koordinat Kantor Pusat (Induk)</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input type="text" name="lat_pusat" class="form-control form-control-sm" placeholder="Latitude Kantor Pusat" required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="long_pusat" class="form-control form-control-sm" placeholder="Longitude Kantor Pusat" required>
                        </div>
                        <div class="col-md-6 mb-12">
                            <label class="small fw-bold">Radius (m)</label>
                            <input type="text" name="radius" class="form-control" required>
                        </div>
                    </div>
                    <small class="text-muted mt-1 d-block">Pejabat/Staf Kantor Induk hanya bisa absen di koordinat ini.</small>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-hospital-user me-2"></i>Lokasi Cabang / UPTD (Opsional)</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="addLocation">
                        <i class="fas fa-plus-circle me-1"></i> Tambah UPTD
                    </button>
                </div>

                <div id="locationContainer">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary px-4 shadow-sm">Simpan OPD & Lokasi</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditOPD" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= base_url('opd/update') ?>" method="post" class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Edit Data Utama OPD</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_opd" id="edit_id">
                <div class="mb-3">
                    <label class="small fw-bold">Nama OPD</label>
                    <input type="text" name="nama_opd" id="edit_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Kode OPD</label>
                    <input type="text" name="kode_opd" id="edit_kode" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Jadwal Kerja</label>
                    <select name="id_jam_kerja" id="edit_jam" class="form-select" required>
                        <option value="">-- Pilih Jadwal --</option>
                        <?php foreach ($list_jam_kerja as $jk): ?>
                            <option value="<?= $jk['id_jam_kerja'] ?>"><?= $jk['nama_jadwal'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold">Lat Pusat</label>
                        <input type="text" name="lat_pusat" id="edit_lat" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="small fw-bold">Long Pusat</label>
                        <input type="text" name="long_pusat" id="edit_long" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-12">
                        <label class="small fw-bold">Radius /m</label>
                        <input type="text" name="radius" id="edit_radius" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-dark px-4 shadow-sm">Update Data Induk</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- DITAMBAHKAN: JS UNTUK FITUR PENCARIAN (LIVE SEARCH) ---
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#tableOPD tbody tr');

        rows.forEach(row => {
            // Mengambil teks dari kolom "NAMA INSTANSI" (kolom ke-2)
            let text = row.querySelector('.searchable-opd').textContent.toLowerCase();
            if (text.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    // ------------------------------------------------------------

    // JS UNTUK TAMBAH BARIS LOKASI
    document.getElementById('addLocation').addEventListener('click', function() {
        const container = document.getElementById('locationContainer');
        const newRow = document.createElement('div');
        newRow.className = 'location-row p-3 border rounded mb-2 bg-white shadow-sm';
        newRow.innerHTML = `
            <div class="row g-2">
                <div class="col-md-11">
                    <label class="small text-muted fw-bold">Nama UPTD / Cabang</label>
                    <input type="text" name="nama_lokasi[]" class="form-control form-control-sm" placeholder="Contoh: Puskesmas A" required>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm w-100 remove-row"><i class="fas fa-trash"></i></button>
                </div>
                <div class="col-md-4">
                    <input type="text" name="lat[]" class="form-control form-control-sm" placeholder="Latitude" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="long[]" class="form-control form-control-sm" placeholder="Longitude" required>
                </div>
                <div class="col-md-4">
                    <input type="number" name="radius[]" class="form-control form-control-sm" placeholder="Radius (Meter)" value="100">
                </div>
            </div>
        `;
        container.appendChild(newRow);
        newRow.querySelector('.remove-row').addEventListener('click', function() {
            newRow.remove();
        });
    });

    // JS UNTUK MENGISI DATA KE MODAL EDIT
    const btnEdit = document.querySelectorAll('.btn-edit');
    btnEdit.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');
            const kode = this.getAttribute('data-kode');
            const jam = this.getAttribute('data-jam');
            const lat = this.getAttribute('data-lat');
            const long = this.getAttribute('data-long');

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_kode').value = kode;
            document.getElementById('edit_jam').value = jam;
            document.getElementById('edit_lat').value = lat;
            document.getElementById('edit_long').value = long;

            const modalEdit = new bootstrap.Modal(document.getElementById('modalEditOPD'));
            modalEdit.show();
        });
    });
</script>
<?= $this->endSection() ?>
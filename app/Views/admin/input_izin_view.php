<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 text-dark fw-bold"><i class="fas fa-file-signature text-primary me-2"></i><?= $title ?></h4>
        <p class="text-muted mb-0 small">Manajemen input manual Izin, Sakit, dan Tugas Luar oleh Admin</p>
    </div>
    <button class="btn btn-primary rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus me-2"></i>Input Baru
    </button>
</div>

<?php if (session()->getFlashdata('success')) : ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')) : ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4" role="alert">
        <i class="fas fa-times-circle me-2"></i> <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <?php if (in_array($level, ['Superadmin', 'kabupaten'])): ?>
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Filter OPD/Instansi</label>
                    <select id="filter_opd" class="form-select border-0 bg-light rounded-3">
                        <option value="">-- Semua OPD --</option>
                        <?php foreach ($list_opd as $o) : ?>
                            <option value="<?= $o->id_opd ?>"><?= $o->nama_opd ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tableIzin" style="width:100%">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0 rounded-start">Pegawai / Instansi</th>
                        <th class="border-0">Kategori</th>
                        <th class="border-0">Tanggal</th>
                        <th class="border-0 text-center">Berkas Bukti</th>
                        <th class="border-0 text-center rounded-end">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Data -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 bg-light rounded-top-4 pb-3">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-edit text-primary me-2"></i>Input Data Pegawai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('inputizin/simpan') ?>" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Pilih Pegawai</label>
                        <select name="pegawai_id" id="pegawai_id" class="form-select select2-modal" required>
                            <option value="">-- Cari Nama atau NIP Pegawai --</option>
                            <?php foreach ($pegawai as $p) : ?>
                                <option value="<?= $p->id_pegawai ?>"><?= $p->nama_lengkap ?> (<?= $p->nip ?>) - <?= $p->nama_opd ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Kategori Pengajuan</label>
                            <select name="kategori" id="kategori" class="form-select bg-light border-0" required onchange="ubahSubKategori()">
                                <option value="">-- Pilih --</option>
                                <option value="Sakit">Sakit</option>
                                <option value="Izin">Izin</option>
                                <option value="Tugas Luar">Tugas Luar</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Sub Kategori</label>
                            <select name="sub_kategori" id="sub_kategori" class="form-select bg-light border-0" required>
                                <option value="">-- Pilih Kategori Dulu --</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai" class="form-control bg-light border-0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Tanggal Selesai</label>
                            <input type="date" name="tgl_selesai" class="form-control bg-light border-0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Keterangan / Alasan</label>
                        <textarea name="keterangan" class="form-control bg-light border-0" rows="3" placeholder="Masukkan keterangan secara lengkap..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Berkas Bukti (Opsional)</label>
                        <input type="file" name="file_bukti" class="form-control bg-light border-0" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text text-muted small"><i class="fas fa-info-circle me-1"></i>Format PDF/JPG maksimal 10MB. Boleh dikosongkan.</div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    function ubahSubKategori() {
        var kat = document.getElementById('kategori').value;
        var sub = document.getElementById('sub_kategori');
        var options = '<option value="">-- Pilih --</option>';

        if (kat === 'Sakit') {
            options += '<option value="Sakit Penuh">Sakit Penuh</option>';
        } else if (kat === 'Izin') {
            options += '<option value="Izin Penuh">Izin Penuh</option>';
            options += '<option value="Setengah Hari">Izin Setengah Hari</option>';
        } else if (kat === 'Tugas Luar') {
            options += '<option value="Luar Daerah">Tugas Luar Daerah</option>';
            options += '<option value="Dalam Daerah">Tugas Luar Dalam Daerah</option>';
        }

        sub.innerHTML = options;
    }

    $(document).ready(function() {
        var csrfName = '<?= csrf_token() ?>';
        var csrfHash = '<?= csrf_hash() ?>';

        $('.select2-modal').select2({
            dropdownParent: $('#modalTambah'),
            theme: 'bootstrap-5',
            width: '100%'
        });

        var table = $('#tableIzin').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "<?= base_url('inputizin/ajax_list') ?>",
                type: "POST",
                data: function(d) {
                    d.filter_opd = $('#filter_opd').val();
                    d[csrfName] = csrfHash;
                }
            },
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json',
            },
            ordering: false,
            pageLength: 10,
        });

        table.on('xhr.dt', function(e, settings, json, xhr) {
            if (json && json.csrf_hash) {
                csrfHash = json.csrf_hash;
            }
        });

        $('#filter_opd').change(function() {
            table.draw();
        });
    });
</script>

<?= $this->endSection() ?>
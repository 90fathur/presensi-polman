<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h5 class="fw-bold mt-2">Data Cuti Pegawai</h5>
        <p class="small text-muted">Kelola administrasi cuti dan dokumen surat izin pegawai</p>
    </div>
    <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fas fa-plus me-1"></i> Input Cuti Pegawai
    </button>
</div>

<?php if (session()->getFlashdata('success')) : ?>
    <div class="alert alert-success border-0 shadow-sm small mb-4">
        <i class="fas fa-check-circle me-1"></i> <?= session()->getFlashdata('success'); ?>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-white border-bottom py-3 px-4">
        <div class="row g-2">
            <?php if (in_array($level, ['Superadmin', 'kabupaten'])) : ?>
                <div class="col-md-4">
                    <select id="filterOpd" class="form-select shadow-none border-light-subtle">
                        <option value="">-- Semua OPD --</option>
                        <?php foreach ($list_opd as $o) : ?>
                            <option value="<?= $o->id_opd ?>"><?= $o->nama_opd ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-<?= in_array($level, ['Superadmin', 'kabupaten']) ? '6' : '10' ?>">
                <div class="input-group">
                    <span class="input-group-text bg-light border-light-subtle text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchCustom" class="form-control shadow-none border-light-subtle bg-light" placeholder="Cari Nama atau NIP Pegawai...">
                </div>
            </div>
            <div class="col-md-2">
                <button type="button" id="btnCari" class="btn btn-primary w-100 shadow-sm">Cari</button>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tableCuti" class="table table-hover align-middle mb-0 w-100">
                <thead class="bg-light small text-uppercase text-muted">
                    <tr>
                        <th class="ps-4 py-3 border-bottom-0" width="300">Pegawai & Instansi</th>
                        <th class="py-3 border-bottom-0">Jenis Cuti</th>
                        <th class="text-center py-3 border-bottom-0">Durasi</th>
                        <th class="py-3 border-bottom-0">Tanggal</th>
                        <th class="text-center py-3 border-bottom-0">Berkas</th>
                        <th class="text-center py-3 border-bottom-0 pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-edit me-2 text-primary"></i>Input Cuti Pegawai</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('cuti/simpan') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="small fw-bold">Pegawai</label>
                        <select name="pegawai_id" id="selectPegawai" class="form-select rounded-3 shadow-none border-light-subtle" required>
                            <option value="">-- Pilih Pegawai --</option>
                            <?php foreach ($pegawai as $p) : ?>
                                <option value="<?= $p->id_pegawai ?>"><?= $p->nip ?> - <?= $p->nama_lengkap ?> (<?= $p->nama_opd ?? '-' ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Jenis Cuti</label>
                        <select name="cuti_id" class="form-select rounded-3 shadow-none border-light-subtle" required>
                            <?php foreach ($jenis_cuti as $jc) : ?>
                                <option value="<?= $jc->id_cuti ?>"><?= $jc->nama_cuti ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai" class="form-control shadow-none border-light-subtle" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Tanggal Selesai</label>
                            <input type="date" name="tgl_selesai" class="form-control shadow-none border-light-subtle" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Lama Cuti (Hari)</label>
                            <input type="number" name="jumlah_hari" class="form-control shadow-none border-light-subtle" placeholder="Contoh: 3" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Upload Surat (PDF/JPG)</label>
                            <input type="file" name="berkas" class="form-control form-control-sm shadow-none border-light-subtle" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="small fw-bold">Alasan / Keterangan</label>
                        <textarea name="alasan" class="form-control shadow-none border-light-subtle" rows="2" placeholder="Input keterangan singkat..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
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
    $(document).ready(function() {
        // [1] Inisialisasi DataTables Server-Side
        var csrfName = '<?= csrf_token() ?>';
        var csrfHash = '<?= csrf_hash() ?>';

        var table = $('#tableCuti').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            // [PERBAIKAN DOM]: Memindahkan "Show entries" (l), Info (i), dan Pagination (p) ke bagian bawah tabel
            dom: "<'row'<'col-sm-12'tr>>" +
                "<'row align-items-center p-3 border-top'<'col-sm-12 col-md-4 small text-muted'l><'col-sm-12 col-md-4 small text-muted text-center'i><'col-sm-12 col-md-4'p>>",
            ajax: {
                url: "<?= base_url('cuti/ajax_list') ?>",
                type: "POST",
                data: function(d) {
                    d[csrfName] = csrfHash; // Token Keamanan CI4
                    d.filter_opd = $('#filterOpd').val(); // Kirim data filter OPD
                    d.search.value = $('#searchCustom').val(); // Kirim data text pencarian
                }
            },
            columnDefs: [{
                    targets: [4, 5],
                    orderable: false
                } // Nonaktifkan sorting di kolom Berkas & Aksi
            ],
            language: {
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 s/d 0 dari 0 data",
                paginate: {
                    previous: "Sebelumnya",
                    next: "Selanjutnya"
                }
            }
        });

        // Update token CSRF setiap kali Datatables me-load data baru (Pencegah Error 403)
        table.on('xhr.dt', function(e, settings, json, xhr) {
            if (json && json.csrf_hash) {
                csrfHash = json.csrf_hash;
            }
        });

        // [2] Pemicu Filter & Pencarian
        $('#filterOpd').on('change', function() {
            table.draw();
        });

        $('#btnCari').on('click', function() {
            table.draw();
        });

        $('#searchCustom').on('keyup', function(e) {
            if (e.key === 'Enter') {
                table.draw();
            }
        });

        // [3] Inisialisasi Select2 pada dropdown Pegawai Modal
        $('#selectPegawai').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#modalTambah'),
            placeholder: '-- Pilih Pegawai --',
            width: '100%'
        });

        // Mengosongkan input/reset Select2 saat modal ditutup
        $('#modalTambah').on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
            $('#selectPegawai').val('').trigger('change');
        });
    });
</script>
<?= $this->endSection() ?>
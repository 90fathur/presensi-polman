<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link active rounded-pill small fw-bold" href="<?= base_url('approval') ?>">Antrean Pengajuan</a></li>
    <li class="nav-item"><a class="nav-link text-muted rounded-pill small fw-bold" href="<?= base_url('approval/riwayat') ?>">Riwayat Selesai</a></li>
</ul>

<div class="mb-4 d-flex justify-content-between align-items-end">
    <div>
        <h5 class="fw-bold text-dark">Antrean Approval Izin / Sakit / DL</h5>
        <p class="text-muted small mb-0">Daftar pengajuan baru (Status: Pending).</p>
    </div>
    <div id="bulkActionArea" style="display: none;">
        <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-sm" onclick="bulkApprove('setuju')">
            <i class="fas fa-check-double me-1"></i> Setujui Terpilih (<span id="countSelected">0</span>)
        </button>
        <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold shadow-sm" onclick="bulkApprove('tolak')">
            <i class="fas fa-times-circle me-1"></i> Tolak Terpilih
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3" style="border-radius: 15px;">
    <div class="card-body py-3">
        <div class="row g-2">
            <?php if (in_array(session()->get('level'), ['Superadmin', 'kabupaten'])): ?>
            <div class="col-md-5">
                <select id="filter_opd" class="form-select form-select-sm border-light bg-light">
                    <option value="">-- Semua OPD --</option>
                    <?php foreach($list_opd as $o): ?>
                        <option value="<?= $o['id_opd'] ?>"><?= $o['nama_opd'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive p-3">
            <table id="tabelPending" class="table table-hover align-middle mb-0 text-center w-100">
                <thead class="bg-light">
                    <tr class="text-muted small text-uppercase">
                        <th class="text-center" width="5%"><input type="checkbox" class="form-check-input shadow-none" id="checkAll"></th>
                        <th class="text-start" width="20%">Tanggal Pengajuan</th>
                        <th class="text-start" width="25%">Pegawai</th>
                        <th width="15%">Kategori</th>
                        <th width="10%">Bukti</th>
                        <th width="10%">Status</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody class="small text-dark"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalApproval" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4 pb-2">
                <h6 class="fw-bold mb-0" id="modalTitle">Proses Pengajuan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('approval/proses') ?>" method="POST">
                <div class="modal-body p-4 pt-2">
                    <input type="hidden" name="id_pengajuan" id="m_id_pengajuan">
                    <input type="hidden" name="aksi" id="m_aksi">
                    <p class="small text-muted mb-3">Tindakan untuk <strong id="m_nama_pegawai" class="text-dark"></strong></p>
                    <div class="mb-4">
                        <label class="small fw-bold text-dark mb-2">Catatan Admin (Opsional)</label>
                        <textarea name="catatan" id="m_catatan" class="form-control bg-light border-0 rounded-3" rows="3"></textarea>
                    </div>
                    <button type="submit" id="btnSubmitModal" class="btn w-100 rounded-pill fw-bold shadow-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        var table = $('#tabelPending').DataTable({
            "processing": true,
            "serverSide": true,
            "responsive": true,
            "pageLength": 25,
            "ajax": {
                "url": "<?= base_url('approval/ajax_pending') ?>",
                "type": "POST",
                "data": function ( d ) {
                    d.id_opd = $('#filter_opd').val(); // Kirim filter OPD
                }
            },
            "columnDefs": [
                { "orderable": false, "targets": [0, 4, 5, 6] } // Matikan sorting di kolom checkbox/aksi
            ],
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Cari Nama / NIP...",
                "lengthMenu": "Tampil _MENU_ data",
                "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ pengajuan",
                "emptyTable": "Tidak ada antrean pengajuan baru."
            },
            // Reset Checkbox saat tabel berganti halaman
            "drawCallback": function() {
                $('#checkAll').prop('checked', false);
                updateBulkArea();
            }
        });

        // Trigger reload tabel saat filter OPD diubah
        $('#filter_opd').change(function(){
            table.ajax.reload();
        });

        // Logika Checkbox Massal (Dinamic Binding karena Server-Side)
        $('#checkAll').on('change', function() {
            $('.rowCheck').prop('checked', this.checked);
            updateBulkArea();
        });

        $('#tabelPending tbody').on('change', '.rowCheck', function() {
            if (!this.checked) $('#checkAll').prop('checked', false);
            updateBulkArea();
        });
    });

    function updateBulkArea() {
        const checkedCount = $('.rowCheck:checked').length;
        if(checkedCount > 0) {
            $('#bulkActionArea').fadeIn();
            $('#countSelected').text(checkedCount);
        } else {
            $('#bulkActionArea').fadeOut();
        }
    }

    // Modal Tunggal
    function modalApproval(id, nama, kategori, aksi) {
        $('#m_id_pengajuan').val(id);
        $('#m_aksi').val(aksi);
        $('#m_nama_pegawai').text(nama + " (" + kategori + ")");
        
        if(aksi === 'setuju') {
            $('#modalTitle').text("Setujui Pengajuan");
            $('#btnSubmitModal').attr('class', 'btn btn-success w-100 rounded-pill fw-bold shadow-sm').text("Setujui & Masukkan ke Presensi");
            $('#m_catatan').prop('required', false);
        } else {
            $('#modalTitle').text("Tolak Pengajuan");
            $('#btnSubmitModal').attr('class', 'btn btn-danger w-100 rounded-pill fw-bold shadow-sm').text("Tolak Pengajuan");
            $('#m_catatan').prop('required', true);
        }
        new bootstrap.Modal(document.getElementById('modalApproval')).show();
    }

    // Proses Massal
    function bulkApprove(aksi) {
        let selectedIds = [];
        $('.rowCheck:checked').each(function() { selectedIds.push($(this).val()); });
        
        Swal.fire({
            title: `Konfirmasi ${aksi == 'setuju' ? 'Persetujuan' : 'Penolakan'} Massal`,
            text: `Memproses ${selectedIds.length} pengajuan sekaligus. Lanjutkan?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Proses Semua',
            confirmButtonColor: aksi == 'setuju' ? '#10b981' : '#ef4444'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = "<?= base_url('approval/proses_massal') ?>"; 
                
                const field = document.createElement('input');
                field.type = 'hidden'; field.name = 'ids'; field.value = JSON.stringify(selectedIds);
                form.appendChild(field);

                const act = document.createElement('input');
                act.type = 'hidden'; act.name = 'aksi'; act.value = aksi;
                form.appendChild(act);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
<?= $this->endSection() ?>
<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link text-muted rounded-pill small fw-bold" href="<?= base_url('approval') ?>">Antrean Pengajuan</a></li>
    <li class="nav-item"><a class="nav-link active rounded-pill small fw-bold" href="<?= base_url('approval/riwayat') ?>">Riwayat Selesai</a></li>
</ul>

<div class="mb-4">
    <h5 class="fw-bold text-dark">Riwayat Approval</h5>
    <p class="text-muted small mb-0">Daftar pengajuan yang sudah disetujui atau ditolak.</p>
</div>

<div class="card border-0 shadow-sm mb-3" style="border-radius: 15px;">
    <div class="card-body py-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="date" id="filter_tanggal" class="form-control form-control-sm border-light bg-light" value="<?= date('Y-m-d') ?>">
            </div>

            <?php if (in_array(session()->get('level'), ['Superadmin', 'kabupaten'])): ?>
                <div class="col-md-5">
                    <select id="filter_opd" class="form-select form-select-sm border-light bg-light">
                        <option value="">-- Semua OPD --</option>
                        <?php foreach ($list_opd as $o): ?>
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
            <table id="tabelRiwayat" class="table table-hover align-middle mb-0 text-center w-100">
                <thead class="bg-light">
                    <tr class="text-muted small text-uppercase">
                        <th class="text-start" width="20%">Tanggal Pengajuan</th>
                        <th class="text-start" width="25%">Pegawai</th>
                        <th width="15%">Kategori</th>
                        <th width="10%">Bukti</th>
                        <th width="15%">Status Akhir</th>
                        <th width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody class="small text-dark"></tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        var table = $('#tabelRiwayat').DataTable({
            "processing": true,
            "serverSide": true,
            "responsive": true,
            "pageLength": 25,
            "ajax": {
                "url": "<?= base_url('approval/ajax_riwayat') ?>",
                "type": "POST",
                "data": function(d) {
                    d.id_opd = $('#filter_opd').val();

                    // --- [TAMBAHAN]: Kirim Data Tanggal ke Controller ---
                    d.tanggal = $('#filter_tanggal').val();
                }
            },
            "columnDefs": [{
                "orderable": false,
                "targets": [3, 5]
            }],
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Cari Nama / NIP...",
                "lengthMenu": "Tampil _MENU_ data",
                "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ riwayat",
                "emptyTable": "Belum ada riwayat approval pada tanggal ini."
            }
        });

        // --- [TAMBAHAN]: Reload Tabel saat Tanggal atau OPD diubah ---
        $('#filter_opd, #filter_tanggal').change(function() {
            table.ajax.reload();
        });
    });

    // FUNGSI HAPUS RIWAYAT
    function hapusRiwayat(id) {
        Swal.fire({
            title: 'Hapus Riwayat?',
            text: "Data pengajuan ini akan dihapus permanen (Data presensi tidak akan terhapus).",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= base_url('approval/hapus/') ?>" + id,
                    type: "POST",
                    dataType: "JSON",
                    success: function(data) {
                        if (data.status == 'success') {
                            Swal.fire('Terhapus!', data.message, 'success');
                            $('#tabelRiwayat').DataTable().ajax.reload();
                        }
                    }
                });
            }
        });
    }

    // FUNGSI BATALKAN & KEMBALIKAN KE ANTREAN
    function batalkanRiwayat(id) {
        Swal.fire({
            title: 'Batalkan Keputusan?',
            text: "Pengajuan ini akan dikembalikan ke Antrean (Pending) dan data absensinya akan ditarik kembali.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Batalkan!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?= base_url('approval/batal/') ?>" + id,
                    type: "POST",
                    dataType: "JSON",
                    success: function(data) {
                        if (data.status == 'success') {
                            Swal.fire('Dibatalkan!', data.message, 'success');
                            $('#tabelRiwayat').DataTable().ajax.reload();
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Terjadi kesalahan pada server.', 'error');
                    }
                });
            }
        });
    }
</script>
<?= $this->endSection() ?>
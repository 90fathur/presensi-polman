<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<style>
    /* Styling khusus untuk merapikan DataTables Search & Length */
    div.dataTables_wrapper div.dataTables_filter input {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        padding: 0.4rem 0.8rem;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.075);
    }

    div.dataTables_wrapper div.dataTables_filter input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.25rem rgba(5, 150, 105, 0.25);
        outline: none;
    }

    div.dataTables_wrapper div.dataTables_length select {
        border-radius: 8px;
        padding: 0.4rem 2rem 0.4rem 0.8rem;
    }

    .dataTables_wrapper .row {
        margin-bottom: 1rem;
        align-items: center;
    }
</style>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Laporan Rekap Tukin</h4>
            <p class="text-muted small mb-0">Rekapitulasi Kehadiran Bulanan Pegawai</p>
        </div>
        <a href="<?= base_url('laporan_tukin/exportExcel') ?>?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&id_opd=<?= $id_opd_filter ?>" class="btn btn-success shadow-sm rounded-pill px-4 fw-bold">
            <i class="fas fa-file-excel me-2"></i> Export Excel
        </a>
    </div>

    <!-- Card Filter -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="<?= base_url('laporan_tukin') ?>" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-bold">Bulan</label>
                    <select name="bulan" class="form-select border-0 bg-light rounded-3">
                        <?php
                        $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                        foreach ($months as $k => $v): ?>
                            <option value="<?= str_pad($k, 2, '0', STR_PAD_LEFT) ?>" <?= $bulan == $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted small fw-bold">Tahun</label>
                    <select name="tahun" class="form-select border-0 bg-light rounded-3">
                        <?php for ($i = date('Y') - 2; $i <= date('Y') + 1; $i++): ?>
                            <option value="<?= $i ?>" <?= $tahun == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <?php if (session()->get('level') == 'kabupaten' || session()->get('level') == 'Superadmin'): ?>
                    <div class="col-md-5">
                        <label class="form-label text-muted small fw-bold">Filter OPD</label>
                        <select name="id_opd" class="form-select border-0 bg-light rounded-3 select2">
                            <option value="">-- Semua OPD --</option>
                            <?php foreach ($opd_list as $o): ?>
                                <option value="<?= $o['id_opd'] ?>" <?= $id_opd_filter == $o['id_opd'] ? 'selected' : '' ?>><?= $o['nama_opd'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold shadow-sm"><i class="fas fa-search me-2"></i> Cari Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Pratinjau -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="tableRekap" class="table table-hover table-striped align-middle mb-0 w-100">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center py-3">No</th>
                            <th>NIP / Nama Pegawai</th>
                            <th>OPD</th>
                            <th class="text-center">Hadir</th>
                            <th class="text-center text-danger">Telat</th>
                            <th class="text-center text-warning">Cepat</th>
                            <th class="text-center text-danger">Sakit</th>
                            <th class="text-center text-info">Izin</th>
                            <th class="text-center text-primary">Tugas Luar</th>
                            <th class="text-center text-secondary">Cuti</th>
                            <th class="text-center text-dark">Alpha</th>
                            <th class="text-center text-dark">Hari Kerja</th>
                            <th class="text-center text-muted">Libur/Lepas Jaga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Diisi oleh DataTables AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#tableRekap').DataTable({
            "processing": true,
            "serverSide": true,
            "ordering": false,
            "ajax": {
                "url": "<?= base_url('laporan_tukin/ajax_list') ?>",
                "type": "POST",
                "data": function(d) {
                    d.bulan = $('select[name="bulan"]').val();
                    d.tahun = $('select[name="tahun"]').val();
                    d.id_opd = $('select[name="id_opd"]').val();
                }
            },
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
            }
        });

        // Refresh datatables on form change
        $('select[name="bulan"], select[name="tahun"], select[name="id_opd"]').change(function() {
            $('#tableRekap').DataTable().ajax.reload();
        });
    });
</script>

<?= $this->endSection() ?>
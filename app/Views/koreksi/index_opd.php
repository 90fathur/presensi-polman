<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<style>
    /* Styling khusus untuk tabel matriks agar checkbox di tengah dan rapi */
    .table-matrix th,
    .table-matrix td {
        text-align: center;
        vertical-align: middle;
        padding: 5px;
        min-width: 40px;
    }

    .table-matrix td.is-holiday {
        background-color: #ffcccc !important;
        /* Merah muda untuk hari libur */
    }

    .table-matrix td.has-presensi {
        background-color: #e6f7ff !important;
        /* Biru muda untuk sudah absen */
    }

    .table-matrix td.is-pending {
        background-color: #fff3cd !important;
        /* Kuning untuk pending */
    }

    .table-matrix td.is-approved {
        background-color: #d4edda !important;
        /* Hijau untuk approved */
    }

    .table-matrix .pegawai-name {
        text-align: left;
        min-width: 250px;
        position: sticky;
        left: 0;
        background-color: #f8f9fa;
        z-index: 1;
        border-right: 2px solid #dee2e6;
    }

    .table-matrix thead th:first-child {
        position: sticky;
        left: 0;
        background-color: #e9ecef;
        z-index: 2;
        border-right: 2px solid #dee2e6;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Pengajuan Koreksi Kehadiran (Matriks)</h5>
        <small class="text-muted">Pilih satu/banyak pegawai dan bulan untuk mengajukan koreksi secara massal.</small>
    </div>
</div>

<?php if (session()->getFlashdata('success')) : ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')) : ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i> <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="small fw-bold text-muted mb-1">Pilih Pegawai (Bisa Multiple)</label>
                <div class="input-group">
                    <select id="id_pegawai" class="form-select select2" multiple="multiple" data-placeholder="Pilih Pegawai...">
                        <?php foreach ($pegawai as $p): ?>
                            <option value="<?= $p['id_pegawai'] ?>"><?= $p['nama_lengkap'] ?> (<?= $p['nip'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-secondary" type="button" id="btnPilihSemua" title="Pilih Semua Pegawai">
                        <i class="fas fa-check-double"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-3">
                <label class="small fw-bold text-muted mb-1">Bulan</label>
                <select id="bulan" class="form-select">
                    <?php
                    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    foreach ($months as $k => $v):
                    ?>
                        <option value="<?= $k + 1 ?>" <?= (date('n') == ($k + 1)) ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small fw-bold text-muted mb-1">Tahun</label>
                <select id="tahun" class="form-select">
                    <?php for ($y = date('Y') - 1; $y <= date('Y'); $y++): ?>
                        <option value="<?= $y ?>" <?= (date('Y') == $y) ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button id="btnTampilkan" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Tampilkan
                </button>
            </div>
        </div>
        <div class="mt-2 small">
            <span class="badge" style="background-color: #ffcccc; color: #000;">Hari Libur</span>
            <span class="badge" style="background-color: #e6f7ff; color: #000;">Sudah Absen</span>
            <span class="badge" style="background-color: #fff3cd; color: #000;">Pending/Diajukan</span>
            <span class="badge" style="background-color: #d4edda; color: #000;">Approved</span>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" id="hasilCard" style="display: none;">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0 text-primary">Matriks Pengajuan Koreksi</h6>
    </div>
    <form action="<?= base_url('koreksikehadiran/submit_koreksi') ?>" method="post" id="formKoreksi">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-bordered table-hover mb-0 table-matrix">
                    <thead class="bg-light sticky-top">
                        <tr id="theadDates">
                            <!-- Diisi via AJAX -->
                        </tr>
                    </thead>
                    <tbody id="tbodyPegawai">
                        <!-- Diisi via AJAX -->
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-top" id="divSubmit" style="display: none; background: #fafafa;">
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted small">Keterangan Koreksi Massal (Opsional)</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Tuliskan keterangan mengapa diajukan koreksi (berlaku untuk semua yang dicentang)..."></textarea>
                </div>
                <button type="submit" class="btn btn-success px-4" onclick="return confirm('Apakah Anda yakin mengajukan koreksi untuk data-data yang dicentang?')">
                    <i class="fas fa-paper-plane me-1"></i> Ajukan Koreksi Terpilih
                </button>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Tambahkan Select2 (Asumsi sudah ada di layout/main, tapi jika belum bisa di-load dari CDN) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%',
            allowClear: true
        });

        $('#btnPilihSemua').click(function() {
            var allOptions = [];
            $('#id_pegawai option').each(function() {
                allOptions.push($(this).val());
            });
            $('#id_pegawai').val(allOptions).trigger('change');
        });

        $('#btnTampilkan').click(function() {
            var id_pegawai = $('#id_pegawai').val();
            var bulan = $('#bulan').val();
            var tahun = $('#tahun').val();

            if (!id_pegawai || id_pegawai.length === 0) {
                alert("Silakan pilih minimal 1 pegawai terlebih dahulu.");
                return;
            }

            $('#btnTampilkan').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading');

            $.ajax({
                url: "<?= base_url('koreksikehadiran/get_dates') ?>",
                type: "POST",
                data: {
                    id_pegawai: id_pegawai,
                    bulan: bulan,
                    tahun: tahun
                },
                dataType: "JSON",
                success: function(response) {
                    $('#btnTampilkan').prop('disabled', false).html('<i class="fas fa-search me-1"></i> Tampilkan');

                    if (response.status == 'success') {
                        $('#hasilCard').show();

                        // Render Thead
                        var jml_hari = response.jml_hari;
                        var theadHtml = '<th>NAMA PEGAWAI</th>';
                        for (var d = 1; d <= jml_hari; d++) {
                            theadHtml += '<th>' + d + '</th>';
                        }
                        $('#theadDates').html(theadHtml);

                        // Render Tbody
                        var tbodyHtml = '';
                        var canSubmit = false;

                        $.each(response.data, function(i, pegawai) {
                            tbodyHtml += '<tr>';
                            tbodyHtml += '<td class="pegawai-name fw-bold small text-dark">' + pegawai.nama_lengkap + '<br><span class="text-muted" style="font-weight:normal; font-size:10px;">NIP: ' + pegawai.nip + '</span></td>';

                            for (var d = 1; d <= jml_hari; d++) {
                                var dt = pegawai.dates[d];
                                var cellClass = '';
                                var cellContent = '';

                                if (!dt) {
                                    tbodyHtml += '<td></td>';
                                    continue;
                                }

                                if (dt.is_future) {
                                    cellContent = '<span class="text-muted small">-</span>';
                                    cellClass = 'bg-light';
                                } else if (dt.koreksi_status == 'pending') {
                                    cellClass = 'is-pending';
                                    cellContent = '<input type="checkbox" checked disabled title="Pending: ' + dt.koreksi_ket + '">';
                                } else if (dt.koreksi_status == 'approved') {
                                    cellClass = 'is-approved';
                                    cellContent = '<i class="fas fa-check text-success" title="Approved"></i>';
                                } else if (dt.has_presensi) {
                                    cellClass = 'has-presensi';
                                    cellContent = '<div class="d-flex justify-content-center align-items-center" style="gap: 4px;">';
                                    cellContent += '<input type="checkbox" name="koreksi[' + pegawai.id_pegawai + '][]" value="' + dt.tanggal + '" title="Timpa Absen (Sudah Absen: ' + dt.presensi_info + ')">';
                                    cellContent += '<i class="fas fa-fingerprint text-primary" title="' + dt.presensi_info + '"></i>';
                                    cellContent += '</div>';
                                    canSubmit = true;
                                } else if (dt.is_holiday) {
                                    cellClass = 'is-holiday';
                                    // Holiday tapi kita buka opsi paksa jika dibutuhkan (koreksi hari libur)
                                    // Sesuai requirement, "muncul kalender menandakan hari libur dll"
                                    var titleLibur = dt.holiday_name ? dt.holiday_name : 'Hari Libur';
                                    cellContent = '<input type="checkbox" name="koreksi[' + pegawai.id_pegawai + '][]" value="' + dt.tanggal + '" title="' + titleLibur + ' (Centang untuk paksa koreksi)">';
                                    canSubmit = true;
                                } else {
                                    // Hari kerja normal, belum absen
                                    cellContent = '<input type="checkbox" name="koreksi[' + pegawai.id_pegawai + '][]" value="' + dt.tanggal + '" title="Ajukan Koreksi">';
                                    canSubmit = true;
                                }

                                tbodyHtml += '<td class="' + cellClass + '">' + cellContent + '</td>';
                            }
                            tbodyHtml += '</tr>';
                        });

                        $('#tbodyPegawai').html(tbodyHtml);

                        if (canSubmit) {
                            $('#divSubmit').show();
                        } else {
                            $('#divSubmit').hide();
                        }
                    } else {
                        alert(response.message);
                    }
                },
                error: function(err) {
                    $('#btnTampilkan').prop('disabled', false).html('<i class="fas fa-search me-1"></i> Tampilkan');
                    alert("Terjadi kesalahan sistem.");
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>
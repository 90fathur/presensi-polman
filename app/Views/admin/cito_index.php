<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h5 class="fw-bold mb-0 text-danger"><i class="fas fa-ambulance me-2"></i>Manajemen Presensi CITO</h5>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white border-bottom pt-3 pb-0">
        <ul class="nav nav-tabs border-0" id="citoTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold text-danger" id="approval-tab" data-bs-toggle="tab" data-bs-target="#approval" type="button" role="tab">
                    <i class="fas fa-check-circle me-1"></i> Approval & Riwayat Cito
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-secondary" id="akses-tab" data-bs-toggle="tab" data-bs-target="#akses" type="button" role="tab">
                    <i class="fas fa-user-md me-1"></i> Manajemen Akses Nakes
                </button>
            </li>
        </ul>
    </div>
    
    <div class="card-body p-4">
        <div class="tab-content" id="citoTabsContent">
            
            <!-- ========================================== -->
            <!-- TAB 1: APPROVAL & RIWAYAT -->
            <!-- ========================================== -->
            <div class="tab-pane fade show active" id="approval" role="tabpanel">
                <form action="<?= base_url('cito') ?>" method="GET" class="mb-4 bg-light p-3 rounded border shadow-sm">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="small fw-bold mb-1">Bulan</label>
                            <select name="bulan" class="form-select border-secondary">
                                <?php 
                                $bulans = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                                foreach($bulans as $b => $nama): ?>
                                    <option value="<?= $b ?>" <?= $bulan == $b ? 'selected' : '' ?>><?= $nama ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="small fw-bold mb-1">Tahun</label>
                            <select name="tahun" class="form-select border-secondary">
                                <option value="<?= date('Y') ?>" <?= $tahun == date('Y') ? 'selected' : '' ?>><?= date('Y') ?></option>
                                <option value="<?= date('Y')-1 ?>" <?= $tahun == date('Y')-1 ? 'selected' : '' ?>><?= date('Y')-1 ?></option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-danger w-100 fw-bold shadow-sm">
                                <i class="fas fa-search me-1"></i> Filter Data
                            </button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle tableCito w-100" style="font-size: 0.85rem;">
                        <thead class="bg-danger text-white">
                            <tr>
                                <th>TANGGAL</th>
                                <th>NAKES / UNIT</th>
                                <th>WAKTU TINDAKAN</th>
                                <th>KETERANGAN / TINDAKAN</th>
                                <th>STATUS</th>
                                <th class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data_cito as $c): ?>
                                <?php 
                                    $durasi = '-';
                                    if(!empty($c['jam_selesai']) && $c['jam_selesai'] != '00:00:00'){
                                        $diff = strtotime($c['jam_selesai']) - strtotime($c['jam_mulai']);
                                        $durasi = gmdate("H:i", $diff) . " Jam";
                                    }
                                ?>
                                <tr>
                                    <td class="fw-bold"><?= date('d/m/Y', strtotime($c['tanggal'])) ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= $c['nama_lengkap'] ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><i class="fas fa-hospital-user me-1"></i><?= $c['nama_opd'] ?></div>
                                    </td>
                                    <td>
                                        <div>Mulai: <span class="badge bg-light text-dark border"><?= date('H:i', strtotime($c['jam_mulai'])) ?></span></div>
                                        <div class="mt-1">Akhir: <span class="badge bg-light text-dark border"><?= !empty($c['jam_selesai']) && $c['jam_selesai']!='00:00:00' ? date('H:i', strtotime($c['jam_selesai'])) : '<i class="fas fa-spinner fa-spin text-warning"></i> Berjalan' ?></span></div>
                                        <div class="mt-1 text-danger fw-bold" style="font-size:0.7rem;">Durasi: <?= $durasi ?></div>
                                    </td>
                                    <td><?= $c['keterangan'] ?></td>
                                    <td>
                                        <?php if($c['status_approval'] == 'Pending'): ?>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Menunggu</span>
                                        <?php elseif($c['status_approval'] == 'Disetujui'): ?>
                                            <span class="badge bg-success"><i class="fas fa-check"></i> Disetujui</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="fas fa-times"></i> Ditolak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if($c['status_approval'] == 'Pending'): ?>
                                            <button class="btn btn-sm btn-success shadow-sm mb-1" onclick="updateStatusCito(<?= $c['id_darurat'] ?>, 'Disetujui')" title="Setujui"><i class="fas fa-check"></i></button>
                                            <button class="btn btn-sm btn-danger shadow-sm mb-1" onclick="updateStatusCito(<?= $c['id_darurat'] ?>, 'Ditolak')" title="Tolak"><i class="fas fa-times"></i></button>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fas fa-lock"></i> Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 2: MANAJEMEN HAK AKSES NAKES -->
            <!-- ========================================== -->
            <div class="tab-pane fade" id="akses" role="tabpanel">
                <div class="alert alert-info border-0 shadow-sm small">
                    <i class="fas fa-info-circle me-1"></i> Aktifkan *switch* di bawah ini untuk memunculkan tombol <b>[Mulai Cito]</b> di aplikasi presensi HP (PWA) milik tenaga kesehatan yang bersangkutan.
                </div>
                
                <div class="table-responsive mt-3">
                    <table class="table table-hover align-middle tableCito w-100" style="font-size: 0.85rem;">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>NIP</th>
                                <th>NAMA PEGAWAI</th>
                                <th>JABATAN</th>
                                <th>UNIT / RUANGAN</th>
                                <th class="text-center">AKSES CITO PWA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pegawai_akses as $p): ?>
                                <tr>
                                    <td class="font-monospace text-muted"><?= $p['nip'] ?></td>
                                    <td class="fw-bold"><?= $p['nama_lengkap'] ?></td>
                                    <td><?= $p['jabatan'] ?: '-' ?></td>
                                    <td><?= $p['nama_opd'] ?></td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input" type="checkbox" role="switch" 
                                                style="width: 40px; height: 20px; cursor: pointer;"
                                                id="switch_<?= $p['id_pegawai'] ?>" 
                                                <?= $p['is_cito'] == 1 ? 'checked' : '' ?>
                                                onchange="updateAksesCito(<?= $p['id_pegawai'] ?>, this.checked)">
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let csrfName = '<?= csrf_token() ?>';
    let csrfHash = '<?= csrf_hash() ?>';

    function updateCsrf(newHash) {
        if (newHash) {
            csrfHash = newHash;
        }
    }

    $(document).ready(function() {
        // Init Datatables
        $('.tableCito').DataTable({
            language: {
                search: "Pencarian Cepat:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                paginate: { first: "Awal", last: "Akhir", next: "Lanjut", previous: "Mundur" }
            }
        });
        
        // Merubah warna tab saat diklik
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            $('button[data-bs-toggle="tab"]').removeClass('text-danger').addClass('text-secondary');
            $(e.target).removeClass('text-secondary').addClass('text-danger');
        });
    });

    // FUNGSI APPROVE/TOLAK CITO
    function updateStatusCito(id, status) {
        let textConfirm = status === 'Disetujui' ? 'menyetujui' : 'menolak';
        let colorConfirm = status === 'Disetujui' ? '#198754' : '#dc3545';

        Swal.fire({
            title: status + '?',
            text: "Anda yakin ingin " + textConfirm + " catatan Cito ini?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: colorConfirm,
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, ' + status
        }).then((result) => {
            if (result.isConfirmed) {
                let payload = { id_darurat: id, status_approval: status };
                payload[csrfName] = csrfHash;

                $.ajax({
                    url: "<?= base_url('cito/update_status') ?>",
                    type: "POST",
                    data: payload,
                    dataType: "json",
                    success: function(response) {
                        updateCsrf(response.csrf_hash);
                        if (response.status === 'success') {
                            Swal.fire('Berhasil!', response.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Gagal!', response.message, 'error');
                        }
                    }
                });
            }
        });
    }

    // FUNGSI TOGGLE AKSES TOMBOL CITO DI HP PEGAWAI
    function updateAksesCito(id_pegawai, is_checked) {
        let is_cito_val = is_checked ? 1 : 0;
        
        let payload = { id_pegawai: id_pegawai, is_cito: is_cito_val };
        payload[csrfName] = csrfHash;

        $.ajax({
            url: "<?= base_url('cito/update_akses') ?>",
            type: "POST",
            data: payload,
            dataType: "json",
            success: function(response) {
                updateCsrf(response.csrf_hash);
                if (response.status === 'success') {
                    // Tampilkan notifikasi kecil di sudut kanan atas agar tidak mengganggu (Toast)
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    Toast.fire({ icon: 'success', title: response.message });
                } else {
                    Swal.fire('Gagal!', response.message, 'error');
                    // Kembalikan posisi switch jika gagal
                    $('#switch_' + id_pegawai).prop('checked', !is_checked);
                }
            },
            error: function(){
                $('#switch_' + id_pegawai).prop('checked', !is_checked);
            }
        });
    }
</script>

<?= $this->endSection() ?>
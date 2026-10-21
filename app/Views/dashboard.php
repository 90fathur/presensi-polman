<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --primary-grad: linear-gradient(135deg, #2563eb, #1e40af);
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: #f0f2f5;
        color: #1a1c22;
    }

    /* Stat Cards Modern dengan Cursor Pointer */
    .stat-card {
        border: none;
        border-radius: 20px;
        transition: all 0.4s ease;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.6);
        cursor: pointer;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 25px rgba(0, 0, 0, 0.15);
    }

    .stat-icon-bg {
        position: absolute;
        right: -10px;
        top: -10px;
        font-size: 4.5rem;
        opacity: 0.12;
        transform: rotate(-15deg);
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 5px;
        color: white;
    }

    .stat-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.85;
        color: white;
    }

    /* WIDGET CARD GRADIENTS */
    .bg-card-istimewa {
        background: linear-gradient(135deg, #155724, #11411b);
    }

    .bg-card-baik {
        background: linear-gradient(135deg, #28a745, #1e7e34);
    }

    .bg-card-cukup {
        background: linear-gradient(135deg, #8ce196, #5cb85c);
    }

    .bg-card-telat {
        background: linear-gradient(135deg, #ffda6a, #eebb22);
    }

    .bg-card-bina {
        background: linear-gradient(135deg, #ffc107, #d39e00);
    }

    .bg-card-teguran {
        background: linear-gradient(135deg, #dc3545, #bd2130);
    }

    .bg-card-izin {
        background: linear-gradient(135deg, #6f42c1, #59359a);
    }

    .bg-card-belum {
        background: linear-gradient(135deg, #6c757d, #495057);
    }

    .bg-card-ditangguhkan {
        background: linear-gradient(135deg, #343a40, #212529);
        color: white;
    }

    /* WIDGET CARD TEKS KONTRAS */
    .bg-card-cukup .stat-value,
    .bg-card-cukup .stat-label,
    .bg-card-telat .stat-value,
    .bg-card-telat .stat-label,
    .bg-card-bina .stat-value,
    .bg-card-bina .stat-label {
        color: #212529;
    }

    .bg-card-cukup .stat-icon-bg,
    .bg-card-telat .stat-icon-bg,
    .bg-card-bina .stat-icon-bg {
        opacity: 0.15;
        color: #000;
    }

    /* WARNA BADGE STATUS DATATABLE */
    .bg-istimewa {
        background: #155724;
        color: white;
    }

    .bg-baik {
        background: #28a745;
        color: white;
    }

    .bg-cukup {
        background: #8ce196;
        color: #000;
    }

    .bg-telat {
        background: #ffda6a;
        color: #000;
    }

    .bg-bina {
        background: #ffc107;
        color: #000;
    }

    .bg-teguran {
        background: #dc3545;
        color: white;
    }

    .bg-izin {
        background: #6f42c1;
        color: white;
    }

    .bg-selesai {
        background: #0d6efd;
        color: white;
    }

    .bg-cepul {
        background: #fd7e14;
        color: white;
    }

    /* Tables & General UI */
    .table-card {
        border-radius: 24px;
        border: none;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.03);
        background: white;
    }

    .avatar-circle {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        object-fit: cover;
    }

    .chart-container-scroll {
        width: 100%;
        overflow-y: auto;
        max-height: 550px;
        padding-right: 5px;
    }

    .chart-container-scroll::-webkit-scrollbar {
        width: 5px;
    }

    .chart-container-scroll::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }
</style>

<div class="row align-items-center mb-4 mt-3">
    <div class="col-md-7 text-center text-md-start">
        <h3 class="fw-extrabold text-dark mb-1" style="font-weight: 800;">Dashboard Analitik</h3>
        <p class="text-muted small mb-0"><i class="fas fa-map-marker-alt text-danger me-1"></i> <?= $nama_kantor ?> &bull; <?= date('d F Y') ?></p>
    </div>
    <div class="col-md-5 d-flex justify-content-center justify-content-md-end gap-3 mt-3 mt-md-0">
        <div class="bg-white px-4 py-2 rounded-4 shadow-sm border d-flex align-items-center">
            <i class="far fa-clock text-primary me-3 fa-lg"></i>
            <div>
                <span id="liveClock" class="fw-bold d-block text-dark font-monospace" style="font-size: 1.2rem; line-height: 1;">00:00:00</span>
                <span class="small text-muted fw-bold" style="font-size: 0.6rem;">WITA ONLINE</span>
            </div>
        </div>
    </div>
</div>

<?php
$persen_hadir = ($total_pegawai > 0) ? round((($total_hadir + $total_izin) / $total_pegawai) * 100, 1) : 0;
$progress_color = 'bg-primary';
$text_color = 'text-primary';

if ($persen_hadir >= 90) {
    $progress_color = 'bg-success';
    $text_color = 'text-success';
} elseif ($persen_hadir < 75) {
    $progress_color = 'bg-danger';
    $text_color = 'text-danger';
} elseif ($persen_hadir < 90) {
    $progress_color = 'bg-warning';
    $text_color = 'text-warning';
}
?>
<div class="row mb-4 no-print">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <div>
                        <h6 class="fw-extrabold text-uppercase mb-1" style="letter-spacing: 1px; color: #1e293b;">
                            <i class="fas fa-chart-line <?= $text_color ?> me-2"></i> Target Kehadiran Global Hari Ini
                        </h6>
                        <span class="text-muted small fw-bold">
                            Tercapai: <span class="text-dark fs-6"><?= number_format($total_hadir + $total_izin) ?></span> dari <?= number_format($total_pegawai) ?> Pegawai (Termasuk Izin/Cuti)
                        </span>
                    </div>
                    <h2 class="fw-extrabold mb-0 <?= $text_color ?>" style="font-size: 2.5rem; text-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <?= $persen_hadir ?>%
                    </h2>
                </div>

                <div class="progress position-relative" style="height: 18px; background-color: #e2e8f0; border-radius: 50px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
                    <div class="progress-bar <?= $progress_color ?> progress-bar-striped progress-bar-animated"
                        role="progressbar"
                        id="globalProgressBar"
                        style="width: 0%; border-radius: 50px; transition: width 2s cubic-bezier(0.4, 0, 0.2, 1);"
                        aria-valuenow="<?= $persen_hadir ?>"
                        aria-valuemin="0"
                        aria-valuemax="100">
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-2 small fw-bold text-muted" style="font-size: 0.65rem;">
                    <span>0%</span>
                    <span>Target Ideal: 100%</span>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mb-4">
    <?php
    $stats = [
        ['Istimewa', $cat_istimewa, 'bg-card-istimewa', 'fa-star'],
        ['Baik', $cat_baik, 'bg-card-baik', 'fa-thumbs-up'],
        ['Cukup Baik', $cat_cukup, 'bg-card-cukup', 'fa-check-circle'],
        ['Terlambat', $cat_telat, 'bg-card-telat', 'fa-clock'],
        ['Pembinaan', $cat_bina, 'bg-card-bina', 'fa-user-shield'],
        ['Teguran', $cat_teguran, 'bg-card-teguran', 'fa-exclamation-triangle'],
        ['Ditangguhkan', $cat_ditangguhkan ?? 0, 'bg-card-ditangguhkan', 'fa-ban'],
        ['Dianulir', $cat_dianulir ?? 0, 'bg-danger text-white', 'fa-times-circle'],
        ['Izin/Sakit/TL', $total_izin, 'bg-card-izin', 'fa-file-medical'],
        ['Belum Absen', $belum_absen, 'bg-card-belum', 'fa-user-clock']
    ];

    foreach ($stats as $s):
    ?>
        <div class="col-md-3 col-6">
            <div class="card stat-card <?= $s[2] ?> shadow-sm h-100" onclick="filterDataKategori('<?= $s[0] ?>')" title="Klik untuk memfilter tabel">
                <div class="card-body p-3 p-md-4">
                    <div class="stat-icon-bg"><i class="fas <?= $s[3] ?>"></i></div>
                    <div class="stat-label"><?= $s[0] ?></div>
                    <div class="stat-value"><?= number_format($s[1]) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-5">
    <div class="col-lg-8">
        <div class="card table-card border-0 h-100">
            <div class="card-header bg-white py-4 px-4 border-0 d-flex flex-wrap justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="fas fa-bolt text-warning me-2"></i>Aktivitas Presensi Pegawai (Realtime)</h6>
                <span id="labelFilterBadge" class="badge bg-primary px-3 py-2 rounded-pill mt-2 mt-md-0 shadow-sm" style="display: none; font-size: 0.75rem;"></span>
            </div>
            <div class="card-body px-4 pb-4 pt-0">
                <div class="table-responsive">
                    <table id="tabelAktivitas" class="table table-hover align-middle mb-0 text-center small" style="width:100%">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-start ps-3">NAMA PEGAWAI</th>
                                <th>MASUK</th>
                                <th>SIANG</th>
                                <th>PULANG</th>
                                <th>STATUS MASUK</th>
                                <th>STATUS PULANG</th>
                            </tr>
                        </thead>
                        <tbody class="small"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card table-card p-4 text-center h-100">
            <h6 class="fw-bold text-dark mb-4 text-uppercase small" style="letter-spacing: 1px;">Kualitas Kedisiplinan</h6>
            <div class="position-relative mb-4" style="height: 240px;">
                <canvas id="absensiChart"></canvas>
                <div class="position-absolute top-50 start-50 translate-middle text-center" style="margin-top: 5px;">
                    <h2 class="fw-bold text-dark mb-0" style="font-size: 2rem;"><?= $total_hadir ?></h2>
                    <p class="text-muted fw-bold mb-0" style="font-size: 0.65rem;">ORANG HADIR</p>
                </div>
            </div>

            <div class="row g-2 mt-2 text-start small" style="font-size: 0.72rem;">
                <div class="col-6"><i class="fas fa-circle me-1" style="color: #155724;"></i> Istimewa</div>
                <div class="col-6"><i class="fas fa-circle me-1" style="color: #28a745;"></i> Baik</div>
                <div class="col-6"><i class="fas fa-circle me-1" style="color: #8ce196;"></i> Cukup Baik</div>
                <div class="col-6"><i class="fas fa-circle me-1" style="color: #ffda6a;"></i> Terlambat</div>
                <div class="col-6"><i class="fas fa-circle me-1" style="color: #ffc107;"></i> Pembinaan</div>
                <div class="col-6"><i class="fas fa-circle me-1" style="color: #dc3545;"></i> Teguran</div>
                <div class="col-6"><i class="fas fa-circle me-1" style="color: #6f42c1;"></i> Izin/Sakit/TL</div>
                <div class="col-6"><i class="fas fa-circle me-1" style="color: #e2e8f0;"></i> Belum Absen</div>
            </div>
        </div>
    </div>
</div>

<?php if (in_array(session()->get('level'), ['kabupaten', 'Superadmin', 'Administrator'])): ?>
    <div class="row">
        <div class="col-12">
            <div class="card table-card p-4 mb-5" id="areaGrafik">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <div>
                        <h6 class="fw-extrabold text-dark mb-0 text-uppercase" style="letter-spacing: 1px;">
                            🏆 Peringkat Kehadiran OPD
                        </h6>
                        <small class="text-muted">Persentase kehadiran pegawai tiap instansi hari ini</small>
                    </div>
                    <button type="button" onclick="downloadChartImage()" class="btn btn-primary rounded-pill px-4 shadow-sm btn-sm fw-bold">
                        <i class="fas fa-camera me-1"></i> Simpan Gambar
                    </button>
                </div>

                <div class="chart-container-scroll">
                    <div id="chartWrapper" style="width: 100%;">
                        <canvas id="opdChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    // Variabel Global untuk menampung status kategori mana yang sedang diklik
    let activeCategoryFilter = '';
    let tableAktivitas;

    $(document).ready(function() {
        // --- 1. DATA TABLES SERVERSIDE DENGAN FILTER INJEKSI ---
        tableAktivitas = $('#tabelAktivitas').DataTable({
            "processing": true,
            "serverSide": true,
            "order": [],
            "ajax": {
                "url": "<?= base_url('home/ajax_aktivitas') ?>",
                "type": "POST",
                "data": function(d) {
                    // Inject variabel ke request PHP
                    d.filter_kategori = activeCategoryFilter;
                }
            },
            "columnDefs": [{
                "targets": [0, 1, 2, 3, 4, 5],
                "orderable": false
            }],
            "language": {
                "search": "Cari Nama/NIP:",
                "info": "Menampilkan _START_ - _END_ dari _TOTAL_ Pegawai",
                "paginate": {
                    "previous": "<",
                    "next": ">"
                }
            }
        });

        // =========================================================================
        // AUTO REFRESH DATATABLE SETIAP 5 MENIT (Mencegah Server OOM / Crash)
        // =========================================================================
        setInterval(function() {
            tableAktivitas.ajax.reload(null, false);

            $('#tabelAktivitas tbody').css('transition', 'opacity 0.3s ease').css('opacity', '0.4');
            setTimeout(function() {
                $('#tabelAktivitas tbody').css('opacity', '1');
            }, 300);
        }, 300000);

        // --- PROGRESS BAR ANIMATION ---
        setTimeout(() => {
            $('#globalProgressBar').css('width', '<?= $persen_hadir ?>%');
        }, 500);

        // --- 2. LIVE CLOCK ---
        setInterval(() => {
            document.getElementById('liveClock').innerText = new Date().toLocaleTimeString('id-ID', {
                hour12: false
            });
        }, 1000);

        // --- 3. CHART DOUGHNUT (KATEGORI DETAIL) ---
        new Chart(document.getElementById('absensiChart'), {
            type: 'doughnut',
            data: {
                labels: ['Istimewa', 'Baik', 'Cukup Baik', 'Terlambat', 'Pembinaan', 'Teguran', 'Izin/Sakit/TL', 'Belum Absen'],
                datasets: [{
                    data: <?= json_encode($chart_data) ?>,
                    backgroundColor: [
                        '#155724', '#28a745', '#8ce196', '#ffda6a', '#ffc107', '#dc3545', '#6f42c1', '#e2e8f0'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    cutout: '75%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) label += ': ';
                                label += context.parsed + ' Pegawai';
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // --- 4. BAR CHART OPD ---
        <?php if (!empty($opd_performance)): ?>
            const opdData = <?= json_encode($opd_performance) ?>;
            const chartWrapper = document.getElementById('chartWrapper');
            chartWrapper.style.height = (opdData.length * 40) + 'px';

            new Chart(document.getElementById('opdChart'), {
                type: 'bar',
                data: {
                    labels: opdData.map(item => item.nama),
                    datasets: [{
                        label: 'Kehadiran (%)',
                        data: opdData.map(item => item.persen),
                        backgroundColor: '#4361ee',
                        borderRadius: 10,
                        barThickness: 18
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            max: 100,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            ticks: {
                                font: {
                                    size: 10,
                                    weight: '700'
                                },
                                color: '#334155'
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        <?php endif; ?>
    });

    // --- FUNGSI KLIK WIDGET CARD ---
    function filterDataKategori(kategori) {
        if (activeCategoryFilter === kategori) {
            activeCategoryFilter = '';
            $('#labelFilterBadge').hide();
        } else {
            activeCategoryFilter = kategori;
            $('#labelFilterBadge').html('<i class="fas fa-filter me-1"></i> Menampilkan: ' + kategori + ' <i class="fas fa-times ms-2" style="cursor:pointer;" onclick="filterDataKategori(\'' + kategori + '\')" title="Hapus Filter"></i>').show();
        }

        tableAktivitas.ajax.reload();

        $('html, body').animate({
            scrollTop: $("#tabelAktivitas").offset().top - 90
        }, 400);
    }

    // --- 5. FUNGSI DOWNLOAD GAMBAR (FULL DATA) ---
    function downloadChartImage() {
        const element = document.getElementById("areaGrafik");
        const btn = event.currentTarget;
        const originalBtnHtml = btn.innerHTML;

        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memotret Seluruh Data...';
        btn.disabled = true;

        const scrollContainer = element.querySelector('.chart-container-scroll');
        const originalScrollTop = scrollContainer.scrollTop;
        scrollContainer.scrollTop = 0;

        const fullHeight = scrollContainer.scrollHeight + 150;

        html2canvas(element, {
            backgroundColor: "#ffffff",
            scale: 2,
            useCORS: true,
            allowTaint: true,
            logging: false,
            height: fullHeight,
            windowHeight: fullHeight,
            onclone: (clonedDoc) => {
                const clonedCard = clonedDoc.getElementById("areaGrafik");
                const clonedScroll = clonedCard.querySelector('.chart-container-scroll');
                const clonedWrapper = clonedDoc.getElementById('chartWrapper');
                const clonedBtn = clonedCard.querySelector('button');

                if (clonedBtn) clonedBtn.style.display = 'none';

                clonedCard.style.height = fullHeight + 'px';
                clonedScroll.style.height = 'auto';
                clonedScroll.style.maxHeight = 'none';
                clonedScroll.style.overflow = 'visible';
                clonedWrapper.style.height = scrollContainer.scrollHeight + 'px';
            }
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'Peringkat-Kehadiran-OPD-<?= date("d-m-Y") ?>.png';
            link.href = canvas.toDataURL("image/png", 1.0);
            link.click();

            btn.innerHTML = originalBtnHtml;
            btn.disabled = false;
            scrollContainer.scrollTop = originalScrollTop;

            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Seluruh data OPD berhasil disimpan.',
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(err => {
            console.error(err);
            btn.innerHTML = originalBtnHtml;
            btn.disabled = false;
            scrollContainer.scrollTop = originalScrollTop;
        });
    }
</script>

<?= $this->endSection() ?>
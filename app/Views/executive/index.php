<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .gradient-card {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        overflow: hidden;
    }

    .gradient-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.1), transparent);
        pointer-events: none;
    }

    .metric-value {
        font-size: 2.5rem;
        font-weight: 900;
        line-height: 1.2;
        background: linear-gradient(to right, #60a5fa, #a78bfa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .rank-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        border: none;
    }

    .rank-item {
        transition: transform 0.2s;
        border-radius: 12px;
    }

    .rank-item:hover {
        transform: translateX(5px);
        background: #f8fafc;
    }

    .medal-1 {
        color: #fbbf24;
        font-size: 1.5rem;
    }

    .medal-2 {
        color: #94a3b8;
        font-size: 1.5rem;
    }

    .medal-3 {
        color: #b45309;
        font-size: 1.5rem;
    }
</style>

<div class="container-fluid px-4 py-4">
    <div class="row mb-4 align-items-center g-3">
        <div class="col-12 col-xl-5">
            <h3 class="fw-bolder text-dark mb-1"><i class="fas fa-crown text-warning me-2"></i>Executive Dashboard</h3>
            <p class="text-muted small mb-0">Pusat Analitik Kedisiplinan Pegawai se-Kabupaten Polewali Mandar</p>
        </div>
        <div class="col-12 col-xl-7 text-xl-end">
            <form method="GET" action="<?= base_url('executive') ?>" class="d-inline-flex flex-column flex-md-row gap-2 bg-white p-2 rounded-4 shadow-sm border align-items-center justify-content-end">
                <?php if (in_array($level, ['kabupaten', 'Superadmin'])): ?>
                    <select name="id_opd" class="form-select border-0 bg-transparent fw-bold" style="min-width: 150px; max-width: 250px; box-shadow: none;">
                        <option value="all" <?= $id_opd_filter == 'all' ? 'selected' : '' ?>>Semua OPD</option>
                        <?php foreach ($list_opd as $opd): ?>
                            <option value="<?= $opd->id_opd ?>" <?= $id_opd_filter == $opd->id_opd ? 'selected' : '' ?>><?= $opd->nama_opd ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="vr my-2 d-none d-md-block"></div>
                <?php endif; ?>
                <select name="bulan" class="form-select border-0 bg-transparent fw-bold" style="min-width: 130px; box-shadow: none;">
                    <option value="all" <?= $bulan == 'all' ? 'selected' : '' ?>>Semua Waktu</option>
                    <?php
                    $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                    foreach ($months as $k => $v): ?>
                        <option value="<?= str_pad($k, 2, '0', STR_PAD_LEFT) ?>" <?= $bulan == $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="vr my-2 d-none d-md-block"></div>
                <select name="tahun" class="form-select border-0 bg-transparent fw-bold" style="min-width: 100px; box-shadow: none;">
                    <option value="all" <?= $tahun == 'all' ? 'selected' : '' ?>>Semua</option>
                    <?php for ($i = date('Y') - 2; $i <= date('Y') + 1; $i++): ?>
                        <option value="<?= $i ?>" <?= $tahun == $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm mt-2 mt-md-0">Filter</button>
            </form>
        </div>
    </div>

    <div class="alert alert-info border-info-subtle text-info-emphasis d-flex align-items-center mb-4 shadow-sm rounded-4" style="background-color: #e0f2fe;" role="alert">
        <i class="fas fa-info-circle fs-4 me-3 text-info"></i>
        <div>
            <strong>Catatan Penting:</strong> Data yang ditampilkan pada dashboard ini murni merupakan <strong>rekam kedisiplinan kehadiran berdasarkan catatan aplikasi (waktu absen)</strong>, dan <strong>bukan</strong> merupakan indikator penilaian capaian kinerja atau produktivitas kerja pegawai secara keseluruhan.
        </div>
    </div>

    <!-- GLOBAL STATS -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="gradient-card p-4 h-100 shadow-lg">
                <div class="text-white-50 fw-bold small text-uppercase mb-2">Total Rekaman Absen</div>
                <div class="metric-value" style="background: linear-gradient(to right, #e2e8f0, #94a3b8); -webkit-background-clip: text;">
                    <?= number_format($global_stats->total_absen ?? 0) ?>
                </div>
                <div class="mt-2 small text-white-50"><i class="fas fa-chart-line me-1"></i> Data valid</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="gradient-card p-4 h-100 shadow-lg">
                <div class="text-white-50 fw-bold small text-uppercase mb-2">Total Hadir / Tepat Waktu</div>
                <div class="metric-value" style="background: linear-gradient(to right, #4ade80, #3b82f6); -webkit-background-clip: text;">
                    <?= number_format($global_stats->total_hadir ?? 0) ?>
                </div>
                <?php
                $persen = ($global_stats->total_absen ?? 0) > 0 ? round((($global_stats->total_hadir ?? 0) / $global_stats->total_absen) * 100, 1) : 0;
                ?>
                <div class="mt-2 small text-success fw-bold"><i class="fas fa-arrow-up me-1"></i> <?= $persen ?>% dari total absen</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="gradient-card p-4 h-100 shadow-lg">
                <div class="text-white-50 fw-bold small text-uppercase mb-2">Total Terlambat</div>
                <div class="metric-value" style="background: linear-gradient(to right, #fbbf24, #f59e0b); -webkit-background-clip: text;">
                    <?= number_format($global_stats->total_telat ?? 0) ?>
                </div>
                <div class="mt-2 small text-warning"><i class="fas fa-exclamation-circle me-1"></i> Keterlambatan tercatat</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="gradient-card p-4 h-100 shadow-lg">
                <div class="text-white-50 fw-bold small text-uppercase mb-2">Izin / Sakit / Cuti</div>
                <div class="metric-value" style="background: linear-gradient(to right, #c084fc, #9333ea); -webkit-background-clip: text;">
                    <?= number_format($global_stats->total_izin ?? 0) ?>
                </div>
                <div class="mt-2 small text-white-50"><i class="fas fa-file-medical me-1"></i> Absen sah</div>
            </div>
        </div>
    </div>

    <!-- RANKING & CHARTS -->
    <div class="row g-4 mb-5">
        <div class="col-lg-4">
            <div class="card rank-card h-100 p-4">
                <h5 class="fw-bolder mb-4 text-dark"><i class="fas fa-trophy text-warning me-2"></i>Top 5 OPD Terdisiplin</h5>
                <?php if (empty($top_disiplin)): ?>
                    <p class="text-muted small">Data tidak cukup untuk diranking.</p>
                <?php else: ?>
                    <?php $no = 1;
                    foreach ($top_disiplin as $opd):
                        $medal = '';
                        if ($no == 1) $medal = '<i class="fas fa-medal medal-1"></i>';
                        elseif ($no == 2) $medal = '<i class="fas fa-medal medal-2"></i>';
                        elseif ($no == 3) $medal = '<i class="fas fa-medal medal-3"></i>';
                        else $medal = '<span class="fw-bold text-muted px-2">' . $no . '</span>';
                    ?>
                        <div class="rank-item d-flex align-items-center mb-3 p-2 border border-light">
                            <div class="me-3 text-center" style="width: 30px;"><?= $medal ?></div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark mb-0" style="font-size: 0.9rem; line-height: 1.2;"><?= $opd['nama_opd'] ?></div>
                                <div class="small text-muted" style="font-size: 0.75rem;"><?= number_format($opd['total_absen']) ?> Absen Tercatat</div>
                            </div>
                            <div class="ms-3 text-end">
                                <div class="badge bg-success-subtle text-success fs-6 rounded-pill px-3"><?= $opd['persen_hadir'] ?>%</div>
                            </div>
                        </div>
                    <?php $no++;
                    endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card rank-card h-100 p-4">
                <h5 class="fw-bolder mb-4 text-dark"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Top 5 Sering Telat</h5>
                <?php if (empty($top_telat)): ?>
                    <p class="text-muted small">Data tidak cukup untuk diranking.</p>
                <?php else: ?>
                    <?php $no = 1;
                    foreach ($top_telat as $opd): ?>
                        <div class="rank-item d-flex align-items-center mb-3 p-2 border border-light">
                            <div class="me-3 text-center"><span class="badge bg-danger rounded-circle p-2"><?= $no ?></span></div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark mb-0" style="font-size: 0.9rem; line-height: 1.2;"><?= $opd['nama_opd'] ?></div>
                                <div class="small text-muted" style="font-size: 0.75rem;"><?= number_format($opd['total_absen']) ?> Absen Tercatat</div>
                            </div>
                            <div class="ms-3 text-end">
                                <div class="badge bg-danger-subtle text-danger fs-6 rounded-pill px-3"><?= $opd['persen_telat'] ?>%</div>
                            </div>
                        </div>
                    <?php $no++;
                    endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card rank-card h-100 p-4">
                <h5 class="fw-bolder mb-4 text-dark"><i class="fas fa-chart-pie text-primary me-2"></i>Komposisi Absensi</h5>
                <div class="position-relative w-100" style="height: 280px;">
                    <canvas id="komposisiChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- PERINGKAT OPD HARI INI -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card rank-card p-4">
                <h5 class="fw-bolder mb-4 text-dark"><i class="fas fa-sun text-warning me-2"></i>Peringkat Kehadiran OPD (Hari Ini)</h5>
                <?php if (empty($top_disiplin_hari_ini)): ?>
                    <p class="text-muted small">Data hari ini belum tersedia.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php $no = 1;
                        foreach ($top_disiplin_hari_ini as $opd):
                            $medal = '';
                            if ($no == 1) $medal = '<i class="fas fa-medal medal-1"></i>';
                            elseif ($no == 2) $medal = '<i class="fas fa-medal medal-2"></i>';
                            elseif ($no == 3) $medal = '<i class="fas fa-medal medal-3"></i>';
                            else $medal = '<span class="fw-bold text-muted px-2">' . $no . '</span>';
                        ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="rank-item d-flex align-items-center mb-3 p-3 border border-light bg-light rounded-4 h-100">
                                    <div class="me-3 text-center" style="width: 30px;"><?= $medal ?></div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark mb-0" style="font-size: 0.9rem; line-height: 1.2;"><?= $opd['nama_opd'] ?></div>
                                        <div class="small text-muted" style="font-size: 0.75rem;"><?= number_format($opd['total_absen']) ?> Absen Tercatat Hari Ini</div>
                                    </div>
                                    <div class="ms-3 text-end">
                                        <div class="badge bg-success-subtle text-success fs-6 rounded-pill px-3"><?= $opd['persen_hadir'] ?>%</div>
                                    </div>
                                </div>
                            </div>
                        <?php $no++;
                        endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TOP 10 PEGAWAI DENGAN POIN KEHADIRAN TERTINGGI -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card rank-card p-4">
                <h5 class="fw-bolder mb-4 text-dark"><i class="fas fa-star text-warning me-2"></i>Top 10 Pegawai dengan Poin Kehadiran Tertinggi</h5>

                <?php if (empty($top_pegawai)): ?>
                    <p class="text-muted small">Data tidak cukup untuk diranking.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php $no = 1;
                        foreach ($top_pegawai as $peg):
                            $avatar = "https://ui-avatars.com/api/?name=" . urlencode($peg->nama_lengkap) . "&background=0D8ABC&color=fff&bold=true";
                        ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="rank-item d-flex align-items-center p-3 border border-light bg-light rounded-4 h-100">
                                    <div class="position-relative me-3">
                                        <img src="<?= $avatar ?>" class="rounded-circle shadow-sm" width="45" height="45">
                                        <?php if (isset($peg->rank) && $peg->rank <= 3): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark border border-white border-2">#<?= $peg->rank ?></span>
                                        <?php else: ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary text-white border border-white border-2">#<?= $peg->rank ?? $no ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark mb-1" style="font-size: 0.9rem; line-height: 1.2;"><?= $peg->nama_lengkap ?></div>
                                        <div class="small text-primary fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-building me-1"></i><?= $peg->nama_opd ?></div>
                                    </div>
                                    <div class="ms-2 text-end">
                                        <div class="badge bg-success-subtle text-success rounded-pill px-2 py-1 shadow-sm mb-1" style="font-size: 0.65rem;" title="Skor Disiplin">Skor: <?= $peg->skor_disiplin ?> Pts</div>
                                        <div class="small text-muted mt-1" style="font-size: 0.65rem;">
                                            <span class="text-success fw-bold"><?= $peg->hadir_tepat_waktu ?> Tepat</span> |
                                            <span class="text-warning fw-bold"><?= $peg->total_telat ?> Telat</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php $no++;
                        endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- TREN KEHADIRAN (Hanya Tampil Jika Bulan Spesifik Dipilih) -->
    <?php if ($bulan != 'all' && $tahun != 'all'): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card rank-card p-4">
                    <h5 class="fw-bolder mb-4 text-dark"><i class="fas fa-chart-area text-info me-2"></i>Tren Harian (Bulan Ini)</h5>
                    <div class="position-relative w-100" style="height: 350px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- TOP 10 PEGAWAI PALING RAJIN HARI INI -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card rank-card p-4 border-info shadow-sm">
                <h5 class="fw-bolder mb-4 text-dark">
                    <i class="fas fa-bolt text-info me-2"></i>Top 10 Kehadiran Tercepat Harian
                    <span class="text-muted fs-6 fw-normal">(Update: <?= isset($harian_date) ? date('d M Y', strtotime($harian_date)) : date('d M Y') ?>)</span>
                </h5>

                <?php if (empty($top_pegawai_hari_ini)): ?>
                    <p class="text-muted small">Belum ada data kehadiran untuk hari ini.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($top_pegawai_hari_ini as $peg):
                            $avatar = "https://ui-avatars.com/api/?name=" . urlencode($peg->nama_lengkap) . "&background=17a2b8&color=fff&bold=true";
                        ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="rank-item d-flex align-items-center p-3 border border-info-subtle bg-light rounded-4 h-100">
                                    <div class="position-relative me-3">
                                        <img src="<?= $avatar ?>" class="rounded-circle shadow-sm" width="45" height="45">
                                        <?php if ($peg->rank <= 3): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark border border-white border-2">#<?= $peg->rank ?></span>
                                        <?php else: ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info text-white border border-white border-2">#<?= $peg->rank ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark mb-1" style="font-size: 0.9rem; line-height: 1.2;"><?= $peg->nama_lengkap ?></div>
                                        <div class="small text-info fw-semibold" style="font-size: 0.75rem;"><i class="fas fa-building me-1"></i><?= $peg->nama_opd ?></div>
                                    </div>
                                    <div class="ms-2 text-end">
                                        <div class="badge bg-info-subtle text-info rounded-pill px-2 py-1 shadow-sm mb-1" style="font-size: 0.65rem;" title="Jam Kedatangan"><i class="fas fa-clock me-1"></i><?= date('H:i:s', strtotime($peg->jam_tercepat)) ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Komposisi Chart
        const ctxKomposisi = document.getElementById('komposisiChart').getContext('2d');
        new Chart(ctxKomposisi, {
            type: 'doughnut',
            data: {
                labels: ['Hadir Tepat Waktu', 'Terlambat', 'Izin/Sakit/Cuti'],
                datasets: [{
                    data: [
                        <?= $global_stats->total_hadir ?? 0 ?>,
                        <?= $global_stats->total_telat ?? 0 ?>,
                        <?= $global_stats->total_izin ?? 0 ?>
                    ],
                    backgroundColor: ['#4ade80', '#fbbf24', '#c084fc'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                }
            }
        });

        // Trend Chart
        <?php if ($bulan != 'all' && $tahun != 'all'): ?>
            const ctxTrend = document.getElementById('trendChart').getContext('2d');

            // Create gradient
            let gradientHadir = ctxTrend.createLinearGradient(0, 0, 0, 400);
            gradientHadir.addColorStop(0, 'rgba(74, 222, 128, 0.5)');
            gradientHadir.addColorStop(1, 'rgba(74, 222, 128, 0.0)');

            new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: <?= json_encode($trend_labels) ?>,
                    datasets: [{
                            label: 'Total Hadir',
                            data: <?= json_encode($trend_hadir) ?>,
                            borderColor: '#4ade80',
                            backgroundColor: gradientHadir,
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#4ade80',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Total Telat',
                            data: <?= json_encode($trend_telat) ?>,
                            borderColor: '#fbbf24',
                            backgroundColor: 'transparent',
                            borderWidth: 3,
                            tension: 0.4,
                            borderDash: [5, 5],
                            pointBackgroundColor: '#fbbf24',
                            pointRadius: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            },
                            beginAtZero: true
                        }
                    }
                }
            });
        <?php endif; ?>
    });
</script>

<?= $this->endSection() ?>
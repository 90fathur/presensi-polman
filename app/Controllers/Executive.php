<?php

namespace App\Controllers;

class Executive extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $level = session()->get('level');
        $id_opd_admin = session()->get('id_opd');
        if (!in_array($level, ['kabupaten', 'Superadmin', 'opd'])) {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak. Dashboard ini khusus Pimpinan/Admin.');
        }

        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        $id_opd_filter = 'all';
        if (in_array($level, ['kabupaten', 'Superadmin'])) {
            $id_opd_filter = $this->request->getGet('id_opd') ?? 'all';
            $data['list_opd'] = $this->db->table('opd')->orderBy('nama_opd', 'ASC')->get()->getResult();
        } else {
            $id_opd_filter = $id_opd_admin;
        }

        $data['bulan'] = $bulan;
        $data['tahun'] = $tahun;
        $data['id_opd_filter'] = $id_opd_filter;
        $data['level'] = $level;

        // --- 1. GLOBAL STATS (Hadir, Telat, Izin/Sakit) ---
        $builder = $this->db->table('presensi pr');
        if ($id_opd_filter != 'all') {
            $builder->join('pegawai p', 'pr.nip = p.nip');
            $builder->where('p.id_opd', $id_opd_filter);
        }
        if ($bulan != 'all') {
            $builder->where('MONTH(pr.tanggal)', $bulan);
        }
        if ($tahun != 'all') {
            $builder->where('YEAR(pr.tanggal)', $tahun);
        }

        $global_stats = $builder->select("
                COUNT(pr.id_presensi) as total_absen,
                SUM(CASE WHEN pr.status_masuk = 'Terlambat' THEN 1 ELSE 0 END) as total_telat,
                SUM(CASE WHEN pr.jam_masuk_aktual IS NOT NULL AND pr.jam_masuk_aktual != '00:00:00' THEN 1 ELSE 0 END) as total_hadir,
                SUM(CASE WHEN pr.kategori LIKE '%Izin%' OR pr.kategori LIKE '%Sakit%' OR pr.kategori LIKE '%Cuti%' OR pr.kategori LIKE '%Luar%' THEN 1 ELSE 0 END) as total_izin
            ", false)->get()->getRow();

        $data['global_stats'] = $global_stats;

        // --- 2. PERINGKAT OPD (Top 5 Disiplin & Top 5 Sering Telat) ---
        $builderOpd = $this->db->table('presensi pr')
            ->select("
                o.nama_opd,
                COUNT(pr.id_presensi) as total_absen,
                SUM(CASE WHEN pr.status_masuk = 'Terlambat' THEN 1 ELSE 0 END) as total_telat,
                SUM(CASE WHEN pr.jam_masuk_aktual IS NOT NULL AND pr.jam_masuk_aktual != '00:00:00' THEN 1 ELSE 0 END) as total_hadir
            ", false)
            ->join('pegawai p', 'pr.nip = p.nip')
            ->join('opd o', 'p.id_opd = o.id_opd')
            ->groupBy('o.id_opd');

        if ($bulan != 'all') {
            $builderOpd->where('MONTH(pr.tanggal)', $bulan);
        }
        if ($tahun != 'all') {
            $builderOpd->where('YEAR(pr.tanggal)', $tahun);
        }
        if ($id_opd_filter != 'all') {
            $builderOpd->where('p.id_opd', $id_opd_filter);
        }

        $opd_ranking = $builderOpd->get()->getResult();

        // Calculate Percentages
        $ranked_data = [];
        foreach ($opd_ranking as $opd) {
            $total = $opd->total_absen > 0 ? $opd->total_absen : 1;
            $persen_hadir = ($opd->total_hadir / $total) * 100;
            $persen_telat = ($opd->total_telat / $total) * 100;

            // Minimal 50 absen agar statistik rasional (tidak 100% karena cuma 1 absen)
            if ($opd->total_absen > 20 || $bulan == 'all') {
                $ranked_data[] = [
                    'nama_opd' => $opd->nama_opd,
                    'persen_hadir' => round($persen_hadir, 1),
                    'persen_telat' => round($persen_telat, 1),
                    'total_absen' => $opd->total_absen
                ];
            }
        }

        // Sort for Top 5 Tepat Waktu (Disiplin)
        usort($ranked_data, function ($a, $b) {
            return $b['persen_hadir'] <=> $a['persen_hadir'];
        });
        $data['top_disiplin'] = array_slice($ranked_data, 0, 5);

        // Sort for Top 5 Sering Telat
        usort($ranked_data, function ($a, $b) {
            return $b['persen_telat'] <=> $a['persen_telat'];
        });
        $data['top_telat'] = array_slice($ranked_data, 0, 5);

        // --- 2.5 PERINGKAT OPD HARI INI ---
        $targetDate = date('Y-m-d');
        $builderOpdHariIni = $this->db->table('presensi pr')
            ->select("
                o.nama_opd,
                COUNT(pr.id_presensi) as total_absen,
                SUM(CASE WHEN pr.jam_masuk_aktual IS NOT NULL AND pr.jam_masuk_aktual != '00:00:00' THEN 1 ELSE 0 END) as total_hadir
            ", false)
            ->join('pegawai p', 'pr.nip = p.nip')
            ->join('opd o', 'p.id_opd = o.id_opd')
            ->where('pr.tanggal', $targetDate)
            ->groupBy('o.id_opd');

        if ($id_opd_filter != 'all') {
            $builderOpdHariIni->where('p.id_opd', $id_opd_filter);
        }

        $opd_ranking_hari_ini = $builderOpdHariIni->get()->getResult();
        $ranked_data_hari_ini = [];
        foreach ($opd_ranking_hari_ini as $opd) {
            $total = $opd->total_absen > 0 ? $opd->total_absen : 1;
            $persen_hadir = ($opd->total_hadir / $total) * 100;
            if ($opd->total_absen > 0) {
                $ranked_data_hari_ini[] = [
                    'nama_opd' => $opd->nama_opd,
                    'persen_hadir' => round($persen_hadir, 1),
                    'total_absen' => $opd->total_absen
                ];
            }
        }

        usort($ranked_data_hari_ini, function ($a, $b) {
            return $b['persen_hadir'] <=> $a['persen_hadir'];
        });
        $data['top_disiplin_hari_ini'] = array_slice($ranked_data_hari_ini, 0, 5);

        // --- 3. TREND GRAFIK (Harian dalam Bulan Ini) ---
        $data['trend_labels'] = [];
        $data['trend_hadir'] = [];
        $data['trend_telat'] = [];

        if ($bulan != 'all' && $tahun != 'all') {
            $days_in_month = date('t', strtotime($tahun . '-' . $bulan . '-01'));
            for ($i = 1; $i <= $days_in_month; $i++) {
                $data['trend_labels'][] = $i;
                $data['trend_hadir'][$i] = 0;
                $data['trend_telat'][$i] = 0;
            }

            $builderTrend = $this->db->table('presensi')
                ->select("
                    DAY(tanggal) as hari,
                    SUM(CASE WHEN jam_masuk_aktual IS NOT NULL AND jam_masuk_aktual != '00:00:00' THEN 1 ELSE 0 END) as total_hadir,
                    SUM(CASE WHEN status_masuk = 'Terlambat' THEN 1 ELSE 0 END) as total_telat
                ", false)
                ->where('MONTH(tanggal)', $bulan)
                ->where('YEAR(tanggal)', $tahun)
                ->groupStart()->where('presensi.status_verifikasi', 'Valid')->orWhere('presensi.status_verifikasi IS NULL')->groupEnd()
                ->groupBy('DAY(tanggal)');

            if ($id_opd_filter != 'all') {
                $builderTrend->join('pegawai p', 'presensi.nip = p.nip');
                $builderTrend->where('p.id_opd', $id_opd_filter);
            }

            $trend_db = $builderTrend->get()->getResult();
            foreach ($trend_db as $t) {
                $data['trend_hadir'][$t->hari] = $t->total_hadir;
                $data['trend_telat'][$t->hari] = $t->total_telat;
            }

            // Reindex untuk JS
            $data['trend_hadir'] = array_values($data['trend_hadir']);
            $data['trend_telat'] = array_values($data['trend_telat']);
        }

        // --- 4. TOP 10 PEGAWAI PALING RAJIN (Skor Disiplin Tertinggi) ---
        $builderPegawai = $this->db->table('presensi pr')
            ->select("
                p.nama_lengkap, o.nama_opd,
                SUM(CASE WHEN pr.status_masuk LIKE '%Hadir%' OR pr.status_masuk LIKE '%Istimewa%' OR pr.status_masuk LIKE '%Baik%' OR pr.status_masuk LIKE '%Tepat Waktu%' THEN 1 ELSE 0 END) as hadir_tepat_waktu,
                SUM(CASE WHEN pr.status_masuk = 'Terlambat' THEN 1 ELSE 0 END) as total_telat,
                (
                    (SUM(CASE WHEN pr.status_masuk LIKE '%Hadir%' OR pr.status_masuk LIKE '%Istimewa%' OR pr.status_masuk LIKE '%Baik%' OR pr.status_masuk LIKE '%Tepat Waktu%' THEN 1 ELSE 0 END) * 3) + 
                    (SUM(CASE WHEN pr.status_masuk = 'Terlambat' THEN 1 ELSE 0 END) * 1)
                ) as skor_disiplin,
                AVG(CASE WHEN (pr.status_masuk LIKE '%Hadir%' OR pr.status_masuk LIKE '%Istimewa%' OR pr.status_masuk LIKE '%Baik%' OR pr.status_masuk LIKE '%Tepat Waktu%') AND pr.jam_masuk_aktual IS NOT NULL THEN TIME_TO_SEC(pr.jam_masuk_aktual) ELSE NULL END) as avg_jam_masuk
            ", false)
            ->join('pegawai p', 'pr.nip = p.nip')
            ->join('opd o', 'p.id_opd = o.id_opd')
            ->notLike('o.nama_opd', 'RSUD') // Pengecualian RSUD
            ->notLike('o.nama_opd', 'Puskesmas') // Pengecualian Puskesmas
            ->groupStart()->where('pr.status_verifikasi', 'Valid')->orWhere('pr.status_verifikasi IS NULL')->groupEnd()
            ->groupBy('p.nip')
            ->having('skor_disiplin >', 0)
            ->orderBy('skor_disiplin', 'DESC') // URUTKAN BERDASARKAN SKOR GABUNGAN
            ->orderBy('hadir_tepat_waktu', 'DESC') // Tie-breaker 1
            ->orderBy('avg_jam_masuk', 'ASC') // Tie-breaker 2
            ->orderBy('p.nama_lengkap', 'ASC') // MENCEGAH NAMA BERUBAH ACAK SAAT REFRESH JIKA SKOR SERI
            ->limit(500);

        if ($bulan != 'all') {
            $builderPegawai->where('MONTH(pr.tanggal)', $bulan);
        }
        if ($tahun != 'all') {
            $builderPegawai->where('YEAR(pr.tanggal)', $tahun);
        }
        if ($id_opd_filter != 'all') {
            $builderPegawai->where('p.id_opd', $id_opd_filter);
        }

        $raw_pegawai = $builderPegawai->get()->getResult();

        $filtered_pegawai = [];
        $seen_opd = [];
        foreach ($raw_pegawai as $peg) {
            if ($id_opd_filter == 'all') { // Jika filter all, maka 1 OPD hanya boleh 1 orang
                if (!isset($seen_opd[$peg->nama_opd])) {
                    $seen_opd[$peg->nama_opd] = true;
                    $filtered_pegawai[] = $peg;
                }
            } else { // Jika sudah di filter per OPD, tampilkan saja 10 terbaik di OPD tersebut
                $filtered_pegawai[] = $peg;
            }
            if (count($filtered_pegawai) >= 10) break;
        }

        foreach ($filtered_pegawai as $idx => $peg) {
            $peg->rank = $idx + 1;
        }

        $data['top_pegawai'] = $filtered_pegawai;

        // --- 5. TOP 10 PEGAWAI PALING RAJIN HARIAN (JAM TERCEPAT HARI INI) ---
        // Mutlak gunakan tanggal server hari ini (real-time)
        $targetDate = date('Y-m-d');
        $data['harian_date'] = $targetDate;

        $builderHariIni = $this->db->table('presensi pr')
            ->select('p.nama_lengkap, o.nama_opd, pr.jam_masuk_aktual as jam_tercepat, p.nip, pr.kategori, pr.status_masuk')
            ->join('pegawai p', 'pr.nip = p.nip')
            ->join('opd o', 'p.id_opd = o.id_opd')
            ->where('pr.tanggal', $targetDate)
            ->where('pr.jam_masuk_aktual IS NOT NULL')
            ->where('pr.jam_masuk_aktual !=', '00:00:00')
            ->notLike('o.nama_opd', 'RSUD')
            ->notLike('o.nama_opd', 'Puskesmas')
            ->groupStart()->where('pr.status_verifikasi', 'Valid')->orWhere('pr.status_verifikasi IS NULL')->groupEnd()
            ->orderBy('pr.jam_masuk_aktual', 'ASC')
            ->limit(500);

        if ($id_opd_filter != 'all') {
            $builderHariIni->where('p.id_opd', $id_opd_filter);
        }

        $builderHariIni->orderBy('pr.jam_masuk_aktual', 'ASC')
            ->orderBy('p.nama_lengkap', 'ASC')
            ->limit(500);

        $raw_hari_ini = $builderHariIni->get()->getResult();

        $filtered_hari_ini = [];
        $seen_opd_hari = [];
        foreach ($raw_hari_ini as $peg) {
            if ($id_opd_filter == 'all') { // Sama seperti atas, 1 OPD = 1 orang jika view all
                if (!isset($seen_opd_hari[$peg->nama_opd])) {
                    $seen_opd_hari[$peg->nama_opd] = true;
                    $filtered_hari_ini[] = $peg;
                }
            } else {
                $filtered_hari_ini[] = $peg;
            }
            if (count($filtered_hari_ini) >= 10) break;
        }

        foreach ($filtered_hari_ini as $idx => $peg) {
            $peg->rank = $idx + 1;
        }
        $data['top_pegawai_hari_ini'] = $filtered_hari_ini;

        return view('executive/index', $data);
    }
}

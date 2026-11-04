<?php

namespace App\Helpers;

class AsnHelper
{
    /**
     * Daftar Baku Kepangkatan ASN (PNS & PPPK) di Indonesia
     */
    public static function get_daftar_pangkat(): array
    {
        return [
            'Golongan IV (Pembina)' => [
                'Pembina Utama|IV/e'          => 'Pembina Utama (IV/e)',
                'Pembina Utama Madya|IV/d'     => 'Pembina Utama Madya (IV/d)',
                'Pembina Utama Muda|IV/c'      => 'Pembina Utama Muda (IV/c)',
                'Pembina Tingkat I|IV/b'       => 'Pembina Tingkat I (IV/b)',
                'Pembina|IV/a'                 => 'Pembina (IV/a)',
            ],
            'Golongan III (Penata)' => [
                'Penata Tingkat I|III/d'       => 'Penata Tingkat I (III/d)',
                'Penata|III/c'                 => 'Penata (III/c)',
                'Penata Muda Tingkat I|III/b'  => 'Penata Muda Tingkat I (III/b)',
                'Penata Muda|III/a'            => 'Penata Muda (III/a)',
            ],
            'Golongan II (Pengatur)' => [
                'Pengatur Tingkat I|II/d'      => 'Pengatur Tingkat I (II/d)',
                'Pengatur|II/c'                => 'Pengatur (II/c)',
                'Pengatur Muda Tingkat I|II/b' => 'Pengatur Muda Tingkat I (II/b)',
                'Pengatur Muda|II/a'           => 'Pengatur Muda (II/a)',
            ],
            'Golongan I (Juru)' => [
                'Juru Tingkat I|I/d'           => 'Juru Tingkat I (I/d)',
                'Juru|I/c'                     => 'Juru (I/c)',
                'Juru Muda Tingkat I|I/b'      => 'Juru Muda Tingkat I (I/b)',
                'Juru Muda|I/a'                => 'Juru Muda (I/a)',
            ],
            'PPPK (Pegawai Pemerintah dengan Perjanjian Kerja)' => [
                'PPPK Golongan I|I'            => 'PPPK - Golongan I',
                'PPPK Golongan II|II'          => 'PPPK - Golongan II',
                'PPPK Golongan III|III'        => 'PPPK - Golongan III',
                'PPPK Golongan IV|IV'          => 'PPPK - Golongan IV',
                'PPPK Golongan V|V'            => 'PPPK - Golongan V',
                'PPPK Golongan VI|VI'          => 'PPPK - Golongan VI',
                'PPPK Golongan VII|VII'        => 'PPPK - Golongan VII',
                'PPPK Golongan VIII|VIII'      => 'PPPK - Golongan VIII',
                'PPPK Golongan IX|IX'          => 'PPPK - Golongan IX',
                'PPPK Golongan X|X'            => 'PPPK - Golongan X',
                'PPPK Golongan XI|XI'          => 'PPPK - Golongan XI',
                'PPPK Golongan XII|XII'        => 'PPPK - Golongan XII',
                'PPPK Golongan XIII|XIII'      => 'PPPK - Golongan XIII',
                'PPPK Golongan XIV|XIV'        => 'PPPK - Golongan XIV',
                'PPPK Golongan XV|XV'          => 'PPPK - Golongan XV',
                'PPPK Golongan XVI|XVI'        => 'PPPK - Golongan XVI',
                'PPPK Golongan XVII|XVII'      => 'PPPK - Golongan XVII',
            ],
            'Non-ASN' => [
                'Non-ASN|-'                    => 'Non-ASN / Tenaga Kontrak / Honor',
            ]
        ];
    }

    /**
     * Mapping nama pangkat standar PNS ke golongan
     */
    public static function map_pangkat_to_golongan($pangkat): string
    {
        $clean = preg_replace('/\s+/', ' ', trim((string)$pangkat));
        $clean = preg_replace('/TK\.?\s*1/i', 'Tingkat I', $clean);

        $map = [
            'Pembina Utama'          => 'IV/e',
            'Pembina Utama Madya'    => 'IV/d',
            'Pembina Utama Muda'     => 'IV/c',
            'Pembina Tingkat I'      => 'IV/b',
            'Pembina'                => 'IV/a',
            'Penata Tingkat I'       => 'III/d',
            'Penata'                 => 'III/c',
            'Penata Muda Tingkat I'  => 'III/b',
            'Penata Muda'            => 'III/a',
            'Pengatur Tingkat I'     => 'II/d',
            'Pengatur'               => 'II/c',
            'Pengatur Muda Tingkat I'=> 'II/b',
            'Pengatur Muda'          => 'II/a',
            'Juru Tingkat I'         => 'I/d',
            'Juru'                   => 'I/c',
            'Juru Muda Tingkat I'    => 'I/b',
            'Juru Muda'              => 'I/a',
        ];

        foreach ($map as $name => $gol) {
            if (strcasecmp($clean, $name) === 0) {
                return $gol;
            }
        }

        return '';
    }

    /**
     * Mapping golongan ke nama pangkat standar PNS
     */
    public static function map_golongan_to_pangkat($golongan): string
    {
        $clean = strtoupper(trim(str_replace([' ', '.'], '', (string)$golongan)));
        $clean = str_replace(['/A', '/B', '/C', '/D', '/E'], ['/a', '/b', '/c', '/d', '/e'], $clean);

        $map = [
            'IV/e' => 'Pembina Utama',
            'IV/d' => 'Pembina Utama Madya',
            'IV/c' => 'Pembina Utama Muda',
            'IV/b' => 'Pembina Tingkat I',
            'IV/a' => 'Pembina',
            'III/d' => 'Penata Tingkat I',
            'III/c' => 'Penata',
            'III/b' => 'Penata Muda Tingkat I',
            'III/a' => 'Penata Muda',
            'II/d' => 'Pengatur Tingkat I',
            'II/c' => 'Pengatur',
            'II/b' => 'Pengatur Muda Tingkat I',
            'II/a' => 'Pengatur Muda',
            'I/d' => 'Juru Tingkat I',
            'I/c' => 'Juru',
            'I/b' => 'Juru Muda Tingkat I',
            'I/a' => 'Juru Muda',
        ];

        return $map[$clean] ?? '';
    }

    /**
     * Membersihkan dan memisahkan pangkat dan golongan secara cerdas
     */
    public static function parse_pangkat_golongan($pangkat_raw, $golongan_raw): array
    {
        $pangkat = trim((string)$pangkat_raw);
        $golongan = trim((string)$golongan_raw);

        // Jika pangkat memuat pemisah pipe | (format dari dropdown: "Pembina Tingkat I|IV/b" atau "PPPK Golongan IX|IX")
        if (strpos($pangkat, '|') !== false) {
            $parts = explode('|', $pangkat, 2);
            $pangkat = trim($parts[0]);
            if ((empty($golongan) || $golongan === '-') && isset($parts[1])) {
                $golongan = trim($parts[1]);
            }
        }

        // Normalisasi format golongan (misal IV/A -> IV/a, IV.a -> IV/a)
        if (!empty($golongan)) {
            $golongan = str_replace('.', '/', $golongan);
            $golongan = str_ireplace(['/A', '/B', '/C', '/D', '/E'], ['/a', '/b', '/c', '/d', '/e'], $golongan);
        }

        // Deteksi PPPK
        if (stripos($pangkat, 'PPPK') !== false || stripos($golongan, 'PPPK') !== false) {
            if (preg_match('/\b(XVII|XVI|XV|XIV|XIII|XII|XI|X|IX|VIII|VII|VI|V|IV|III|II|I)\b/i', $pangkat_raw . ' ' . $golongan_raw, $m_pppk)) {
                $golongan = strtoupper($m_pppk[1]);
            }
            return [
                'pangkat' => 'PPPK',
                'golongan' => !empty($golongan) ? $golongan : '-'
            ];
        }

        // Deteksi Non-ASN
        if (stripos($pangkat, 'Non-ASN') !== false || stripos($pangkat, 'Kontrak') !== false || stripos($pangkat, 'Honor') !== false) {
            return [
                'pangkat' => 'Non-ASN',
                'golongan' => '-'
            ];
        }

        // Ekstrak golongan dari teks pangkat_raw jika ada (contoh: "Pembina TK.1 / IVb", "Pembina, IV/a")
        $golongan_in_pangkat = '';
        if (preg_match('/(IV\/[a-e]|III\/[a-d]|II\/[a-d]|I\/[a-d]|IV[a-e]|III[a-d]|II[a-d]|I[a-d])/i', $pangkat_raw, $m_gol)) {
            $raw_g = strtoupper($m_gol[1]);
            if (strpos($raw_g, '/') === false) {
                $raw_g = preg_replace('/([A-E])$/', '/$1', $raw_g);
            }
            $golongan_in_pangkat = str_ireplace(['/A', '/B', '/C', '/D', '/E'], ['/a', '/b', '/c', '/d', '/e'], $raw_g);
        }

        // Ekstrak nama pangkat baku PNS jika ada dalam teks pangkat
        $extracted_pangkat = '';
        if (preg_match('/(Pembina\s*Utama\s*(?:Madya|Muda)?|Pembina\s*(?:TK\.?\s*1|Tingkat\s*I)?|Penata\s*Muda\s*(?:TK\.?\s*1|Tingkat\s*I)?|Penata\s*(?:TK\.?\s*1|Tingkat\s*I|Muda)?|Pengatur\s*Muda\s*(?:TK\.?\s*1|Tingkat\s*I)?|Pengatur\s*(?:TK\.?\s*1|Tingkat\s*I|Muda)?|Juru\s*Muda\s*(?:TK\.?\s*1|Tingkat\s*I)?|Juru\s*(?:TK\.?\s*1|Tingkat\s*I|Muda)?)/i', $pangkat, $matches)) {
            $nama = trim($matches[1]);
            $nama = preg_replace('/TK\.?\s*1/i', 'Tingkat I', $nama);
            $extracted_pangkat = ucwords(strtolower($nama));
            $extracted_pangkat = preg_replace('/\bTingkat\s+i\b/i', 'Tingkat I', $extracted_pangkat);
        }

        if (!empty($extracted_pangkat)) {
            $pangkat = $extracted_pangkat;
            $std_gol = self::map_pangkat_to_golongan($pangkat);

            if (!empty($golongan_in_pangkat)) {
                $golongan = $golongan_in_pangkat;
            } elseif (empty($golongan) || $golongan === '-' || !empty($std_gol)) {
                $golongan = $std_gol ?: $golongan;
            }
        } else {
            // Jika pangkat teks tidak memuat nama pangkat baku tapi golongan ada
            if (!empty($golongan) && $golongan !== '-') {
                $derived = self::map_golongan_to_pangkat($golongan);
                if ($derived) {
                    $pangkat = $derived;
                }
            }
        }

        return [
            'pangkat'  => !empty($pangkat) ? $pangkat : '-',
            'golongan' => !empty($golongan) ? $golongan : '-'
        ];
    }
}

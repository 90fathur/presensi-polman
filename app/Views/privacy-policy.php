<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kebijakan Privasi - Presensi Polman 2.0</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0284c7;
            --primary-dark: #0369a1;
            --secondary: #0f172a;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text-main: #334155;
            --text-heading: #0f172a;
            --text-muted: #64748b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            line-height: 1.7;
            padding: 40px 20px;
        }
        .container {
            max-width: 860px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.02);
            border: 1px solid var(--border);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 1.85rem;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }
        .header p {
            font-size: 0.95rem;
            opacity: 0.9;
        }
        .meta-badge {
            display: inline-block;
            background: rgba(255,255,255,0.18);
            backdrop-filter: blur(4px);
            padding: 4px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 12px;
        }
        .content {
            padding: 40px 35px;
        }
        h2 {
            font-size: 1.25rem;
            color: var(--text-heading);
            margin: 30px 0 12px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        h2:first-of-type { margin-top: 0; }
        p { margin-bottom: 14px; font-size: 0.95rem; }
        ul { margin: 0 0 18px 22px; font-size: 0.95rem; }
        li { margin-bottom: 8px; }
        .highlight-box {
            background-color: #f0fdf4;
            border-left: 4px solid #16a34a;
            padding: 16px 20px;
            border-radius: 0 12px 12px 0;
            margin: 20px 0;
            color: #166534;
            font-size: 0.93rem;
        }
        .warning-box {
            background-color: #f0f9ff;
            border-left: 4px solid var(--primary);
            padding: 16px 20px;
            border-radius: 0 12px 12px 0;
            margin: 20px 0;
            color: #075985;
            font-size: 0.93rem;
        }
        .contact-card {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-top: 15px;
        }
        .contact-card p { margin-bottom: 6px; }
        a { color: var(--primary); text-decoration: none; font-weight: 600; }
        a:hover { text-decoration: underline; }
        .footer {
            text-align: center;
            padding: 24px 20px;
            border-top: 1px solid var(--border);
            font-size: 0.82rem;
            color: var(--text-muted);
            background: #fafafa;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Kebijakan Privasi (Privacy Policy)</h1>
        <p>Aplikasi Presensi Polman 2.0 - Sistem Presensi ASN Pemerintah Kabupaten Polewali Mandar</p>
        <span class="meta-badge">Terakhir Diperbarui: November 2026</span>
    </div>

    <div class="content">
        <p>Pemerintah Kabupaten Polewali Mandar ("Kami") melalui Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM) serta Dinas Komunikasi, Informatika, Statistik dan Persandian berkomitmen penuh untuk melindungi privasi, integritas, dan keamanan data pengguna ("Anda" atau "Pegawai").</p>
        <p>Kebijakan Privasi ini menjelaskan bagaimana aplikasi <strong>Presensi Polman 2.0</strong> mengumpulkan, menggunakan, menyimpan, dan melindungi data pribadi Anda saat menggunakan aplikasi mobile ini sesuai dengan kebijakan Google Play Store dan peraturan perundang-undangan Republik Indonesia yang berlaku.</p>

        <h2>1. Informasi yang Kami Kumpulkan</h2>
        <p>Untuk menjalankan fungsi absensi elektronik dan kedisiplinan pegawai, aplikasi mengumpulkan data berikut:</p>
        <ul>
            <li><strong>Data Identitas Pribadi:</strong> Nama Lengkap, Nomor Induk Pegawai (NIP), Jabatan, Tingkat Eselon, Golongan/Pangkat, Unit Kerja (OPD), dan Nomor Telepon/WhatsApp.</li>
            <li><strong>Data Lokasi Presisi (GPS Geofencing):</strong> Aplikasi mengumpulkan koordinat lokasi Anda (Latitude, Longitude, dan Tingkat Akurasi GPS) <strong>hanya saat Anda melakukan proses presensi (Foreground Usage)</strong>. Data ini digunakan untuk memvalidasi apakah Anda berada dalam radius sah kantor/unit kerja yang telah ditentukan. Aplikasi <strong>TIDAK</strong> melacak lokasi Anda secara terus-menerus di latar belakang (*background continuous tracking*).</li>
            <li><strong>Data Kamera & Biometrik Wajah:</strong> Aplikasi membutuhkan izin kamera untuk mengambil foto wajah saat pendaftaran biometrik awal (*Face Enrollment*) dan pencocokan wajah harian (*Liveness Detection / Anti-Spoofing*). Sistem mengekstraksi titik koordinat wajah menjadi representasi kode vektor matematika numerik guna mencegah pemalsuan absensi.</li>
            <li><strong>Informasi Perangkat (Device Binding):</strong> Aplikasi mencatat ID perangkat (*Device Token*) unik untuk memastikan satu akun hanya dapat melakukan presensi melalui perangkat resmi yang telah terdaftar, mencegah joki absensi atau penggunaan emulator.</li>
            <li><strong>Penyimpanan & Dokumen (Storage/Media):</strong> Akses ke berkas/galeri hanya diminta jika Anda mengunggah dokumen bukti surat keterangan sakit, surat permohonan cuti, atau surat tugas luar (Dinas Luar).</li>
        </ul>

        <h2>2. Tujuan Penggunaan Informasi</h2>
        <p>Data yang dikumpulkan digunakan semata-mata untuk keperluan kedinasan tata kelola Aparatur Sipil Negara di lingkungan Pemkab Polewali Mandar, meliputi:</p>
        <ul>
            <li>Verifikasi kehadiran harian pegawai secara akurat (Masuk Kerja, Siang, Pulang, dan Jadwal Shift).</li>
            <li>Penegakan disiplin ASN dan dasar perhitungan Tambahan Penghasilan Pegawai (TPP) sesuai Peraturan Bupati Polewali Mandar.</li>
            <li>Pencegahan manipulasi lokasi (Fake GPS) dan manipulasi foto wajah (Foto Palsu / Joki Presensi).</li>
            <li>Pengiriman notifikasi kedinasan resmi, jadwal apel, dan status persetujuan cuti/izin via Push Notification dan WhatsApp.</li>
        </ul>

        <div class="highlight-box">
            <strong>Komitmen Perlindungan Data:</strong> Kami tidak pernah menjual, menyewakan, membagikan, atau memindahtangankan data pribadi, koordinat lokasi, maupun biometrik wajah Anda kepada pihak ketiga atau jaringan periklanan mana pun.
        </div>

        <h2>3. Keamanan Data</h2>
        <p>Kami menerapkan standar keamanan informasi tingkat tinggi, antara lain:</p>
        <ul>
            <li>Seluruh pertukaran data antara aplikasi mobile dan server diamankan menggunakan protokol enkripsi <strong>HTTPS / TLS 1.3</strong>.</li>
            <li>Kredensial akun (password) disimpan menggunakan algoritma *one-way cryptographic hash* (bcrypt) yang tidak dapat dibaca kembali.</li>
            <li>Data vektor wajah disimpan dalam bentuk data numerik matematis di dalam pusat data (Server) resmi Pemerintah Kabupaten Polewali Mandar yang terproteksi firewall.</li>
        </ul>

        <h2>4. Permintaan Penghapusan Data & Akun (User Data Deletion)</h2>
        <div class="warning-box">
            <strong>Hak Penghapusan Data Pengguna:</strong> Sesuai kebijakan Google Play mengenai <i>User Data Deletion</i>, Anda berhak mengajukan permohonan penghapusan data akun, reset binding perangkat, atau penghapusan data biometrik wajah.
        </div>
        <p>Prosedur pengajuan penghapusan data:</p>
        <ul>
            <li><strong>Reset Kunci HP / Device Binding:</strong> Jika Anda berganti perangkat telepon genggam, Anda dapat melapor ke Administrator OPD Anda atau BKPSDM Kabupaten Polewali Mandar untuk mereset kunci token perangkat secara instan.</li>
            <li><strong>Penghapusan Data Biometrik Wajah:</strong> Pegawai dapat meminta registrasi ulang atau penghapusan vektor wajah melalui Administrator Kabupaten di BKPSDM.</li>
            <li><strong>Penghapusan Akun Lengkap:</strong> Bagi pegawai yang telah pensiun, mutasi keluar daerah, atau diberhentikan, permohonan penonaktifan dan penghapusan data akun dapat diajukan secara tertulis atau melalui email resmi ke <strong>bkpsdm@polmankab.go.id</strong> dengan melampirkan NIP dan Surat Keterangan resmi.</li>
        </ul>

        <h2>5. Batasan Usia & Privasi Anak (Children's Privacy)</h2>
        <p>Aplikasi ini dirancang khusus untuk keperluan ketenagakerjaan dan administrasi Aparatur Sipil Negara serta pegawai dewasa (berusia 18 tahun ke atas). Aplikasi ini tidak ditujukan dan tidak mengumpulkan informasi dari anak-anak di bawah usia 13 tahun.</p>

        <h2>6. Hubungi Kami</h2>
        <p>Jika Anda memiliki pertanyaan, kendala, atau permintaan terkait Kebijakan Privasi ini, silakan hubungi pengelola resmi sistem:</p>
        
        <div class="contact-card">
            <p><strong>Pengembang / Pengelola:</strong></p>
            <p>Badan Kepegawaian dan Pengembangan Sumber Daya Manusia (BKPSDM) bekerjasama dengan Dinas Komunikasi, Informatika, Statistik dan Persandian (Diskominfo SP)</p>
            <p><strong>Pemerintah Kabupaten Polewali Mandar</strong></p>
            <p><strong>Alamat:</strong> Jl. Manuggal No. 11, Kel. Pekkabata, Kec. Polewali, Kabupaten Polewali Mandar, Sulawesi Barat 91311</p>
            <p><strong>Website Resmi:</strong> <a href="https://polmankab.go.id" target="_blank">polmankab.go.id</a> | <a href="https://presensi.polmankab.go.id" target="_blank">presensi.polmankab.go.id</a></p>
            <p><strong>Email Dukungan:</strong> <a href="mailto:bkpsdm@polmankab.go.id">bkpsdm@polmankab.go.id</a> / <a href="mailto:dinaskominfosp@polmankab.go.id">dinaskominfosp@polmankab.go.id</a></p>
        </div>
    </div>

    <div class="footer">
        &copy; 2026 Pemerintah Kabupaten Polewali Mandar. Seluruh hak cipta dilindungi undang-undang.
    </div>
</div>

</body>
</html>

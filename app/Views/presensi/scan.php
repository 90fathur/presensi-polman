<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-md-6 text-center">
        <h5 class="fw-bold mb-3">Presensi Wajah OPD Polman</h5>
        <div class="position-relative shadow-lg rounded-4 overflow-hidden bg-dark" style="height: 400px;">
            <video id="video" width="100%" height="100%" autoplay muted style="object-fit: cover;"></video>
            <canvas id="overlay" class="position-absolute top-0 start-0"></canvas>

            <div id="loadingAI" class="position-absolute top-50 start-50 translate-middle text-white">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="small mt-2">Memuat AI...</p>
            </div>
        </div>

        <div id="statusAbsen" class="mt-4 p-3 rounded-4 bg-light border d-none">
            <h6 id="msgPresensi" class="fw-bold mb-0"></h6>
        </div>
    </div>
</div>

<script defer src="<?= base_url('js/face-api.min.js') ?>"></script>
<script>
    // Di dalam script View Scan
    async function startAttendance() {
        // Ambil semua vektor wajah pegawai dari DB
        const res = await fetch('<?= base_url('pegawai/get_all_vectors') ?>');
        const dbPegawai = await res.json();

        const labeledDescriptors = dbPegawai.map(p => {
            const desc = new Float32Array(Object.values(JSON.parse(p.face_vector)));
            return new faceapi.LabeledFaceDescriptors(p.id_pegawai.toString(), [desc]);
        });

        const faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);

        setInterval(async () => {
            const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks().withFaceDescriptors();

            detections.forEach(det => {
                const match = faceMatcher.findBestMatch(det.descriptor);
                if (match.label !== 'unknown') {
                    doAbsen(match.label); // match.label adalah id_pegawai
                }
            });
        }, 1000);
    }
</script>

<?= $this->endSection() ?>
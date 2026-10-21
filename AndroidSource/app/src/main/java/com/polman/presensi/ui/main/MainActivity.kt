package com.polman.presensi.ui.main

import android.Manifest
import android.content.pm.PackageManager
import android.location.Location
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.view.View
import android.widget.FrameLayout
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.core.app.ActivityCompat
import com.google.android.gms.location.FusedLocationProviderClient
import com.google.android.gms.location.LocationServices
import com.polman.presensi.network.BaseResponse
import com.polman.presensi.network.NetworkClient
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class MainActivity : AppCompatActivity() {

    private lateinit var fusedLocationClient: FusedLocationProviderClient
    private var currentLocation: Location? = null

    private lateinit var tvLocation: TextView
    private lateinit var btnAbsen: FrameLayout
    private lateinit var progressBar: ProgressBar
    private lateinit var tvNamaPegawai: TextView
    private lateinit var tvTanggal: TextView
    private lateinit var tvJamRealtime: TextView

    private val handler = Handler(Looper.getMainLooper())
    private lateinit var clockRunnable: Runnable

    private var profileNip: String = ""
    private var profileNama: String = ""
    private var profileJabatan: String = ""
    private var profileOpd: String = ""
    private var profilePhone: String = ""
    private var profileHasFace: Boolean = false
    private var profileIdPegawai: String = ""
    private var profileIsBercadar: Boolean = false

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        supportActionBar?.hide()
        setContentView(resources.getIdentifier("activity_main", "layout", packageName))

        com.polman.presensi.utils.AutoUpdateChecker.checkUpdate(this)

        if (com.polman.presensi.utils.SecurityHelper.isEmulator()) {
            android.app.AlertDialog.Builder(this)
                .setTitle("Peringatan Keamanan")
                .setMessage("Aplikasi Presensi tidak dapat digunakan di Emulator. Harap gunakan perangkat Smartphone asli.")
                .setPositiveButton("Tutup") { _, _ -> finishAffinity() }
                .setCancelable(false)
                .show()
            return
        }

        if (com.polman.presensi.utils.SecurityHelper.isRooted()) {
            android.app.AlertDialog.Builder(this)
                .setTitle("Peringatan Keamanan")
                .setMessage("Sistem mendeteksi bahwa perangkat Anda telah di-Root. Akses ke Aplikasi Presensi diblokir demi keamanan.")
                .setPositiveButton("Tutup") { _, _ -> finishAffinity() }
                .setCancelable(false)
                .show()
            return
        }

        tvLocation = findViewById(resources.getIdentifier("tvLocation", "id", packageName))
        btnAbsen = findViewById(resources.getIdentifier("btnAbsen", "id", packageName))
        progressBar = findViewById(resources.getIdentifier("progressBar", "id", packageName))
        tvNamaPegawai = findViewById(resources.getIdentifier("tvNamaPegawai", "id", packageName))
        tvTanggal = findViewById(resources.getIdentifier("tvTanggal", "id", packageName))
        tvJamRealtime = findViewById(resources.getIdentifier("tvJamRealtime", "id", packageName))

        fusedLocationClient = LocationServices.getFusedLocationProviderClient(this)

        startRealtimeClock()

        // Request Location & Notification Permission
        val permissionsToRequest = mutableListOf<String>()
        if (ActivityCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            permissionsToRequest.add(Manifest.permission.ACCESS_FINE_LOCATION)
        }
        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.TIRAMISU) {
            if (ActivityCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
                permissionsToRequest.add(Manifest.permission.POST_NOTIFICATIONS)
            }
        }
        if (permissionsToRequest.isNotEmpty()) {
            ActivityCompat.requestPermissions(this, permissionsToRequest.toTypedArray(), 100)
        } else {
            fetchLocation()
        }

        loadDashboardData()

        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.TIRAMISU) {
            if (ActivityCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(this, arrayOf(Manifest.permission.POST_NOTIFICATIONS), 102)
            }
        }

        com.google.firebase.messaging.FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
            if (task.isSuccessful) {
                val token = task.result
                android.util.Log.d("FCM", "Token: $token")
                val prefs = getSharedPreferences("PresensiPolmanApp", android.content.Context.MODE_PRIVATE)
                val nip = prefs.getString("saved_nip", "") ?: ""
                if (nip.isNotEmpty() && token != null) {
                    NetworkClient.apiService.updateFcmToken(nip, token).enqueue(object : Callback<BaseResponse> {
                        override fun onResponse(call: Call<BaseResponse>, response: Response<BaseResponse>) {}
                        override fun onFailure(call: Call<BaseResponse>, t: Throwable) {}
                    })
                }
            }
        }
        btnAbsen.setOnClickListener {
            if (currentLocation == null) {
                Toast.makeText(this, "Tunggu, sedang mengunci titik satelit GPS...", Toast.LENGTH_SHORT).show()
                fetchLocation()
                return@setOnClickListener
            }

            // Anti-Cheat: Emulator Check
            if (com.polman.presensi.utils.SecurityHelper.isEmulator()) {
                android.app.AlertDialog.Builder(this)
                    .setTitle("Peringatan Keamanan")
                    .setMessage("Aplikasi Presensi tidak dapat digunakan di Emulator. Harap gunakan perangkat Smartphone asli untuk melakukan Absen.")
                    .setPositiveButton("Mengerti", null)
                    .show()
                return@setOnClickListener
            }

            // Anti-Cheat: Fake GPS Check
            if (com.polman.presensi.utils.SecurityHelper.isMockLocation(currentLocation)) {
                android.app.AlertDialog.Builder(this)
                    .setTitle("Peringatan Keamanan")
                    .setMessage("Sistem mendeteksi penggunaan Fake GPS (Lokasi Palsu) di perangkat Anda. Harap nonaktifkan aplikasi tersebut untuk melakukan Absen.")
                    .setPositiveButton("Mengerti", null)
                    .show()
                return@setOnClickListener
            }
            if (ActivityCompat.checkSelfPermission(this, Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(this, arrayOf(Manifest.permission.CAMERA), 101)
                return@setOnClickListener
            }
            
            if (profileIsBercadar) {
                showBiometricPrompt()
            } else {
                if (ActivityCompat.checkSelfPermission(this, Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
                    ActivityCompat.requestPermissions(this, arrayOf(Manifest.permission.CAMERA), 101)
                    return@setOnClickListener
                }
                val intent = android.content.Intent(this, com.polman.presensi.ui.absen.CameraLivenessActivity::class.java)
                intent.putExtra("LAT", currentLocation!!.latitude)
                intent.putExtra("LNG", currentLocation!!.longitude)
                val nip = getSharedPreferences("PresensiPolmanApp", MODE_PRIVATE).getString("saved_nip", "") ?: ""
                intent.putExtra("NIP", nip)
                startActivityForResult(intent, 200)
            }
        }

        val btnLogout = findViewById<android.widget.ImageView>(resources.getIdentifier("btnLogout", "id", packageName))
        btnLogout.setOnClickListener {
            val sharedPrefs = getSharedPreferences("PresensiPolmanApp", MODE_PRIVATE)
            sharedPrefs.edit().clear().apply()
            val intent = android.content.Intent(this, com.polman.presensi.ui.auth.LoginActivity::class.java)
            startActivity(intent)
            finish()
        }

        // Navigasi Bawah (Bottom Navigation)
        findViewById<android.widget.ImageView>(resources.getIdentifier("btnNavRiwayat", "id", packageName)).setOnClickListener {
            val scrollView = findViewById<androidx.core.widget.NestedScrollView>(resources.getIdentifier("mainScrollView", "id", packageName))
            val headerRiwayat = findViewById<android.widget.LinearLayout>(resources.getIdentifier("layoutHeaderRiwayat", "id", packageName))
            scrollView.smoothScrollTo(0, headerRiwayat.top)
        }

        findViewById<android.widget.ImageView>(resources.getIdentifier("btnNavIzin", "id", packageName)).setOnClickListener {
            val intent = android.content.Intent(this, com.polman.presensi.ui.izin.IzinActivity::class.java)
            startActivity(intent)
        }

        findViewById<android.widget.ImageView>(resources.getIdentifier("btnNavAbsen", "id", packageName)).setOnClickListener {
            btnAbsen.performClick()
        }

        val btnProfile = findViewById<android.widget.ImageView>(resources.getIdentifier("btnProfile", "id", packageName))
        btnProfile.setOnClickListener {
            val intent = android.content.Intent(this, com.polman.presensi.ui.profile.ProfileActivity::class.java)
            intent.putExtra("ID_PEGAWAI", profileIdPegawai)
            intent.putExtra("NAMA", profileNama)
            intent.putExtra("NIP", profileNip)
            intent.putExtra("JABATAN", profileJabatan)
            intent.putExtra("OPD", profileOpd)
            intent.putExtra("PHONE", profilePhone)
            intent.putExtra("HAS_FACE", profileHasFace)
            startActivity(intent)
        }

        // Ganti Bulan
        findViewById<TextView>(resources.getIdentifier("tvGantiBulan", "id", packageName)).setOnClickListener {
            val bulanList = arrayOf("Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember")
            android.app.AlertDialog.Builder(this)
                .setTitle("Pilih Bulan")
                .setItems(bulanList) { _, which ->
                    val bulanStr = String.format(Locale.getDefault(), "%02d", which + 1)
                    val tahunStr = SimpleDateFormat("yyyy", Locale.getDefault()).format(Date())
                    loadDashboardData(bulanStr, tahunStr)
                }.show()
        }

        // Layanan Presensi Click Listeners
        val intentIzin = android.content.Intent(this, com.polman.presensi.ui.izin.IzinActivity::class.java)
        findViewById<androidx.cardview.widget.CardView>(resources.getIdentifier("cardIzinSakit", "id", packageName)).setOnClickListener {
            intentIzin.putExtra("KATEGORI", "Sakit")
            startActivity(intentIzin)
        }
        findViewById<androidx.cardview.widget.CardView>(resources.getIdentifier("cardIzinAbsen", "id", packageName)).setOnClickListener {
            intentIzin.putExtra("KATEGORI", "Izin Absen")
            startActivity(intentIzin)
        }
        findViewById<androidx.cardview.widget.CardView>(resources.getIdentifier("cardTugasLuar", "id", packageName)).setOnClickListener {
            intentIzin.putExtra("KATEGORI", "Tugas Luar")
            startActivity(intentIzin)
        }

        // Swipe Refresh
        val swipeRefresh = findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(resources.getIdentifier("swipeRefresh", "id", packageName))
        swipeRefresh.setOnRefreshListener {
            loadDashboardData()
        }

        // Perbarui Lokasi
        findViewById<TextView>(resources.getIdentifier("btnRefreshLokasi", "id", packageName)).setOnClickListener {
            tvLocation.text = "Mencari GPS..."
            fetchLocation()
            Toast.makeText(this, "Memperbarui lokasi...", Toast.LENGTH_SHORT).show()
        }
    }

    private fun startRealtimeClock() {
        val dateFormater = SimpleDateFormat("EEEE, dd MMM yyyy", Locale("id", "ID"))
        val timeFormater = SimpleDateFormat("HH:mm:ss", Locale.getDefault())

        clockRunnable = object : Runnable {
            override fun run() {
                val now = Date()
                tvTanggal.text = dateFormater.format(now).uppercase(Locale.getDefault())
                tvJamRealtime.text = "⌚ " + timeFormater.format(now)
                handler.postDelayed(this, 1000)
            }
        }
        handler.post(clockRunnable)
    }

    override fun onDestroy() {
        super.onDestroy()
        handler.removeCallbacks(clockRunnable)
    }

    private fun loadDashboardData(bulan: String? = null, tahun: String? = null) {
        tvNamaPegawai.text = "Mengambil data profil..."
        val swipeRefresh = findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(resources.getIdentifier("swipeRefresh", "id", packageName))
        NetworkClient.apiService.getDashboard(bulan, tahun).enqueue(object : Callback<Any> {
            override fun onResponse(call: Call<Any>, response: Response<Any>) {
                swipeRefresh.isRefreshing = false
                if (response.isSuccessful && response.body() != null) {
                    try {
                        val body = response.body() as Map<String, Any>
                        val pegawai = body["pegawai"] as? Map<String, Any>
                        
                        val nama = pegawai?.get("nama_lengkap")?.toString() ?: "Pegawai Polman"
                        val nip = pegawai?.get("nip")?.toString() ?: "-"
                        val opd = pegawai?.get("nama_opd")?.toString() ?: "KANTOR PEMERINTAH"
                        
                        profileNama = nama
                        profileNip = nip
                        profileOpd = opd
                        profileJabatan = pegawai?.get("jabatan")?.toString() ?: "-"
                        profilePhone = pegawai?.get("nomor_hp")?.toString() ?: ""
                        profileIdPegawai = pegawai?.get("id_pegawai")?.toString() ?: ""
                        
                        val faceVec = pegawai?.get("face_vector")?.toString() ?: ""
                        profileHasFace = faceVec.isNotEmpty() && faceVec != "null"
                        
                        val isBercadar = body["is_bercadar"]?.toString() ?: "0"
                        profileIsBercadar = (isBercadar == "1" || isBercadar == "1.0" || isBercadar == "true")
                        
                        tvNamaPegawai.text = nama
                        
                        val tvInfoPegawai = findViewById<TextView>(resources.getIdentifier("tvInfoPegawai", "id", packageName))
                        tvInfoPegawai.text = "NIP. $nip • $opd"
                        
                        // Parse Jadwal
                        val jadwal = body["jadwal_hari_ini"] as? Map<String, Any>
                        if(jadwal != null) {
                            val jamMasuk = jadwal["jam_masuk"]?.toString()?.take(5) ?: "--:--"
                            val jamPulang = jadwal["jam_pulang"]?.toString()?.take(5) ?: "--:--"
                            
                            findViewById<TextView>(resources.getIdentifier("tvJadwalMasuk", "id", packageName)).text = jamMasuk
                            findViewById<TextView>(resources.getIdentifier("tvJadwalPulang", "id", packageName)).text = jamPulang
                        }

                        // Parse Prototype Estimasi TPP Dinamis (Beban Kerja 60% & Disiplin 40%)
                        val cardTpp = findViewById<androidx.cardview.widget.CardView>(resources.getIdentifier("cardTpp", "id", packageName))
                        val tvTppLabelBulan = findViewById<TextView>(resources.getIdentifier("tvTppLabelBulan", "id", packageName))
                        val tvTppNominal = findViewById<TextView>(resources.getIdentifier("tvTppNominal", "id", packageName))
                        val tvTppSubtext = findViewById<TextView>(resources.getIdentifier("tvTppSubtext", "id", packageName))
                        val btnRincianTpp = findViewById<TextView>(resources.getIdentifier("btnRincianTpp", "id", packageName))

                        val tppInfo = body["tpp_info"] as? Map<String, Any>
                        val isTppAktif = tppInfo?.get("is_aktif") as? Boolean ?: (tppInfo?.get("is_aktif")?.toString() == "true")

                        if (cardTpp != null) {
                            if (isTppAktif && tppInfo != null) {
                                cardTpp.visibility = View.VISIBLE
                                
                                val namaBulan = tppInfo["nama_bulan"]?.toString() ?: "Bulan Ini"
                                val tahunTpp = tppInfo["tahun"]?.toString() ?: ""
                                if (tvTppLabelBulan != null) {
                                    tvTppLabelBulan.text = "💰 Estimasi TPP ($namaBulan $tahunTpp)"
                                }
                                
                                val tppBersihFormat = tppInfo["tpp_bersih_format"]?.toString() ?: "Rp 0"
                                if (tvTppNominal != null) {
                                    tvTppNominal.text = tppBersihFormat
                                }
                                
                                val predikat = tppInfo["predikat_kinerja"]?.toString() ?: "Baik"
                                val potPersen = tppInfo["persen_pot_disiplin"]?.toString() ?: "0"
                                if (tvTppSubtext != null) {
                                    tvTppSubtext.text = "Kin: $predikat • Pot. Disiplin: $potPersen%"
                                }

                                if (btnRincianTpp != null) {
                                    btnRincianTpp.setOnClickListener {
                                        showDialogRincianTpp(tppInfo)
                                    }
                                }
                            } else {
                                cardTpp.visibility = View.GONE
                            }
                        }
                        
                        // Parse Alerts
                        val tvAlertPengajuan = findViewById<TextView>(resources.getIdentifier("tvAlertPengajuan", "id", packageName))
                        val tvAlertCito = findViewById<TextView>(resources.getIdentifier("tvAlertCito", "id", packageName))

                        val pengajuan = body["pengajuan_aktif"] as? Map<String, Any>
                        val terakhirUntukCek = body["terakhir"] as? Map<String, Any>
                        val statusVerifikasi = terakhirUntukCek?.get("status_verifikasi")?.toString() ?: ""
                        
                        if (statusVerifikasi == "Ditangguhkan") {
                            val alasan = terakhirUntukCek?.get("alasan_tangguh")?.toString() ?: "Tanpa keterangan."
                            tvAlertPengajuan.visibility = View.VISIBLE
                            tvAlertPengajuan.text = "PRESENSI DITANGGUHKAN \uD83D\uDEAB\n\nAlasan: $alasan\n\nHarap segera menghadap pimpinan Anda untuk klarifikasi."
                            tvAlertPengajuan.setBackgroundResource(resources.getIdentifier("bg_badge_danger", "drawable", packageName))
                            tvAlertPengajuan.setTextColor(android.graphics.Color.parseColor("#C62828"))
                            
                            btnAbsen.visibility = View.GONE
                            findViewById<android.widget.ImageView>(resources.getIdentifier("btnNavAbsen", "id", packageName)).visibility = View.GONE
                        } else if (pengajuan != null && pengajuan.isNotEmpty()) {
                            val jenis = pengajuan["kategori"]?.toString() ?: "Izin"
                            val subJenis = pengajuan["sub_kategori"]?.toString() ?: ""
                            val ket = pengajuan["keterangan"]?.toString() ?: "-"
                            val tglMulai = pengajuan["tanggal_mulai"]?.toString() ?: ""
                            val tglSelesai = pengajuan["tanggal_selesai"]?.toString() ?: ""
                            val slotWaktu = pengajuan["slot_waktu"]?.toString() ?: ""
                            val statusApp = pengajuan["status_approval"]?.toString() ?: "Pending"
                            
                            var displayTgl = tglMulai
                            if(tglMulai != tglSelesai && tglSelesai.isNotEmpty()) {
                                displayTgl = "$tglMulai s/d $tglSelesai"
                            }
                            if(slotWaktu.isNotEmpty() && slotWaktu != "null" && slotWaktu != "Penuh") {
                                displayTgl += " ($slotWaktu)"
                            }
                            
                            var detailText = "Pengajuan: $jenis"
                            if(subJenis.isNotEmpty() && subJenis != "null") detailText += " ($subJenis)"
                            detailText += "\nTanggal: $displayTgl\nKeterangan: $ket\nStatus: $statusApp"
                            
                            tvAlertPengajuan.visibility = View.VISIBLE
                            tvAlertPengajuan.text = detailText
                            if(statusApp.contains("Disetujui", true)) {
                                tvAlertPengajuan.setBackgroundResource(resources.getIdentifier("bg_badge_success", "drawable", packageName))
                                tvAlertPengajuan.setTextColor(android.graphics.Color.parseColor("#2E7D32"))
                                
                                val isFullDay = slotWaktu.isEmpty() || slotWaktu == "null" || slotWaktu.equals("Penuh", ignoreCase = true) || slotWaktu.equals("Satu Hari Penuh", ignoreCase = true)
                                if (isFullDay) {
                                    btnAbsen.visibility = View.GONE
                                    findViewById<android.widget.ImageView>(resources.getIdentifier("btnNavAbsen", "id", packageName)).visibility = View.GONE
                                } else {
                                    btnAbsen.visibility = View.VISIBLE
                                    findViewById<android.widget.ImageView>(resources.getIdentifier("btnNavAbsen", "id", packageName)).visibility = View.VISIBLE
                                }
                            } else if(statusApp.contains("Ditolak", true)) {
                                tvAlertPengajuan.setBackgroundResource(resources.getIdentifier("bg_badge_danger", "drawable", packageName))
                                tvAlertPengajuan.setTextColor(android.graphics.Color.parseColor("#C62828"))
                                btnAbsen.visibility = View.VISIBLE
                                findViewById<android.widget.ImageView>(resources.getIdentifier("btnNavAbsen", "id", packageName)).visibility = View.VISIBLE
                            } else {
                                tvAlertPengajuan.setBackgroundResource(resources.getIdentifier("bg_badge_warning", "drawable", packageName))
                                tvAlertPengajuan.setTextColor(android.graphics.Color.parseColor("#B5952F"))
                                btnAbsen.visibility = View.VISIBLE
                                findViewById<android.widget.ImageView>(resources.getIdentifier("btnNavAbsen", "id", packageName)).visibility = View.VISIBLE
                            }
                        } else {
                            tvAlertPengajuan.visibility = View.GONE
                            btnAbsen.visibility = View.VISIBLE
                            findViewById<android.widget.ImageView>(resources.getIdentifier("btnNavAbsen", "id", packageName)).visibility = View.VISIBLE
                        }

                        val cito = body["cito_aktif"] as? Map<String, Any>
                        val isHariIniCito = body["is_hari_ini_cito"] as? Boolean ?: false
                        
                        if (cito != null || isHariIniCito) {
                            tvAlertCito.visibility = View.VISIBLE
                            tvAlertCito.setOnClickListener {
                                if (isHariIniCito && cito == null) {
                                    Toast.makeText(this@MainActivity, "Anda berada di jadwal Standby/Cito harian. Gunakan tombol Absen biasa di bawah.", Toast.LENGTH_LONG).show()
                                    return@setOnClickListener
                                }
                                
                                if (currentLocation == null) {
                                    Toast.makeText(this@MainActivity, "Tunggu, sedang mengunci titik satelit GPS...", Toast.LENGTH_SHORT).show()
                                    fetchLocation()
                                    return@setOnClickListener
                                }

                                if (cito != null) {
                                    android.app.AlertDialog.Builder(this@MainActivity)
                                        .setTitle("Akhiri Cito")
                                        .setMessage("Apakah Anda yakin ingin mengakhiri sesi Cito / Darurat ini?")
                                        .setPositiveButton("Ya") { _, _ ->
                                            if (profileIsBercadar) {
                                                showBiometricPrompt(isCito = true, citoKeterangan = "")
                                            } else {
                                                if (ActivityCompat.checkSelfPermission(this@MainActivity, Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
                                                    ActivityCompat.requestPermissions(this@MainActivity, arrayOf(Manifest.permission.CAMERA), 101)
                                                    return@setPositiveButton
                                                }
                                                val intent = android.content.Intent(this@MainActivity, com.polman.presensi.ui.absen.CameraLivenessActivity::class.java)
                                                intent.putExtra("LAT", currentLocation!!.latitude)
                                                intent.putExtra("LNG", currentLocation!!.longitude)
                                                val nipSaved = getSharedPreferences("PresensiPolmanApp", MODE_PRIVATE).getString("saved_nip", "") ?: ""
                                                intent.putExtra("NIP", nipSaved)
                                                intent.putExtra("is_cito", true)
                                                intent.putExtra("citoKeterangan", "")
                                                startActivityForResult(intent, 200)
                                            }
                                        }
                                        .setNegativeButton("Batal", null)
                                        .show()
                                } else {
                                    val input = android.widget.EditText(this@MainActivity)
                                    input.hint = "Contoh: Panggilan Darurat Pasien X"
                                    val container = android.widget.LinearLayout(this@MainActivity)
                                    container.setPadding(50, 20, 50, 0)
                                    container.addView(input, android.widget.LinearLayout.LayoutParams(
                                        android.widget.LinearLayout.LayoutParams.MATCH_PARENT,
                                        android.widget.LinearLayout.LayoutParams.MATCH_PARENT
                                    ))

                                    val dialog = android.app.AlertDialog.Builder(this@MainActivity)
                                        .setTitle("Mulai Cito")
                                        .setMessage("Masukkan keterangan tindakan darurat:")
                                        .setView(container)
                                        .setPositiveButton("Lanjut Absen", null)
                                        .setNegativeButton("Batal", null)
                                        .create()
                                        
                                    dialog.setOnShowListener {
                                        dialog.getButton(android.app.AlertDialog.BUTTON_POSITIVE).setOnClickListener {
                                            val ket = input.text.toString().trim()
                                            if (ket.isEmpty()) {
                                                Toast.makeText(this@MainActivity, "Keterangan wajib diisi!", Toast.LENGTH_SHORT).show()
                                            } else {
                                                if (profileIsBercadar) {
                                                    dialog.dismiss()
                                                    showBiometricPrompt(isCito = true, citoKeterangan = ket)
                                                } else {
                                                    if (ActivityCompat.checkSelfPermission(this@MainActivity, Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
                                                        ActivityCompat.requestPermissions(this@MainActivity, arrayOf(Manifest.permission.CAMERA), 101)
                                                        return@setOnClickListener
                                                    }
                                                    val intent = android.content.Intent(this@MainActivity, com.polman.presensi.ui.absen.CameraLivenessActivity::class.java)
                                                    intent.putExtra("LAT", currentLocation!!.latitude)
                                                    intent.putExtra("LNG", currentLocation!!.longitude)
                                                    val nipSaved = getSharedPreferences("PresensiPolmanApp", MODE_PRIVATE).getString("saved_nip", "") ?: ""
                                                    intent.putExtra("NIP", nipSaved)
                                                    intent.putExtra("is_cito", true)
                                                    intent.putExtra("citoKeterangan", ket)
                                                    startActivityForResult(intent, 200)
                                                    dialog.dismiss()
                                                }
                                            }
                                        }
                                    }
                                    dialog.show()
                                }
                            }
                        } else {
                            tvAlertCito.visibility = View.GONE
                        }

                        // Parse Tugas Apel
                        val tvAlertApel = findViewById<TextView>(resources.getIdentifier("tvAlertApel", "id", packageName))
                        val tugasApel = body["tugas_apel"] as? Map<String, Any>
                        if (tugasApel != null && tugasApel.isNotEmpty()) {
                            val sudahAbsen = tugasApel["sudah_absen_apel"]?.toString()
                            val idApel = tugasApel["id_apel"]?.toString() ?: ""
                            if (sudahAbsen == null || sudahAbsen == "null" || sudahAbsen.isEmpty()) {
                                tvAlertApel.visibility = View.VISIBLE
                                tvAlertApel.setOnClickListener {
                                    if (currentLocation == null) {
                                        Toast.makeText(this@MainActivity, "Tunggu, sedang mengunci titik satelit GPS...", Toast.LENGTH_SHORT).show()
                                        fetchLocation()
                                        return@setOnClickListener
                                    }
                                    if (profileIsBercadar) {
                                        showBiometricPrompt(isApel = true, idApel = idApel)
                                    } else {
                                        if (ActivityCompat.checkSelfPermission(this@MainActivity, Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
                                            ActivityCompat.requestPermissions(this@MainActivity, arrayOf(Manifest.permission.CAMERA), 101)
                                            return@setOnClickListener
                                        }
                                        val intent = android.content.Intent(this@MainActivity, com.polman.presensi.ui.absen.CameraLivenessActivity::class.java)
                                        intent.putExtra("LAT", currentLocation!!.latitude)
                                        intent.putExtra("LNG", currentLocation!!.longitude)
                                        val nipSaved = getSharedPreferences("PresensiPolmanApp", MODE_PRIVATE).getString("saved_nip", "") ?: ""
                                        intent.putExtra("NIP", nipSaved)
                                        intent.putExtra("is_apel", true)
                                        intent.putExtra("id_apel", idApel)
                                        startActivityForResult(intent, 200)
                                    }
                                }
                            } else {
                                tvAlertApel.visibility = View.GONE
                            }
                        } else {
                            tvAlertApel.visibility = View.GONE
                        }
                        
                        // Parse Presensi Terakhir (Hari Ini)
                        val terakhir = body["terakhir"] as? Map<String, Any>
                        
                        // Masuk
                        val jamMsk = terakhir?.get("jam_masuk_aktual")?.toString()?.take(5) ?: "--:--"
                        val statusMsk = terakhir?.get("status_masuk")?.toString() ?: "Belum"
                        findViewById<TextView>(resources.getIdentifier("tvJamMasuk", "id", packageName)).text = if(jamMsk != "null") jamMsk else "--:--"
                        findViewById<TextView>(resources.getIdentifier("tvStatusMasuk", "id", packageName)).text = if(statusMsk != "null") statusMsk else "Belum"
                        
                        // Siang
                        val jamSiang = terakhir?.get("jam_siang")?.toString()?.take(5) ?: "--:--"
                        val rawStatusSiang = terakhir?.get("status_siang")?.toString()
                        val statusSiang = if (jamSiang != "--:--" && jamSiang != "null") "Selesai" else "Belum"
                        findViewById<TextView>(resources.getIdentifier("tvJamSiang", "id", packageName)).text = if(jamSiang != "null") jamSiang else "--:--"
                        findViewById<TextView>(resources.getIdentifier("tvStatusSiang", "id", packageName)).text = statusSiang
                        
                        // Pulang
                        val jamPlg = terakhir?.get("jam_pulang_aktual")?.toString()?.take(5) ?: "--:--"
                        val statusPlg = terakhir?.get("status_pulang")?.toString() ?: "Belum"
                        findViewById<TextView>(resources.getIdentifier("tvJamPulang", "id", packageName)).text = if(jamPlg != "null") jamPlg else "--:--"
                        findViewById<TextView>(resources.getIdentifier("tvStatusPulang", "id", packageName)).text = if(statusPlg != "null") statusPlg else "Belum"
                        
                        // Parse Riwayat Bulan Ini
                        val riwayatList = body["riwayat"] as? List<Map<String, Any>>
                        val container = findViewById<android.widget.LinearLayout>(resources.getIdentifier("containerRiwayat", "id", packageName))
                        if(container != null) {
                            container.removeAllViews()
                            riwayatList?.forEach { item ->
                                val tglFull = item["tanggal"]?.toString() ?: ""
                                val tgl = if(tglFull.length >= 10) tglFull.substring(8, 10) else "00"
                                
                                val formatAsli = java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.getDefault())
                                val formatBln = java.text.SimpleDateFormat("MMM", java.util.Locale.getDefault())
                                var bln = "BLN"
                                try {
                                    val dateObj = formatAsli.parse(tglFull)
                                    if(dateObj != null) bln = formatBln.format(dateObj).uppercase(java.util.Locale.getDefault())
                                } catch (e: Exception) {}
                                
                                val rJamMsk = item["jam_masuk_aktual"]?.toString()?.take(5) ?: "--:--"
                                val rJamPlg = item["jam_pulang_aktual"]?.toString()?.take(5) ?: "--:--"
                                val rStatusMsk = item["status_masuk"]?.toString() ?: "Hadir"
                                val rStatusPlg = item["status_pulang"]?.toString() ?: ""
                                
                                val itemView = layoutInflater.inflate(resources.getIdentifier("item_riwayat", "layout", packageName), container, false)
                                
                                itemView.findViewById<TextView>(resources.getIdentifier("tvRiwayatTgl", "id", packageName)).text = tgl
                                itemView.findViewById<TextView>(resources.getIdentifier("tvRiwayatBulan", "id", packageName)).text = bln
                                itemView.findViewById<TextView>(resources.getIdentifier("tvRiwayatJamMsk", "id", packageName)).text = if(rJamMsk != "null") rJamMsk else "--:--"
                                itemView.findViewById<TextView>(resources.getIdentifier("tvRiwayatJamPlg", "id", packageName)).text = if(rJamPlg != "null") rJamPlg else "--:--"
                                itemView.findViewById<TextView>(resources.getIdentifier("tvRiwayatStatus", "id", packageName)).text = if(rStatusMsk != "null") rStatusMsk else "Hadir"
                                
                                val tvStatusPlgView = itemView.findViewById<TextView>(resources.getIdentifier("tvRiwayatStatusPulang", "id", packageName))
                                if(rStatusPlg.isNotEmpty() && rStatusPlg != "null" && rStatusPlg != "-") {
                                    tvStatusPlgView.visibility = View.VISIBLE
                                    tvStatusPlgView.text = rStatusPlg
                                } else {
                                    tvStatusPlgView.visibility = View.GONE
                                }
                                
                                container.addView(itemView)
                            }
                        }
                    } catch (e: Exception) {
                        tvNamaPegawai.text = "Error Format Data"
                    }
                } else {
                    handleSessionExpired(bulan, tahun)
                }
            }
            override fun onFailure(call: Call<Any>, t: Throwable) {
                val swipeRefresh = findViewById<androidx.swiperefreshlayout.widget.SwipeRefreshLayout>(resources.getIdentifier("swipeRefresh", "id", packageName))
                swipeRefresh.isRefreshing = false
                tvNamaPegawai.text = "Gagal memuat profil."
            }
        })
    }

    private fun handleSessionExpired(bulan: String? = null, tahun: String? = null) {
        val prefs = getSharedPreferences("PresensiPolmanApp", MODE_PRIVATE)
        val savedNip = prefs.getString("saved_nip", "")
        val savedPassword = prefs.getString("saved_password", "")
        if (!savedNip.isNullOrEmpty() && !savedPassword.isNullOrEmpty()) {
            tvNamaPegawai.text = "Memulihkan Sesi..."
            NetworkClient.apiService.login(savedNip, savedPassword).enqueue(object : Callback<com.polman.presensi.network.LoginResponse> {
                override fun onResponse(call: Call<com.polman.presensi.network.LoginResponse>, response: Response<com.polman.presensi.network.LoginResponse>) {
                    if (response.isSuccessful && response.body()?.status == "success") {
                        loadDashboardData(bulan, tahun)
                    } else {
                        tvNamaPegawai.text = "Sesi Habis. Silakan Logout."
                    }
                }
                override fun onFailure(call: Call<com.polman.presensi.network.LoginResponse>, t: Throwable) {
                    tvNamaPegawai.text = "Koneksi Error."
                }
            })
        } else {
            tvNamaPegawai.text = "Sesi Habis."
        }
    }

    private fun fetchLocation() {
        if (ActivityCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED) {
            tvLocation.text = "Mengunci GPS satelit..."
            fusedLocationClient.getCurrentLocation(com.google.android.gms.location.Priority.PRIORITY_HIGH_ACCURACY, null)
                .addOnSuccessListener { location: Location? ->
                    if (location != null) {
                        currentLocation = location
                        val latStr = String.format(Locale.US, "%.5f", location.latitude)
                        val lngStr = String.format(Locale.US, "%.5f", location.longitude)
                        tvLocation.text = "Lat: $latStr, Lng: $lngStr\nAkurasi: ${location.accuracy}m"
                    } else {
                        tvLocation.text = "Gagal mengunci GPS. Pastikan GPS HP nyala dan Anda di luar ruangan."
                    }
                }
        }
    }

    override fun onRequestPermissionsResult(requestCode: Int, permissions: Array<out String>, grantResults: IntArray) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        if (requestCode == 100 && grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
            fetchLocation()
        }
        if (requestCode == 101 && grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
            // Camera permission granted, user can click absen again
            Toast.makeText(this, "Izin kamera diberikan. Silakan klik absen lagi.", Toast.LENGTH_SHORT).show()
        }
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: android.content.Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode == 200 && resultCode == RESULT_OK) {
            // Absen berhasil dari Camera Liveness
            loadDashboardData()
        }
    }
    
    private fun showBiometricPrompt(isApel: Boolean = false, idApel: String = "", isCito: Boolean = false, citoKeterangan: String = "") {
        val executor = androidx.core.content.ContextCompat.getMainExecutor(this)
        val biometricPrompt = androidx.biometric.BiometricPrompt(this, executor,
            object : androidx.biometric.BiometricPrompt.AuthenticationCallback() {
                override fun onAuthenticationError(errorCode: Int, errString: CharSequence) {
                    super.onAuthenticationError(errorCode, errString)
                    Toast.makeText(applicationContext, "Biometrik Error: $errString", Toast.LENGTH_SHORT).show()
                }

                override fun onAuthenticationSucceeded(result: androidx.biometric.BiometricPrompt.AuthenticationResult) {
                    super.onAuthenticationSucceeded(result)
                    submitAbsenBercadar(isApel, idApel, isCito, citoKeterangan)
                }

                override fun onAuthenticationFailed() {
                    super.onAuthenticationFailed()
                    Toast.makeText(applicationContext, "Sidik Jari Tidak Dikenali", Toast.LENGTH_SHORT).show()
                }
            })

        val promptInfo = androidx.biometric.BiometricPrompt.PromptInfo.Builder()
            .setTitle("Verifikasi Sidik Jari (Jalur Khusus)")
            .setSubtitle("Autentikasi diwajibkan untuk pegawai bercadar")
            .setAllowedAuthenticators(androidx.biometric.BiometricManager.Authenticators.BIOMETRIC_STRONG)
            .setNegativeButtonText("Batal")
            .build()

        biometricPrompt.authenticate(promptInfo)
    }

    private fun submitAbsenBercadar(isApel: Boolean = false, idApel: String = "", isCito: Boolean = false, citoKeterangan: String = "") {
        if (currentLocation == null) return
        val nip = getSharedPreferences("PresensiPolmanApp", MODE_PRIVATE).getString("saved_nip", "") ?: ""
        val deviceInfo = android.os.Build.MODEL + " (Bercadar Fingerprint)"
        
        Toast.makeText(this, "Mengirim Absen Sidik Jari...", Toast.LENGTH_SHORT).show()
        
        val callback = object : Callback<BaseResponse> {
            override fun onResponse(call: Call<BaseResponse>, response: Response<BaseResponse>) {
                if (response.isSuccessful && response.body()?.status == "success") {
                    val msg = response.body()?.message ?: "Berhasil Absen!"
                    val parsedMsg = androidx.core.text.HtmlCompat.fromHtml(msg, androidx.core.text.HtmlCompat.FROM_HTML_MODE_LEGACY)
                    android.app.AlertDialog.Builder(this@MainActivity)
                        .setTitle("Presensi Berhasil \uD83C\uDF89")
                        .setMessage(parsedMsg)
                        .setCancelable(false)
                        .setPositiveButton("Selesai") { _, _ ->
                            loadDashboardData()
                        }
                        .show()
                } else if (response.isSuccessful && response.body()?.status == "confirm_pulang_cepat") {
                    // Logic confirm pulang bypass sidik jari
                    val msg = response.body()?.message ?: "Anda pulang lebih awal. Konfirmasi?"
                    val parsedMsg = androidx.core.text.HtmlCompat.fromHtml(msg, androidx.core.text.HtmlCompat.FROM_HTML_MODE_LEGACY)
                    android.app.AlertDialog.Builder(this@MainActivity)
                        .setTitle("Konfirmasi")
                        .setMessage(parsedMsg)
                        .setCancelable(false)
                        .setPositiveButton("Ya") { _, _ ->
                            NetworkClient.apiService.submitAbsen(
                                lat = currentLocation!!.latitude,
                                lng = currentLocation!!.longitude,
                                device_info = deviceInfo,
                                nip = nip,
                                confirm_pulang = "1"
                            ).enqueue(this)
                        }
                        .setNegativeButton("Batal", null)
                        .show()
                } else {
                    val msg = response.body()?.message ?: "Gagal Absen"
                    val parsedMsg = androidx.core.text.HtmlCompat.fromHtml(msg, androidx.core.text.HtmlCompat.FROM_HTML_MODE_LEGACY)
                    android.app.AlertDialog.Builder(this@MainActivity)
                        .setTitle("Gagal \u26A0\uFE0F")
                        .setMessage(parsedMsg)
                        .setCancelable(true)
                        .setPositiveButton("Tutup", null)
                        .show()
                }
            }

            override fun onFailure(call: Call<BaseResponse>, t: Throwable) {
                Toast.makeText(this@MainActivity, "Koneksi Error", Toast.LENGTH_SHORT).show()
            }
        }

        if (isCito) {
            NetworkClient.apiService.submitCito(
                lat = currentLocation!!.latitude,
                lng = currentLocation!!.longitude,
                device_info = deviceInfo,
                nip = nip,
                keterangan = citoKeterangan
            ).enqueue(callback)
        } else if (isApel) {
            NetworkClient.apiService.submitAbsenApel(
                lat = currentLocation!!.latitude,
                lng = currentLocation!!.longitude,
                device_info = deviceInfo,
                nip = nip,
                id_apel = idApel
            ).enqueue(callback)
        } else {
            NetworkClient.apiService.submitAbsen(
                lat = currentLocation!!.latitude,
                lng = currentLocation!!.longitude,
                device_info = deviceInfo,
                nip = nip
            ).enqueue(callback)
        }
    }

    private fun showDialogRincianTpp(tppInfo: Map<String, Any>) {
        try {
            val dialogView = layoutInflater.inflate(resources.getIdentifier("dialog_rincian_tpp", "layout", packageName), null)
            val dialog = android.app.AlertDialog.Builder(this)
                .setView(dialogView)
                .create()

            dialog.window?.setBackgroundDrawable(android.graphics.drawable.ColorDrawable(android.graphics.Color.TRANSPARENT))

            val tvModalPeriode = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalPeriode", "id", packageName))
            val btnModalClose = dialogView.findViewById<TextView>(resources.getIdentifier("btnModalClose", "id", packageName))
            val btnModalTutupBawah = dialogView.findViewById<TextView>(resources.getIdentifier("btnModalTutupBawah", "id", packageName))

            val tvModalPaguBk = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalPaguBk", "id", packageName))
            val tvModalPaguKk = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalPaguKk", "id", packageName))
            val tvModalTotalPagu = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalTotalPagu", "id", packageName))
            val tvModalBpjs = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalBpjs", "id", packageName))
            val tvModalPaguNetto = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalPaguNetto", "id", packageName))

            val tvModalTarget60 = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalTarget60", "id", packageName))
            val tvModalPredikat = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalPredikat", "id", packageName))
            val tvModalHasilKinerja = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalHasilKinerja", "id", packageName))

            val tvModalTarget40 = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalTarget40", "id", packageName))
            val tvModalPotonganPersen = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalPotonganPersen", "id", packageName))
            val tvModalNominalPotDisiplin = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalNominalPotDisiplin", "id", packageName))
            val tvModalHasilDisiplin = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalHasilDisiplin", "id", packageName))

            val tvModalTppKotor = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalTppKotor", "id", packageName))
            val tvModalTarifPphLabel = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalTarifPphLabel", "id", packageName))
            val tvModalNominalPph = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalNominalPph", "id", packageName))
            val tvModalTppBersih = dialogView.findViewById<TextView>(resources.getIdentifier("tvModalTppBersih", "id", packageName))

            val namaBulan = tppInfo["nama_bulan"]?.toString() ?: ""
            val tahunTpp = tppInfo["tahun"]?.toString() ?: ""
            tvModalPeriode?.text = "Periode: $namaBulan $tahunTpp"

            tvModalPaguBk?.text = tppInfo["tpp_bk_format"]?.toString() ?: "Rp 0"
            tvModalPaguKk?.text = tppInfo["tpp_kk_format"]?.toString() ?: "Rp 0"
            tvModalTotalPagu?.text = tppInfo["jumlah_pagu_format"]?.toString() ?: "Rp 0"
            tvModalBpjs?.text = "- " + (tppInfo["bpjs_1pct_format"]?.toString() ?: "Rp 0")
            tvModalPaguNetto?.text = tppInfo["tpp_stlh_bpjs_format"]?.toString() ?: "Rp 0"

            tvModalTarget60?.text = tppInfo["tpp_60_target_format"]?.toString() ?: "Rp 0"
            val predikat = tppInfo["predikat_kinerja"]?.toString() ?: "Baik"
            val persenKin = tppInfo["persen_kinerja"]?.toString() ?: "100"
            tvModalPredikat?.text = "$predikat ($persenKin%)"
            tvModalHasilKinerja?.text = tppInfo["jumlah_kinerja_format"]?.toString() ?: "Rp 0"

            tvModalTarget40?.text = tppInfo["tpp_40_target_format"]?.toString() ?: "Rp 0"
            val potPersen = tppInfo["persen_pot_disiplin"]?.toString() ?: "0"
            tvModalPotonganPersen?.text = "-$potPersen%"
            tvModalNominalPotDisiplin?.text = "- " + (tppInfo["nominal_pot_disiplin_format"]?.toString() ?: "Rp 0")
            tvModalHasilDisiplin?.text = tppInfo["jumlah_disiplin_format"]?.toString() ?: "Rp 0"

            tvModalTppKotor?.text = tppInfo["tpp_kotor_format"]?.toString() ?: "Rp 0"
            val tarifPphPersen = tppInfo["tarif_pph_persen"]?.toString() ?: "0%"
            tvModalTarifPphLabel?.text = "PPh 21 ($tarifPphPersen)"
            tvModalNominalPph?.text = "- " + (tppInfo["nominal_pph21_format"]?.toString() ?: "Rp 0")
            tvModalTppBersih?.text = tppInfo["tpp_bersih_format"]?.toString() ?: "Rp 0"

            btnModalClose?.setOnClickListener { dialog.dismiss() }
            btnModalTutupBawah?.setOnClickListener { dialog.dismiss() }

            dialog.show()
        } catch (e: Exception) {
            Toast.makeText(this, "Gagal menampilkan rincian: ${e.message}", Toast.LENGTH_SHORT).show()
        }
    }
}

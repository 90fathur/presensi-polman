package com.polman.presensi.ui.absen

import android.annotation.SuppressLint
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.util.Log
import android.view.View
import android.widget.ImageView
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.annotation.OptIn
import androidx.appcompat.app.AppCompatActivity
import androidx.camera.core.*
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.core.content.ContextCompat
import com.google.mlkit.vision.common.InputImage
import com.google.mlkit.vision.face.FaceDetection
import com.google.mlkit.vision.face.FaceDetectorOptions
import com.polman.presensi.network.BaseResponse
import com.polman.presensi.network.NetworkClient
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response
import java.util.concurrent.ExecutorService
import java.util.concurrent.Executors

class CameraLivenessActivity : AppCompatActivity() {

    private lateinit var viewFinder: PreviewView
    private lateinit var tvInstruction: TextView
    private lateinit var tvSubtext: TextView
    private lateinit var faceOverlay: View
    private lateinit var progressLoading: ProgressBar

    private lateinit var cameraExecutor: ExecutorService
    private var imageAnalyzer: ImageAnalysis? = null
    
    private var lat: Double = 0.0
    private var lng: Double = 0.0
    private var nip: String = ""
    private var isApel: Boolean = false
    private var idApel: String = ""

    private var isCito: Boolean = false
    private var citoKeterangan: String = ""

    // Challenges
    private enum class Challenge {
        SMILE
    }
    private val challengeList = mutableListOf<Challenge>()
    private var currentChallengeIndex = -1 // -1 means wait for NEUTRAL first

    private var neutralFramesCount = 0
    private var challengeFramesCount = 0
    private val REQUIRED_FRAMES = 3 // Frames to confirm a state

    private var capturedBitmap: android.graphics.Bitmap? = null
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        supportActionBar?.hide()
        setContentView(resources.getIdentifier("activity_camera_liveness", "layout", packageName))

        viewFinder = findViewById(resources.getIdentifier("viewFinder", "id", packageName))
        tvInstruction = findViewById(resources.getIdentifier("tvLivenessInstruction", "id", packageName))
        tvSubtext = findViewById(resources.getIdentifier("tvLivenessSubtext", "id", packageName))
        faceOverlay = findViewById(resources.getIdentifier("faceOverlay", "id", packageName))
        progressLoading = findViewById(resources.getIdentifier("progressLoading", "id", packageName))

        lat = intent.getDoubleExtra("LAT", 0.0)
        lng = intent.getDoubleExtra("LNG", 0.0)
        nip = intent.getStringExtra("NIP") ?: ""
        isApel = intent.getBooleanExtra("is_apel", false)
        idApel = intent.getStringExtra("id_apel") ?: ""
        isCito = intent.getBooleanExtra("is_cito", false)
        citoKeterangan = intent.getStringExtra("citoKeterangan") ?: ""

        findViewById<ImageView>(resources.getIdentifier("btnBack", "id", packageName)).setOnClickListener {
            finish()
        }

        // Generate 1 Challenge
        challengeList.add(Challenge.SMILE)

        cameraExecutor = Executors.newSingleThreadExecutor()

        startCamera()
    }

    private fun startCamera() {
        val cameraProviderFuture = ProcessCameraProvider.getInstance(this)

        cameraProviderFuture.addListener({
            val cameraProvider: ProcessCameraProvider = cameraProviderFuture.get()

            val preview = Preview.Builder()
                .build()
                .also {
                    it.setSurfaceProvider(viewFinder.surfaceProvider)
                }

            val cameraSelector = CameraSelector.DEFAULT_FRONT_CAMERA

            // Setup ML Kit Face Detector
            val options = FaceDetectorOptions.Builder()
                .setClassificationMode(FaceDetectorOptions.CLASSIFICATION_MODE_ALL)
                .setPerformanceMode(FaceDetectorOptions.PERFORMANCE_MODE_FAST)
                .build()
            val faceDetector = FaceDetection.getClient(options)

            imageAnalyzer = ImageAnalysis.Builder()
                .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
                .build()
                .also { analysis ->
                    analysis.setAnalyzer(cameraExecutor) { imageProxy ->
                        processImageProxy(faceDetector, imageProxy)
                    }
                }

            try {
                cameraProvider.unbindAll()
                cameraProvider.bindToLifecycle(this, cameraSelector, preview, imageAnalyzer)
                updateUIState(0)
            } catch (exc: Exception) {
                Log.e("Liveness", "Use case binding failed", exc)
                runOnUiThread {
                    Toast.makeText(this, "Gagal membuka kamera depan.", Toast.LENGTH_SHORT).show()
                }
            }

        }, ContextCompat.getMainExecutor(this))
    }

    @OptIn(ExperimentalGetImage::class)
    private fun processImageProxy(faceDetector: com.google.mlkit.vision.face.FaceDetector, imageProxy: ImageProxy) {
        val mediaImage = imageProxy.image
        if (mediaImage != null && currentChallengeIndex < challengeList.size) {
            val image = InputImage.fromMediaImage(mediaImage, imageProxy.imageInfo.rotationDegrees)
            faceDetector.process(image)
                .addOnSuccessListener { faces ->
                    if (faces.isEmpty()) {
                        neutralFramesCount = 0
                        challengeFramesCount = 0
                        if (currentChallengeIndex != -1) {
                            // Reset if face is lost
                            currentChallengeIndex = -1
                            updateUIState(-1)
                        }
                    } else if (faces.size > 1) {
                        runOnUiThread {
                            tvInstruction.text = "Terdeteksi lebih dari 1 wajah!"
                            tvSubtext.text = "Pastikan hanya wajah Anda di kamera."
                            faceOverlay.setBackgroundResource(android.graphics.Color.parseColor("#00000000"))
                        }
                    } else {
                        val face = faces[0]
                        val smileProb = face.smilingProbability ?: 0f
                        val leftEyeOpen = face.leftEyeOpenProbability ?: 1f
                        val rightEyeOpen = face.rightEyeOpenProbability ?: 1f
                        val headEulerY = face.headEulerAngleY // + for left, - for right
                        
                        // Cek wajah netral
                        val isNeutral = smileProb < 0.2f && leftEyeOpen > 0.6f && rightEyeOpen > 0.6f && headEulerY in -15f..15f

                        if (currentChallengeIndex == -1) {
                            if (isNeutral) {
                                neutralFramesCount++
                                if (neutralFramesCount >= REQUIRED_FRAMES) {
                                    currentChallengeIndex = 0
                                    updateUIState(0)
                                }
                            } else {
                                neutralFramesCount = 0
                                updateUIState(-1)
                            }
                        } else if (currentChallengeIndex < challengeList.size) {
                            val targetChallenge = challengeList[currentChallengeIndex]
                            var challengePassed = false
                            when (targetChallenge) {
                                Challenge.SMILE -> challengePassed = smileProb > 0.7f
                            }

                            if (challengePassed) {
                                challengeFramesCount++
                                if (challengeFramesCount >= 2) { // Need 2 frames to avoid accidental blink bugs
                                    currentChallengeIndex++
                                    challengeFramesCount = 0
                                    if (currentChallengeIndex >= challengeList.size) {
                                        val bitmap = imageProxy.toBitmap()
                                        val matrix = android.graphics.Matrix()
                                        matrix.postRotate(imageProxy.imageInfo.rotationDegrees.toFloat())
                                        matrix.postScale(-1f, 1f, bitmap.width / 2f, bitmap.height / 2f)
                                        capturedBitmap = android.graphics.Bitmap.createBitmap(bitmap, 0, 0, bitmap.width, bitmap.height, matrix, true)

                                        updateUIState(99) // Done
                                        submitAbsen()
                                    } else {
                                        updateUIState(currentChallengeIndex)
                                    }
                                }
                            } else {
                                challengeFramesCount = 0
                            }
                        }
                    }
                }
                .addOnFailureListener { e ->
                    Log.e("Liveness", "Face detection failed", e)
                }
                .addOnCompleteListener {
                    imageProxy.close()
                }
        } else {
            imageProxy.close()
        }
    }

    private fun updateUIState(state: Int) {
        runOnUiThread {
            when (state) {
                -1 -> {
                    tvInstruction.text = "Posisikan Wajah"
                    tvSubtext.text = "Tatap lurus ke kamera dengan wajah biasa (Netral)."
                    faceOverlay.setBackgroundColor(android.graphics.Color.TRANSPARENT)
                }
                0 -> {
                    val stepText = "Langkah 1/1: "
                    val instr = "Sekarang Tersenyumlah!"
                    tvInstruction.text = stepText + instr
                    tvSubtext.text = "Tahan senyuman Anda untuk konfirmasi kehadiran."
                }
                99 -> {
                    tvInstruction.text = "Memproses Presensi..."
                    tvSubtext.text = "Verifikasi Wajah Berhasil! Mengirim data..."
                    progressLoading.visibility = View.VISIBLE
                    imageAnalyzer?.clearAnalyzer()
                }
            }
        }
    }

    private fun submitAbsen(confirmPulang: String? = null, confirmDouble: String? = null) {
        val androidId = android.provider.Settings.Secure.getString(contentResolver, android.provider.Settings.Secure.ANDROID_ID)
        val deviceInfo = "Android-$androidId"
        
        val callback = object : Callback<BaseResponse> {
            override fun onResponse(call: Call<BaseResponse>, response: Response<BaseResponse>) {
                runOnUiThread {
                    progressLoading.visibility = View.GONE
                    val status = response.body()?.status
                    val msg = response.body()?.message ?: "Gagal mengirim data"

                    if (response.isSuccessful && status == "success") {
                        val parsedSuccessMsg = androidx.core.text.HtmlCompat.fromHtml(msg, androidx.core.text.HtmlCompat.FROM_HTML_MODE_LEGACY)
                        android.app.AlertDialog.Builder(this@CameraLivenessActivity)
                            .setTitle("Presensi Berhasil \uD83C\uDF89")
                            .setMessage(parsedSuccessMsg)
                            .setCancelable(false)
                            .setPositiveButton("Selesai") { _, _ ->
                                val notifTitle = response.body()?.notif_title ?: "Aktivitas Berhasil"
                                val notifContent = response.body()?.notif_content ?: "Data Anda telah tercatat di server."
                                com.polman.presensi.utils.NotificationHelper.showNotification(this@CameraLivenessActivity, notifTitle, notifContent)
                                saveWatermarkedPhoto()
                                setResult(RESULT_OK)
                                finish()
                            }
                            .show()
                    } else if (response.isSuccessful && (status == "confirm_pulang_cepat" || status == "confirm_double")) {
                        val parsedMsg = androidx.core.text.HtmlCompat.fromHtml(msg, androidx.core.text.HtmlCompat.FROM_HTML_MODE_LEGACY)
                        android.app.AlertDialog.Builder(this@CameraLivenessActivity)
                            .setTitle("Konfirmasi")
                            .setMessage(parsedMsg)
                            .setCancelable(false)
                            .setPositiveButton("Ya, Lanjutkan") { _, _ ->
                                progressLoading.visibility = View.VISIBLE
                                if (status == "confirm_pulang_cepat") {
                                    submitAbsen(confirmPulang = "1", confirmDouble = null)
                                } else {
                                    submitAbsen(confirmPulang = null, confirmDouble = "1")
                                }
                            }
                            .setNegativeButton("Batal") { dialog, _ ->
                                dialog.dismiss()
                                // Reset liveness to try again
                                currentChallengeIndex = -1
                                updateUIState(-1)
                                startCamera()
                            }
                            .show()
                    } else if (response.isSuccessful && status == "info") {
                        val parsedInfo = androidx.core.text.HtmlCompat.fromHtml(msg, androidx.core.text.HtmlCompat.FROM_HTML_MODE_LEGACY)
                        android.app.AlertDialog.Builder(this@CameraLivenessActivity)
                            .setTitle("Informasi")
                            .setMessage(parsedInfo)
                            .setPositiveButton("Tutup") { dialog, _ -> 
                                dialog.dismiss()
                                finish()
                            }
                            .show()
                    } else {
                        val parsedError = androidx.core.text.HtmlCompat.fromHtml(msg, androidx.core.text.HtmlCompat.FROM_HTML_MODE_LEGACY)
                        android.app.AlertDialog.Builder(this@CameraLivenessActivity)
                            .setTitle("Gagal")
                            .setMessage(parsedError)
                            .setPositiveButton("Tutup") { dialog, _ -> 
                                dialog.dismiss()
                                // Reset liveness to try again
                                currentChallengeIndex = -1
                                updateUIState(-1)
                                startCamera() // Re-bind analyzer
                            }
                            .show()
                    }
                }
            }

            override fun onFailure(call: Call<BaseResponse>, t: Throwable) {
                runOnUiThread {
                    progressLoading.visibility = View.GONE
                    Toast.makeText(this@CameraLivenessActivity, "Koneksi Error: ${t.message}", Toast.LENGTH_LONG).show()
                    currentChallengeIndex = -1
                    updateUIState(-1)
                    startCamera()
                }
            }
        }
        
        if (isCito) {
            NetworkClient.apiService.submitCito(lat, lng, deviceInfo, nip, citoKeterangan).enqueue(callback)
        } else if (isApel) {
            NetworkClient.apiService.submitAbsenApel(lat, lng, deviceInfo, nip, idApel).enqueue(callback)
        } else {
            NetworkClient.apiService.submitAbsen(lat, lng, deviceInfo, nip, confirmPulang, confirmDouble).enqueue(callback)
        }
    }

    override fun onDestroy() {
        super.onDestroy()
        cameraExecutor.shutdown()
    }

    private fun saveWatermarkedPhoto() {
        val bitmap = capturedBitmap ?: return
        try {
            // Because ImageProxy returns immutable bitmap, we need a mutable copy
            val mutableBitmap = bitmap.copy(android.graphics.Bitmap.Config.ARGB_8888, true)
            val canvas = android.graphics.Canvas(mutableBitmap)
            val paint = android.graphics.Paint().apply {
                color = android.graphics.Color.WHITE
                textSize = mutableBitmap.width * 0.04f
                style = android.graphics.Paint.Style.FILL
                isAntiAlias = true
                setShadowLayer(5f, 2f, 2f, android.graphics.Color.BLACK)
            }
            
            val bgPaint = android.graphics.Paint().apply {
                color = android.graphics.Color.parseColor("#80000000") // 50% Black
                style = android.graphics.Paint.Style.FILL
            }

            val timeStamp = java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss", java.util.Locale.getDefault()).format(java.util.Date())
            val text1 = "NIP: $nip"
            val text2 = "Waktu: $timeStamp"
            val text3 = "Lokasi: $lat, $lng"

            val padding = 30f
            val startY = mutableBitmap.height - (paint.textSize * 4) - padding
            
            // Draw background block
            canvas.drawRect(0f, startY - padding, mutableBitmap.width.toFloat(), mutableBitmap.height.toFloat(), bgPaint)
            
            // Draw text
            canvas.drawText(text1, padding, startY + paint.textSize, paint)
            canvas.drawText(text2, padding, startY + paint.textSize * 2.2f, paint)
            canvas.drawText(text3, padding, startY + paint.textSize * 3.4f, paint)

            // Save to Gallery
            val contentValues = android.content.ContentValues().apply {
                put(android.provider.MediaStore.MediaColumns.DISPLAY_NAME, "Presensi_${nip}_${System.currentTimeMillis()}.jpg")
                put(android.provider.MediaStore.MediaColumns.MIME_TYPE, "image/jpeg")
                if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.Q) {
                    put(android.provider.MediaStore.MediaColumns.RELATIVE_PATH, android.os.Environment.DIRECTORY_PICTURES + "/PresensiPolman")
                }
            }

            val uri = contentResolver.insert(android.provider.MediaStore.Images.Media.EXTERNAL_CONTENT_URI, contentValues)
            if (uri != null) {
                val outputStream = contentResolver.openOutputStream(uri)
                if (outputStream != null) {
                    mutableBitmap.compress(android.graphics.Bitmap.CompressFormat.JPEG, 90, outputStream)
                    outputStream.close()
                }
            }
        } catch (e: Exception) {
            e.printStackTrace()
            runOnUiThread {
                Toast.makeText(this, "Gagal menyimpan foto ke galeri: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }
}

package com.polman.presensi.ui.izin

import android.app.Activity
import android.app.DatePickerDialog
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.polman.presensi.network.BaseResponse
import com.polman.presensi.network.NetworkClient
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response
import android.provider.OpenableColumns
import java.io.File
import java.io.FileOutputStream
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale

class IzinActivity : AppCompatActivity() {

    private lateinit var btnBack: ImageView
    private lateinit var edtKategori: EditText
    private lateinit var tvTglMulai: TextView
    private lateinit var tvTglSelesai: TextView
    private lateinit var edtKeterangan: EditText
    private lateinit var btnPilihFile: Button
    private lateinit var tvNamaFile: TextView
    private lateinit var btnSubmit: Button

    private var selectedFileUri: Uri? = null
    private var tglMulaiStr = ""
    private var tglSelesaiStr = ""
    private lateinit var wrapperSubKategori: android.view.View
    private lateinit var tvSubKategori: TextView
    private lateinit var wrapperSlotWaktu: android.view.View
    private lateinit var tvSlotWaktu: TextView
    private var subKategoriStr = ""
    private var slotWaktuStr = ""

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        supportActionBar?.hide()
        setContentView(resources.getIdentifier("activity_izin", "layout", packageName))

        btnBack = findViewById(resources.getIdentifier("btnBack", "id", packageName))
        edtKategori = findViewById(resources.getIdentifier("edtKategori", "id", packageName))
        tvTglMulai = findViewById(resources.getIdentifier("tvTglMulai", "id", packageName))
        tvTglSelesai = findViewById(resources.getIdentifier("tvTglSelesai", "id", packageName))
        edtKeterangan = findViewById(resources.getIdentifier("edtKeterangan", "id", packageName))
        btnPilihFile = findViewById(resources.getIdentifier("btnPilihFile", "id", packageName))
        tvNamaFile = findViewById(resources.getIdentifier("tvNamaFile", "id", packageName))
        btnSubmit = findViewById(resources.getIdentifier("btnSubmit", "id", packageName))
        wrapperSubKategori = findViewById(resources.getIdentifier("wrapperSubKategori", "id", packageName))
        tvSubKategori = findViewById(resources.getIdentifier("tvSubKategori", "id", packageName))
        wrapperSlotWaktu = findViewById(resources.getIdentifier("wrapperSlotWaktu", "id", packageName))
        tvSlotWaktu = findViewById(resources.getIdentifier("tvSlotWaktu", "id", packageName))

        val kategori = intent.getStringExtra("KATEGORI") ?: "Izin"
        edtKategori.setText(kategori)

        var subOptions = arrayOf<String>()
        if (kategori == "Tugas Luar") {
            subOptions = arrayOf("Luar Daerah", "Dalam Daerah")
            wrapperSubKategori.visibility = android.view.View.VISIBLE
        } else if (kategori == "Izin Absen" || kategori == "Izin") {
            subOptions = arrayOf("Izin Penuh", "Setengah Hari")
            wrapperSubKategori.visibility = android.view.View.VISIBLE
        } else if (kategori == "Sakit") {
            subOptions = arrayOf("Sakit Penuh")
            wrapperSubKategori.visibility = android.view.View.VISIBLE
        }

        tvSubKategori.setOnClickListener {
            android.app.AlertDialog.Builder(this)
                .setTitle("Pilih Sub Kategori")
                .setItems(subOptions) { _, which ->
                    subKategoriStr = subOptions[which]
                    tvSubKategori.text = subKategoriStr

                    if (subKategoriStr == "Setengah Hari" || subKategoriStr == "Dalam Daerah") {
                        wrapperSlotWaktu.visibility = android.view.View.VISIBLE
                    } else {
                        wrapperSlotWaktu.visibility = android.view.View.GONE
                    }
                }.show()
        }

        val slotOptionsDisplay = arrayOf("Satu Hari Penuh", "Sesi Awal (Absen Masuk)", "Sesi Siang (Absen Ishoma)", "Sesi Akhir (Absen Pulang)")
        val slotOptionsValue = arrayOf("Penuh", "Pagi", "Siang", "Sore")
        tvSlotWaktu.setOnClickListener {
            android.app.AlertDialog.Builder(this)
                .setTitle("Pilih Slot Waktu")
                .setItems(slotOptionsDisplay) { _, which ->
                    slotWaktuStr = slotOptionsValue[which]
                    tvSlotWaktu.text = slotOptionsDisplay[which]
                }.show()
        }

        btnBack.setOnClickListener { finish() }

        tvTglMulai.setOnClickListener { showDatePicker { tglStr, tglDisplay ->
            tglMulaiStr = tglStr
            tvTglMulai.text = tglDisplay
        }}

        tvTglSelesai.setOnClickListener { showDatePicker { tglStr, tglDisplay ->
            tglSelesaiStr = tglStr
            tvTglSelesai.text = tglDisplay
        }}

        btnPilihFile.setOnClickListener {
            val intent = Intent(Intent.ACTION_GET_CONTENT).apply {
                type = "*/*"
                putExtra(Intent.EXTRA_MIME_TYPES, arrayOf("image/jpeg", "image/png", "image/jpg", "application/pdf"))
            }
            startActivityForResult(intent, 100)
        }

        btnSubmit.setOnClickListener {
            submitData()
        }
    }

    private fun showDatePicker(onDateSelected: (String, String) -> Unit) {
        val calendar = Calendar.getInstance()
        val datePickerDialog = DatePickerDialog(this, { _, year, month, dayOfMonth ->
            val selCal = Calendar.getInstance()
            selCal.set(year, month, dayOfMonth)
            val formatApi = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault())
            val formatDisplay = SimpleDateFormat("dd MMM yyyy", Locale.getDefault())
            
            onDateSelected(formatApi.format(selCal.time), formatDisplay.format(selCal.time))
        }, calendar.get(Calendar.YEAR), calendar.get(Calendar.MONTH), calendar.get(Calendar.DAY_OF_MONTH))
        datePickerDialog.show()
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode == 100 && resultCode == Activity.RESULT_OK) {
            selectedFileUri = data?.data
            if (selectedFileUri != null) {
                var displayName = "File Terpilih"
                try {
                    val cursor = contentResolver.query(selectedFileUri!!, null, null, null, null)
                    cursor?.use {
                        if (it.moveToFirst()) {
                            val nameIndex = it.getColumnIndex(OpenableColumns.DISPLAY_NAME)
                            if (nameIndex != -1) displayName = it.getString(nameIndex)
                        }
                    }
                } catch (e: Exception) {
                    e.printStackTrace()
                }
                tvNamaFile.text = displayName
                tvNamaFile.setTextColor(android.graphics.Color.parseColor("#2E7D32"))
            }
        }
    }

    private fun submitData() {
        if (tglMulaiStr.isEmpty() || tglSelesaiStr.isEmpty()) {
            Toast.makeText(this, "Tanggal Mulai & Selesai wajib diisi!", Toast.LENGTH_SHORT).show()
            return
        }

        btnSubmit.isEnabled = false
        btnSubmit.text = "MENGIRIM..."

        val kategoriReq = edtKategori.text.toString().toRequestBody("text/plain".toMediaTypeOrNull())
        val tglMulaiReq = tglMulaiStr.toRequestBody("text/plain".toMediaTypeOrNull())
        val tglSelesaiReq = tglSelesaiStr.toRequestBody("text/plain".toMediaTypeOrNull())
        
        val subKatReq = if (subKategoriStr.isNotEmpty()) subKategoriStr.toRequestBody("text/plain".toMediaTypeOrNull()) else null
        val finalSlotWaktu = if (wrapperSlotWaktu.visibility == android.view.View.VISIBLE && slotWaktuStr.isNotEmpty()) slotWaktuStr else "Penuh"
        val slotWaktuReq = finalSlotWaktu.toRequestBody("text/plain".toMediaTypeOrNull())

        var filePart: MultipartBody.Part? = null
        if (selectedFileUri != null) {
            try {
                val mimeType = contentResolver.getType(selectedFileUri!!) ?: ""
                var originalName = "bukti_izin.jpg"
                try {
                    val cursor = contentResolver.query(selectedFileUri!!, null, null, null, null)
                    cursor?.use {
                        if (it.moveToFirst()) {
                            val nameIndex = it.getColumnIndex(OpenableColumns.DISPLAY_NAME)
                            if (nameIndex != -1) originalName = it.getString(nameIndex)
                        }
                    }
                } catch (e: Exception) {
                    e.printStackTrace()
                }

                val isPdf = mimeType.equals("application/pdf", ignoreCase = true) || originalName.lowercase().endsWith(".pdf")
                val ext = if (isPdf) ".pdf" else if (originalName.lowercase().endsWith(".png")) ".png" else ".jpg"
                val finalFileName = if (originalName.contains(".")) originalName else "bukti_izin$ext"
                val tempFile = File(cacheDir, "temp_upload_$finalFileName")
                
                val inputStream = contentResolver.openInputStream(selectedFileUri!!)
                val outputStream = FileOutputStream(tempFile)
                inputStream?.copyTo(outputStream)
                outputStream.close()
                inputStream?.close()

                val actualMediaType = (if (isPdf) "application/pdf" else if (ext == ".png") "image/png" else "image/jpeg").toMediaTypeOrNull()
                val requestFile = tempFile.asRequestBody(actualMediaType)
                filePart = MultipartBody.Part.createFormData("file_bukti", finalFileName, requestFile)
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }

        val nip = getSharedPreferences("PresensiPolmanApp", MODE_PRIVATE).getString("saved_nip", "") ?: ""
        val nipManualReq = nip.toRequestBody("text/plain".toMediaTypeOrNull())

        val keteranganReq = edtKeterangan.text.toString().toRequestBody("text/plain".toMediaTypeOrNull())

        NetworkClient.apiService.submitIzin(kategoriReq, subKatReq, slotWaktuReq, tglMulaiReq, tglSelesaiReq, keteranganReq, nipManualReq, filePart).enqueue(object : Callback<BaseResponse> {
            override fun onResponse(call: Call<BaseResponse>, response: Response<BaseResponse>) {
                btnSubmit.isEnabled = true
                btnSubmit.text = "AJUKAN SEKARANG"
                if (response.isSuccessful && response.body()?.status == "success") {
                    val msg = response.body()?.message ?: "Pengajuan Berhasil Dikirim!"
                    val parsedMsg = androidx.core.text.HtmlCompat.fromHtml(msg, androidx.core.text.HtmlCompat.FROM_HTML_MODE_LEGACY)
                    android.app.AlertDialog.Builder(this@IzinActivity)
                        .setTitle("Berhasil")
                        .setMessage(parsedMsg)
                        .setCancelable(false)
                        .setPositiveButton("Tutup") { _, _ ->
                            val notifTitle = response.body()?.notif_title ?: "Pengajuan Berhasil"
                            val notifContent = response.body()?.notif_content ?: "Data Izin/Sakit Anda telah terkirim."
                            com.polman.presensi.utils.NotificationHelper.showNotification(this@IzinActivity, notifTitle, notifContent)
                            finish()
                        }
                        .show()
                } else {
                    val msg = response.body()?.message ?: "Gagal mengirim pengajuan"
                    val parsedMsg = androidx.core.text.HtmlCompat.fromHtml(msg, androidx.core.text.HtmlCompat.FROM_HTML_MODE_LEGACY)
                    android.app.AlertDialog.Builder(this@IzinActivity)
                        .setTitle("Gagal")
                        .setMessage(parsedMsg)
                        .setPositiveButton("Tutup", null)
                        .show()
                }
            }

            override fun onFailure(call: Call<BaseResponse>, t: Throwable) {
                btnSubmit.isEnabled = true
                btnSubmit.text = "AJUKAN SEKARANG"
                Toast.makeText(this@IzinActivity, "Error: ${t.message}", Toast.LENGTH_SHORT).show()
            }
        })
    }
}

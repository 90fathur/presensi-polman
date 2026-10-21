package com.polman.presensi.ui.profile

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
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class ProfileActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        supportActionBar?.hide()
        setContentView(resources.getIdentifier("activity_profile", "layout", packageName))

        val btnBack = findViewById<ImageView>(resources.getIdentifier("btnBack", "id", packageName))
        btnBack.setOnClickListener { finish() }

        // Tangkap data dari Intent (dikirim oleh MainActivity)
        val idPegawai = intent.getStringExtra("ID_PEGAWAI") ?: ""
        val nama = intent.getStringExtra("NAMA") ?: "Pegawai Polman"
        val nip = intent.getStringExtra("NIP") ?: "-"
        val jabatan = intent.getStringExtra("JABATAN") ?: "Jabatan Fungsional"
        val opd = intent.getStringExtra("OPD") ?: "KANTOR PEMERINTAH"
        val phone = intent.getStringExtra("PHONE") ?: ""
        val hasFace = intent.getBooleanExtra("HAS_FACE", false)

        // Bind Kartu Identitas
        findViewById<TextView>(resources.getIdentifier("tvProfilNama", "id", packageName)).text = nama
        findViewById<TextView>(resources.getIdentifier("tvProfilNip", "id", packageName)).text = "NIP: $nip"
        findViewById<TextView>(resources.getIdentifier("tvProfilJabatan", "id", packageName)).text = jabatan
        findViewById<TextView>(resources.getIdentifier("tvProfilOpd", "id", packageName)).text = opd

        // Bind Biometric Status
        val tvBiometricStatus = findViewById<TextView>(resources.getIdentifier("tvBiometricStatus", "id", packageName))
        if (hasFace) {
            tvBiometricStatus.text = "✅ Sidik Wajah Telah Terdaftar"
            tvBiometricStatus.setTextColor(android.graphics.Color.parseColor("#2E7D32"))
        } else {
            tvBiometricStatus.text = "❌ Wajah Belum Didaftarkan"
            tvBiometricStatus.setTextColor(android.graphics.Color.parseColor("#C62828"))
        }

        // Bind Form Inputs
        val edtPhone = findViewById<EditText>(resources.getIdentifier("edtPhone", "id", packageName))
        val edtOldPassword = findViewById<EditText>(resources.getIdentifier("edtOldPassword", "id", packageName))
        val edtNewPassword = findViewById<EditText>(resources.getIdentifier("edtNewPassword", "id", packageName))
        val edtConfirmPassword = findViewById<EditText>(resources.getIdentifier("edtConfirmPassword", "id", packageName))
        val btnSimpanProfil = findViewById<Button>(resources.getIdentifier("btnSimpanProfil", "id", packageName))

        edtPhone.setText(phone)

        btnSimpanProfil.setOnClickListener {
            val phoneInput = edtPhone.text.toString().trim()
            val oldPass = edtOldPassword.text.toString()
            val newPass = edtNewPassword.text.toString()
            val confPass = edtConfirmPassword.text.toString()

            if (phoneInput.isEmpty()) {
                Toast.makeText(this, "Nomor HP tidak boleh kosong", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            if (newPass.isNotEmpty() || confPass.isNotEmpty()) {
                if (oldPass.isEmpty()) {
                    Toast.makeText(this, "Masukkan Sandi Lama untuk mengubah Sandi", Toast.LENGTH_SHORT).show()
                    return@setOnClickListener
                }
                if (newPass != confPass) {
                    Toast.makeText(this, "Konfirmasi Sandi Baru tidak cocok!", Toast.LENGTH_SHORT).show()
                    return@setOnClickListener
                }
                if (newPass.length < 6) {
                    Toast.makeText(this, "Sandi minimal 6 karakter", Toast.LENGTH_SHORT).show()
                    return@setOnClickListener
                }
            }

            btnSimpanProfil.isEnabled = false
            btnSimpanProfil.text = "Menyimpan..."

            NetworkClient.apiService.updateProfile(
                idPegawai, phoneInput, oldPass, newPass, confPass
            ).enqueue(object : Callback<BaseResponse> {
                override fun onResponse(call: Call<BaseResponse>, response: Response<BaseResponse>) {
                    btnSimpanProfil.isEnabled = true
                    btnSimpanProfil.text = "SIMPAN PERUBAHAN"

                    if (response.isSuccessful && response.body() != null) {
                        val body = response.body()!!
                        if (body.status == "success") {
                            Toast.makeText(this@ProfileActivity, body.message, Toast.LENGTH_LONG).show()
                            finish()
                        } else {
                            Toast.makeText(this@ProfileActivity, body.message, Toast.LENGTH_LONG).show()
                        }
                    } else {
                        Toast.makeText(this@ProfileActivity, "Terjadi kesalahan server.", Toast.LENGTH_SHORT).show()
                    }
                }

                override fun onFailure(call: Call<BaseResponse>, t: Throwable) {
                    btnSimpanProfil.isEnabled = true
                    btnSimpanProfil.text = "SIMPAN PERUBAHAN"
                    Toast.makeText(this@ProfileActivity, "Koneksi gagal: ${t.message}", Toast.LENGTH_SHORT).show()
                }
            })
        }

        // Bantuan Admin
        findViewById<TextView>(resources.getIdentifier("btnHubungiAdmin", "id", packageName)).setOnClickListener {
            val url = "https://wa.me/6281234567890?text=Halo%20Admin%20Presensi%20Polman,%20saya%20butuh%20bantuan."
            val i = Intent(Intent.ACTION_VIEW)
            i.data = Uri.parse(url)
            startActivity(i)
        }
    }
}

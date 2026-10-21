package com.polman.presensi.ui.auth

import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.EditText
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.polman.presensi.network.LoginResponse
import com.polman.presensi.network.NetworkClient
import com.polman.presensi.ui.main.MainActivity
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class LoginActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        supportActionBar?.hide()
        setContentView(resources.getIdentifier("activity_login", "layout", packageName))

        com.polman.presensi.utils.AutoUpdateChecker.checkUpdate(this)

        if (com.polman.presensi.utils.SecurityHelper.isEmulator()) {
            android.app.AlertDialog.Builder(this)
                .setTitle("Peringatan Keamanan")
                .setMessage("Aplikasi Presensi tidak dapat digunakan di Emulator. Harap gunakan perangkat Smartphone asli.")
                .setPositiveButton("Tutup") { _, _ -> finish() }
                .setCancelable(false)
                .show()
            return
        }

        if (com.polman.presensi.utils.SecurityHelper.isRooted()) {
            android.app.AlertDialog.Builder(this)
                .setTitle("Peringatan Keamanan")
                .setMessage("Aplikasi Presensi tidak dapat dijalankan di perangkat yang sudah di-Root karena alasan keamanan.")
                .setPositiveButton("Tutup") { _, _ -> finish() }
                .setCancelable(false)
                .show()
            return
        }

        val sharedPrefs = getSharedPreferences("PresensiPolmanApp", MODE_PRIVATE)
        val savedNip = sharedPrefs.getString("saved_nip", "")
        val savedPassword = sharedPrefs.getString("saved_password", "")

        val btnLogin = findViewById<Button>(resources.getIdentifier("btnLogin", "id", packageName))
        val edtNip = findViewById<EditText>(resources.getIdentifier("edtNip", "id", packageName))
        val edtPassword = findViewById<EditText>(resources.getIdentifier("edtPassword", "id", packageName))

        if (savedNip!!.isNotEmpty() && savedPassword!!.isNotEmpty()) {
            val intent = Intent(this@LoginActivity, MainActivity::class.java)
            startActivity(intent)
            finish()
            return
        }

        btnLogin.setOnClickListener {
            val nip = edtNip.text.toString().trim()
            val password = edtPassword.text.toString().trim()

            if (nip.isEmpty() || password.isEmpty()) {
                Toast.makeText(this, "NIP dan Password wajib diisi", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            performLogin(nip, password, btnLogin)
        }
    }

    private fun performLogin(nip: String, password: String, btnLogin: Button) {
        btnLogin.isEnabled = false
        btnLogin.text = "Loading..."

        // Memanggil API CodeIgniter
        NetworkClient.apiService.login(nip, password).enqueue(object : Callback<LoginResponse> {
            override fun onResponse(call: Call<LoginResponse>, response: Response<LoginResponse>) {
                btnLogin.isEnabled = true
                btnLogin.text = "Login"

                val body = response.body()
                if (response.isSuccessful && body != null) {
                    if (body.status == "success") {
                        val sharedPrefs = getSharedPreferences("PresensiPolmanApp", MODE_PRIVATE)
                        sharedPrefs.edit()
                            .putString("saved_nip", nip)
                            .putString("saved_password", password)
                            .apply()

                        Toast.makeText(this@LoginActivity, body.message, Toast.LENGTH_SHORT).show()
                        
                        // Karena kita pakai CookieJar, sesi otomatis tersimpan!
                        // Langsung pindah ke Beranda
                        val intent = Intent(this@LoginActivity, MainActivity::class.java)
                        startActivity(intent)
                        finish()
                    } else {
                        Toast.makeText(this@LoginActivity, body.message, Toast.LENGTH_LONG).show()
                    }
                } else {
                    Toast.makeText(this@LoginActivity, "Gagal terhubung ke server", Toast.LENGTH_SHORT).show()
                }
            }

            override fun onFailure(call: Call<LoginResponse>, t: Throwable) {
                btnLogin.isEnabled = true
                btnLogin.text = "Login"
                Toast.makeText(this@LoginActivity, "Error jaringan: ${t.message}", Toast.LENGTH_LONG).show()
            }
        })
    }
}

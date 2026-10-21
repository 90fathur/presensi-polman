package com.polman.presensi.utils

import android.app.AlertDialog
import android.app.DownloadManager
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.net.Uri
import android.os.Environment
import android.os.Handler
import android.os.Looper
import android.widget.Toast
import com.polman.presensi.BuildConfig
import java.io.BufferedReader
import java.io.InputStreamReader
import java.net.HttpURLConnection
import java.net.URL
import kotlin.concurrent.thread

object AutoUpdateChecker {
    private var downloadId: Long = -1

    fun checkUpdate(context: Context) {
        thread {
            try {
                val url = URL("https://presensi.polmankab.go.id/version.txt")
                val connection = url.openConnection() as HttpURLConnection
                connection.requestMethod = "GET"
                connection.connectTimeout = 5000
                connection.readTimeout = 5000

                if (connection.responseCode == HttpURLConnection.HTTP_OK) {
                    val reader = BufferedReader(InputStreamReader(connection.inputStream))
                    val remoteVersionStr = reader.readLine()?.trim() ?: "0"
                    reader.close()

                    val remoteVersion = remoteVersionStr.toIntOrNull() ?: 0
                    val currentVersion = BuildConfig.VERSION_CODE

                    if (remoteVersion > currentVersion) {
                        Handler(Looper.getMainLooper()).post {
                            showUpdateDialog(context)
                        }
                    }
                }
                connection.disconnect()
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }
    }

    private fun showUpdateDialog(context: Context) {
        if (context is android.app.Activity) {
            if (context.isFinishing || context.isDestroyed) {
                return // Prevent BadTokenException if Activity is already closed
            }
        }

        AlertDialog.Builder(context)
            .setTitle("Update Tersedia")
            .setMessage("Versi terbaru Aplikasi Presensi telah tersedia. Harap segera perbarui aplikasi Anda untuk terus menggunakan layanan.")
            .setCancelable(false)
            .setPositiveButton("Download & Update") { _, _ ->
                startDownload(context)
            }
            .show()
    }

    private fun startDownload(context: Context) {
        val url = "https://presensi.polmankab.go.id/download/presensinew.apk"
        val request = DownloadManager.Request(Uri.parse(url))
            .setTitle("Mengunduh Update Presensi")
            .setDescription("Mohon tunggu, sedang mendownload versi terbaru...")
            .setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
            .setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, "presensinew.apk")
            .setAllowedOverMetered(true)
            .setAllowedOverRoaming(true)

        val downloadManager = context.getSystemService(Context.DOWNLOAD_SERVICE) as DownloadManager
        downloadId = downloadManager.enqueue(request)
        Toast.makeText(context, "Download Update Dimulai... (Lihat Notifikasi)", Toast.LENGTH_LONG).show()

        val onComplete = object : BroadcastReceiver() {
            override fun onReceive(ctxt: Context, intent: Intent) {
                val id = intent.getLongExtra(DownloadManager.EXTRA_DOWNLOAD_ID, -1)
                if (id == downloadId) {
                    installApk(ctxt, downloadManager, downloadId)
                    ctxt.unregisterReceiver(this)
                }
            }
        }
        
        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.TIRAMISU) {
            context.registerReceiver(onComplete, IntentFilter(DownloadManager.ACTION_DOWNLOAD_COMPLETE), Context.RECEIVER_EXPORTED)
        } else {
            context.registerReceiver(onComplete, IntentFilter(DownloadManager.ACTION_DOWNLOAD_COMPLETE))
        }
    }

    private fun installApk(context: Context, downloadManager: DownloadManager, downloadId: Long) {
        try {
            val uri = downloadManager.getUriForDownloadedFile(downloadId)
            if (uri != null) {
                val intent = Intent(Intent.ACTION_VIEW)
                intent.setDataAndType(uri, "application/vnd.android.package-archive")
                intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_GRANT_READ_URI_PERMISSION
                context.startActivity(intent)
            } else {
                Toast.makeText(context, "Gagal membuka file update.", Toast.LENGTH_SHORT).show()
            }
        } catch (e: Exception) {
            e.printStackTrace()
            Toast.makeText(context, "Gagal install update.", Toast.LENGTH_SHORT).show()
        }
    }
}

package com.polman.presensi.utils

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.os.Build
import androidx.core.app.NotificationCompat
import com.polman.presensi.ui.main.MainActivity

object NotificationHelper {

    private const val CHANNEL_ID = "PRESENSI_POLMAN_HEADSUP_V1"
    private const val CHANNEL_NAME = "Aktivitas Presensi (Penting)"
    private const val CHANNEL_DESC = "Notifikasi pop-up dari atas layar"

    fun showNotification(context: Context, title: String, message: String) {
        try {
            val notificationManager = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager

            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                val importance = NotificationManager.IMPORTANCE_HIGH
                val channel = NotificationChannel(CHANNEL_ID, CHANNEL_NAME, importance).apply {
                    description = CHANNEL_DESC
                    enableVibration(true)
                    vibrationPattern = longArrayOf(0, 500, 200, 500)
                }
                notificationManager.createNotificationChannel(channel)
            }

            val intent = Intent(context, MainActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
            }
            val pendingIntent: PendingIntent = PendingIntent.getActivity(
                context, 0, intent,
                PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT
            )

            val builder = NotificationCompat.Builder(context, CHANNEL_ID)
                .setSmallIcon(android.R.drawable.ic_dialog_info)
                .setContentTitle(title)
                .setContentText(message)
                .setStyle(NotificationCompat.BigTextStyle().bigText(message))
                .setPriority(NotificationCompat.PRIORITY_MAX) // Force MAX priority for Heads-Up
                .setDefaults(NotificationCompat.DEFAULT_ALL) // Force Sound & Vibrate
                .setVibrate(longArrayOf(0, 500, 200, 500))
                .setContentIntent(pendingIntent)
                .setAutoCancel(true)

            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                if (androidx.core.app.ActivityCompat.checkSelfPermission(context, android.Manifest.permission.POST_NOTIFICATIONS) == android.content.pm.PackageManager.PERMISSION_GRANTED) {
                    notificationManager.notify(System.currentTimeMillis().toInt(), builder.build())
                } else {
                    android.util.Log.e("NotificationHelper", "Permission POST_NOTIFICATIONS not granted")
                }
            } else {
                notificationManager.notify(System.currentTimeMillis().toInt(), builder.build())
            }
        } catch (e: Exception) {
            android.util.Log.e("NotificationHelper", "Error showing notification", e)
            e.printStackTrace()
        }
    }
}

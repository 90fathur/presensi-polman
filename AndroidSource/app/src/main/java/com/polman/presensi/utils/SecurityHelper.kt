package com.polman.presensi.utils

import android.location.Location
import android.os.Build

object SecurityHelper {

    /**
     * Mengecek apakah lokasi yang dikirim merupakan hasil dari aplikasi Fake GPS
     */
    fun isMockLocation(location: Location?): Boolean {
        if (location == null) return false
        
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            location.isMock
        } else {
            @Suppress("DEPRECATION")
            location.isFromMockProvider
        }
    }

    /**
     * Mengecek apakah aplikasi dijalankan di Emulator (Nox, Bluestacks, Android Studio, dll)
     */
    fun isEmulator(): Boolean {
        val fingerprint = Build.FINGERPRINT ?: ""
        val model = Build.MODEL ?: ""
        val manufacturer = Build.MANUFACTURER ?: ""
        val brand = Build.BRAND ?: ""
        val device = Build.DEVICE ?: ""
        val product = Build.PRODUCT ?: ""
        val hardware = Build.HARDWARE ?: ""

        var isEmulator = (fingerprint.startsWith("generic")
                || fingerprint.startsWith("unknown")
                || model.contains("google_sdk")
                || model.contains("Emulator")
                || model.contains("Android SDK built for x86")
                || manufacturer.contains("Genymotion")
                || brand.startsWith("generic") && device.startsWith("generic")
                || "google_sdk" == product)

        // Deteksi hardware khas emulator
        if (hardware.contains("goldfish") || hardware.contains("vbox86") || hardware.contains("ranchu") || hardware.contains("nox")) {
            isEmulator = true
        }

        return isEmulator
    }
    /**
     * Mengecek apakah device sudah di-Root
     */
    fun isRooted(): Boolean {
        return checkRootMethod1() || checkRootMethod2() || checkRootMethod3()
    }

    private fun checkRootMethod1(): Boolean {
        val buildTags = Build.TAGS
        return buildTags != null && buildTags.contains("test-keys")
    }

    private fun checkRootMethod2(): Boolean {
        val paths = arrayOf(
            "/system/app/Superuser.apk", "/sbin/su", "/system/bin/su", "/system/xbin/su",
            "/data/local/xbin/su", "/data/local/bin/su", "/system/sd/xbin/su",
            "/system/bin/failsafe/su", "/data/local/su", "/su/bin/su", "/su/xbin/su",
            "/magisk/.core/bin/su", "/system/usr/we-need-root/su"
        )
        for (path in paths) {
            if (java.io.File(path).exists()) return true
        }
        return false
    }

    private fun checkRootMethod3(): Boolean {
        var process: Process? = null
        return try {
            process = Runtime.getRuntime().exec(arrayOf("/system/xbin/which", "su"))
            val `in` = java.io.BufferedReader(java.io.InputStreamReader(process.inputStream))
            `in`.readLine() != null
        } catch (t: Throwable) {
            false
        } finally {
            process?.destroy()
        }
    }
}

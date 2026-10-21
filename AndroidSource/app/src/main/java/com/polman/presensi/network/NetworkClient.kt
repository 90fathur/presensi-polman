package com.polman.presensi.network

import android.content.Context
import com.polman.presensi.MyApp
import okhttp3.Cookie
import okhttp3.CookieJar
import okhttp3.HttpUrl
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

class PersistentCookieJar(context: Context) : CookieJar {
    private val prefs = context.getSharedPreferences("CookiePrefs", Context.MODE_PRIVATE)

    override fun saveFromResponse(url: HttpUrl, cookies: List<Cookie>) {
        val editor = prefs.edit()
        for (cookie in cookies) {
            editor.putString(cookie.name, cookie.toString())
        }
        editor.apply()
    }

    override fun loadForRequest(url: HttpUrl): List<Cookie> {
        val cookies = mutableListOf<Cookie>()
        for ((_, value) in prefs.all) {
            if (value is String) {
                Cookie.parse(url, value)?.let { cookies.add(it) }
            }
        }
        return cookies
    }
}

object NetworkClient {
    private const val BASE_URL = "https://presensi.polmankab.go.id/"

    val apiService: ApiService by lazy {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }

        val client = OkHttpClient.Builder()
            .addInterceptor(logging)
            .cookieJar(PersistentCookieJar(MyApp.appContext))
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .build()

        Retrofit.Builder()
            .baseUrl(BASE_URL)
            .addConverterFactory(GsonConverterFactory.create())
            .client(client)
            .build()
            .create(ApiService::class.java)
    }
}

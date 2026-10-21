package com.polman.presensi.network

import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.Call
import retrofit2.http.Field
import retrofit2.http.FormUrlEncoded
import retrofit2.http.GET
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.Part

// Base Response Template
data class BaseResponse(
    val status: String,
    val message: String? = null,
    val notif_title: String? = null,
    val notif_content: String? = null
)

// Login Response
data class LoginResponse(
    val status: String,
    val message: String,
    val data: PegawaiData?,
    val session_id: String?
)

data class PegawaiData(
    val id_pegawai: String,
    val nip: String,
    val nama_lengkap: String,
    val id_opd: String,
    val is_bercadar: String?
)

interface ApiService {

    @FormUrlEncoded
    @POST("api/login")
    fun login(
        @Field("username") username: String,
        @Field("password") password: String
    ): Call<LoginResponse>

    @POST("api/dashboard")
    fun getDashboard(
        @retrofit2.http.Query("bulan") bulan: String? = null,
        @retrofit2.http.Query("tahun") tahun: String? = null
    ): Call<Any> // Nanti diubah ke model Data Class yang sesuai

    @FormUrlEncoded
    @POST("api/submit_absen")
    fun submitAbsen(
        @Field("latitude") lat: Double,
        @Field("longitude") lng: Double,
        @Field("device_info") device_info: String,
        @Field("nip") nip: String,
        @Field("confirm_pulang") confirm_pulang: String? = null,
        @Field("confirm_double") confirm_double: String? = null
    ): Call<BaseResponse>

    @FormUrlEncoded
    @POST("api/submit_absen_apel")
    fun submitAbsenApel(
        @Field("latitude") lat: Double,
        @Field("longitude") lng: Double,
        @Field("device_info") device_info: String,
        @Field("nip") nip: String,
        @Field("id_apel") id_apel: String
    ): Call<BaseResponse>

    @FormUrlEncoded
    @POST("api/submit_cito")
    fun submitCito(
        @Field("latitude") lat: Double,
        @Field("longitude") lng: Double,
        @Field("device_info") device_info: String,
        @Field("nip") nip: String,
        @Field("keterangan") keterangan: String
    ): Call<BaseResponse>

    @FormUrlEncoded
    @POST("pwa/update_profile")
    fun updateProfile(
        @Field("id_pegawai") idPegawai: String,
        @Field("nomor_hp") nomorHp: String,
        @Field("lama") passwordLama: String,
        @Field("baru") passwordBaru: String,
        @Field("konfirmasi") konfirmasi: String
    ): Call<BaseResponse>

    // Untuk Izin / Sakit / Tugas Luar dengan upload file PDF/Image
    @Multipart
    @POST("api/submit_izin")
    fun submitIzin(
        @Part("kategori") kategori: RequestBody,
        @Part("sub_kategori") subKategori: RequestBody?,
        @Part("slot_waktu") slotWaktu: RequestBody,
        @Part("tgl_mulai") tglMulai: RequestBody,
        @Part("tgl_selesai") tglSelesai: RequestBody,
        @Part("keterangan") keterangan: RequestBody,
        @Part("nip_manual") nipManual: RequestBody,
        @Part file_bukti: MultipartBody.Part?
    ): Call<BaseResponse>
    
    @GET("api/logout")
    fun logout(): Call<BaseResponse>

    @FormUrlEncoded
    @POST("api/update_fcm_token")
    fun updateFcmToken(
        @Field("nip") nip: String,
        @Field("token") token: String
    ): Call<BaseResponse>
}

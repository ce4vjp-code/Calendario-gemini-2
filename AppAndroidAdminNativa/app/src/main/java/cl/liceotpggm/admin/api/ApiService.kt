package cl.liceotpggm.admin.api

import cl.liceotpggm.admin.models.*
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {

    @POST("api/login.php")
    suspend fun login(@Body request: LoginRequest): Response<LoginResponse>

    @POST("api/verify_2fa.php")
    suspend fun verify2Fa(@Body request: Verify2FaRequest): Response<LoginResponse>

    @GET("api/check_session.php")
    suspend fun checkSession(): Response<SessionResponse>

    @GET("api/admin_get_usuarios.php")
    suspend fun getUsers(): Response<UsersResponse>

    @POST("api/admin_delete_usuario.php")
    suspend fun deleteUser(@Body request: DeleteUserRequest): Response<BaseResponse>

    @POST("api/admin_edit_email.php")
    suspend fun editEmail(@Body request: EditEmailRequest): Response<BaseResponse>

    @POST("api/admin_reset_clave.php")
    suspend fun resetPassword(@Body request: ResetPasswordRequest): Response<ResetPasswordResponse>

    @GET("api/admin_get_logs.php")
    suspend fun getLogs(@Query("fecha") fecha: String): Response<LogsResponse>
    
    @POST("api/admin_invitacion.php")
    suspend fun sendInvite(@Body request: InviteRequest): Response<InviteResponse>
}

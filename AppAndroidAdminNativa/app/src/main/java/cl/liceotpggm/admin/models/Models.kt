package cl.liceotpggm.admin.models

import com.google.gson.annotations.SerializedName

data class LoginRequest(
    val rut: String,
    val clave: String
)

data class Verify2FaRequest(
    @SerializedName("token_2fa") val token2fa: String,
    val code: String
)

data class LoginResponse(
    val success: Boolean?,
    val error: String?,
    @SerializedName("require_2fa") val require2fa: Boolean?,
    @SerializedName("token_2fa") val token2fa: String?,
    val user: UserDto?
)

data class SessionResponse(
    val authenticated: Boolean,
    val user: UserDto?
)

data class UserDto(
    val nombre: String,
    val rol: String
)

data class User(
    val id: Int,
    val rut: String,
    val nombre: String,
    val rol: String,
    val email: String?
)

data class UsersResponse(
    val success: Boolean,
    val error: String?,
    @SerializedName("usuarios") val users: List<User>?
)

data class DeleteUserRequest(
    val id: Int
)

data class EditEmailRequest(
    val id: Int,
    val email: String
)

data class ResetPasswordRequest(
    val id: Int
)

data class ResetPasswordResponse(
    val success: Boolean,
    val message: String?,
    @SerializedName("nueva_clave") val nuevaClave: String?,
    val mailEnviado: Boolean?,
    val error: String?
)

data class BaseResponse(
    val success: Boolean,
    val error: String?,
    val message: String?
)

data class InviteRequest(
    val email: String
)

data class InviteResponse(
    val success: Boolean,
    val codigo: String?,
    val mailEnviado: Boolean?,
    val message: String?,
    val error: String?
)

data class LogActivity(
    @SerializedName("fecha_hora") val fechaHora: String,
    @SerializedName("usuario_nombre") val usuarioNombre: String,
    @SerializedName("usuario_rut") val usuarioRut: String,
    val modulo: String,
    val accion: String,
    val detalles: String,
    @SerializedName("ip_address") val ipAddress: String
)

data class LogLogin(
    @SerializedName("fecha_hora") val fechaHora: String,
    @SerializedName("rut_ingresado") val rutIngresado: String,
    @SerializedName("nombre_usuario") val nombreUsuario: String?,
    val estado: String,
    val navegador: String?,
    val dispositivo: String?,
    @SerializedName("ip_address") val ipAddress: String
)

data class LogsResponse(
    val success: Boolean,
    val error: String?,
    val actividades: List<LogActivity>?,
    val ingresos: List<LogLogin>?
)

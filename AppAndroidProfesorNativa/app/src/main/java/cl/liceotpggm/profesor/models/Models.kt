package cl.liceotpggm.profesor.models

import com.google.gson.annotations.SerializedName

data class LoginRequest(
    val rut: String,
    val clave: String
)

data class LoginResponse(
    val success: Boolean?,
    val error: String?,
    val user: UserDto?
)

data class SessionResponse(
    val authenticated: Boolean,
    val user: UserDto?
)

data class UserDto(
    val nombre: String,
    val rol: String,
    val id: Int?,
    @SerializedName("asignaturas_asignadas") val asignaturasAsignadas: List<String>?
)

data class Evaluacion(
    val id: Int,
    val asignatura: String,
    val curso: String,
    val profesor: String,
    val fecha: String,
    val hora: String,
    val tipo: String,
    val observaciones: String?
)

data class EvaluacionesResponse(
    val success: Boolean,
    val error: String?,
    val evaluaciones: List<Evaluacion>?
)

data class EvaluacionRequest(
    val id: Int? = null,
    val asignatura: String,
    val curso: String,
    val fecha: String,
    val hora: String,
    val observaciones: String,
    val tipo: String
)

data class DeleteEvaluacionRequest(
    val id: Int
)

data class BaseResponse(
    val success: Boolean,
    val error: String?,
    val message: String?
)

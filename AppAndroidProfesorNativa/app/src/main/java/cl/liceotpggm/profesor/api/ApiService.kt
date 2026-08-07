package cl.liceotpggm.profesor.api

import cl.liceotpggm.profesor.models.*
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {

    @POST("api/login.php")
    suspend fun login(@Body request: LoginRequest): Response<LoginResponse>

    @GET("api/check_session.php")
    suspend fun checkSession(): Response<SessionResponse>

    @GET("api/profesor_get_evaluaciones.php")
    suspend fun getEvaluaciones(): Response<EvaluacionesResponse>

    @POST("api/add_evaluacion.php")
    suspend fun addEvaluacion(@Body request: EvaluacionRequest): Response<BaseResponse>

    @POST("api/edit_evaluacion.php")
    suspend fun editEvaluacion(@Body request: EvaluacionRequest): Response<BaseResponse>

    @POST("api/delete_evaluacion.php")
    suspend fun deleteEvaluacion(@Body request: DeleteEvaluacionRequest): Response<BaseResponse>
}

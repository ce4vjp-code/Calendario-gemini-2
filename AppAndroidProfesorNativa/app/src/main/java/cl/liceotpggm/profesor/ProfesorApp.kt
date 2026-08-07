package cl.liceotpggm.profesor

import android.app.Application
import android.content.Context
import android.content.Intent

class ProfesorApp : Application() {

    override fun onCreate() {
        super.onCreate()
        appContext = applicationContext
    }

    companion object {
        lateinit var appContext: Context
            private set
            
        var asignaturasAsignadas: List<String> = emptyList()
            
        fun forceLogout() {
            // Limpiar cookies en memoria de Retrofit
            cl.liceotpggm.profesor.api.ApiClient.clearCookies()
            
            // Limpiar cookies
            val sharedPreferences = appContext.getSharedPreferences("CookiePrefs", Context.MODE_PRIVATE)
            sharedPreferences.edit().clear().apply()
            
            // Redirigir al Login
            val intent = Intent(appContext, LoginActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                putExtra("SESSION_EXPIRED", true)
            }
            appContext.startActivity(intent)
        }
    }
}

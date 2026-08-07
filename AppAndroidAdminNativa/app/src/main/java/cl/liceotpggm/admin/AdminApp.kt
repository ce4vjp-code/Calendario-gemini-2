package cl.liceotpggm.admin

import android.app.Application
import android.content.Context
import android.content.Intent

class AdminApp : Application() {

    override fun onCreate() {
        super.onCreate()
        appContext = applicationContext
    }

    companion object {
        lateinit var appContext: Context
            private set
            
        fun forceLogout() {
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

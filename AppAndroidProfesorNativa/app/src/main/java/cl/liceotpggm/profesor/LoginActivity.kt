package cl.liceotpggm.profesor

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.ProgressBar
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import cl.liceotpggm.profesor.api.ApiClient
import cl.liceotpggm.profesor.models.LoginRequest
import com.google.android.material.textfield.TextInputEditText
import com.google.android.material.textfield.TextInputLayout
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class LoginActivity : AppCompatActivity() {

    private lateinit var etRut: TextInputEditText
    private lateinit var etClave: TextInputEditText
    private lateinit var tilRut: TextInputLayout
    private lateinit var tilClave: TextInputLayout
    private lateinit var btnLogin: Button
    private lateinit var progressBar: ProgressBar

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_login)

        if (intent.getBooleanExtra("SESSION_EXPIRED", false)) {
            Toast.makeText(this, "Tu sesión ha expirado o fue cerrada por seguridad.", Toast.LENGTH_LONG).show()
        }

        etRut = findViewById(R.id.etRut)
        etClave = findViewById(R.id.etClave)
        tilRut = findViewById(R.id.tilRut)
        tilClave = findViewById(R.id.tilClave)
        btnLogin = findViewById(R.id.btnLogin)
        progressBar = findViewById(R.id.progressBar)
        
        // Hide 2FA field since it's only for admins. It exists in the layout but we won't use it.
        val til2Fa = findViewById<TextInputLayout>(R.id.til2Fa)
        til2Fa.visibility = View.GONE

        btnLogin.setOnClickListener {
            performLogin()
        }

        checkExistingSession()
    }

    private fun checkExistingSession() {
        showLoading(true)
        lifecycleScope.launch {
            try {
                val response = withContext(Dispatchers.IO) {
                    ApiClient.apiService.checkSession()
                }
                if (response.isSuccessful && response.body()?.authenticated == true) {
                    if (response.body()?.user?.rol == "profesor") {
                        goToMain()
                    }
                }
            } catch (e: Exception) {
                // Ignore, just stay on login
            } finally {
                showLoading(false)
            }
        }
    }

    private fun performLogin() {
        val rut = etRut.text.toString().trim()
        val clave = etClave.text.toString()

        if (rut.isEmpty() || clave.isEmpty()) {
            Toast.makeText(this, "RUT y Clave son requeridos", Toast.LENGTH_SHORT).show()
            return
        }

        showLoading(true)
        lifecycleScope.launch {
            try {
                val response = withContext(Dispatchers.IO) {
                    ApiClient.apiService.login(LoginRequest(rut, clave))
                }
                
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body?.success == true) {
                        if (body.user?.rol == "profesor") {
                            goToMain()
                        } else {
                            Toast.makeText(this@LoginActivity, "Acceso denegado. App exclusiva para profesores.", Toast.LENGTH_LONG).show()
                        }
                    } else {
                        Toast.makeText(this@LoginActivity, body?.error ?: "Credenciales incorrectas", Toast.LENGTH_SHORT).show()
                    }
                } else {
                    Toast.makeText(this@LoginActivity, "Error del servidor", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@LoginActivity, "Error de red: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                showLoading(false)
            }
        }
    }

    private fun goToMain() {
        startActivity(Intent(this, MainActivity::class.java))
        finish()
    }

    private fun showLoading(isLoading: Boolean) {
        progressBar.visibility = if (isLoading) View.VISIBLE else View.GONE
        btnLogin.isEnabled = !isLoading
    }
}

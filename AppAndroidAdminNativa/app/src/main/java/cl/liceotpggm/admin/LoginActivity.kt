package cl.liceotpggm.admin

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.ProgressBar
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import cl.liceotpggm.admin.api.ApiClient
import cl.liceotpggm.admin.models.LoginRequest
import cl.liceotpggm.admin.models.Verify2FaRequest
import com.google.android.material.textfield.TextInputEditText
import com.google.android.material.textfield.TextInputLayout
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class LoginActivity : AppCompatActivity() {

    private lateinit var etRut: TextInputEditText
    private lateinit var etClave: TextInputEditText
    private lateinit var et2Fa: TextInputEditText
    private lateinit var til2Fa: TextInputLayout
    private lateinit var tilRut: TextInputLayout
    private lateinit var tilClave: TextInputLayout
    private lateinit var btnLogin: Button
    private lateinit var progressBar: ProgressBar

    private var token2Fa: String? = null
    private var isWaitingFor2Fa = false

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_login)

        if (intent.getBooleanExtra("SESSION_EXPIRED", false)) {
            Toast.makeText(this, "Tu sesión ha expirado o fue cerrada por seguridad.", Toast.LENGTH_LONG).show()
        }

        etRut = findViewById(R.id.etRut)
        etClave = findViewById(R.id.etClave)
        et2Fa = findViewById(R.id.et2Fa)
        til2Fa = findViewById(R.id.til2Fa)
        tilRut = findViewById(R.id.tilRut)
        tilClave = findViewById(R.id.tilClave)
        btnLogin = findViewById(R.id.btnLogin)
        progressBar = findViewById(R.id.progressBar)

        btnLogin.setOnClickListener {
            if (isWaitingFor2Fa) {
                verify2Fa()
            } else {
                performLogin()
            }
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
                    if (response.body()?.user?.rol == "admin") {
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
                    if (body?.require2fa == true) {
                        token2Fa = body.token2fa
                        isWaitingFor2Fa = true
                        til2Fa.visibility = View.VISIBLE
                        tilRut.visibility = View.GONE
                        tilClave.visibility = View.GONE
                        btnLogin.text = "Verificar Código"
                        Toast.makeText(this@LoginActivity, "Ingresa el código 2FA", Toast.LENGTH_SHORT).show()
                    } else if (body?.success == true) {
                        if (body.user?.rol == "admin") {
                            goToMain()
                        } else {
                            Toast.makeText(this@LoginActivity, "Acceso denegado. Solo administradores.", Toast.LENGTH_LONG).show()
                        }
                    }
                } else {
                    Toast.makeText(this@LoginActivity, "Credenciales incorrectas", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@LoginActivity, "Error de red: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                showLoading(false)
            }
        }
    }

    private fun verify2Fa() {
        val code = et2Fa.text.toString().trim()
        if (code.isEmpty() || token2Fa == null) {
            Toast.makeText(this, "Ingresa el código", Toast.LENGTH_SHORT).show()
            return
        }

        showLoading(true)
        lifecycleScope.launch {
            try {
                val response = withContext(Dispatchers.IO) {
                    ApiClient.apiService.verify2Fa(Verify2FaRequest(token2Fa!!, code))
                }
                
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body?.success == true && body.user?.rol == "admin") {
                        goToMain()
                    } else {
                        Toast.makeText(this@LoginActivity, "Acceso denegado.", Toast.LENGTH_LONG).show()
                    }
                } else {
                    Toast.makeText(this@LoginActivity, "Código 2FA incorrecto", Toast.LENGTH_SHORT).show()
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

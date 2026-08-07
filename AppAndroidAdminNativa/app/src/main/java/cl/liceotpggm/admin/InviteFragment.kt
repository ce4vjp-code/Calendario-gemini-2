package cl.liceotpggm.admin

import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import cl.liceotpggm.admin.api.ApiClient
import cl.liceotpggm.admin.models.InviteRequest
import com.google.android.material.card.MaterialCardView
import com.google.android.material.textfield.TextInputEditText
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class InviteFragment : Fragment(R.layout.fragment_invite) {

    private lateinit var etEmail: TextInputEditText
    private lateinit var btnSend: Button
    private lateinit var progressBar: ProgressBar
    private lateinit var cardResult: MaterialCardView
    private lateinit var tvCode: TextView
    private lateinit var tvEmailStatus: TextView

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        etEmail = view.findViewById(R.id.etEmail)
        btnSend = view.findViewById(R.id.btnSend)
        progressBar = view.findViewById(R.id.progressBar)
        cardResult = view.findViewById(R.id.cardResult)
        tvCode = view.findViewById(R.id.tvCode)
        tvEmailStatus = view.findViewById(R.id.tvEmailStatus)

        btnSend.setOnClickListener {
            val email = etEmail.text.toString().trim()
            if (email.isNotEmpty()) {
                sendInvite(email)
            } else {
                Toast.makeText(requireContext(), "Ingresa un correo", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun sendInvite(email: String) {
        progressBar.visibility = View.VISIBLE
        btnSend.isEnabled = false
        cardResult.visibility = View.GONE

        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = withContext(Dispatchers.IO) {
                    ApiClient.apiService.sendInvite(InviteRequest(email))
                }
                
                val body = response.body()
                if (response.isSuccessful && body?.success == true) {
                    cardResult.visibility = View.VISIBLE
                    tvCode.text = body.codigo
                    tvEmailStatus.text = body.message
                    etEmail.setText("")
                } else {
                    Toast.makeText(requireContext(), "Error: ${body?.error ?: "Desconocido"}", Toast.LENGTH_LONG).show()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error de red: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                progressBar.visibility = View.GONE
                btnSend.isEnabled = true
            }
        }
    }
}

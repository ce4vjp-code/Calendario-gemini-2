package cl.liceotpggm.admin

import android.os.Bundle
import android.view.View
import android.widget.ProgressBar
import android.widget.Toast
import androidx.appcompat.widget.Toolbar
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import cl.liceotpggm.admin.api.ApiClient
import cl.liceotpggm.admin.models.DeleteUserRequest
import cl.liceotpggm.admin.models.User
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class UsersFragment : Fragment(R.layout.fragment_users) {

    private lateinit var rvUsers: RecyclerView
    private lateinit var swipeRefresh: SwipeRefreshLayout
    private lateinit var progressBar: ProgressBar
    private lateinit var adapter: UsersAdapter

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val toolbar = view.findViewById<Toolbar>(R.id.toolbar)
        toolbar.title = "Usuarios"

        rvUsers = view.findViewById(R.id.rvUsers)
        swipeRefresh = view.findViewById(R.id.swipeRefresh)
        progressBar = view.findViewById(R.id.progressBar)

        rvUsers.layoutManager = LinearLayoutManager(requireContext())
        adapter = UsersAdapter { user, action ->
            when (action) {
                "edit" -> showEditEmailDialog(user)
                "reset" -> resetPassword(user)
                "delete" -> confirmDeleteUser(user)
            }
        }
        rvUsers.adapter = adapter

        swipeRefresh.setOnRefreshListener {
            loadUsers()
        }

        loadUsers()
    }

    private fun loadUsers() {
        if (!swipeRefresh.isRefreshing) {
            progressBar.visibility = View.VISIBLE
        }
        
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = withContext(Dispatchers.IO) {
                    ApiClient.apiService.getUsers()
                }
                if (response.isSuccessful && response.body()?.success == true) {
                    val users = response.body()?.users ?: emptyList()
                    val filtered = users.filter { it.rut != "admin" }
                    adapter.submitList(filtered)
                } else {
                    Toast.makeText(requireContext(), "Error al cargar usuarios", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error de red: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                progressBar.visibility = View.GONE
                swipeRefresh.isRefreshing = false
            }
        }
    }

    private fun showEditEmailDialog(user: User) {
        val input = android.widget.EditText(requireContext())
        input.setText(user.email ?: "")
        input.hint = "Nuevo correo electrónico"
        
        android.app.AlertDialog.Builder(requireContext())
            .setTitle("Editar Correo")
            .setMessage("Modifica el correo de ${user.nombre}:")
            .setView(input)
            .setPositiveButton("Guardar") { _, _ ->
                val newEmail = input.text.toString().trim()
                editEmail(user, newEmail)
            }
            .setNegativeButton("Cancelar", null)
            .show()
    }

    private fun editEmail(user: User, newEmail: String) {
        progressBar.visibility = View.VISIBLE
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = withContext(Dispatchers.IO) {
                    ApiClient.apiService.editEmail(cl.liceotpggm.admin.models.EditEmailRequest(user.id, newEmail))
                }
                if (response.isSuccessful && response.body()?.success == true) {
                    Toast.makeText(requireContext(), "Correo actualizado", Toast.LENGTH_SHORT).show()
                    loadUsers()
                } else {
                    Toast.makeText(requireContext(), "Error: ${response.body()?.error}", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error de red", Toast.LENGTH_SHORT).show()
            } finally {
                progressBar.visibility = View.GONE
            }
        }
    }

    private fun resetPassword(user: User) {
        progressBar.visibility = View.VISIBLE
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = withContext(Dispatchers.IO) {
                    ApiClient.apiService.resetPassword(cl.liceotpggm.admin.models.ResetPasswordRequest(user.id))
                }
                val body = response.body()
                if (response.isSuccessful && body?.success == true) {
                    android.app.AlertDialog.Builder(requireContext())
                        .setTitle("Contraseña Restablecida")
                        .setMessage("La nueva clave de ${user.nombre} es:\n\n${body.nuevaClave}\n\n${if (body.mailEnviado == true) "Se ha enviado un correo." else "Anota esta clave, el correo no se pudo enviar."}")
                        .setPositiveButton("OK", null)
                        .show()
                } else {
                    Toast.makeText(requireContext(), "Error: ${body?.error}", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error de red", Toast.LENGTH_SHORT).show()
            } finally {
                progressBar.visibility = View.GONE
            }
        }
    }

    private fun confirmDeleteUser(user: User) {
        android.app.AlertDialog.Builder(requireContext())
            .setTitle("Confirmar Eliminación")
            .setMessage("¿Estás seguro que deseas eliminar a ${user.nombre}? Esta acción no se puede deshacer.")
            .setPositiveButton("Eliminar") { _, _ ->
                deleteUser(user)
            }
            .setNegativeButton("Cancelar", null)
            .show()
    }

    private fun deleteUser(user: User) {
        progressBar.visibility = View.VISIBLE
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = withContext(Dispatchers.IO) {
                    ApiClient.apiService.deleteUser(DeleteUserRequest(user.id))
                }
                if (response.isSuccessful && response.body()?.success == true) {
                    Toast.makeText(requireContext(), "Usuario eliminado", Toast.LENGTH_SHORT).show()
                    loadUsers()
                } else {
                    Toast.makeText(requireContext(), "Error: ${response.body()?.error}", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error de red", Toast.LENGTH_SHORT).show()
            } finally {
                progressBar.visibility = View.GONE
            }
        }
    }
}

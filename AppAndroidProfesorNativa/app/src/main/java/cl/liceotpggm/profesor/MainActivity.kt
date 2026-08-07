package cl.liceotpggm.profesor

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import cl.liceotpggm.profesor.api.ApiClient
import cl.liceotpggm.profesor.models.DeleteEvaluacionRequest
import cl.liceotpggm.profesor.models.Evaluacion
import com.google.android.material.appbar.MaterialToolbar
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.google.android.material.floatingactionbutton.FloatingActionButton
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext

class MainActivity : AppCompatActivity() {

    private lateinit var swipeRefresh: SwipeRefreshLayout
    private lateinit var recyclerView: RecyclerView
    private lateinit var tvEmpty: TextView
    private lateinit var fabAdd: FloatingActionButton
    private lateinit var adapter: EvaluationsAdapter

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        val toolbar = findViewById<MaterialToolbar>(R.id.toolbar)
        toolbar.inflateMenu(R.menu.menu_main)
        toolbar.setOnMenuItemClickListener {
            when (it.itemId) {
                R.id.action_logout -> {
                    logout()
                    true
                }
                else -> false
            }
        }

        swipeRefresh = findViewById(R.id.swipeRefresh)
        recyclerView = findViewById(R.id.recyclerView)
        tvEmpty = findViewById(R.id.tvEmpty)
        fabAdd = findViewById(R.id.fabAdd)

        adapter = EvaluationsAdapter(mutableListOf(), ::onEditClicked, ::onDeleteClicked)
        recyclerView.layoutManager = LinearLayoutManager(this)
        recyclerView.adapter = adapter

        swipeRefresh.setOnRefreshListener {
            loadEvaluations()
        }

        fabAdd.setOnClickListener {
            // Open Add Dialog
            val dialog = EvaluationDialog(this, null) {
                loadEvaluations()
            }
            dialog.show()
        }
    }

    override fun onResume() {
        super.onResume()
        loadEvaluations()
    }

    private fun loadEvaluations() {
        swipeRefresh.isRefreshing = true
        lifecycleScope.launch {
            try {
                // Fetch asignaturas asignadas
                val sessionResponse = withContext(Dispatchers.IO) {
                    ApiClient.apiService.checkSession()
                }
                if (sessionResponse.isSuccessful && sessionResponse.body()?.authenticated == true) {
                    ProfesorApp.asignaturasAsignadas = sessionResponse.body()?.user?.asignaturasAsignadas ?: emptyList()
                }

                val response = withContext(Dispatchers.IO) {
                    ApiClient.apiService.getEvaluaciones()
                }
                
                if (response.isSuccessful && response.body()?.success == true) {
                    val evals = response.body()?.evaluaciones ?: emptyList()
                    adapter.updateData(evals)
                    tvEmpty.visibility = if (evals.isEmpty()) View.VISIBLE else View.GONE
                } else {
                    Toast.makeText(this@MainActivity, response.body()?.error ?: "Error al cargar", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@MainActivity, "Error de red: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                swipeRefresh.isRefreshing = false
            }
        }
    }

    private fun onEditClicked(evaluacion: Evaluacion) {
        val dialog = EvaluationDialog(this, evaluacion) {
            loadEvaluations()
        }
        dialog.show()
    }

    private fun onDeleteClicked(evaluacion: Evaluacion) {
        MaterialAlertDialogBuilder(this)
            .setTitle("Eliminar Evaluación")
            .setMessage("¿Estás seguro que deseas eliminar la evaluación de ${evaluacion.asignatura} para ${evaluacion.curso}?")
            .setPositiveButton("Eliminar") { _, _ ->
                deleteEvaluacion(evaluacion.id)
            }
            .setNegativeButton("Cancelar", null)
            .show()
    }

    private fun deleteEvaluacion(id: Int) {
        swipeRefresh.isRefreshing = true
        lifecycleScope.launch {
            try {
                val response = withContext(Dispatchers.IO) {
                    ApiClient.apiService.deleteEvaluacion(DeleteEvaluacionRequest(id))
                }
                
                if (response.isSuccessful && response.body()?.success == true) {
                    Toast.makeText(this@MainActivity, "Evaluación eliminada", Toast.LENGTH_SHORT).show()
                    loadEvaluations()
                } else {
                    Toast.makeText(this@MainActivity, response.body()?.error ?: "Error al eliminar", Toast.LENGTH_SHORT).show()
                    swipeRefresh.isRefreshing = false
                }
            } catch (e: Exception) {
                Toast.makeText(this@MainActivity, "Error de red: ${e.message}", Toast.LENGTH_SHORT).show()
                swipeRefresh.isRefreshing = false
            }
        }
    }

    private fun logout() {
        lifecycleScope.launch(Dispatchers.IO) {
            withContext(Dispatchers.Main) {
                ProfesorApp.forceLogout()
            }
        }
    }
}

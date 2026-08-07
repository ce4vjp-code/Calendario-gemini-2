package cl.liceotpggm.admin

import android.app.DatePickerDialog
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import cl.liceotpggm.admin.api.ApiClient
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale

class AuditFragment : Fragment(R.layout.fragment_audit) {

    private lateinit var rvLogs: RecyclerView
    private lateinit var progressBar: ProgressBar
    private lateinit var tvDate: TextView
    private lateinit var btnSelectDate: Button
    private lateinit var adapter: LogsAdapter

    private var currentDateStr = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault()).format(Date())

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        rvLogs = view.findViewById(R.id.rvLogs)
        progressBar = view.findViewById(R.id.progressBar)
        tvDate = view.findViewById(R.id.tvDate)
        btnSelectDate = view.findViewById(R.id.btnSelectDate)

        tvDate.text = currentDateStr

        rvLogs.layoutManager = LinearLayoutManager(requireContext())
        adapter = LogsAdapter()
        rvLogs.adapter = adapter

        btnSelectDate.setOnClickListener {
            showDatePicker()
        }

        loadLogs(currentDateStr)
    }

    private fun showDatePicker() {
        val calendar = Calendar.getInstance()
        val dateParts = currentDateStr.split("-")
        if (dateParts.size == 3) {
            calendar.set(dateParts[0].toInt(), dateParts[1].toInt() - 1, dateParts[2].toInt())
        }

        DatePickerDialog(requireContext(), { _, year, month, dayOfMonth ->
            currentDateStr = String.format(Locale.getDefault(), "%04d-%02d-%02d", year, month + 1, dayOfMonth)
            tvDate.text = currentDateStr
            loadLogs(currentDateStr)
        }, calendar.get(Calendar.YEAR), calendar.get(Calendar.MONTH), calendar.get(Calendar.DAY_OF_MONTH)).show()
    }

    private fun loadLogs(fecha: String) {
        progressBar.visibility = View.VISIBLE
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = withContext(Dispatchers.IO) {
                    ApiClient.apiService.getLogs(fecha)
                }
                if (response.isSuccessful && response.body()?.success == true) {
                    val body = response.body()!!
                    
                    val combinedLogs = mutableListOf<LogItem>()
                    body.ingresos?.forEach { combinedLogs.add(LogItem.Login(it)) }
                    body.actividades?.forEach { combinedLogs.add(LogItem.Activity(it)) }

                    // Sort by time descending
                    combinedLogs.sortByDescending { 
                        when(it) {
                            is LogItem.Login -> it.log.fechaHora
                            is LogItem.Activity -> it.log.fechaHora
                        }
                    }

                    adapter.submitList(combinedLogs)
                    
                    if (combinedLogs.isEmpty()) {
                        Toast.makeText(requireContext(), "No hay registros para este día", Toast.LENGTH_SHORT).show()
                    }
                } else {
                    Toast.makeText(requireContext(), "Error al cargar auditoría", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error de red: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                progressBar.visibility = View.GONE
            }
        }
    }
}

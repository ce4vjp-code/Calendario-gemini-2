package cl.liceotpggm.profesor

import android.app.DatePickerDialog
import android.app.Dialog
import android.content.Context
import android.os.Bundle
import android.widget.ArrayAdapter
import android.widget.AutoCompleteTextView
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import cl.liceotpggm.profesor.api.ApiClient
import cl.liceotpggm.profesor.models.Evaluacion
import cl.liceotpggm.profesor.models.EvaluacionRequest
import com.google.android.material.textfield.TextInputEditText
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.util.Calendar

class EvaluationDialog(
    context: Context,
    private val evaluacion: Evaluacion?,
    private val onSuccess: () -> Unit
) : Dialog(context) {

    private lateinit var etAsignatura: AutoCompleteTextView
    private lateinit var etCurso: AutoCompleteTextView
    private lateinit var etFecha: TextInputEditText
    private lateinit var etHora: AutoCompleteTextView
    private lateinit var etTipo: AutoCompleteTextView
    private lateinit var etObservaciones: TextInputEditText

    private val cursos = listOf(
        "1° Básico", "2° Básico", "3° Básico", "4° Básico",
        "5° Básico", "6° Básico", "7° Básico", "8° Básico",
        "1° Medio A", "1° Medio B", "2° Medio A", "2° Medio B",
        "3° Medio A - Telecomunicaciones", "3° Medio B - Muebles y Terminaciones en Madera", "3° Medio C - Atención de Enfermería",
        "4° Medio A - Telecomunicaciones", "4° Medio B - Muebles y Terminaciones en Madera", "4° Medio C - Atención de Enfermería"
    )

    private val tipos = listOf(
        "Prueba Escrita", "Trabajo Práctico", "Disertación", "Control de Lectura", "Otro"
    )

    private val horas = listOf(
        "08:00", "08:30", "09:00", "09:30", "10:00", "10:30",
        "11:00", "11:30", "12:00", "12:30", "13:00", "13:30",
        "14:00", "14:30", "15:00", "15:30", "16:00", "16:30", "17:00"
    )

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.dialog_evaluation)
        
        window?.setLayout(
            (context.resources.displayMetrics.widthPixels * 0.9).toInt(),
            android.view.ViewGroup.LayoutParams.WRAP_CONTENT
        )

        etAsignatura = findViewById(R.id.etAsignatura)
        etCurso = findViewById(R.id.etCurso)
        etFecha = findViewById(R.id.etFecha)
        etHora = findViewById(R.id.etHora)
        etTipo = findViewById(R.id.etTipo)
        etObservaciones = findViewById(R.id.etObservaciones)

        val tvTitle = findViewById<TextView>(R.id.tvDialogTitle)
        val btnSave = findViewById<Button>(R.id.btnSave)
        val btnCancel = findViewById<Button>(R.id.btnCancel)

        setupDropdowns()
        setupDatePicker()

        if (evaluacion != null) {
            tvTitle.text = "Editar Evaluación"
            etAsignatura.setText(evaluacion.asignatura, false)
            etCurso.setText(evaluacion.curso, false)
            etFecha.setText(evaluacion.fecha)
            etHora.setText(evaluacion.hora, false)
            etTipo.setText(evaluacion.tipo, false)
            etObservaciones.setText(evaluacion.observaciones)
        }

        btnCancel.setOnClickListener { dismiss() }
        btnSave.setOnClickListener { saveEvaluation() }
    }

    private fun setupDropdowns() {
        val asignaturas = ProfesorApp.asignaturasAsignadas.ifEmpty { listOf("No hay asignaturas asignadas") }
        
        val adapterAsignaturas = ArrayAdapter(context, android.R.layout.simple_dropdown_item_1line, asignaturas)
        etAsignatura.setAdapter(adapterAsignaturas)

        val adapterCursos = ArrayAdapter(context, android.R.layout.simple_dropdown_item_1line, cursos)
        etCurso.setAdapter(adapterCursos)

        val adapterTipos = ArrayAdapter(context, android.R.layout.simple_dropdown_item_1line, tipos)
        etTipo.setAdapter(adapterTipos)

        val adapterHoras = ArrayAdapter(context, android.R.layout.simple_dropdown_item_1line, horas)
        etHora.setAdapter(adapterHoras)
    }

    private fun setupDatePicker() {
        etFecha.setOnClickListener {
            val calendar = Calendar.getInstance()
            
            // Si ya hay una fecha, intentamos usarla como valor inicial
            val currentText = etFecha.text.toString()
            if (currentText.isNotEmpty() && currentText.contains("-")) {
                try {
                    val parts = currentText.split("-")
                    if (parts.size == 3) {
                        calendar.set(parts[0].toInt(), parts[1].toInt() - 1, parts[2].toInt())
                    }
                } catch (e: Exception) {
                    // Ignorar si el formato es inválido
                }
            }

            val datePickerDialog = DatePickerDialog(
                context,
                { _, year, month, dayOfMonth ->
                    val dateString = String.format("%04d-%02d-%02d", year, month + 1, dayOfMonth)
                    etFecha.setText(dateString)
                },
                calendar.get(Calendar.YEAR),
                calendar.get(Calendar.MONTH),
                calendar.get(Calendar.DAY_OF_MONTH)
            )
            datePickerDialog.show()
        }
    }

    private fun saveEvaluation() {
        val request = EvaluacionRequest(
            id = evaluacion?.id,
            asignatura = etAsignatura.text.toString().trim(),
            curso = etCurso.text.toString().trim(),
            fecha = etFecha.text.toString().trim(),
            hora = etHora.text.toString().trim(),
            tipo = etTipo.text.toString().trim(),
            observaciones = etObservaciones.text.toString().trim()
        )

        if (request.asignatura.isEmpty() || request.curso.isEmpty() || request.fecha.isEmpty() || request.hora.isEmpty() || request.tipo.isEmpty()) {
            Toast.makeText(context, "Por favor completa los campos obligatorios", Toast.LENGTH_SHORT).show()
            return
        }

        CoroutineScope(Dispatchers.Main).launch {
            try {
                val response = withContext(Dispatchers.IO) {
                    if (evaluacion == null) {
                        ApiClient.apiService.addEvaluacion(request)
                    } else {
                        ApiClient.apiService.editEvaluacion(request)
                    }
                }
                
                if (response.isSuccessful && response.body()?.success == true) {
                    Toast.makeText(context, "Guardado correctamente", Toast.LENGTH_SHORT).show()
                    onSuccess()
                    dismiss()
                } else {
                    Toast.makeText(context, response.body()?.error ?: "Error al guardar", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(context, "Error de red: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }
}

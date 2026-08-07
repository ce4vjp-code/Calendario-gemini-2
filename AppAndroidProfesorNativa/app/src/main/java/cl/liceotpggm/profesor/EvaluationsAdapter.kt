package cl.liceotpggm.profesor

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageButton
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import cl.liceotpggm.profesor.models.Evaluacion

class EvaluationsAdapter(
    private val evaluations: MutableList<Evaluacion>,
    private val onEdit: (Evaluacion) -> Unit,
    private val onDelete: (Evaluacion) -> Unit
) : RecyclerView.Adapter<EvaluationsAdapter.ViewHolder>() {

    class ViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        val tvAsignatura: TextView = view.findViewById(R.id.tvAsignatura)
        val tvCurso: TextView = view.findViewById(R.id.tvCurso)
        val tvFechaHora: TextView = view.findViewById(R.id.tvFechaHora)
        val tvTipo: TextView = view.findViewById(R.id.tvTipo)
        val btnEdit: ImageButton = view.findViewById(R.id.btnEdit)
        val btnDelete: ImageButton = view.findViewById(R.id.btnDelete)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_evaluation, parent, false)
        return ViewHolder(view)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val eval = evaluations[position]
        holder.tvAsignatura.text = eval.asignatura
        holder.tvCurso.text = eval.curso
        holder.tvFechaHora.text = "${eval.fecha} - ${eval.hora}"
        holder.tvTipo.text = eval.tipo

        holder.btnEdit.setOnClickListener { onEdit(eval) }
        holder.btnDelete.setOnClickListener { onDelete(eval) }
    }

    override fun getItemCount() = evaluations.size

    fun updateData(newEvaluations: List<Evaluacion>) {
        evaluations.clear()
        evaluations.addAll(newEvaluations)
        notifyDataSetChanged()
    }
}

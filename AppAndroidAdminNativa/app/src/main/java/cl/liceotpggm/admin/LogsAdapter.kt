package cl.liceotpggm.admin

import android.graphics.Color
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import cl.liceotpggm.admin.models.LogActivity
import cl.liceotpggm.admin.models.LogLogin

sealed class LogItem {
    data class Login(val log: LogLogin) : LogItem()
    data class Activity(val log: LogActivity) : LogItem()
}

class LogsAdapter : RecyclerView.Adapter<LogsAdapter.LogViewHolder>() {

    private var items = listOf<LogItem>()

    fun submitList(newItems: List<LogItem>) {
        items = newItems
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): LogViewHolder {
        val view = LayoutInflater.from(parent.context).inflate(R.layout.item_log, parent, false)
        return LogViewHolder(view)
    }

    override fun onBindViewHolder(holder: LogViewHolder, position: Int) {
        holder.bind(items[position])
    }

    override fun getItemCount() = items.size

    class LogViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val tvLogTime: TextView = itemView.findViewById(R.id.tvLogTime)
        private val tvLogType: TextView = itemView.findViewById(R.id.tvLogType)
        private val tvLogUser: TextView = itemView.findViewById(R.id.tvLogUser)
        private val tvLogDetails: TextView = itemView.findViewById(R.id.tvLogDetails)

        fun bind(item: LogItem) {
            when (item) {
                is LogItem.Login -> {
                    tvLogTime.text = item.log.fechaHora.split(" ").lastOrNull() ?: item.log.fechaHora
                    tvLogType.text = "INGRESO - ${item.log.estado.uppercase()}"
                    tvLogType.setTextColor(if (item.log.estado == "exitoso") Color.parseColor("#16a34a") else Color.parseColor("#dc2626"))
                    tvLogUser.text = item.log.nombreUsuario ?: item.log.rutIngresado
                    tvLogDetails.text = "Dispositivo: ${item.log.dispositivo ?: "Desconocido"} | IP: ${item.log.ipAddress}"
                }
                is LogItem.Activity -> {
                    tvLogTime.text = item.log.fechaHora.split(" ").lastOrNull() ?: item.log.fechaHora
                    tvLogType.text = "ACTIVIDAD"
                    tvLogType.setTextColor(Color.parseColor("#2563eb"))
                    tvLogUser.text = item.log.usuarioNombre
                    tvLogDetails.text = "[${item.log.modulo}] ${item.log.accion}\n${item.log.detalles}"
                }
            }
        }
    }
}

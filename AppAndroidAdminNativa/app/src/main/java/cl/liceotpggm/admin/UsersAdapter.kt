package cl.liceotpggm.admin

import android.graphics.Color
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Button
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import cl.liceotpggm.admin.models.User

class UsersAdapter(private val onManage: (User, String) -> Unit) : RecyclerView.Adapter<UsersAdapter.UserViewHolder>() {

    private var users = listOf<User>()

    fun submitList(newUsers: List<User>) {
        users = newUsers
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): UserViewHolder {
        val view = LayoutInflater.from(parent.context).inflate(R.layout.item_user, parent, false)
        return UserViewHolder(view)
    }

    override fun onBindViewHolder(holder: UserViewHolder, position: Int) {
        val user = users[position]
        holder.bind(user, onManage)
    }

    override fun getItemCount() = users.size

    class UserViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val tvName: TextView = itemView.findViewById(R.id.tvName)
        private val tvRut: TextView = itemView.findViewById(R.id.tvRut)
        private val tvRole: TextView = itemView.findViewById(R.id.tvRole)
        private val btnEdit: Button = itemView.findViewById(R.id.btnEdit)
        private val btnResetPassword: Button = itemView.findViewById(R.id.btnResetPassword)
        private val btnDelete: Button = itemView.findViewById(R.id.btnDelete)

        fun bind(user: User, onManage: (User, String) -> Unit) {
            tvName.text = user.nombre
            tvRut.text = user.rut
            tvRole.text = if (user.rol == "admin") "Administrador" else "Profesor"
            
            tvRole.setBackgroundColor(Color.parseColor("#dcfce7"))
            tvRole.setTextColor(Color.parseColor("#166534"))
            
            // Ocultar botón eliminar para admin principal
            if (user.rut == "admin") {
                btnDelete.visibility = View.GONE
            } else {
                btnDelete.visibility = View.VISIBLE
            }

            btnEdit.setOnClickListener { onManage(user, "edit") }
            btnResetPassword.setOnClickListener { onManage(user, "reset") }
            btnDelete.setOnClickListener { onManage(user, "delete") }
        }
    }
}

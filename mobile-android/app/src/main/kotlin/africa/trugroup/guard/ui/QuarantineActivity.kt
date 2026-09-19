package africa.trugroup.guard.ui

import africa.trugroup.guard.R
import africa.trugroup.guard.data.GuardDatabase
import africa.trugroup.guard.data.QuarantineEntity
import africa.trugroup.guard.quarantine.QuarantineManager
import android.app.AlertDialog
import android.os.Bundle
import android.widget.ArrayAdapter
import android.widget.ListView
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import kotlinx.coroutines.launch
import java.io.File

/**
 * T043 — Interface de gestion de la quarantaine (EF-EP-25) : liste, restaurer,
 * supprimer. Le déchiffrement (restauration) réutilise QuarantineManager.
 */
class QuarantineActivity : AppCompatActivity() {

    private lateinit var liste: ListView
    private var elements: List<QuarantineEntity> = emptyList()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_quarantine)

        liste = findViewById(R.id.liste_quarantaine)
        liste.setOnItemClickListener { _, _, position, _ -> proposerAction(elements[position]) }

        rafraichir()
    }

    private fun rafraichir() {
        lifecycleScope.launch {
            elements = GuardDatabase.obtenir(this@QuarantineActivity).quarantineDao().listerActifs()
            val libelles = if (elements.isEmpty()) {
                listOf(getString(R.string.quarantaine_vide))
            } else {
                elements.map { "${it.nomFichierOrigine} — ${it.raison}" }
            }

            liste.adapter = ArrayAdapter(this@QuarantineActivity, android.R.layout.simple_list_item_1, libelles)
        }
    }

    private fun proposerAction(element: QuarantineEntity) {
        AlertDialog.Builder(this)
            .setTitle(element.nomFichierOrigine)
            .setItems(arrayOf(getString(R.string.action_restaurer), getString(R.string.action_supprimer))) { _, choix ->
                if (choix == 0) restaurer(element) else supprimer(element)
            }
            .show()
    }

    private fun restaurer(element: QuarantineEntity) {
        lifecycleScope.launch {
            val manager = QuarantineManager(File(filesDir, "quarantaine"), cleQuarantaine())
            manager.restaurer(element.id, File(element.cheminOrigine))

            val db = GuardDatabase.obtenir(this@QuarantineActivity)
            db.quarantineDao().mettreAJour(element.copy(statut = "restaure"))

            rafraichir()
        }
    }

    private fun supprimer(element: QuarantineEntity) {
        lifecycleScope.launch {
            val manager = QuarantineManager(File(filesDir, "quarantaine"), cleQuarantaine())
            manager.supprimerDefinitivement(element.id)

            val db = GuardDatabase.obtenir(this@QuarantineActivity)
            db.quarantineDao().mettreAJour(element.copy(statut = "supprime"))

            rafraichir()
        }
    }

    private fun cleQuarantaine(): ByteArray {
        val prefs = getSharedPreferences("guard_prefs", MODE_PRIVATE)
        val existante = prefs.getString("quarantine_key", null)
            ?: error("Clé de quarantaine absente — aucun élément n'a encore été mis en quarantaine")

        return existante.chunked(2).map { it.toInt(16).toByte() }.toByteArray()
    }
}

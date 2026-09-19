package africa.trugroup.guard.data

import androidx.room.Entity
import androidx.room.PrimaryKey

/** Représentation locale d'un ElementQuarantaine (data-model.md) — chemin_origine reste local (Principe IV). */
@Entity(tableName = "element_quarantaine")
data class QuarantineEntity(
    @PrimaryKey val id: String, // UUID local, sert de nom de fichier chiffré (QuarantineManager)
    val scanEventClientId: String,
    val nomFichierOrigine: String,
    val cheminOrigine: String,
    val raison: String, // "hash_connu" | "yara"
    val statut: String = "en_quarantaine", // en_quarantaine | restaure | supprime
    val miseEnQuarantaineLeIso8601: String,
)

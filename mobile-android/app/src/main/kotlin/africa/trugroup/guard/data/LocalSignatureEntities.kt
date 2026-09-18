package africa.trugroup.guard.data

import androidx.room.Entity
import androidx.room.PrimaryKey

/** Hash SHA-256 connu localement (base de signatures synchronisée en delta, T041). */
@Entity(tableName = "hash_connu")
data class HashConnuEntity(
    @PrimaryKey val sha256: String,
)

/** Règle YARA connue localement, au format texte brut publié par le backend. */
@Entity(tableName = "regle_yara")
data class RegleYaraEntity(
    @PrimaryKey(autoGenerate = true) val id: Long = 0,
    val texteYar: String,
)

/** Version de signatures actuellement appliquée localement (pour GET /signatures/delta?from_version=). */
@Entity(tableName = "version_signatures_locale")
data class VersionSignaturesLocaleEntity(
    @PrimaryKey val id: Int = 1, // singleton
    val numeroVersion: String,
)

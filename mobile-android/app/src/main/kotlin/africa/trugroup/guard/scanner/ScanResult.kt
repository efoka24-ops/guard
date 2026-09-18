package africa.trugroup.guard.scanner

/** Reflète l'enum `classification` du contrat endpoint-sync-api.yaml. */
enum class Classification {
    CLEAN, SUSPECT, PROBABLE_MALWARE, MALWARE_CONFIRME;

    fun versApi(): String = when (this) {
        CLEAN -> "clean"
        SUSPECT -> "suspect"
        PROBABLE_MALWARE -> "probable_malware"
        MALWARE_CONFIRME -> "malware_confirme"
    }
}

/** Niveau atteint par le scan à 3 niveaux — 3_ia hors périmètre de cet incrément (research.md §5). */
enum class NiveauScan { NIVEAU_1_HASH, NIVEAU_2_YARA;

    fun versApi(): String = when (this) {
        NIVEAU_1_HASH -> "1_hash"
        NIVEAU_2_YARA -> "2_yara"
    }
}

data class ScanResult(
    val niveauMaxAtteint: NiveauScan,
    val classification: Classification,
    val regleYaraCorrespondante: String? = null,
)

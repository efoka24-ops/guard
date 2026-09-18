package africa.trugroup.guard.scanner

import java.io.File

/**
 * T039 — Niveau 2 : correspondance de règles YARA (EF-EP-04/05). Voir la
 * limitation documentée dans YaraRule — moteur simplifié, pas libyara.
 */
class YaraScanner(private val regles: List<YaraRule>) {

    fun scanner(fichier: File): ScanResult {
        val contenu = fichier.readText(Charsets.ISO_8859_1) // binaire-safe pour un matching de chaînes ASCII

        val regleCorrespondante = regles.firstOrNull { it.correspond(contenu) }

        return if (regleCorrespondante != null) {
            ScanResult(
                niveauMaxAtteint = NiveauScan.NIVEAU_2_YARA,
                classification = Classification.PROBABLE_MALWARE,
                regleYaraCorrespondante = regleCorrespondante.nom,
            )
        } else {
            ScanResult(NiveauScan.NIVEAU_2_YARA, Classification.CLEAN)
        }
    }
}

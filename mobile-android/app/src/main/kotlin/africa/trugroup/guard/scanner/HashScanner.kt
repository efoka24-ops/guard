package africa.trugroup.guard.scanner

import java.io.File
import java.security.MessageDigest

/**
 * T038 — Niveau 1 : hash SHA-256 comparé à la base de signatures locale
 * (EF-EP-01/02). Logique pure JVM, testable sans dépendance Android.
 */
class HashScanner(private val hashesConnus: (String) -> Boolean) {

    fun scanner(fichier: File): ScanResult {
        val hash = sha256(fichier)

        return if (hashesConnus(hash)) {
            ScanResult(NiveauScan.NIVEAU_1_HASH, Classification.MALWARE_CONFIRME)
        } else {
            ScanResult(NiveauScan.NIVEAU_1_HASH, Classification.CLEAN)
        }
    }

    fun sha256(fichier: File): String = sha256(fichier.readBytes())

    fun sha256(contenu: ByteArray): String {
        val digest = MessageDigest.getInstance("SHA-256").digest(contenu)

        return digest.joinToString("") { "%02x".format(it) }
    }
}

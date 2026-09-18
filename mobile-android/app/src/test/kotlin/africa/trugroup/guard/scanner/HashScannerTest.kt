package africa.trugroup.guard.scanner

import org.junit.Assert.assertEquals
import org.junit.Test
import java.io.File

/** T022 — calcul hash SHA-256 + correspondance base locale (niveau 1). */
class HashScannerTest {

    private val hashEicar = "275a021bbfb6489e54d471899f7db9d1663fc695ec2fe2a2c4538aabf651fd0f"
    private val contenuEicar = "X5O!P%@AP[4\\PZX54(P^)7CC)7}\$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!\$H+H*"

    @Test
    fun `calcule le hash SHA-256 correct du fichier EICAR`() {
        val fichier = File.createTempFile("eicar", ".txt").apply { writeText(contenuEicar) }
        val scanner = HashScanner { false }

        assertEquals(hashEicar, scanner.sha256(fichier))
    }

    @Test
    fun `classe malware_confirme un hash connu`() {
        val fichier = File.createTempFile("eicar", ".txt").apply { writeText(contenuEicar) }
        val scanner = HashScanner { hash -> hash == hashEicar }

        val resultat = scanner.scanner(fichier)

        assertEquals(Classification.MALWARE_CONFIRME, resultat.classification)
        assertEquals(NiveauScan.NIVEAU_1_HASH, resultat.niveauMaxAtteint)
    }

    @Test
    fun `classe clean un hash inconnu`() {
        val fichier = File.createTempFile("innocent", ".txt").apply { writeText("contenu banal") }
        val scanner = HashScanner { hash -> hash == hashEicar }

        val resultat = scanner.scanner(fichier)

        assertEquals(Classification.CLEAN, resultat.classification)
    }
}

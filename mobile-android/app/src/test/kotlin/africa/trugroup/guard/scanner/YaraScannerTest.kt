package africa.trugroup.guard.scanner

import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import java.io.File

/** T023 — correspondance de règle YARA sur fichier de test (niveau 2). */
class YaraScannerTest {

    private val regleEicar = """
        rule Test_EICAR_String
        {
            meta:
                description = "Détecte la chaîne standard EICAR"
            strings:
                ${'$'}eicar = "X5O!P%@AP[4\\PZX54(P^)7CC)7}${'$'}EICAR-STANDARD-ANTIVIRUS-TEST-FILE!${'$'}H+H*"
            condition:
                ${'$'}eicar
        }
    """.trimIndent()

    @Test
    fun `parse une regle avec une chaine litterale`() {
        val regles = YaraRule.parserFichier(regleEicar)

        assertEquals(1, regles.size)
        assertEquals("Test_EICAR_String", regles.first().nom)
        assertTrue(regles.first().chaines.first().contains("EICAR-STANDARD-ANTIVIRUS-TEST-FILE"))
    }

    @Test
    fun `detecte la chaine EICAR dans un fichier correspondant`() {
        val fichier = File.createTempFile("eicar", ".txt")
            .apply { writeText("X5O!P%@AP[4\\PZX54(P^)7CC)7}\$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!\$H+H*") }
        val scanner = YaraScanner(YaraRule.parserFichier(regleEicar))

        val resultat = scanner.scanner(fichier)

        assertEquals(Classification.PROBABLE_MALWARE, resultat.classification)
        assertEquals("Test_EICAR_String", resultat.regleYaraCorrespondante)
    }

    @Test
    fun `ne declenche rien sur un fichier sans correspondance`() {
        val fichier = File.createTempFile("clean", ".txt").apply { writeText("contenu tout a fait normal") }
        val scanner = YaraScanner(YaraRule.parserFichier(regleEicar))

        val resultat = scanner.scanner(fichier)

        assertEquals(Classification.CLEAN, resultat.classification)
    }
}

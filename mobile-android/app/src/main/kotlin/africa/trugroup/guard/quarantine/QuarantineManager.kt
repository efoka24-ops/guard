package africa.trugroup.guard.quarantine

import java.io.File
import java.security.SecureRandom
import javax.crypto.Cipher
import javax.crypto.spec.GCMParameterSpec
import javax.crypto.spec.SecretKeySpec

/**
 * T040 — Met en quarantaine un fichier détecté malveillant (EF-EP-24) :
 * chiffrement AES-256-GCM puis déplacement dans le répertoire de quarantaine,
 * rendant le fichier inutilisable pour toute autre application. La clé est
 * fournie par l'appelant (dérivée du Android Keystore côté production — hors
 * périmètre de cette classe, qui reste pure JVM et testable).
 */
class QuarantineManager(private val dossierQuarantaine: File, private val cle: ByteArray) {

    init {
        require(cle.size == 32) { "La clé AES-256 doit faire 32 octets" }
        dossierQuarantaine.mkdirs()
    }

    /** Chiffre [fichierOrigine] dans le dossier de quarantaine puis supprime l'original. Retourne le fichier chiffré. */
    fun mettreEnQuarantaine(fichierOrigine: File, idElementQuarantaine: String): File {
        val iv = ByteArray(12).also { SecureRandom().nextBytes(it) }
        val cipher = Cipher.getInstance("AES/GCM/NoPadding").apply {
            init(Cipher.ENCRYPT_MODE, SecretKeySpec(cle, "AES"), GCMParameterSpec(128, iv))
        }

        val contenuChiffre = cipher.doFinal(fichierOrigine.readBytes())
        val fichierQuarantaine = File(dossierQuarantaine, "$idElementQuarantaine.enc")
        fichierQuarantaine.writeBytes(iv + contenuChiffre) // IV préfixé, nécessaire à la restauration

        fichierOrigine.delete()

        return fichierQuarantaine
    }

    /** Déchiffre un élément en quarantaine vers [destination] (restauration, EF-EP-25). */
    fun restaurer(idElementQuarantaine: String, destination: File): Boolean {
        val fichierQuarantaine = File(dossierQuarantaine, "$idElementQuarantaine.enc")
        if (! fichierQuarantaine.exists()) return false

        val donnees = fichierQuarantaine.readBytes()
        val iv = donnees.copyOfRange(0, 12)
        val contenuChiffre = donnees.copyOfRange(12, donnees.size)

        val cipher = Cipher.getInstance("AES/GCM/NoPadding").apply {
            init(Cipher.DECRYPT_MODE, SecretKeySpec(cle, "AES"), GCMParameterSpec(128, iv))
        }

        destination.writeBytes(cipher.doFinal(contenuChiffre))
        fichierQuarantaine.delete()

        return true
    }

    /** Supprime définitivement un élément en quarantaine (EF-EP-25). */
    fun supprimerDefinitivement(idElementQuarantaine: String): Boolean =
        File(dossierQuarantaine, "$idElementQuarantaine.enc").delete()
}

package africa.trugroup.guard.scanner

import africa.trugroup.guard.GuardApplication
import africa.trugroup.guard.data.GuardDatabase
import africa.trugroup.guard.data.QuarantineEntity
import africa.trugroup.guard.data.ScanEventEntity
import africa.trugroup.guard.quarantine.QuarantineManager
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.os.Environment
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import java.io.File
import java.time.Instant
import java.util.UUID

/**
 * T037 — Déclenche le scan à 3 niveaux (ici : niveaux 1-2, cf. research.md
 * §5) dès qu'un support externe est monté (EF-EP-01). L'installation d'APK
 * hors Play Store (EF-EP-11) suit le même pipeline dans un incrément
 * ultérieur — nécessite l'interception de l'intent INSTALL_PACKAGE, hors
 * périmètre de ce premier incrément.
 */
class DetectionTrigger : BroadcastReceiver() {

    override fun onReceive(context: Context, intent: Intent) {
        val support = intent.data?.path?.let(::File) ?: Environment.getExternalStorageDirectory()

        // Le scan et l'IO fichier ne doivent jamais bloquer le thread principal
        // (BroadcastReceiver a un délai d'exécution très court sous Android).
        CoroutineScope(Dispatchers.IO).launch {
            scannerRecursivement(context, support)
        }
    }

    private suspend fun scannerRecursivement(context: Context, racine: File) {
        val app = context.applicationContext as GuardApplication
        val db = GuardDatabase.obtenir(context)

        // Base de hashes préchargée en mémoire (Set) : HashScanner attend un
        // lambda synchrone (classe pure JVM testable, cf. HashScannerTest),
        // impossible d'y appeler une fonction Room `suspend`.
        val hashesConnus = db.signatureDao().tousLesHashes().toHashSet()
        val hashScanner = HashScanner { sha256 -> sha256 in hashesConnus }
        val reglesYara = db.signatureDao().toutesLesReglesTexte()
            .flatMap { YaraRule.parserFichier(it) }
        val yaraScanner = YaraScanner(reglesYara)

        // Priorité aux extensions à risque (EF-EP-03) — évite de scanner
        // inutilement les fichiers multimédias volumineux en niveau 2/3.
        val extensionsRisque = setOf("exe", "apk", "bat", "cmd", "ps1", "vbs", "js", "jar", "dll", "sh", "py")

        racine.walkTopDown()
            .filter { it.isFile }
            .forEach { fichier ->
                val hash = hashScanner.sha256(fichier)
                val resultatHash = hashScanner.scanner(fichier)

                val resultat = if (resultatHash.classification == Classification.CLEAN &&
                    fichier.extension.lowercase() in extensionsRisque
                ) {
                    yaraScanner.scanner(fichier)
                } else {
                    resultatHash
                }

                traiterResultat(context, app, fichier, hash, resultat)
            }
    }

    private suspend fun traiterResultat(
        context: Context,
        app: GuardApplication,
        fichier: File,
        hash: String,
        resultat: ScanResult,
    ) {
        val db = GuardDatabase.obtenir(context)
        val clientEventId = UUID.randomUUID().toString()

        val actionPrise = when (resultat.classification) {
            Classification.MALWARE_CONFIRME -> "quarantaine_auto"
            Classification.PROBABLE_MALWARE -> "quarantaine_proposee"
            Classification.SUSPECT -> "surveillance"
            Classification.CLEAN -> "aucune"
        }

        db.scanEventDao().enfiler(
            ScanEventEntity(
                clientEventId = clientEventId,
                trigger = "usb",
                sha256 = hash,
                yaraRuleMatched = resultat.regleYaraCorrespondante,
                levelReached = resultat.niveauMaxAtteint.versApi(),
                classification = resultat.classification.versApi(),
                actionTaken = actionPrise,
                occurredAtIso8601 = Instant.now().toString(),
            ),
        )

        if (actionPrise == "quarantaine_auto") {
            val quarantaineId = UUID.randomUUID().toString()
            val manager = QuarantineManager(
                dossierQuarantaine = File(context.filesDir, "quarantaine"),
                cle = cleQuarantaine(context),
            )
            val cheminOrigine = fichier.absolutePath

            manager.mettreEnQuarantaine(fichier, quarantaineId)

            db.quarantineDao().inserer(
                QuarantineEntity(
                    id = quarantaineId,
                    scanEventClientId = clientEventId,
                    nomFichierOrigine = fichier.name,
                    cheminOrigine = cheminOrigine,
                    raison = if (resultat.niveauMaxAtteint == NiveauScan.NIVEAU_1_HASH) "hash_connu" else "yara",
                    miseEnQuarantaineLeIso8601 = Instant.now().toString(),
                ),
            )
        }

        // Tentative de synchronisation immédiate si une connexion est
        // disponible ; en cas d'échec, l'événement reste en file (Principe I).
        app.syncClient.synchroniserEvenements()
    }

    /**
     * TODO production : dériver cette clé depuis Android Keystore
     * (KeyGenParameterSpec, StrongBox si disponible) plutôt qu'un placeholder
     * — hors périmètre de ce premier incrément, qui valide le pipeline
     * hash/YARA/quarantaine de bout en bout.
     */
    private fun cleQuarantaine(context: Context): ByteArray {
        val prefs = context.getSharedPreferences("guard_prefs", Context.MODE_PRIVATE)
        val existante = prefs.getString("quarantine_key", null)
        if (existante != null) return existante.chunked(2).map { it.toInt(16).toByte() }.toByteArray()

        val nouvelleCle = ByteArray(32).also { java.security.SecureRandom().nextBytes(it) }
        prefs.edit().putString("quarantine_key", nouvelleCle.joinToString("") { "%02x".format(it) }).apply()

        return nouvelleCle
    }
}

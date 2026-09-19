package africa.trugroup.guard.sync

import africa.trugroup.guard.data.GuardDatabase
import africa.trugroup.guard.data.HashConnuEntity
import africa.trugroup.guard.data.RegleYaraEntity
import africa.trugroup.guard.data.ScanEventEntity
import africa.trugroup.guard.data.VersionSignaturesLocaleEntity
import android.content.Context
import android.content.SharedPreferences

/**
 * T042 — Orchestration de la synchronisation offline-first (Principe I) :
 * ne lève jamais d'exception fatale sur un échec réseau, se contente de
 * laisser la file locale intacte pour une reprise ultérieure.
 */
class SyncClient(
    private val context: Context,
    private val api: GuardApi,
    private val prefs: SharedPreferences,
) {
    private val db get() = GuardDatabase.obtenir(context)

    /** Appelé une fois à l'installation, avec le organisation_token fourni à l'organisation cliente. */
    suspend fun enregistrer(deviceHash: String, plateforme: String, versionApp: String, organisationToken: String): Boolean {
        val reponse = runCatching {
            api.enregistrerTerminal(
                RegisterTerminalRequest(deviceHash, plateforme, versionApp, organisationToken),
            )
        }.getOrNull() ?: return false

        val corps = reponse.body() ?: return false
        if (! reponse.isSuccessful) return false

        prefs.edit()
            .putString(CLE_TERMINAL_ID, corps.id)
            .putString(CLE_ACCESS_TOKEN, corps.accessToken)
            .apply()

        return true
    }

    /** Récupère et applique le delta de signatures (EF-EP-06/27) — tolère l'absence de réseau. */
    suspend fun synchroniserSignatures(): Boolean {
        val token = prefs.getString(CLE_ACCESS_TOKEN, null) ?: return false
        val versionLocale = db.signatureDao().versionActuelle()

        val reponse = runCatching {
            api.deltaSignatures("Bearer $token", versionLocale)
        }.getOrNull() ?: return false

        if (reponse.code() == 204) return true // déjà à jour
        val corps = reponse.body() ?: return false
        if (! reponse.isSuccessful) return false

        db.signatureDao().ajouterHashes(corps.hashesAdded.map { HashConnuEntity(it) })
        db.signatureDao().ajouterRegles(corps.yaraRulesAdded.map { RegleYaraEntity(texteYar = it) })
        db.signatureDao().definirVersion(VersionSignaturesLocaleEntity(numeroVersion = corps.version))

        return true
    }

    /** Envoie la file locale de scan-events non encore synchronisés (T041), en un seul lot. */
    suspend fun synchroniserEvenements(): Boolean {
        val token = prefs.getString(CLE_ACCESS_TOKEN, null) ?: return false
        val terminalId = prefs.getString(CLE_TERMINAL_ID, null) ?: return false
        val enAttente = db.scanEventDao().enAttenteDeSynchro()
        if (enAttente.isEmpty()) return true

        val requete = ScanEventsRequest(
            terminalId = terminalId,
            events = enAttente.map {
                ScanEventPayload(
                    clientEventId = it.clientEventId,
                    trigger = it.trigger,
                    sha256 = it.sha256,
                    yaraRuleMatched = it.yaraRuleMatched,
                    levelReached = it.levelReached,
                    classification = it.classification,
                    actionTaken = it.actionTaken,
                    occurredAt = it.occurredAtIso8601,
                )
            },
        )

        val reponse = runCatching { api.envoyerEvenements("Bearer $token", requete) }.getOrNull() ?: return false

        // 202 (accepté) ou 409 (déjà connu du backend) marquent la file comme traitée :
        // dans les deux cas, retenter n'apporterait rien de plus.
        if (reponse.isSuccessful || reponse.code() == 409) {
            db.scanEventDao().marquerSynchronise(enAttente.map { it.clientEventId })

            return true
        }

        return false
    }

    /** Ajoute un événement à la file locale (appelé par le pipeline de scan, jamais bloquant sur le réseau). */
    suspend fun enfilerEvenement(evenement: ScanEventEntity) {
        db.scanEventDao().enfiler(evenement)
    }

    companion object {
        private const val CLE_TERMINAL_ID = "terminal_id"
        private const val CLE_ACCESS_TOKEN = "access_token"
    }
}

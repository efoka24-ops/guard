package africa.trugroup.guard.data

import androidx.room.Entity
import androidx.room.PrimaryKey

/**
 * T041 — File d'envoi différée des événements de scan (offline-first,
 * Principe I). `synchronise` passe à true une fois acquitté par le backend
 * (POST /scan-events -> 202/409), permettant la reprise après coupure réseau
 * sans double-lecture.
 */
@Entity(tableName = "scan_event_queue")
data class ScanEventEntity(
    @PrimaryKey val clientEventId: String, // UUID généré localement — idempotence côté backend
    val trigger: String,
    val sha256: String,
    val yaraRuleMatched: String?,
    val levelReached: String, // "1_hash" | "2_yara"
    val classification: String,
    val actionTaken: String,
    val occurredAtIso8601: String,
    val synchronise: Boolean = false,
)

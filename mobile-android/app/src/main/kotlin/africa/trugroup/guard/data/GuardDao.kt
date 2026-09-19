package africa.trugroup.guard.data

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import androidx.room.Update

@Dao
interface SignatureDao {
    @Query("SELECT EXISTS(SELECT 1 FROM hash_connu WHERE sha256 = :sha256)")
    suspend fun hashConnu(sha256: String): Boolean

    // Préchargée en mémoire par DetectionTrigger : HashScanner attend un
    // lambda synchrone (classe pure JVM testable, cf. HashScannerTest), donc
    // impossible d'y appeler directement une fonction Room `suspend`.
    @Query("SELECT sha256 FROM hash_connu")
    suspend fun tousLesHashes(): List<String>

    @Insert(onConflict = OnConflictStrategy.IGNORE)
    suspend fun ajouterHashes(hashes: List<HashConnuEntity>)

    @Query("SELECT texteYar FROM regle_yara")
    suspend fun toutesLesReglesTexte(): List<String>

    @Insert
    suspend fun ajouterRegles(regles: List<RegleYaraEntity>)

    @Query("SELECT numeroVersion FROM version_signatures_locale WHERE id = 1")
    suspend fun versionActuelle(): String?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun definirVersion(version: VersionSignaturesLocaleEntity)
}

@Dao
interface ScanEventDao {
    @Insert(onConflict = OnConflictStrategy.IGNORE) // idempotence locale par clientEventId
    suspend fun enfiler(evenement: ScanEventEntity)

    @Query("SELECT * FROM scan_event_queue WHERE synchronise = 0 ORDER BY occurredAtIso8601")
    suspend fun enAttenteDeSynchro(): List<ScanEventEntity>

    @Query("UPDATE scan_event_queue SET synchronise = 1 WHERE clientEventId IN (:ids)")
    suspend fun marquerSynchronise(ids: List<String>)
}

@Dao
interface QuarantineDao {
    @Insert
    suspend fun inserer(element: QuarantineEntity)

    @Query("SELECT * FROM element_quarantaine WHERE statut = 'en_quarantaine' ORDER BY miseEnQuarantaineLeIso8601 DESC")
    suspend fun listerActifs(): List<QuarantineEntity>

    @Update
    suspend fun mettreAJour(element: QuarantineEntity)
}

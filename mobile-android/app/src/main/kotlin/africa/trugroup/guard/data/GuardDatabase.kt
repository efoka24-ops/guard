package africa.trugroup.guard.data

import android.content.Context
import androidx.room.Database
import androidx.room.Room
import androidx.room.RoomDatabase

@Database(
    entities = [
        HashConnuEntity::class,
        RegleYaraEntity::class,
        VersionSignaturesLocaleEntity::class,
        ScanEventEntity::class,
        QuarantineEntity::class,
    ],
    version = 1,
    exportSchema = false,
)
abstract class GuardDatabase : RoomDatabase() {
    abstract fun signatureDao(): SignatureDao
    abstract fun scanEventDao(): ScanEventDao
    abstract fun quarantineDao(): QuarantineDao

    companion object {
        @Volatile private var instance: GuardDatabase? = null

        fun obtenir(context: Context): GuardDatabase =
            instance ?: synchronized(this) {
                instance ?: Room.databaseBuilder(
                    context.applicationContext,
                    GuardDatabase::class.java,
                    "guard.db",
                ).build().also { instance = it }
            }
    }
}

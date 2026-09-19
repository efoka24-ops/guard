package africa.trugroup.guard

import africa.trugroup.guard.sync.RetrofitFactory
import africa.trugroup.guard.sync.SyncClient
import android.app.Application

class GuardApplication : Application() {
    lateinit var syncClient: SyncClient
        private set

    override fun onCreate() {
        super.onCreate()

        val prefs = getSharedPreferences("guard_prefs", MODE_PRIVATE)
        syncClient = SyncClient(this, RetrofitFactory.creer(), prefs)
    }
}

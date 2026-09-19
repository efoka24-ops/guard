package africa.trugroup.guard.sync

import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory

object RetrofitFactory {
    /** URL de production : guard.trugroup.cm (cf. prerequisites.md). Surchargeable en debug/test. */
    fun creer(baseUrl: String = "https://guard.trugroup.cm/api/"): GuardApi {
        val moshi = Moshi.Builder().add(KotlinJsonAdapterFactory()).build()

        return Retrofit.Builder()
            .baseUrl(baseUrl)
            .addConverterFactory(MoshiConverterFactory.create(moshi))
            .build()
            .create(GuardApi::class.java)
    }
}

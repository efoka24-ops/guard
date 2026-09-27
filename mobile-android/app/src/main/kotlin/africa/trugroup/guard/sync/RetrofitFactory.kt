package africa.trugroup.guard.sync

import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import retrofit2.Retrofit
import retrofit2.converter.moshi.MoshiConverterFactory

object RetrofitFactory {
    /**
     * URL de production : guard.trugroup.cm (cf. prerequisites.md). Surchargeable en debug/test.
     *
     * HTTP (pas HTTPS) tant que le SSL n'est pas activé sur cet hébergement (Camoo, cf.
     * specs/001-guard-platform/deployment.md — reste à faire). Le trafic clair n'est autorisé
     * que vers ce domaine précis, via res/xml/network_security_config.xml — jamais en global.
     */
    fun creer(baseUrl: String = "http://guard.trugroup.cm/api/"): GuardApi {
        val moshi = Moshi.Builder().add(KotlinJsonAdapterFactory()).build()

        return Retrofit.Builder()
            .baseUrl(baseUrl)
            .addConverterFactory(MoshiConverterFactory.create(moshi))
            .build()
            .create(GuardApi::class.java)
    }
}

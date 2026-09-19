package africa.trugroup.guard.sync

import com.squareup.moshi.Json
import com.squareup.moshi.JsonClass
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST
import retrofit2.http.Query

/** Correspond à contracts/endpoint-sync-api.yaml (T042). */
interface GuardApi {

    @POST("v1/terminals/register")
    suspend fun enregistrerTerminal(@Body body: RegisterTerminalRequest): Response<RegisterTerminalResponse>

    @GET("v1/signatures/delta")
    suspend fun deltaSignatures(
        @Header("Authorization") bearerToken: String,
        @Query("from_version") fromVersion: String?,
    ): Response<SignatureDeltaResponse>

    @POST("v1/scan-events")
    suspend fun envoyerEvenements(
        @Header("Authorization") bearerToken: String,
        @Body body: ScanEventsRequest,
    ): Response<Unit>
}

@JsonClass(generateAdapter = true)
data class RegisterTerminalRequest(
    @Json(name = "device_hash") val deviceHash: String,
    val platform: String,
    @Json(name = "app_version") val appVersion: String,
    @Json(name = "organisation_token") val organisationToken: String,
)

@JsonClass(generateAdapter = true)
data class RegisterTerminalResponse(
    val id: String,
    val platform: String,
    val status: String,
    @Json(name = "access_token") val accessToken: String,
)

@JsonClass(generateAdapter = true)
data class SignatureDeltaResponse(
    val version: String,
    @Json(name = "hashes_added") val hashesAdded: List<String>,
    @Json(name = "yara_rules_added") val yaraRulesAdded: List<String>,
)

@JsonClass(generateAdapter = true)
data class ScanEventsRequest(
    @Json(name = "terminal_id") val terminalId: String,
    val events: List<ScanEventPayload>,
)

@JsonClass(generateAdapter = true)
data class ScanEventPayload(
    @Json(name = "client_event_id") val clientEventId: String,
    val trigger: String,
    val sha256: String,
    @Json(name = "yara_rule_matched") val yaraRuleMatched: String?,
    @Json(name = "level_reached") val levelReached: String,
    val classification: String,
    @Json(name = "action_taken") val actionTaken: String,
    @Json(name = "occurred_at") val occurredAt: String,
)

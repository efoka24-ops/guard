/*
 * Règle de test (T035) — marqueur textuel arbitraire simulant une détection
 * comportementale simple (cf. EF-EP-05 smishing / EF-EP-06 entropie). Ne pas
 * utiliser en production.
 */
rule Test_Suspicious_Marker
{
    meta:
        description = "Détecte un marqueur de test 'GUARD-TEST-MALWARE-MARKER'"
        category = "test"

    strings:
        $marker = "GUARD-TEST-MALWARE-MARKER"

    condition:
        $marker
}

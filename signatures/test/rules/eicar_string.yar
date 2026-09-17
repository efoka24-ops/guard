/*
 * Règle de test (T035) — détecte la chaîne caractéristique EICAR même si le
 * hash exact diffère (ex. fichier EICAR recompressé). Ne pas utiliser en
 * production.
 */
rule Test_EICAR_String
{
    meta:
        description = "Détecte la chaîne standard EICAR"
        category = "test"

    strings:
        $eicar = "X5O!P%@AP[4\\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*"

    condition:
        $eicar
}

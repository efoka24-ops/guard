package africa.trugroup.guard.scanner

/**
 * Représentation minimale d'une règle YARA : un nom et un ensemble de
 * chaînes littérales (`strings:`). La condition supportée est "au moins une
 * chaîne présente" — suffisant pour les fichiers .yar de guard/signatures/test/rules
 * (T035), mais PAS un moteur YARA complet (pas de regex, pas d'opérateurs
 * booléens combinés, pas de hex strings). Un vrai moteur YARA embarqué
 * (libyara via JNI) est hors périmètre de ce premier incrément.
 */
data class YaraRule(val nom: String, val chaines: List<String>) {

    fun correspond(contenu: String): Boolean = chaines.any { contenu.contains(it) }

    companion object {
        private val REGLE_REGEX = Regex("""rule\s+(\w+)\s*\{([\s\S]*?)\n\}""")
        // Concaténation pour éviter l'ambiguïté """..."" en fin de raw string Kotlin
        // (un guillemet littéral juste avant le délimiteur fermant """ le prolonge).
        private val CHAINE_REGEX = Regex("\"" + """((?:[^"\\]|\\.)*)""" + "\"")

        /** Parse un fichier .yar au format restreint décrit ci-dessus. */
        fun parserFichier(texteYar: String): List<YaraRule> =
            REGLE_REGEX.findAll(texteYar).map { match ->
                val nom = match.groupValues[1]
                val corps = match.groupValues[2]
                val sectionStrings = corps.substringAfter("strings:", "").substringBefore("condition:")
                val chaines = CHAINE_REGEX.findAll(sectionStrings)
                    .map { it.groupValues[1].replace("\\\\", "\\").replace("\\\"", "\"") }
                    .toList()

                YaraRule(nom, chaines)
            }.toList()
    }
}

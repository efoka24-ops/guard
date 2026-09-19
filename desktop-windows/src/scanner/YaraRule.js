'use strict';

/**
 * Représentation minimale d'une règle YARA : un nom et un ensemble de
 * chaînes littérales (`strings:`). La condition supportée est "au moins une
 * chaîne présente" — suffisant pour guard/signatures/test/rules/*.yar, mais
 * PAS un moteur YARA complet (pas de regex, pas d'opérateurs booléens
 * combinés, pas de hex strings). Un vrai moteur YARA embarqué (libyara via
 * bindings natifs Node) est hors périmètre de ce premier incrément —
 * limitation identique côté Android (cf. YaraRule.kt).
 */
const REGLE_REGEX = /rule\s+(\w+)\s*\{([\s\S]*?)\n\}/g;
const CHAINE_REGEX = /"((?:[^"\\]|\\.)*)"/g;

class YaraRule {
  constructor(nom, chaines) {
    this.nom = nom;
    this.chaines = chaines;
  }

  correspond(contenu) {
    return this.chaines.some((chaine) => contenu.includes(chaine));
  }

  /** Parse un fichier .yar au format restreint décrit ci-dessus. */
  static parserFichier(texteYar) {
    const regles = [];
    let match;
    REGLE_REGEX.lastIndex = 0;

    while ((match = REGLE_REGEX.exec(texteYar)) !== null) {
      const nom = match[1];
      const corps = match[2];
      const sectionStrings = corps.includes('strings:')
        ? corps.split('strings:')[1].split('condition:')[0]
        : '';

      const chaines = [];
      let matchChaine;
      CHAINE_REGEX.lastIndex = 0;
      while ((matchChaine = CHAINE_REGEX.exec(sectionStrings)) !== null) {
        chaines.push(matchChaine[1].replace(/\\\\/g, '\\').replace(/\\"/g, '"'));
      }

      regles.push(new YaraRule(nom, chaines));
    }

    return regles;
  }
}

module.exports = { YaraRule };

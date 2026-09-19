'use strict';

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { NiveauScan, Classification, creerResultat } = require('./ScanResult');
const { YaraRule } = require('./YaraRule');

/**
 * Extensions à risque priorisées (EF-EP-03) : évite de scanner en niveau 2
 * les fichiers multimédias volumineux, inutile pour ce premier incrément
 * (limite l'usage CPU, cf. SC-009). Liste alignée sur DetectionTrigger.kt,
 * étendue à .apk/.py au périmètre desktop (transit possible vers mobile).
 */
const EXTENSIONS_RISQUE = new Set([
  'exe', 'bat', 'cmd', 'ps1', 'vbs', 'js', 'jar', 'dll', 'py', 'apk',
]);

/**
 * T045 — Scanner niveau 1 (hash SHA-256 + lookup base locale) et niveau 2
 * (moteur YARA simplifié). Logique pure Node.js, testable sans Electron.
 */
class FileScanner {
  /**
   * @param {Set<string>} hashesConnus Ensemble de hash SHA-256 (minuscules) chargé depuis hashes.json
   * @param {import('./YaraRule').YaraRule[]} reglesYara Règles YARA parsées depuis les fichiers .yar
   */
  constructor(hashesConnus, reglesYara) {
    this.hashesConnus = hashesConnus;
    this.reglesYara = reglesYara;
  }

  /** Charge la base de hashes de test au format guard/signatures/test/hashes.json. */
  static chargerHashesDepuisFichier(cheminJson) {
    const donnees = JSON.parse(fs.readFileSync(cheminJson, 'utf8'));
    return new Set(donnees.hashes.map((h) => h.sha256.toLowerCase()));
  }

  /** Charge et parse tous les fichiers .yar d'un dossier (guard/signatures/test/rules/). */
  static chargerReglesDepuisDossier(cheminDossier) {
    const fichiers = fs.readdirSync(cheminDossier).filter((f) => f.endsWith('.yar'));
    return fichiers.flatMap((f) =>
      YaraRule.parserFichier(fs.readFileSync(path.join(cheminDossier, f), 'utf8')),
    );
  }

  sha256Fichier(cheminFichier) {
    return this.sha256Buffer(fs.readFileSync(cheminFichier));
  }

  sha256Buffer(tampon) {
    return crypto.createHash('sha256').update(tampon).digest('hex');
  }

  /** Niveau 1 : hash SHA-256 comparé à la base locale (EF-EP-01/02). */
  scannerNiveau1(cheminFichier) {
    const hash = this.sha256Fichier(cheminFichier);

    if (this.hashesConnus.has(hash)) {
      return { hash, resultat: creerResultat(NiveauScan.NIVEAU_1_HASH, Classification.MALWARE_CONFIRME) };
    }

    return { hash, resultat: creerResultat(NiveauScan.NIVEAU_1_HASH, Classification.CLEAN) };
  }

  /** Niveau 2 : correspondance de règles YARA (EF-EP-04/05). Voir limitation dans YaraRule. */
  scannerNiveau2(cheminFichier) {
    // Lecture en latin1 : binaire-safe pour un matching de chaînes ASCII,
    // même logique que YaraScanner.kt (Charsets.ISO_8859_1).
    const contenu = fs.readFileSync(cheminFichier, 'latin1');
    const regleCorrespondante = this.reglesYara.find((r) => r.correspond(contenu));

    if (regleCorrespondante) {
      return creerResultat(NiveauScan.NIVEAU_2_YARA, Classification.PROBABLE_MALWARE, regleCorrespondante.nom);
    }

    return creerResultat(NiveauScan.NIVEAU_2_YARA, Classification.CLEAN);
  }

  /**
   * Pipeline complet : niveau 1 puis niveau 2 si le fichier a une extension
   * à risque et n'a pas déjà été classifié malware_confirme au niveau 1.
   * Retourne { hash, resultat }.
   */
  scanner(cheminFichier) {
    const { hash, resultat: resultatNiveau1 } = this.scannerNiveau1(cheminFichier);

    const extension = path.extname(cheminFichier).slice(1).toLowerCase();
    if (resultatNiveau1.classification === Classification.CLEAN && EXTENSIONS_RISQUE.has(extension)) {
      return { hash, resultat: this.scannerNiveau2(cheminFichier) };
    }

    return { hash, resultat: resultatNiveau1 };
  }
}

module.exports = { FileScanner, EXTENSIONS_RISQUE };

'use strict';

/**
 * Valeurs alignées sur contracts/endpoint-sync-api.yaml (enum ScanEvent) et
 * sur le modèle Android équivalent (ScanResult.kt) pour cohérence multi-plateforme.
 */
const NiveauScan = Object.freeze({
  NIVEAU_1_HASH: '1_hash',
  NIVEAU_2_YARA: '2_yara',
});

const Classification = Object.freeze({
  CLEAN: 'clean',
  SUSPECT: 'suspect',
  PROBABLE_MALWARE: 'probable_malware',
  MALWARE_CONFIRME: 'malware_confirme',
});

/** @typedef {{niveauMaxAtteint: string, classification: string, regleYaraCorrespondante?: string|null}} ScanResult */

function creerResultat(niveauMaxAtteint, classification, regleYaraCorrespondante = null) {
  return { niveauMaxAtteint, classification, regleYaraCorrespondante };
}

module.exports = { NiveauScan, Classification, creerResultat };

'use strict';

const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('fs');
const os = require('os');
const path = require('path');

const { FileScanner } = require('./FileScanner');
const { YaraRule } = require('./YaraRule');
const { Classification, NiveauScan } = require('./ScanResult');

const HASH_EICAR = '275a021bbfb6489e54d471899f7db9d1663fc695ec2fe2a2c4538aabf651fd0f';
const CONTENU_EICAR = 'X5O!P%@AP[4\\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';

function creerFichierTemp(nom, contenu) {
  const dossier = fs.mkdtempSync(path.join(os.tmpdir(), 'guard-test-'));
  const chemin = path.join(dossier, nom);
  fs.writeFileSync(chemin, contenu);
  return chemin;
}

test('le hash EICAR fait bien 64 caractères', () => {
  assert.equal(HASH_EICAR.length, 64);
});

test('scannerNiveau1 détecte un hash connu (EICAR)', () => {
  const scanner = new FileScanner(new Set([HASH_EICAR]), []);
  const chemin = creerFichierTemp('eicar.com', CONTENU_EICAR);

  const { hash, resultat } = scanner.scannerNiveau1(chemin);

  assert.equal(hash, HASH_EICAR);
  assert.equal(resultat.classification, Classification.MALWARE_CONFIRME);
  assert.equal(resultat.niveauMaxAtteint, NiveauScan.NIVEAU_1_HASH);
});

test('scannerNiveau1 classe un fichier inconnu comme clean', () => {
  const scanner = new FileScanner(new Set([HASH_EICAR]), []);
  const chemin = creerFichierTemp('inoffensif.txt', 'contenu tout à fait normal');

  const { resultat } = scanner.scannerNiveau1(chemin);

  assert.equal(resultat.classification, Classification.CLEAN);
});

test('scannerNiveau2 détecte une règle YARA correspondante', () => {
  const regle = new YaraRule('Test_EICAR_String', [CONTENU_EICAR]);
  const scanner = new FileScanner(new Set(), [regle]);
  const chemin = creerFichierTemp('variante.exe', `préfixe ${CONTENU_EICAR} suffixe`);

  const resultat = scanner.scannerNiveau2(chemin);

  assert.equal(resultat.classification, Classification.PROBABLE_MALWARE);
  assert.equal(resultat.regleYaraCorrespondante, 'Test_EICAR_String');
});

test('scannerNiveau2 ne déclenche rien sans correspondance', () => {
  const regle = new YaraRule('Test_Marker', ['GUARD-TEST-MALWARE-MARKER']);
  const scanner = new FileScanner(new Set(), [regle]);
  const chemin = creerFichierTemp('propre.js', 'console.log("rien de suspect");');

  const resultat = scanner.scannerNiveau2(chemin);

  assert.equal(resultat.classification, Classification.CLEAN);
});

test('scanner() applique le niveau 2 uniquement sur une extension à risque', () => {
  const regle = new YaraRule('Test_Marker', ['GUARD-TEST-MALWARE-MARKER']);
  const scanner = new FileScanner(new Set(), [regle]);

  // Extension à risque (.js) + marqueur -> niveau 2 déclenché
  const cheminRisque = creerFichierTemp('script.js', 'GUARD-TEST-MALWARE-MARKER');
  const { resultat: resultatRisque } = scanner.scanner(cheminRisque);
  assert.equal(resultatRisque.classification, Classification.PROBABLE_MALWARE);

  // Extension non à risque (.txt) + marqueur -> reste au niveau 1 (clean, hash inconnu)
  const cheminSansRisque = creerFichierTemp('notes.txt', 'GUARD-TEST-MALWARE-MARKER');
  const { resultat: resultatSansRisque } = scanner.scanner(cheminSansRisque);
  assert.equal(resultatSansRisque.classification, Classification.CLEAN);
  assert.equal(resultatSansRisque.niveauMaxAtteint, NiveauScan.NIVEAU_1_HASH);
});

test('chargerHashesDepuisFichier lit le format guard/signatures/test/hashes.json', () => {
  const cheminHashes = require('path').join(
    __dirname, '..', '..', '..', 'signatures', 'test', 'hashes.json',
  );

  if (!fs.existsSync(cheminHashes)) {
    // Environnement isolé sans le dossier signatures partagé : on ne fait pas échouer le test.
    return;
  }

  const hashes = FileScanner.chargerHashesDepuisFichier(cheminHashes);
  assert.ok(hashes.has(HASH_EICAR));
});

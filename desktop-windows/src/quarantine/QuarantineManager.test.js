'use strict';

const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('fs');
const os = require('os');
const path = require('path');
const crypto = require('crypto');

const { QuarantineManager } = require('./QuarantineManager');

function dossierTemp() {
  return fs.mkdtempSync(path.join(os.tmpdir(), 'guard-quarantaine-'));
}

test('mettreEnQuarantaine chiffre puis supprime le fichier original', () => {
  const source = dossierTemp();
  const quarantaine = path.join(dossierTemp(), 'quarantaine');
  const cheminOrigine = path.join(source, 'malware.exe');
  fs.writeFileSync(cheminOrigine, 'contenu malveillant simulé');

  const manager = new QuarantineManager(quarantaine, crypto.randomBytes(32));
  const cheminChiffre = manager.mettreEnQuarantaine(cheminOrigine, 'abc-123');

  assert.equal(fs.existsSync(cheminOrigine), false);
  assert.equal(fs.existsSync(cheminChiffre), true);
  assert.notEqual(fs.readFileSync(cheminChiffre).toString('latin1'), 'contenu malveillant simulé');
});

test('restaurer déchiffre correctement le contenu original', () => {
  const source = dossierTemp();
  const quarantaine = path.join(dossierTemp(), 'quarantaine');
  const cheminOrigine = path.join(source, 'suspect.js');
  fs.writeFileSync(cheminOrigine, 'contenu original');

  const cle = crypto.randomBytes(32);
  const manager = new QuarantineManager(quarantaine, cle);
  manager.mettreEnQuarantaine(cheminOrigine, 'xyz-789');

  const destination = path.join(source, 'restaure.js');
  const succes = manager.restaurer('xyz-789', destination);

  assert.equal(succes, true);
  assert.equal(fs.readFileSync(destination, 'utf8'), 'contenu original');
});

test('supprimerDefinitivement retire le fichier chiffré', () => {
  const source = dossierTemp();
  const quarantaine = path.join(dossierTemp(), 'quarantaine');
  const cheminOrigine = path.join(source, 'a-supprimer.bat');
  fs.writeFileSync(cheminOrigine, 'contenu');

  const manager = new QuarantineManager(quarantaine, crypto.randomBytes(32));
  manager.mettreEnQuarantaine(cheminOrigine, 'del-1');

  assert.equal(manager.supprimerDefinitivement('del-1'), true);
  assert.equal(manager.supprimerDefinitivement('del-1'), false);
});

test('rejette une clé qui ne fait pas 32 octets', () => {
  assert.throws(() => new QuarantineManager(dossierTemp(), crypto.randomBytes(16)));
});

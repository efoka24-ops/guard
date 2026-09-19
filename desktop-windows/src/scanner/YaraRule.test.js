'use strict';

const { test } = require('node:test');
const assert = require('node:assert/strict');

const { YaraRule } = require('./YaraRule');

const TEXTE_YAR = `
rule Test_EICAR_String
{
    meta:
        description = "Détecte la chaîne standard EICAR"
        category = "test"

    strings:
        $eicar = "X5O!P%@AP[4\\\\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*"

    condition:
        $eicar
}
`;

test('parserFichier extrait le nom et les chaînes de la règle', () => {
  const regles = YaraRule.parserFichier(TEXTE_YAR);

  assert.equal(regles.length, 1);
  assert.equal(regles[0].nom, 'Test_EICAR_String');
  assert.equal(regles[0].chaines.length, 1);
});

test('correspond() détecte une chaîne présente dans le contenu', () => {
  const regle = new YaraRule('R', ['MARQUEUR']);
  assert.equal(regle.correspond('préfixe MARQUEUR suffixe'), true);
  assert.equal(regle.correspond('rien ici'), false);
});

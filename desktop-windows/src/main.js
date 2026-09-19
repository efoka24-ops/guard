const { app } = require('electron');
const os = require('os');
const path = require('path');
const crypto = require('crypto');
const fs = require('fs');

const { UsbWatcher } = require('./usb-watcher/UsbWatcher');
const { FileScanner, EXTENSIONS_RISQUE } = require('./scanner/FileScanner');
const { QuarantineManager } = require('./quarantine/QuarantineManager');
const { SyncClient } = require('./sync/SyncClient');
const { Classification, NiveauScan } = require('./scanner/ScanResult');

// Point d'entrée de l'agent GUARD ENDPOINT (Windows).
// Détection USB → scanner niveau 1/2 → quarantaine → synchronisation (T044-T047),
// orchestration équivalente à DetectionTrigger.kt côté Android.

const DOSSIER_DONNEES = app.getPath('userData');
const DOSSIER_SIGNATURES = path.join(__dirname, '..', '..', 'signatures', 'test'); // guard/signatures/test
const CHEMIN_ETAT_SYNC = path.join(DOSSIER_DONNEES, 'guard-etat.json');
const DOSSIER_QUARANTAINE = path.join(DOSSIER_DONNEES, 'quarantaine');
const BASE_URL_BACKEND = process.env.GUARD_BACKEND_URL || 'https://guard.trugroup.cm/api/v1';

/**
 * TODO production : dériver cette clé depuis Windows DPAPI
 * (CryptProtectData) plutôt qu'un placeholder stocké en clair — hors
 * périmètre de ce premier incrément, qui valide le pipeline
 * hash/YARA/quarantaine de bout en bout (même limitation documentée côté
 * Android dans DetectionTrigger.kt).
 */
function obtenirCleQuarantaine() {
  const cheminCle = path.join(DOSSIER_DONNEES, 'quarantine.key');

  if (fs.existsSync(cheminCle)) {
    return Buffer.from(fs.readFileSync(cheminCle, 'utf8'), 'hex');
  }

  const nouvelleCle = crypto.randomBytes(32);
  fs.mkdirSync(DOSSIER_DONNEES, { recursive: true });
  fs.writeFileSync(cheminCle, nouvelleCle.toString('hex'), 'utf8');

  return nouvelleCle;
}

/** Identifiant appareil salé, jamais un GUID/MAC brut (cf. contrat endpoint-sync-api.yaml). */
function obtenirDeviceHash() {
  const base = `${os.hostname()}-${os.platform()}-${os.arch()}`;
  return crypto.createHash('sha256').update(base).digest('hex');
}

async function traiterFichier(fileScanner, syncClient, cheminFichier) {
  let hash;
  let resultat;

  try {
    ({ hash, resultat } = fileScanner.scanner(cheminFichier));
  } catch (erreur) {
    // Fichier verrouillé/supprimé entre la détection et la lecture : on
    // ignore silencieusement, le scan ne doit jamais planter l'agent.
    console.error(`Impossible de scanner ${cheminFichier} :`, erreur.message);
    return;
  }

  const clientEventId = crypto.randomUUID();

  const actionPrise = {
    [Classification.MALWARE_CONFIRME]: 'quarantaine_auto',
    [Classification.PROBABLE_MALWARE]: 'quarantaine_proposee',
    [Classification.SUSPECT]: 'surveillance',
    [Classification.CLEAN]: 'aucune',
  }[resultat.classification];

  syncClient.enfilerEvenement({
    client_event_id: clientEventId,
    trigger: 'usb',
    sha256: hash,
    yara_rule_matched: resultat.regleYaraCorrespondante,
    level_reached: resultat.niveauMaxAtteint,
    classification: resultat.classification,
    action_taken: actionPrise,
    occurred_at: new Date().toISOString(),
  });

  if (actionPrise === 'quarantaine_auto') {
    const quarantaineId = crypto.randomUUID();
    const manager = new QuarantineManager(DOSSIER_QUARANTAINE, obtenirCleQuarantaine());

    try {
      manager.mettreEnQuarantaine(cheminFichier, quarantaineId);
      console.log(`Fichier mis en quarantaine : ${cheminFichier} -> ${quarantaineId}`);
    } catch (erreur) {
      console.error(`Échec de mise en quarantaine de ${cheminFichier} :`, erreur.message);
    }
  }

  // Tentative de synchronisation immédiate si une connexion est disponible ;
  // en cas d'échec, l'événement reste en file (Principe I, offline-first).
  await syncClient.synchroniserEvenements();
}

app.whenReady().then(async () => {
  console.log('GUARD ENDPOINT (Windows) démarré');

  const syncClient = new SyncClient({ baseUrl: BASE_URL_BACKEND, cheminEtat: CHEMIN_ETAT_SYNC });

  if (!syncClient.etat.accessToken) {
    const organisationToken = process.env.GUARD_ORG_TOKEN;
    if (organisationToken) {
      await syncClient.enregistrer(obtenirDeviceHash(), app.getVersion(), organisationToken);
    } else {
      console.warn('GUARD_ORG_TOKEN absent : enrôlement non effectué, la synchro restera inactive.');
    }
  }

  let hashesConnus = new Set();
  let reglesYara = [];
  try {
    hashesConnus = FileScanner.chargerHashesDepuisFichier(path.join(DOSSIER_SIGNATURES, 'hashes.json'));
    reglesYara = FileScanner.chargerReglesDepuisDossier(path.join(DOSSIER_SIGNATURES, 'rules'));
  } catch (erreur) {
    console.error('Impossible de charger la base de signatures locale :', erreur.message);
  }

  const fileScanner = new FileScanner(hashesConnus, reglesYara);
  const usbWatcher = new UsbWatcher();

  usbWatcher.demarrer((lecteur) => {
    console.log(`Support USB détecté : ${lecteur}`);

    usbWatcher.surveillerContenu(lecteur, async (cheminFichier) => {
      await traiterFichier(fileScanner, syncClient, cheminFichier);
    });
  });

  // Tentative de synchro périodique des signatures (EF-EP-06/27), tolère l'absence de réseau.
  setInterval(() => syncClient.synchroniserSignatures(), 60 * 60 * 1000);
});

app.on('window-all-closed', () => {
  // Agent en tâche de fond : ne pas quitter à la fermeture d'une fenêtre.
});

module.exports = { traiterFichier, obtenirDeviceHash, EXTENSIONS_RISQUE, NiveauScan };

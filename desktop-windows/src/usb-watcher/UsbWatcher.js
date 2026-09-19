'use strict';

const chokidar = require('chokidar');
const { execFile } = require('child_process');

/**
 * T044 — Détecteur de connexion USB (Windows).
 *
 * Choix d'implémentation : `usb-detection` (listé initialement dans
 * package.json) est un module natif (node-gyp) qui expose les événements
 * `add`/`remove` de périphériques USB bruts, mais il ne donne PAS la lettre
 * de lecteur montée par Windows — il faut de toute façon interroger WMIC/PowerShell
 * pour retrouver le point de montage, et sa compilation native est fragile
 * sur les postes cible (absence fréquente de toolchain de build). On préfère
 * ici une approche 100% JS, sans dépendance native :
 *   1. `wmic logicaldisk where drivetype=2 get name` (drivetype=2 = amovible)
 *      pour lister les lecteurs amovibles actuellement montés, en polling.
 *   2. `chokidar` pour surveiller l'apparition de nouveaux points de montage
 *      (`X:\`) une fois détectés, et déclencher le scan sur leur contenu.
 *
 * Ce choix privilégie la fiabilité et la portabilité (pas de compilation
 * native requise) au prix d'un polling léger (toutes les 3s) plutôt que
 * d'événements système instantanés — acceptable pour ce premier incrément
 * (EF-EP-01 ne fixe pas de contrainte de latence de détection).
 */
class UsbWatcher {
  constructor({ intervalleMs = 3000 } = {}) {
    this.intervalleMs = intervalleMs;
    this.lecteursConnus = new Set();
    this.minuteur = null;
    this.gestionnaireAjout = null;
  }

  /** Démarre la surveillance. [gestionnaireAjout] reçoit la lettre de lecteur (ex. "E:\\"). */
  demarrer(gestionnaireAjout) {
    this.gestionnaireAjout = gestionnaireAjout;
    this._initialiserEtat().then(() => {
      this.minuteur = setInterval(() => this._verifier(), this.intervalleMs);
    });
  }

  arreter() {
    if (this.minuteur) clearInterval(this.minuteur);
    this.minuteur = null;
  }

  async _initialiserEtat() {
    const lecteurs = await this._listerLecteursAmovibles();
    lecteurs.forEach((l) => this.lecteursConnus.add(l));
  }

  async _verifier() {
    const lecteurs = await this._listerLecteursAmovibles();

    for (const lecteur of lecteurs) {
      if (!this.lecteursConnus.has(lecteur)) {
        this.lecteursConnus.add(lecteur);
        if (this.gestionnaireAjout) this.gestionnaireAjout(lecteur);
      }
    }

    // Nettoie les lecteurs débranchés pour permettre une nouvelle détection
    // si le même lecteur est rebranché plus tard.
    for (const connu of this.lecteursConnus) {
      if (!lecteurs.includes(connu)) this.lecteursConnus.delete(connu);
    }
  }

  /** Interroge WMIC pour les disques logiques amovibles (drivetype=2). */
  _listerLecteursAmovibles() {
    return new Promise((resolve) => {
      execFile(
        'wmic',
        ['logicaldisk', 'where', 'drivetype=2', 'get', 'name'],
        { windowsHide: true },
        (erreur, stdout) => {
          if (erreur || !stdout) {
            resolve([]);
            return;
          }

          const lecteurs = stdout
            .split(/\r?\n/)
            .map((ligne) => ligne.trim())
            .filter((ligne) => /^[A-Z]:$/.test(ligne))
            .map((lettre) => `${lettre}\\`);

          resolve(lecteurs);
        },
      );
    });
  }

  /**
   * Surveille en continu un point de montage déjà détecté (utilisé pour
   * réagir aux fichiers ajoutés après le branchement initial). Optionnel :
   * le scan initial (UsbWatcher -> FileScanner) suffit au parcours
   * quickstart.md §6 (support déjà rempli au branchement).
   */
  surveillerContenu(cheminMontage, gestionnaireFichier) {
    const observateur = chokidar.watch(cheminMontage, { ignoreInitial: false, depth: 20 });
    observateur.on('add', gestionnaireFichier);
    return observateur;
  }
}

module.exports = { UsbWatcher };

'use strict';

const fs = require('fs');
const path = require('path');

/**
 * T047 — Client de synchronisation vers le backend GUARD (mêmes 3 endpoints
 * que l'agent Android, cf. contracts/endpoint-sync-api.yaml). Offline-first
 * (Principe I) : aucune méthode ne lève d'exception fatale sur un échec
 * réseau — un échec laisse simplement la file locale intacte pour une
 * reprise ultérieure.
 *
 * Choix d'implémentation : `fetch` natif (disponible nativement depuis
 * Node 18 / Electron ≥ 28, cf. package.json `electron@^31`), pas de
 * dépendance HTTP supplémentaire.
 *
 * Persistance locale : un simple fichier JSON (pas de dépendance
 * `electron-store`, pour garder l'empreinte minimale — cohérent avec
 * SC-009). Le fichier contient uniquement des identifiants opaques
 * (terminal_id, access_token) et la file d'événements en attente ; il n'est
 * pas destiné à contenir de secret à haute valeur (pas de mot de passe), un
 * chiffrement au repos pourra être ajouté en durcissement (T053).
 */
class SyncClient {
  constructor({ baseUrl, cheminEtat }) {
    this.baseUrl = baseUrl.replace(/\/$/, '');
    this.cheminEtat = cheminEtat;
    this._chargerEtat();
  }

  _chargerEtat() {
    try {
      const brut = fs.readFileSync(this.cheminEtat, 'utf8');
      this.etat = JSON.parse(brut);
    } catch {
      this.etat = { terminalId: null, accessToken: null, versionSignatures: null, fileEvenements: [] };
    }
  }

  _sauvegarderEtat() {
    fs.mkdirSync(path.dirname(this.cheminEtat), { recursive: true });
    fs.writeFileSync(this.cheminEtat, JSON.stringify(this.etat, null, 2), 'utf8');
  }

  /** Appelé une fois à l'installation, avec l'organisation_token fourni à l'organisation cliente. */
  async enregistrer(deviceHash, appVersion, organisationToken) {
    try {
      const reponse = await fetch(`${this.baseUrl}/terminals/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          device_hash: deviceHash,
          platform: 'windows',
          app_version: appVersion,
          organisation_token: organisationToken,
        }),
      });

      if (!reponse.ok) return false;

      const corps = await reponse.json();
      this.etat.terminalId = corps.id;
      this.etat.accessToken = corps.access_token;
      this._sauvegarderEtat();

      return true;
    } catch {
      // Pas de réseau à l'installation : l'agent reste inopérant tant que
      // l'enrôlement n'a pas réussi (aucun scan_event ne peut être envoyé
      // sans access_token), mais ne plante jamais l'application.
      return false;
    }
  }

  /** Récupère et applique le delta de signatures (EF-EP-06/27) — tolère l'absence de réseau. */
  async synchroniserSignatures() {
    if (!this.etat.accessToken) return false;

    try {
      const params = this.etat.versionSignatures ? `?from_version=${encodeURIComponent(this.etat.versionSignatures)}` : '';
      const reponse = await fetch(`${this.baseUrl}/signatures/delta${params}`, {
        headers: { Authorization: `Bearer ${this.etat.accessToken}` },
      });

      if (reponse.status === 204) return true; // déjà à jour
      if (!reponse.ok) return false;

      const corps = await reponse.json();
      this.etat.versionSignatures = corps.version;
      this._sauvegarderEtat();

      return { hashesAjoutes: corps.hashes_added, reglesYaraAjoutees: corps.yara_rules_added };
    } catch {
      return false;
    }
  }

  /** Ajoute un événement à la file locale (appelé par le pipeline de scan, jamais bloquant sur le réseau). */
  enfilerEvenement(evenement) {
    this.etat.fileEvenements.push(evenement);
    this._sauvegarderEtat();
  }

  /** Envoie la file locale de scan-events non encore synchronisés, en un seul lot. */
  async synchroniserEvenements() {
    if (!this.etat.accessToken || !this.etat.terminalId) return false;
    if (this.etat.fileEvenements.length === 0) return true;

    try {
      const reponse = await fetch(`${this.baseUrl}/scan-events`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${this.etat.accessToken}`,
        },
        body: JSON.stringify({
          terminal_id: this.etat.terminalId,
          events: this.etat.fileEvenements,
        }),
      });

      // 202 (accepté) ou 409 (déjà connu du backend, idempotence par
      // client_event_id) marquent la file comme traitée : dans les deux cas,
      // retenter n'apporterait rien de plus.
      if (reponse.ok || reponse.status === 409) {
        this.etat.fileEvenements = [];
        this._sauvegarderEtat();
        return true;
      }

      return false;
    } catch {
      return false;
    }
  }
}

module.exports = { SyncClient };

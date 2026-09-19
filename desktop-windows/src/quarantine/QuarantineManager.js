'use strict';

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

/**
 * T046 — Met en quarantaine un fichier détecté malveillant (EF-EP-24) :
 * chiffrement AES-256-GCM puis déplacement dans le répertoire de quarantaine,
 * rendant le fichier inutilisable pour toute autre application. La clé est
 * fournie par l'appelant (dérivée de DPAPI / Windows Credential Manager côté
 * production — hors périmètre de cette classe, qui reste pure Node.js et
 * testable). Mêmes noms de méthode que côté Android (QuarantineManager.kt)
 * pour cohérence multi-plateforme.
 */
class QuarantineManager {
  constructor(dossierQuarantaine, cle) {
    if (cle.length !== 32) {
      throw new Error('La clé AES-256 doit faire 32 octets');
    }

    this.dossierQuarantaine = dossierQuarantaine;
    this.cle = cle;
    fs.mkdirSync(dossierQuarantaine, { recursive: true });
  }

  _cheminQuarantaine(idElementQuarantaine) {
    return path.join(this.dossierQuarantaine, `${idElementQuarantaine}.enc`);
  }

  /** Chiffre [cheminFichierOrigine] dans le dossier de quarantaine puis supprime l'original. */
  mettreEnQuarantaine(cheminFichierOrigine, idElementQuarantaine) {
    const iv = crypto.randomBytes(12);
    const cipher = crypto.createCipheriv('aes-256-gcm', this.cle, iv);

    const contenu = fs.readFileSync(cheminFichierOrigine);
    const chiffre = Buffer.concat([cipher.update(contenu), cipher.final()]);
    const tagAuth = cipher.getAuthTag();

    // IV (12) + tag d'authentification GCM (16) préfixés, nécessaires à la restauration.
    const cheminQuarantaine = this._cheminQuarantaine(idElementQuarantaine);
    fs.writeFileSync(cheminQuarantaine, Buffer.concat([iv, tagAuth, chiffre]));

    fs.unlinkSync(cheminFichierOrigine);

    return cheminQuarantaine;
  }

  /** Déchiffre un élément en quarantaine vers [cheminDestination] (restauration, EF-EP-25). */
  restaurer(idElementQuarantaine, cheminDestination) {
    const cheminQuarantaine = this._cheminQuarantaine(idElementQuarantaine);
    if (!fs.existsSync(cheminQuarantaine)) return false;

    const donnees = fs.readFileSync(cheminQuarantaine);
    const iv = donnees.subarray(0, 12);
    const tagAuth = donnees.subarray(12, 28);
    const chiffre = donnees.subarray(28);

    const decipher = crypto.createDecipheriv('aes-256-gcm', this.cle, iv);
    decipher.setAuthTag(tagAuth);

    const contenu = Buffer.concat([decipher.update(chiffre), decipher.final()]);
    fs.writeFileSync(cheminDestination, contenu);
    fs.unlinkSync(cheminQuarantaine);

    return true;
  }

  /** Supprime définitivement un élément en quarantaine (EF-EP-25). */
  supprimerDefinitivement(idElementQuarantaine) {
    const cheminQuarantaine = this._cheminQuarantaine(idElementQuarantaine);
    if (!fs.existsSync(cheminQuarantaine)) return false;

    fs.unlinkSync(cheminQuarantaine);
    return true;
  }
}

module.exports = { QuarantineManager };

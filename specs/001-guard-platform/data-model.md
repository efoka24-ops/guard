# Phase 1 — Modèle de données : GUARD Platform v2.0

**Feature**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md) | **Recherche**: [research.md](./research.md)

Périmètre de ce document : les entités nécessaires au premier incrément (**GUARD ENDPOINT niveaux 1-2** + squelette **Command Center**), en réutilisant les Key Entities du cadrage. Les entités propres à SOCIAL/WEB/ID/CODE seront complétées lors de leurs specs dérivées respectives, mais `Organisation`, `Alerte` et `Incident` sont conçues dès maintenant comme le socle commun à tous les modules (Principe VII : testabilité indépendante, mais modèle de données central partagé).

## 1. Vue d'ensemble des relations

```text
Organisation 1───* Utilisateur
Organisation 1───* Terminal
Organisation 1───* Alerte
Alerte      *───1 Terminal (nullable — une alerte peut venir d'un autre module)
Alerte      *───0..1 Incident
Incident     1───* Alerte
Terminal     1───* EvenementScan
EvenementScan 1───0..1 ElementQuarantaine
Terminal     *───1 VersionSignatures (dernière synchro connue)
```

## 2. Entités

### Organisation
Client GUARD (PME, ONG, institution). Racine du cloisonnement multi-tenant (Row Level Security applicative).

| Champ | Type | Règle |
|---|---|---|
| id | UUID | PK |
| nom | string | requis |
| pays | enum | CM, RW, SN, CI, ... (conditionne le format de rapport légal — FR-017) |
| modules_actifs | set(enum) | sous-ensemble de {ENDPOINT, SOCIAL, WEB, ID, CODE} — pilote l'affichage du Command Center (FR-023) |
| score_securite_global | int 0-100 | calculé, recalculé à chaque nouvelle alerte/résolution |
| mode_msp | bool | true si géré par un MSP partenaire (FR-025) |
| cree_le / maj_le | timestamp | |

### Utilisateur
Personne rattachée à une Organisation, avec un profil (cf. section 2 de la SFD : Dirigeant, IT Manager, Développeur, Community Manager, Admin GUARD, Auditeur).

> **Implémentation** : table physique `users` (convention Laravel/Sanctum), pas `utilisateurs` — évite de dupliquer le système d'authentification. Voir `guard/backend/app/Models/User.php`.

| Champ | Type | Règle |
|---|---|---|
| id | UUID | PK |
| organisation_id | UUID (nullable) | null uniquement pour un Admin GUARD multi-organisations |
| email | string | unique, requis |
| role | enum | dirigeant, it_manager, developpeur, community_manager, admin_guard, auditeur |
| mfa_active | bool | requis = true pour admin_guard et auditeur (Principe V) |
| langue | enum | fr, en (extensible kinyarwanda/wolof/dioula plus tard) |

### Terminal *(GUARD ENDPOINT)*
Appareil Android ou Windows protégé.

| Champ | Type | Règle |
|---|---|---|
| id | UUID | PK |
| organisation_id | UUID | requis |
| plateforme | enum | android, windows |
| identifiant_appareil | string | hash stable (IMEI/Android ID salé, ou GUID machine Windows) — jamais l'identifiant brut (vie privée) |
| version_app | string | version de l'agent GUARD ENDPOINT installé |
| version_signatures | string | dernière version de la base locale synchronisée (FR-006) |
| derniere_synchro_le | timestamp | nullable si jamais synchronisé (fonctionnement offline pur toléré — Principe I) |
| score_risque_comportemental | int 0-100 | dernière valeur connue (EF-EP-09) |
| statut | enum | actif, hors_ligne_prolonge (>30j), desinstalle |

### EvenementScan *(GUARD ENDPOINT)*
Résultat d'un scan à 3 niveaux sur un fichier détecté (support externe, APK, etc.).

| Champ | Type | Règle |
|---|---|---|
| id | UUID | PK |
| terminal_id | UUID | requis |
| declencheur | enum | usb, apk_install, fichier_local, sms_lien, email_piece_jointe |
| nom_fichier | string | tel que vu localement (jamais uploadé en clair vers le backend, seul le hash l'est — Principe IV) |
| hash_sha256 | string(64) | requis |
| niveau_max_atteint | enum | 1_hash, 2_yara, 3_ia | jusqu'où le scan est allé avant conclusion |
| regle_yara_correspondante | string (nullable) | si niveau 2 déclenché |
| score_confiance_ia | int 0-100 (nullable) | si niveau 3 exécuté (EF-EP-07) — **hors périmètre du 1er incrément (niveaux 1-2 seulement, cf. research.md §5)** |
| classification | enum | clean, suspect, probable_malware, malware_confirme |
| action_prise | enum | aucune, surveillance, quarantaine_proposee, quarantaine_auto |
| survenu_le | timestamp | requis, horodatage local terminal (fonctionnement offline) |
| synchronise_le | timestamp (nullable) | rempli à la remontée backend |

### ElementQuarantaine *(GUARD ENDPOINT)*
Fichier isolé suite à un EvenementScan positif (EF-EP-24/25).

| Champ | Type | Règle |
|---|---|---|
| id | UUID | PK |
| evenement_scan_id | UUID | requis, unique |
| chemin_origine | string | local au terminal uniquement (non remonté en clair) |
| raison | enum | hash_connu, yara, score_ia, comportemental |
| statut | enum | en_quarantaine, restaure, supprime, envoye_analyse |
| resolu_par_utilisateur_id | UUID (nullable) | traçabilité action manuelle |
| resolu_le | timestamp (nullable) | |

### VersionSignatures *(GUARD ENDPOINT, référentiel partagé)*
Représente une version publiée de la base de signatures/règles YARA (delta hebdomadaire — FR-006).

| Champ | Type | Règle |
|---|---|---|
| id | UUID | PK |
| numero_version | string | ex. `2026.09.15` |
| taille_delta_ko | int | doit rester < 50 (contrainte SC-010) |
| nb_hashes_ajoutes | int | |
| nb_regles_yara_ajoutees | int | |
| publie_le | timestamp | |
| checksum_delta | string | intégrité du paquet delta |

### Alerte *(transversale — Command Center)*
Événement de sécurité détecté par n'importe quel module.

| Champ | Type | Règle |
|---|---|---|
| id | UUID | PK |
| organisation_id | UUID | requis |
| module_source | enum | ENDPOINT, SOCIAL, WEB, ID, CODE |
| terminal_id | UUID (nullable) | rempli si module_source = ENDPOINT |
| niveau_criticite | enum | info, faible, moyen, eleve, critique |
| titre | string | résumé compréhensible non technicien (Principe VI) |
| description_technique | text (nullable) | détail réservé profils IT/Dev |
| statut | enum | nouvelle, accusee_reception, assignee, resolue, ignoree |
| sla_echeance_le | timestamp | calculée selon niveau_criticite (FR-024) |
| assignee_utilisateur_id | UUID (nullable) | |
| incident_id | UUID (nullable) | rattachement si corrélée |
| cree_le | timestamp | |

### Incident *(transversale)*
Regroupement d'alertes corrélées, base du GUARD LEGAL PACK (FR-017).

| Champ | Type | Règle |
|---|---|---|
| id | UUID | PK |
| organisation_id | UUID | requis |
| titre | string | |
| type | enum | malware, piratage_social, defacement, fuite_donnees, usurpation, autre |
| statut | enum | ouvert, en_cours, cloture |
| dossier_legal_genere | bool | true si GUARD LEGAL PACK produit |
| dossier_legal_url | string (nullable) | lien S3 signé |
| pays_autorite_cible | enum (nullable) | ANTIC_CM, RCA_RW, ARTCI_CI, ADIE_SN |
| ouvert_le / cloture_le | timestamp | |

## 3. Règles de cohérence transverses

- Toute alerte `niveau_criticite = critique` DOIT avoir `sla_echeance_le` ≤ 24h après création (FR-024, SLA rouge à J+3 sinon).
- Un `Terminal` en `statut = desinstalle` ne doit plus générer de nouvel `EvenementScan` ; les événements historiques sont conservés (traçabilité GUARD LEGAL PACK).
- `EvenementScan.score_confiance_ia` reste `null` tant que le niveau 3 (modèle TFLite) n'est pas disponible pour l'organisation — cohérent avec le séquencement retenu en Phase 0 (niveaux 1-2 d'abord).
- La suppression d'une `Organisation` (résiliation) déclenche une purge différée (period de rétention légale à définir avec juridique par pays) plutôt qu'une suppression immédiate en cascade — hors périmètre détaillé de ce premier incrément, à traiter dans une spec dédiée "Rétention & RGPD/DPPA".

## 4. Hors périmètre de ce document (renvoyé aux specs dérivées des autres modules)

CompteSocialSurveille, SiteWebSurveille, IdentiteNumeriqueSurveillee, ProjetCode — modélisés lors du cadrage Phase 1 spécifique à SOCIAL, WEB, ID et CODE respectivement, une fois ces modules priorisés (cf. séquencement research.md §5).

# Prérequis — GUARD Platform v2.0 (`001-guard-platform`)

État vérifié le 2026-09-17, équivalent au contrôle effectué par `check-prerequisites.ps1`.

## 1. Prérequis structurels (speckit)

| Élément | Statut | Chemin |
|---|---|---|
| Dossier feature | ✅ Créé | `guard/specs/001-guard-platform/` |
| `spec.md` (cadrage) | ✅ Présent | `guard/specs/001-guard-platform/spec.md` |
| Checklist qualité du cadrage | ✅ Présent, toutes cases validées | `guard/specs/001-guard-platform/checklists/requirements.md` |
| `plan.md` (plan d'implémentation) | ✅ Présent | `guard/specs/001-guard-platform/plan.md` |
| Constitution GUARD | ✅ Ratifiée v1.0.0 | `guard/.specify/memory/constitution.md` |
| `research.md` (Phase 0) | ⬜ À produire | non créé |
| `data-model.md` (Phase 1) | ⬜ À produire | non créé |
| `contracts/` (Phase 1) | ⬜ À produire | non créé |
| `quickstart.md` (Phase 1) | ⬜ À produire | non créé |
| `tasks.md` (Phase 2) | ⬜ Bloqué tant que Phase 0/1 incomplètes | non créé |

**Constat** : `spec.md` et `plan.md` existent — le prérequis minimal pour lancer `$speckit-tasks` est techniquement satisfait, mais les livrables de Phase 0 (recherche technique) et Phase 1 (modèle de données, contrats API, quickstart) sont fortement recommandés avant de découper les tâches, vu la taille du périmètre (5 modules).

## 2. Prérequis d'environnement (hors speckit)

| Élément | Statut | Remarque |
|---|---|---|
| Dépôt Git initialisé | ❌ Non initialisé | Le répertoire de travail `C:\Users\YCXL3291\tru trace` n'est pas un dépôt git ; `check-prerequisites.ps1` et la création de branche par hook (`before_specify`) ne fonctionneront pas tant que `git init` n'a pas été fait. |
| PowerShell 5.1 disponible | ✅ | Scripts `.specify/scripts/powershell/*.ps1` utilisables |
| Backend Laravel 10 / PHP 8.1 | ⬜ À provisionner | Aucun code backend `guard/backend` existant à ce stade |
| Microservice Python FastAPI (GUARD CODE) | ⬜ À provisionner | Dépendances : Semgrep, Bandit, detect-secrets, truffleHog, pip-audit |
| Projet Android Kotlin (GUARD ENDPOINT mobile) | ⬜ À provisionner | Min SDK 26, TensorFlow Lite runtime |
| Projet Electron (GUARD ENDPOINT Windows) | ⬜ À provisionner | |
| Dashboard React (COMMAND CENTER) | ⬜ À provisionner | |
| Comptes/API tiers | ⬜ À obtenir | Meta Graph API, LinkedIn API, X API v2, Have I Been Pwned, VirusTotal, AbuseIPDB, NVD/OSV — clés d'accès et quotas à négocier avant Phase 0 |
| Infrastructure AWS af-south-1 | ⬜ À provisionner | MySQL 8, Redis 7, S3 chiffré, CloudFront |
| Base de signatures/YARA initiale | ⬜ À constituer | ~80 000 hashes + règles YARA (source initiale à définir : VirusTotal, CIRCL MISP, base propriétaire TRU GROUP) |
| Modèle IA TFLite (MobileNetV3-Small fine-tuné) | ⬜ À entraîner | Nécessite un corpus labellisé de binaires (clean/trojan/ransomware/spyware/coinminer/adware/backdoor) |

## 3. Recommandation de séquencement

1. `git init` sur le dépôt (ou confirmer le dépôt cible réel si différent de ce répertoire) — bloquant pour l'automatisation de branche speckit.
2. Confirmer le périmètre du **premier incrément livrable** parmi les 5 modules (recommandation : GUARD ENDPOINT en premier, cf. priorité P1 et valeur socle — voir `spec.md` User Story 1).
3. Lancer la Phase 0 (`research.md`) : lever les inconnues techniques (choix définitif du corpus d'entraînement IA, quotas des APIs tierces, choix de l'hébergeur de dev/staging avant af-south-1 prod).
4. Lancer la Phase 1 (`data-model.md`, `contracts/`, `quickstart.md`) sur la base des Key Entities du `spec.md` (Organisation, Terminal, Alerte, Incident, etc.).
5. Exécuter `$speckit-tasks` pour générer le découpage en tâches par module, en respectant l'ordre de priorité P1 → P2 défini dans `spec.md`.

## 4. Risques identifiés à ce stade

- **Dépendance à des APIs tierces à quotas/coûts variables** (Meta, LinkedIn, X, VirusTotal) : à cadrer financièrement avant engagement du développement GUARD SOCIAL/ENDPOINT.
- **Entraînement du modèle IA TFLite** : activité à plus long délai (collecte + labellisation de corpus malveillant) — à démarrer en parallèle dès la Phase 0 pour ne pas bloquer GUARD ENDPOINT.
- **Conformité réglementaire multi-pays** (ANTIC, RCA, ARTCI, ADIE) : formats de rapport à valider avec un interlocuteur juridique/réglementaire par pays avant de figer `GUARD LEGAL PACK`.

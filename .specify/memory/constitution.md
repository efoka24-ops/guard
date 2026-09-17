# GUARD Platform Constitution

## Core Principles

### I. Offline-First
Chaque module GUARD (ENDPOINT, SOCIAL, WEB, ID, CODE) doit fonctionner en mode dégradé sans connexion permanente. La détection critique locale (hash SHA-256, YARA, IA TFLite) ne dépend jamais du réseau. La synchronisation avec le backend est asynchrone, reprend depuis le dernier point de contrôle, et est dimensionnée pour des connexions 2G/3G intermittentes (deltas < 50 Ko). Aucune fonctionnalité de sécurité critique ne peut être bloquante sur la disponibilité réseau.

### II. Légèreté & Sobriété Ressources (NON-NÉGOCIABLE)
Les agents terminaux doivent respecter des budgets de ressources stricts : < 80 Mo RAM sur Android, < 5 % CPU en surveillance continue, inférence IA embarquée < 2 secondes offline. Tout ajout de fonctionnalité côté terminal doit être mesuré contre ces budgets avant merge ; un dépassement doit être justifié explicitement (Complexity Tracking) ou refusé.

### III. Threat Intelligence Africaine Locale
La base de signatures, les règles YARA, les templates de smishing/phishing et les feeds de threat intelligence doivent inclure une catégorie propriétaire dédiée aux menaces spécifiquement africaines (arnaques Mobile Money, fausses pages gouvernementales, faux concours ONG, malwares USB régionaux), mise à jour au minimum hebdomadairement par l'équipe TRU GROUP.

### IV. Confidentialité & Souveraineté des Données
Les données des organisations africaines (logs, alertes, preuves légales, contenu scanné) sont hébergées en priorité sur des infrastructures situées en Afrique (AWS af-south-1). Aucun secret ou contenu de fichier scanné n'est envoyé en clair vers un tiers sans consentement explicite. Les rapports et preuves légales sont horodatés et signés (SHA-256 + RFC 3161) pour rester recevables devant l'ANTIC (Cameroun), la RCA (Rwanda) et les autorités homologues.

### V. Sécurité par Conception (Secure by Design)
Tout code livré dans la plateforme GUARD est lui-même soumis aux contrôles qu'il propose à ses clients (dogfooding via GUARD CODE en CI/CD). Aucune clé API, secret ou credential n'est commité en clair. L'authentification backend utilise JWT (Laravel Sanctum) avec MFA obligatoire pour les comptes administrateur GUARD et les comptes MSP multi-clients.

### VI. Explicabilité & Accessibilité des Alertes
Chaque alerte générée par un module GUARD doit être compréhensible par un utilisateur non technicien (dirigeant PME) : score de risque 0–100, explication en français en langage clair, action recommandée. Le detail technique (score CVSS, logs bruts) reste disponible pour les profils IT/Dev sans être imposé par défaut.

### VII. Testabilité Indépendante par Module
Chaque module (ENDPOINT, SOCIAL, WEB, ID, CODE) doit livrer une valeur autonome et être testable indépendamment des autres modules. Le GUARD COMMAND CENTER agrège mais ne doit jamais être une dépendance bloquante pour qu'un module fonctionne seul.

## Contraintes Techniques & Conformité

- Stack imposée : PHP 8.2+ / Laravel 12 (backend — dernière version supportée ; Laravel 10 écarté le 2026-09-17 pour cause de fin de support et d'avis de sécurité non patchés, cf. research.md §4bis de 001-guard-platform), Kotlin (Android), Electron (Windows), Python/FastAPI (moteur SAST/SCA), TensorFlow Lite INT8 (IA embarquée), MySQL 8, Redis 7, React.js (dashboard), AWS af-south-1.
- Conformité obligatoire : OWASP Top 10, CVE/NVD, MITRE ATT&CK, ANTIC (Cameroun), RCA (Rwanda), DPPA Rwanda 2021, ARTCI (Côte d'Ivoire), ADIE (Sénégal).
- Android minimum API 26 (Android 8.0). Compatibilité Windows 7/10/11 pour l'agent Electron.
- Toute intégration tierce (Meta Graph API, LinkedIn API, X API, HIBP, VirusTotal, AbuseIPDB) doit être encapsulée derrière une couche d'abstraction backend pour permettre substitution sans impact client.

## Workflow de Développement

- Toute nouvelle fonctionnalité démarre par une spécification (`spec.md`) validée contre le checklist qualité avant passage en `plan.md`.
- Les gates de conformité (RAM, CPU, offline-first, conformité réglementaire) sont vérifiées au Constitution Check du plan, avant la Phase 0 (research) et re-vérifiées après la Phase 1 (design).
- Tout écart aux principes I, II ou IV doit être documenté et justifié dans la section Complexity Tracking du plan correspondant ; à défaut, la fonctionnalité est refusée en revue.
- Les rapports générés (PDF, dossiers légaux) doivent être testés avec des données réelles de test avant toute mise en production.

## Governance

Cette constitution prévaut sur toute pratique de développement ponctuelle au sein du dossier `guard/`. Toute modification nécessite : documentation du changement, justification, et mise à jour de la version ci-dessous. Les revues de code et de plan doivent vérifier explicitement la conformité aux principes I à VII.

**Version**: 1.0.1 | **Ratified**: 2026-09-17 | **Last Amended**: 2026-09-17
<!-- 1.0.1 : mise à jour de la stack backend (Laravel 10 EOL → Laravel 12), voir Contraintes Techniques & Conformité -->

# Implementation Plan: GUARD Platform — Suite de Cybersécurité Africaine v2.0

**Branch**: `001-guard-platform` | **Date**: 2026-09-17 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `guard/specs/001-guard-platform/spec.md`

## Summary

Livrer une suite de cybersécurité à 5 modules indépendants mais interopérables (ENDPOINT, SOCIAL, WEB, ID, CODE), agrégés par un GUARD COMMAND CENTER, conçus offline-first pour le contexte africain (connectivité intermittente, terminaux d'entrée de gamme, menaces régionales spécifiques). L'approche technique retenue est une architecture multi-agents (Android Kotlin, Electron Windows) + backend API central (Laravel) + microservice Python dédié à l'analyse de code (GUARD CODE) + dashboard web React, avec un moteur IA embarqué (TFLite) pour la détection offline et des bases de signatures locales synchronisées en delta.

## Technical Context

**Language/Version**: Kotlin (Android, min API 26) · Node.js/Electron (Windows 7/10/11) · PHP 8.1 (Laravel 10) · Python 3.11 (FastAPI) · TypeScript/React.js (dashboard)

**Primary Dependencies**: Laravel 10 + Sanctum (JWT) + Horizon (queues) · Semgrep + Bandit + detect-secrets + truffleHog (moteur SAST/secrets) · TensorFlow Lite (INT8, MobileNetV3-Small) · YARA · Meta Graph API / LinkedIn API / X API v2 (GUARD SOCIAL) · Puppeteer (captures d'écran, PDF) · Recharts + MapLibre GL (dashboard)

**Storage**: MySQL 8.0 (backend, partitionné par organisation) · SQLite local embarqué (signatures/YARA sur terminal) · Redis 7 (cache sessions, queues, pub/sub temps réel) · AWS S3 chiffré (preuves légales, rapports PDF)

**Testing**: PHPUnit (backend Laravel) · pytest (moteur Python SAST/SCA) · JUnit/Espresso (Android) · Playwright/Jest (dashboard React) · EICAR + corpus de malwares de test (validation moteur ENDPOINT, environnement isolé)

**Target Platform**: Android 8.0+ (GUARD ENDPOINT mobile) · Windows 7/10/11 (GUARD ENDPOINT desktop) · Web responsive desktop-first (GUARD COMMAND CENTER) · Linux/AWS af-south-1 (backend et microservices)

**Project Type**: Suite multi-projets (mobile-app + desktop-app + web-service + web-dashboard + cli/ci-cd-plugin), orchestrée par un backend API central commun

**Performance Goals**: Scan niveau 1 (hash) < 0,5 s/fichier · niveau 2 (YARA) < 5 s/fichier · niveau 3 (IA TFLite) < 2 s/fichier offline · monitoring GUARD WEB : ping 2 min, détection défacement < 5 min · scan GUARD CODE complet < 5 min pour un projet < 50k LOC en CI/CD

**Constraints**: < 80 Mo RAM et < 5 % CPU en continu sur Android · deltas de mise à jour de signatures < 50 Ko (compatible 2G/EDGE) · fonctionnement offline complet pour la détection critique (niveaux 1–3 ENDPOINT) · données hébergées prioritairement en Afrique (af-south-1) · secrets jamais affichés en clair dans les rapports

**Scale/Scope**: 5 modules fonctionnels + 1 command center + 1 module transversal (AI Analyst) ; cible initiale PME 5–200 employés, marchés Cameroun/Rwanda/Sénégal/Côte d'Ivoire ; architecture multi-tenant (mode MSP multi-organisations)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principe constitutionnel | Statut | Justification |
|---|---|---|
| I. Offline-First | PASS | Scan 3-niveaux ENDPOINT, base SQLite locale et modèle TFLite embarqué garantissent la détection critique sans réseau (FR-001, FR-002) |
| II. Légèreté & Sobriété Ressources | PASS (à revérifier Phase 1) | Budgets RAM/CPU/latence explicitement repris en Technical Context et SC-009 ; nécessite validation empirique lors du design détaillé de l'agent Android |
| III. Threat Intelligence Africaine Locale | PASS | Base propriétaire TRU GROUP identifiée comme composant explicite (FR-006, FR-015, FR-016) |
| IV. Confidentialité & Souveraineté des Données | PASS | Hébergement af-south-1 retenu, S3 chiffré pour preuves légales, secrets masqués (FR-020) |
| V. Sécurité par Conception | PASS | GUARD CODE s'applique en dogfooding sur le pipeline CI/CD de GUARD lui-même (recommandation Phase 1) ; JWT + MFA pour comptes admin/MSP à détailler en Phase 1 |
| VI. Explicabilité & Accessibilité des Alertes | PASS | Score 0–100 et langage clair repris systématiquement dans les FR de chaque module |
| VII. Testabilité Indépendante par Module | PASS | Chaque user story (P1–P2) est indépendamment testable ; Command Center non bloquant par conception (FR-023 à FR-025) |

Aucune violation nécessitant une entrée dans Complexity Tracking à ce stade.

## Project Structure

### Documentation (this feature)

```text
guard/specs/001-guard-platform/
├── plan.md                # Ce fichier
├── spec.md                # Cadrage fonctionnel (Phase préalable)
├── checklists/
│   └── requirements.md    # Checklist qualité du cadrage
├── research.md             # Phase 0 (à produire — $speckit-plan / recherche technique)
├── data-model.md           # Phase 1 (à produire)
├── quickstart.md           # Phase 1 (à produire)
├── contracts/               # Phase 1 (à produire — contrats API par module)
└── tasks.md                 # Phase 2 (produit par $speckit-tasks, hors périmètre de ce plan)
```

### Source Code (repository root, proposé)

```text
guard/
├── backend/                       # Laravel 10 — API centrale, orchestration, facturation, alertes
│   ├── app/
│   │   ├── Modules/
│   │   │   ├── Endpoint/          # Ingestion signatures, gestion terminaux, quarantaine
│   │   │   ├── Social/            # Intégrations Meta/LinkedIn/X, scoring connexions
│   │   │   ├── Web/               # Monitoring, scanner OWASP, gestion SSL
│   │   │   ├── Id/                # Threat intel, breach monitoring, legal pack
│   │   │   └── Code/              # Orchestration appels au microservice SAST/SCA
│   │   └── Services/CommandCenter/ # Agrégation score global, alertes, SLA
│   └── tests/
│       ├── Feature/ (par module)
│       └── Unit/
│
├── code-engine/                   # Microservice Python FastAPI — GUARD CODE (SAST/SCA/secrets/IaC)
│   ├── app/
│   │   ├── sast/  (Semgrep wrappers par langage)
│   │   ├── sca/   (comparaison CVE : NVD, OSV, GitHub Advisory)
│   │   ├── secrets/ (detect-secrets, truffleHog, scan historique Git)
│   │   └── iac/   (Dockerfile, K8s, Terraform, CI/CD configs)
│   └── tests/
│
├── mobile-android/                # GUARD ENDPOINT — app Kotlin
│   ├── app/src/main/kotlin/
│   │   ├── scanner/  (hash, YARA, IA TFLite)
│   │   ├── mobilemoney/ (anti-overlay, anti-keylogger)
│   │   ├── phishing/ (SMS/email/WhatsApp)
│   │   └── quarantine/
│   ├── models/  (fichier .tflite embarqué)
│   └── src/test + src/androidTest
│
├── desktop-windows/                # GUARD ENDPOINT — app Electron
│   ├── src/scanner/
│   ├── src/usb-watcher/
│   └── tests/
│
├── dashboard-web/                  # GUARD COMMAND CENTER — React.js
│   ├── src/
│   │   ├── modules/ (Endpoint, Social, Web, Id, Code — vues dédiées)
│   │   ├── commandcenter/ (score global, alertes, SLA, mode MSP)
│   │   └── components/
│   └── tests/
│
├── cli/                             # @tru-group/guard-code — package npm CLI
│   └── src/
│
├── integrations/
│   ├── github-action/               # guard-code-action
│   ├── gitlab-ci-image/
│   └── vscode-extension/
│
└── signatures/                      # Base de signatures/YARA/threat-intel africaine (source de vérité, synchronisée en delta)
```

**Structure Decision**: Monorepo `guard/` multi-projets avec un backend Laravel central orchestrant 4 modules métier (Endpoint, Social, Web, Id) via des sous-domaines applicatifs (`app/Modules/*`), un microservice Python dédié pour GUARD CODE (isolé pour ses dépendances spécifiques SAST/SCA), des clients terminaux natifs séparés (Android Kotlin, Electron Windows) pour respecter les contraintes de légèreté (Principe II), et un dashboard web React consommant l'API centrale. Cette séparation permet la Testabilité Indépendante par Module (Principe VII) : chaque module peut être développé, testé et déployé sans bloquer les autres, tout en partageant l'authentification et le modèle de données Organisation/Alerte/Incident au niveau du backend central.

## Complexity Tracking

> Aucune violation constitutionnelle identifiée à ce stade — section laissée vide intentionnellement.

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| — | — | — |

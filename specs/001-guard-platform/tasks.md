# Tasks: GUARD Platform v2.0 — Premier incrément (Backend + GUARD ENDPOINT niveaux 1-2)

**Input**: [spec.md](./spec.md), [plan.md](./plan.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/endpoint-sync-api.yaml](./contracts/endpoint-sync-api.yaml), [quickstart.md](./quickstart.md)

**Périmètre retenu pour cet incrément** (cf. research.md §5) : Setup + Foundational + **User Story 1** (GUARD ENDPOINT, niveaux 1-2 uniquement — le niveau 3 IA TFLite est un incrément séparé). User Story 2 (GUARD WEB) est esquissée en Phase 5 comme prochain incrément mais non détaillée tâche par tâche ici.

**Tests**: inclus (contrat + intégration) car le cadrage définit des critères d'acceptation testables (spec.md) et un parcours de bout en bout explicite (quickstart.md §6).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Peut être fait en parallèle (fichiers différents, pas de dépendance)
- **[Story]**: US1 = GUARD ENDPOINT niveaux 1-2

## Path Conventions

Suit `plan.md` : `guard/backend/` (Laravel), `guard/mobile-android/` (Kotlin), `guard/desktop-windows/` (Electron).

---

## Phase 1: Setup (Infrastructure partagée)

- [x] T001 Créer le projet Laravel dans `guard/backend/` (Laravel 12.69.2, PHP 8.2+ — Laravel 10 abandonné, EOL/vulnérable, cf. research.md §4bis)
- [x] T002 [P] Configurer `guard/backend/.env` — `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database` sont déjà les défauts Laravel 12 (aucune modif nécessaire), `APP_NAME=GUARD`, `APP_LOCALE=fr`
- [x] T003 [P] Installer et configurer Sanctum (`php artisan install:api`) pour l'auth JWT (Principe V) — migration `personal_access_tokens` appliquée
- [x] T004 [P] Configurer le linting PHP (Pint) dans `guard/backend/` — déjà inclus par `laravel/laravel`, `vendor/bin/pint --test` passe
- [x] T005 [P] Initialiser le squelette agent Android dans `guard/mobile-android/` (Kotlin, min SDK 26, Gradle + arborescence scanner/quarantine/sync/ui)
- [x] T006 [P] Initialiser le squelette agent Windows dans `guard/desktop-windows/` (Electron, package.json + main.js)

---

## Phase 2: Foundational (Bloquant — doit être fait avant toute User Story)

**⚠️ CRITIQUE**: Aucune tâche de User Story ne démarre avant la fin de cette phase.

- [x] T007 Créer la migration `organisations` dans `guard/backend/database/migrations/` (cf. data-model.md §2 Organisation)
- [x] T008 Étendre la table `users` (convention Laravel/Sanctum conservée au lieu de `utilisateurs`, cf. note dans le modèle `User`) avec `organisation_id`, `role`, `mfa_active`, `langue`
- [x] T009 Table `jobs`/`failed_jobs` pour la queue driver database — déjà créée par le scaffold Laravel 12 (T001)
- [x] T010 [P] Implémenter le modèle `Organisation` dans `guard/backend/app/Models/Organisation.php`
- [x] T011 [P] Étendre le modèle `User` (`HasApiTokens`, relation `organisation()`) dans `guard/backend/app/Models/User.php`
- [x] T012 Middleware `ScopeToOrganisation` (alias `scope.organisation`) dans `guard/backend/app/Http/Middleware/ScopeToOrganisation.php`
- [x] T013 [P] Créer les migrations `alertes` et `incidents` (entités transversales, cf. data-model.md §2)
- [x] T014 [P] Implémenter les modèles `Alerte` et `Incident` dans `guard/backend/app/Services/CommandCenter/Models/`
- [x] T015 `Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()` dans `routes/console.php` — point d'entrée cron unique (research.md §1)
- [x] T016 [P] Canal de log dédié `guard_alerts` (rétention 90j) dans `guard/backend/config/logging.php`

**Checkpoint**: Fondations prêtes — le développement de User Story 1 peut commencer.

---

## Phase 3: User Story 1 — Protection USB/APK/Mobile Money, niveaux 1-2 (Priority: P1) 🎯 MVP

**Goal**: Détecter et mettre en quarantaine un fichier malveillant connu (hash) ou suspect (YARA) introduit via un support externe, offline, avec remontée asynchrone d'alerte au backend dès reconnexion.

**Independent Test**: Brancher un support contenant un fichier EICAR sur un terminal sans réseau → quarantaine locale immédiate → reconnexion → alerte visible dans `GET /alerts` (cf. quickstart.md §6).

### Tests for User Story 1 ⚠️

- [ ] T017 [P] [US1] Test de contrat `POST /terminals/register` dans `guard/backend/tests/Feature/Endpoint/RegisterTerminalTest.php`
- [ ] T018 [P] [US1] Test de contrat `GET /signatures/delta` dans `guard/backend/tests/Feature/Endpoint/SignatureDeltaTest.php`
- [ ] T019 [P] [US1] Test de contrat `POST /scan-events` (y compris idempotence via `client_event_id`) dans `guard/backend/tests/Feature/Endpoint/ScanEventsTest.php`
- [ ] T020 [P] [US1] Test de contrat `GET /alerts` + `POST /alerts/{id}/acknowledge` dans `guard/backend/tests/Feature/CommandCenter/AlertsTest.php`
- [ ] T021 [US1] Test d'intégration bout-en-bout "EICAR offline → quarantaine → sync → alerte critique ≤ SLA 24h" dans `guard/backend/tests/Feature/Endpoint/EndToEndScanTest.php` (dépend de T017-T020)
- [ ] T022 [P] [US1] Test unitaire Android : calcul hash SHA-256 + correspondance base locale dans `guard/mobile-android/app/src/test/HashScannerTest.kt`
- [ ] T023 [P] [US1] Test unitaire Android : correspondance règle YARA sur fichier de test dans `guard/mobile-android/app/src/test/YaraScannerTest.kt`

### Implementation — Backend (`guard/backend/`)

- [ ] T024 [P] [US1] Migration + modèle `Terminal` dans `guard/backend/database/migrations/` et `app/Modules/Endpoint/Models/Terminal.php` (dépend de T007)
- [ ] T025 [P] [US1] Migration + modèle `VersionSignatures` dans `app/Modules/Endpoint/Models/VersionSignatures.php`
- [ ] T026 [US1] Migration + modèle `EvenementScan` dans `app/Modules/Endpoint/Models/EvenementScan.php` (dépend de T024)
- [ ] T027 [US1] Migration + modèle `ElementQuarantaine` dans `app/Modules/Endpoint/Models/ElementQuarantaine.php` (dépend de T026)
- [ ] T028 [US1] Implémenter `POST /terminals/register` dans `app/Modules/Endpoint/Http/Controllers/TerminalController.php` (dépend de T024)
- [ ] T029 [US1] Implémenter `GET /signatures/delta` (calcul du delta depuis `from_version`) dans `app/Modules/Endpoint/Http/Controllers/SignatureController.php` (dépend de T025)
- [ ] T030 [US1] Implémenter `POST /scan-events` en batch idempotent (clé `client_event_id`) dans `app/Modules/Endpoint/Http/Controllers/ScanEventController.php` (dépend de T026)
- [ ] T031 [US1] Job asynchrone (queue database) : création d'`Alerte` à partir d'un `EvenementScan` classifié `malware_confirme`/`probable_malware`, avec calcul du `sla_echeance_le` selon criticité dans `app/Modules/Endpoint/Jobs/CreerAlerteDepuisScan.php` (dépend de T030, T014)
- [ ] T032 [US1] Implémenter `GET /alerts` et `POST /alerts/{id}/acknowledge` dans `app/Services/CommandCenter/Http/Controllers/AlertController.php` (dépend de T014)
- [ ] T033 [US1] Validation des payloads (hash 64 car., enum trigger/classification/action) et gestion d'erreurs 401/409 dans les controllers Endpoint

### Implementation — Base de signatures de test

- [ ] T034 [P] [US1] Constituer le jeu de hashes de test (EICAR + échantillons bénins) dans `guard/signatures/test/hashes.json`
- [ ] T035 [P] [US1] Écrire 2-3 règles YARA de test dans `guard/signatures/test/rules/`
- [ ] T036 [US1] Générer le premier `VersionSignatures` (paquet complet initial) via une commande artisan `php artisan signatures:publish` dans `guard/backend/app/Console/Commands/PublishSignatures.php` (dépend de T025, T034, T035)

### Implementation — Agent Android (`guard/mobile-android/`)

- [ ] T037 [P] [US1] Détecteur de connexion USB / nouvelle installation APK (BroadcastReceiver) dans `app/src/main/kotlin/scanner/DetectionTrigger.kt`
- [ ] T038 [US1] Scanner niveau 1 (hash SHA-256 + lookup SQLite local) dans `app/src/main/kotlin/scanner/HashScanner.kt` (dépend de T022)
- [ ] T039 [US1] Scanner niveau 2 (moteur YARA embarqué) dans `app/src/main/kotlin/scanner/YaraScanner.kt` (dépend de T038, T023)
- [ ] T040 [US1] Gestion de la quarantaine locale (déplacement chiffré AES-256) dans `app/src/main/kotlin/quarantine/QuarantineManager.kt` (dépend de T039)
- [ ] T041 [US1] File d'envoi différée des `ScanEvent` (Room/SQLite local, UUID `client_event_id`) dans `app/src/main/kotlin/sync/ScanEventQueue.kt` (dépend de T040)
- [ ] T042 [US1] Client de synchronisation vers `POST /scan-events` et `GET /signatures/delta` dans `app/src/main/kotlin/sync/SyncClient.kt` (dépend de T041, T028-T030)
- [ ] T043 [US1] Écran de quarantaine (liste, restaurer, supprimer) dans `app/src/main/kotlin/ui/QuarantineScreen.kt` (dépend de T040)

### Implementation — Agent Windows (`guard/desktop-windows/`)

- [ ] T044 [P] [US1] Détecteur de connexion USB (Electron, écoute des événements système) dans `src/usb-watcher/UsbWatcher.js`
- [ ] T045 [US1] Scanner niveau 1 + 2 (réutilisation de la logique hash/YARA côté Node.js) dans `src/scanner/FileScanner.js` (dépend de T044)
- [ ] T046 [US1] Quarantaine locale Windows dans `src/quarantine/QuarantineManager.js` (dépend de T045)
- [ ] T047 [US1] Synchronisation vers le backend (mêmes endpoints que l'agent Android) dans `src/sync/SyncClient.js` (dépend de T046, T028-T030)

**Checkpoint**: User Story 1 fonctionnelle et testable indépendamment (parcours quickstart.md §6 validé sur Android ET Windows).

---

## Phase 4: Command Center minimal (support transversal de US1)

**Purpose**: Rendre visibles les alertes générées par US1 sans attendre le dashboard complet.

- [ ] T048 [P] Endpoint `GET /organisations/{id}/score` (calcul simple : 100 − pondération alertes actives par criticité) dans `guard/backend/app/Services/CommandCenter/Http/Controllers/ScoreController.php` (dépend de T014)
- [ ] T049 [P] Vue minimale (CLI ou route JSON brute) listant les alertes actives pour validation manuelle avant dashboard React (dépend de T032)

---

## Phase 5: Prochain incrément (aperçu, non détaillé ici)

- [ ] T050 Rédiger le cadrage Phase 1 dédié à **GUARD WEB** (User Story 2 du spec.md) une fois US1 validée en conditions réelles (cf. research.md §5 — module #2 recommandé)
- [ ] T051 Rédiger le cadrage Phase 1 dédié au **niveau 3 GUARD ENDPOINT** (IA TFLite) une fois le corpus d'entraînement constitué (cf. research.md §3)

---

## Phase 6: Polish & Déploiement

- [ ] T052 [P] Documenter le déploiement sur l'hébergement Camoo dans `guard/specs/001-guard-platform/deployment.md` (après confirmation support Camoo — cf. research.md §1)
- [ ] T053 [P] Durcissement sécurité : audit des permissions Android déclarées, revue OWASP des endpoints Endpoint/CommandCenter
- [ ] T054 Exécuter la validation complète de `quickstart.md` de bout en bout (Android + Windows + backend)
- [ ] T055 [P] Mesurer consommation RAM/CPU de l'agent Android en conditions réelles et comparer aux budgets SC-009 (< 80 Mo RAM, < 5 % CPU)

---

## Dependencies & Execution Order

- **Setup (Phase 1)** : aucune dépendance, démarrage immédiat.
- **Foundational (Phase 2)** : dépend de Setup ; **bloque** toute la Phase 3.
- **User Story 1 (Phase 3)** : dépend de Phase 2. Backend, agent Android et agent Windows peuvent progresser en parallèle une fois les contrats (T028-T032) stabilisés, mais les tâches d'intégration (T042, T047) dépendent des endpoints backend correspondants.
- **Command Center minimal (Phase 4)** : dépend des modèles `Alerte`/`Incident` (T014) et du contrôleur d'alertes (T032).
- **Polish (Phase 6)** : dépend de la validation de Phase 3.

### Opportunités de parallélisation

- Toutes les tâches `[P]` de Setup et Foundational sont indépendantes entre elles.
- Une fois T024-T027 (modèles Endpoint) faits, T028-T032 (controllers) peuvent être répartis entre plusieurs développeurs.
- Les agents Android (T037-T043) et Windows (T044-T047) peuvent être développés en parallèle par deux équipes distinctes, chacune ne dépendant que des endpoints backend stabilisés (T028-T030).

---

## Implementation Strategy

### MVP (recommandé)

1. Phase 1 (Setup) → Phase 2 (Foundational) → Phase 3 (User Story 1, backend + **un seul** agent au choix, Android en priorité vu le marché cible mobile-first).
2. **STOP et VALIDER** : exécuter le parcours quickstart.md §6 en conditions réelles (connexion 2G simulée, coupure réseau).
3. Ajouter l'agent Windows (T044-T047) une fois l'agent Android validé.
4. Ajouter Command Center minimal (Phase 4) pour rendre les alertes visibles aux équipes internes.
5. Passer au module #2 (GUARD WEB) selon Phase 5.

## Notes

- `[P]` = fichiers différents, pas de dépendance.
- `[US1]` trace chaque tâche à la User Story 1 du cadrage.
- Les tests (T017-T023) doivent être écrits et échouer avant l'implémentation correspondante (TDD), conformément au workflow de développement de la constitution GUARD.
- Commit après chaque tâche ou groupe logique cohérent.

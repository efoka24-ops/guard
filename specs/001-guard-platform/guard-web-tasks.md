# Tasks: GUARD WEB (module #2)

**Input**: [guard-web-cadrage.md](./guard-web-cadrage.md), [guard-web-plan.md](./guard-web-plan.md)

**Tests**: inclus (TDD, comme ENDPOINT) — `Http::fake()` systématique, aucun appel réseau réel vers un site tiers pendant les tests.

## Path Conventions

`guard/backend/app/Modules/Web/`, migrations dans `guard/backend/database/migrations/`, tests dans `guard/backend/tests/Feature/Web/`.

---

## Phase 1: Fondations (bloquant)

- [ ] T060 Migration `sites_web_surveilles` (uuid, organisation_id FK, url normalisée, hash_page_accueil, statut_ssl, ssl_expiration_le, statut, timestamps) — **unique composite (organisation_id, url)**, pas d'unicité globale (éviter la faille corrigée sur Terminal en T053)
- [ ] T061 Migration `evenements_web` (uuid, site_web_id FK, type enum(defacement, en_tete_manquant, ssl_expiration_proche, ssl_expire), detail json, detecte_le, timestamps)
- [ ] T062 [P] Modèle `SiteWebSurveille` (`app/Modules/Web/Models/`), relation `organisation()`, scope `parOrganisation($id)`
- [ ] T063 [P] Modèle `EvenementWeb`
- [ ] T064 Service `NormalisateurHtml` : retire les éléments dynamiques (regex sur tokens CSRF `name="_token" value="..."`, timestamps ISO 8601, attributs `nonce="..."`) avant hash SHA-256, pour éviter les faux positifs de défacement sur un site qui n'a pas changé de contenu

## Phase 2: Détection de défacement (User Story 3, scénario 1)

### Tests d'abord

- [ ] T065 [P] Test `VerifierDefacementSiteTest` : `Http::fake()` renvoie un contenu identique au hash stocké → aucun `EvenementWeb` créé
- [ ] T066 [P] Test : `Http::fake()` renvoie un contenu différent → `EvenementWeb(type=defacement)` créé + `Alerte(module_source=WEB, niveau_criticite=critique)` dispatchée
- [ ] T067 [P] Test : site injoignable (timeout, 5xx) → pas de défacement présumé, mais un `EvenementWeb(type=indisponible)` distinct (éviter les faux positifs d'un site simplement en panne)

### Implémentation

- [ ] T068 Job `VerifierDefacementSite` : `Http::timeout(10)->get($site->url)`, hash via `NormalisateurHtml`, compare à `hash_page_accueil` stocké
- [ ] T069 `CreerAlerteDepuisEvenementWeb` (job, même patron que `CreerAlerteDepuisScan` d'ENDPOINT) : SLA critique 24h pour un défacement confirmé
- [ ] T070 `Schedule::command('web:verifier-defacements')->everyTwoMinutes()->withoutOverlapping()` dans `app/Console/Kernel.php`

## Phase 3: En-têtes de sécurité (User Story 3, scénario 2 — version passive)

### Tests d'abord

- [ ] T071 [P] Test `VerifierEnTetesSiteTest` : réponse sans `Content-Security-Policy`, `Strict-Transport-Security`, `X-Frame-Options` → `EvenementWeb(type=en_tete_manquant)` avec le détail des en-têtes absents
- [ ] T072 [P] Test : `Set-Cookie` sans `Secure` ou `HttpOnly` sur une réponse HTTPS → événement dédié

### Implémentation

- [ ] T073 Job `VerifierEnTetesSite` : liste blanche des en-têtes attendus, comparaison simple (pas d'analyse de contenu, pas de payload envoyé)
- [ ] T074 `Schedule::command('web:verifier-en-tetes')->weekly()`

## Phase 4: Suivi SSL (User Story 3, scénario 3)

### Tests d'abord

- [ ] T075 [P] Test `VerifierSslSiteTest` : certificat expirant dans 25 jours → pas d'alerte (seuils = J-30/14/7/1 uniquement, pas de spam quotidien)
- [ ] T076 [P] Test : certificat expirant dans 6 jours → `Alerte` créée une seule fois (idempotence — ne pas re-créer une alerte identique à chaque exécution du job avant le prochain seuil)
- [ ] T077 [P] Test : certificat déjà expiré → `statut_ssl = expire`, alerte critique

### Implémentation

- [ ] T078 Job `VerifierSslSite` : lecture du certificat via un flux `ssl://host:443` (stream context PHP natif, pas de dépendance Composer), extraction de la date d'expiration
- [ ] T079 Logique de seuils J-30/14/7/1 avec déduplication (vérifier qu'une alerte du même seuil n'existe pas déjà avant d'en créer une)
- [ ] T080 `Schedule::command('web:verifier-ssl')->daily()`

## Phase 5: API Command Center

- [ ] T081 [P] Test `SiteWebControllerTest` : `POST /sites-web` (auth:sanctum + scope.organisation) crée un site scopé à l'organisation de l'utilisateur connecté
- [ ] T082 [P] Test : `GET /sites-web` ne retourne que les sites de l'organisation de l'utilisateur (isolation multi-tenant — test explicite avec 2 organisations, comme `ScoreTest` d'ENDPOINT)
- [ ] T083 Contrôleur `SiteWebController` (index, store, show, destroy) + routes dans `routes/api.php` sous le même groupe `auth:sanctum + scope.organisation` que `/alerts`

## Phase 6: Validation & déploiement

- [ ] T084 Ajouter `WEB` à la liste déjà supportée par `Alerte::slaPour()` et au filtre `module_source` du Command Center (déjà prévu dans data-model.md §116, aucun changement de schéma requis)
- [ ] T085 Documenter dans `deployment.md` : nouveau cron `web:verifier-*` à ajouter au `schedule:run` existant (aucune nouvelle entrée cPanel, un seul cron couvre tout)
- [ ] T086 Dimensionner la fréquence de 2 min selon le nombre de sites réels avant engagement contractuel sur ce SLA (cf. plan.md — avertissement charge mutualisé)

---

## Notes

- Reprend exactement les conventions d'ENDPOINT : `[P]` = fichiers différents sans dépendance, tests avant implémentation (TDD).
- Aucune tâche de ce plan n'envoie de payload d'attaque à un site tiers — conforme au périmètre volontairement restreint du cadrage (Principe V).
- Commit après chaque phase ou groupe logique cohérent, comme pour ENDPOINT.

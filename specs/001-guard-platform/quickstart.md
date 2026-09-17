# Quickstart — GUARD Platform (premier incrément : Backend + GUARD ENDPOINT niveaux 1-2)

**Feature**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md) | **Data model**: [data-model.md](./data-model.md) | **Contrats**: [contracts/endpoint-sync-api.yaml](./contracts/endpoint-sync-api.yaml)

Ce guide couvre la mise en route locale du **backend Laravel** (API de synchronisation + Command Center minimal) et du **socle de l'agent GUARD ENDPOINT** (niveaux 1-2 : hash SHA-256 + YARA). Le niveau 3 (IA TFLite) et les modules SOCIAL/WEB/ID/CODE sont hors périmètre de ce premier incrément (cf. research.md §5).

## 1. Pré-requis locaux

- PHP 8.1+, Composer
- MySQL 8.0 (ou accès à la base distante `trugro9159_guard` via `pma-12.camoo.net` pour tests contre la vraie base)
- Node.js LTS (pour le dashboard, phase suivante)
- Pas besoin de Redis pour ce premier incrément (driver `database`, cf. research.md §2)

## 2. Configuration

```bash
cd guard
cp .env.example .env
# Renseigner DB_* avec vos identifiants (voir .env local déjà présent pour la base de prod Camoo — usage prod uniquement, ne pas pointer le dev dessus)
```

Variables clés à vérifier dans `.env` pour ce périmètre :

```env
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

## 3. Backend Laravel (à créer — `guard/backend/`)

```bash
composer create-project laravel/laravel backend "10.*"
cd backend
php artisan install:api        # Sanctum (JWT) — Principe V
php artisan migrate            # une fois les migrations issues de data-model.md créées
php artisan queue:table && php artisan migrate   # queue driver database
```

Modules à créer selon `plan.md` (`app/Modules/Endpoint/`, `app/Services/CommandCenter/`) : migrations pour `organisations`, `utilisateurs`, `terminaux`, `evenements_scan`, `elements_quarantaine`, `versions_signatures`, `alertes`, `incidents` (cf. data-model.md).

Implémenter les endpoints de `contracts/endpoint-sync-api.yaml` :
- `POST /api/v1/terminals/register`
- `GET /api/v1/signatures/delta`
- `POST /api/v1/scan-events`
- `GET /api/v1/alerts`
- `POST /api/v1/alerts/{id}/acknowledge`

```bash
php artisan schedule:work   # simule le cron cPanel en local (schedule:run chaque minute)
php artisan serve
```

## 4. Base de signatures initiale (SQLite embarquée)

Le premier incrément ne couvre que les niveaux 1 (hash) et 2 (YARA) :

1. Constituer un premier jeu de hashes SHA-256 de test (ex. hash du fichier EICAR standard pour valider la détection sans manipuler de vrai malware).
2. Écrire 2-3 règles YARA de test simples (détection de chaîne caractéristique) pour valider le pipeline niveau 2.
3. Générer le premier `VersionSignatures` (paquet complet initial, `from_version` absent dans l'appel `GET /signatures/delta`).

## 5. Agent GUARD ENDPOINT (squelette — `guard/mobile-android/` ou `guard/desktop-windows/`)

Pour ce premier incrément, l'agent doit uniquement :
1. Détecter la connexion d'un support externe (USB) ou une nouvelle installation d'APK.
2. Calculer le hash SHA-256 de chaque fichier détecté → comparer à la base SQLite locale (niveau 1).
3. Si non trouvé : appliquer les règles YARA locales (niveau 2).
4. Générer un `ScanEvent` local, l'ajouter à une file d'envoi différée (`client_event_id` en UUID pour idempotence).
5. Synchroniser vers `POST /scan-events` dès qu'une connexion est disponible ; tenter `GET /signatures/delta` une fois par jour ou au démarrage.

Test de validation minimal : brancher un support contenant un fichier EICAR → vérifier la quarantaine locale + l'apparition du `ScanEvent` côté backend après synchronisation (avec et sans coupure réseau simulée entre les deux étapes).

## 6. Vérification du parcours de bout en bout (P1, User Story 1 du cadrage)

```text
1. Terminal hors ligne détecte fichier EICAR sur USB → quarantaine locale immédiate (< 1s, SC-001)
2. Reconnexion réseau (même après plusieurs jours, cf. Terminal.derniere_synchro_le nullable)
3. POST /scan-events → 202 Accepted
4. Backend crée une Alerte (module_source=ENDPOINT, niveau_criticite=critique)
5. GET /alerts (Command Center) → l'alerte apparaît avec sla_due_at ≤ 24h
6. POST /alerts/{id}/acknowledge → statut = accusee_reception
```

## 7. Hors périmètre explicite de ce quickstart

- Déploiement sur l'hébergement Camoo (`/home/trugro9159/guard`) : à traiter une fois le backend validé en local, en tenant compte des contraintes de research.md §1 (confirmer PHP 8.1, Redis, cron avec le support avant bascule prod).
- Niveau 3 IA TFLite, dashboard React complet, modules SOCIAL/WEB/ID/CODE : specs et quickstarts dérivés à produire lors de leur priorisation.

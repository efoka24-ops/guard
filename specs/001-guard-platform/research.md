# Phase 0 — Recherche technique : GUARD Platform v2.0

**Feature**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md)

Objectif : lever les inconnues techniques identifiées dans le plan avant de figer le modèle de données et les contrats (Phase 1), en tenant compte de l'environnement d'hébergement réel désormais connu (Camoo, mutualisé) et non plus uniquement de la cible visée à terme (AWS af-south-1).

## 1. Écart entre hébergement cible (constitution) et hébergement disponible (réalité actuelle)

**Constat** : la constitution GUARD (Principe IV) et le plan visent AWS af-south-1 (EC2, RDS/MySQL, Redis, S3, CloudFront). L'hébergement réellement fourni pour `guard.trugroup.cm` est un **mutualisé Camoo** (`/home/trugro9159/guard`, MySQL 8.0.46 partagé, accès FTP), architecture très différente : pas de garantie de Redis, pas de queue worker persistant garanti, accès SSH généralement limité/optionnel selon le forfait.

**Recherche effectuée** : recherche web sur les capacités du mutualisé Camoo pour Laravel (support PHP 7/8, SSH+Composer annoncés sur les forfaits partagés ; un article support dédié "Comment installer une application Laravel avec un forfait Mutualisé chez CAMOO" existe mais n'a pas pu être consulté en détail — retour HTTP 403 sur `community.camoo.hosting`).

**Décision retenue** :
- Traiter le mutualisé Camoo comme **environnement de démarrage / MVP low-cost**, pas comme cible finale de la constitution. La constitution garde af-south-1 comme cible de scale-up ; ce n'est pas une violation du Principe IV (les deux sont en Afrique) mais un compromis de Principe II/VII à documenter.
- Le backend Laravel MVP doit fonctionner en **mode dégradé sans Redis obligatoire** : cache et sessions en driver `database` ou `file`, queue en driver `database` (table `jobs`) au lieu de Horizon+Redis tant que le mutualisé ne confirme pas le support Redis.
- Les tâches planifiées (mise à jour signatures, scans hebdo GUARD WEB, monitoring 2 min) doivent s'appuyer sur le **cron cPanel standard** (`php artisan schedule:run` toutes les minutes), pas sur un worker de queue long-running non garanti sur mutualisé.
- Le microservice Python FastAPI (GUARD CODE) et tout composant nécessitant un processus long-running (WebSocket temps réel, worker permanent) **ne peuvent pas tourner sur le mutualisé Camoo** : ils doivent être hébergés séparément (VPS Camoo, conteneur, ou service managé) dès le premier incrément qui les inclut. Le MVP GUARD ENDPOINT (P1, terminaux) n'en dépend pas directement côté backend — seule l'API de synchronisation (Laravel) doit tourner sur le mutualisé.

**Action de suivi (à ne pas bloquer la Phase 1, mais à vérifier avant le déploiement)** : contacter le support Camoo pour confirmer explicitement — version PHP exacte activable (8.1 requis), disponibilité de Redis/Memcached, limite de cron jobs, quota FTP/disque, accès SSH réel sur le forfait souscrit pour `trugro9159`.

## 2. Driver de queue/cache pour le MVP Laravel

**Décision** : `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database` par défaut pour l'environnement Camoo ; `.env` de production (non commité) surchargera vers `redis` si sa disponibilité est confirmée par le support.

**Alternatives rejetées** : imposer Redis dès le MVP → bloquerait le déploiement tant que non confirmé par l'hébergeur ; utiliser Sync queue (`QUEUE_CONNECTION=sync`) → inacceptable car les scans (GUARD WEB, GUARD ID) doivent rester asynchrones pour ne pas bloquer les requêtes HTTP sur un hébergement mutualisé aux ressources limitées.

## 3. Corpus d'entraînement du modèle IA TFLite (GUARD ENDPOINT, niveau 3)

**Inconnue** : aucun corpus labellisé de binaires (clean/trojan/ransomware/spyware/coinminer/adware/backdoor) n'existe encore en interne.

**Décision** : démarrer avec des datasets publics reconnus pour la recherche en sécurité (ex. corpus malware académiques, échantillons soumis volontairement, VirusTotal via abonnement recherche) complétés par un jeu de fichiers "clean" représentatifs de l'usage réel (APK Play Store officielles, documents bureautiques usuels). L'entraînement du modèle CNN MobileNetV3-Small est une activité **parallèle et longue** : elle doit démarrer dès la Phase 0/1 pour ne pas bloquer le développement de l'agent Android (qui peut d'abord livrer les niveaux 1 (hash) et 2 (YARA) seuls, le niveau 3 arrivant en incrément suivant).

**Alternative rejetée** : bloquer la livraison de GUARD ENDPOINT tant que le modèle IA n'est pas prêt → contredit le principe de testabilité indépendante (Principe VII) ; les niveaux 1 et 2 apportent déjà une valeur autonome significative.

## 4. Quotas et coûts des APIs tierces

| API | Usage GUARD | Contrainte connue | Décision |
|---|---|---|---|
| VirusTotal | Vérification hash/URL (ENDPOINT, WEB) | API publique gratuite limitée (requêtes/min) ; API premium payante pour volume | Démarrer en tier gratuit avec cache local agressif des résultats (SQLite) pour limiter les appels redondants |
| AbuseIPDB | Réputation IP (SOCIAL, ENDPOINT) | Quota gratuit quotidien limité | Idem : cache + fallback sur liste noire locale si quota atteint |
| Have I Been Pwned (HIBP) | Fuites de données (ID) | Nécessite une clé API payante pour recherche par domaine | Budgétiser un abonnement HIBP dès le lancement du module GUARD ID (dépendance bloquante, pas de contournement gratuit fiable) |
| Meta Graph API / LinkedIn API / X API v2 | GUARD SOCIAL | Nécessitent chacune une app développeur validée, révision de permissions (Meta), accès payant à certains niveaux (X API) | Prérequis administratif à lancer en parallèle de la Phase 1 (délais de validation d'app potentiellement longs, notamment Meta) |

**Décision générale** : toute intégration tierce payante ou à validation longue doit être identifiée et lancée administrativement dès la Phase 0/1, en parallèle du développement, pour ne pas devenir un chemin critique en fin de projet.

## 5. Choix du premier module à livrer

**Décision** : confirmer **GUARD ENDPOINT (niveaux 1 et 2 uniquement pour le premier incrément)** comme premier module, conformément à la priorité P1 du cadrage et à sa faible dépendance aux APIs tierces coûteuses/à validation longue (contrairement à SOCIAL et ID). GUARD WEB (P1 également) est un candidat de second incrément rapide car il ne nécessite pas d'agent terminal et peut tourner entièrement côté backend Laravel mutualisé (ping HTTP, hash de page, scan OWASP basique).

**Alternative envisagée** : démarrer par GUARD WEB en premier (plus simple, 100% backend, aucun agent mobile/desktop à développer). Rejetée en tant que *premier* module car le cadrage désigne ENDPOINT comme socle de valeur (Mobile Money, USB) le plus différenciant pour le marché cible — mais retenue comme **module #2** immédiat vu sa simplicité de déploiement sur l'infra actuelle.

## 6. Synthèse des décisions de Phase 0

| # | Sujet | Décision |
|---|---|---|
| 1 | Hébergement MVP | Camoo mutualisé pour Laravel API ; AWS af-south-1 reste la cible de scale-up documentée séparément |
| 2 | Queue/Cache MVP | Driver `database` par défaut, `redis` en surcouche si confirmé disponible |
| 3 | Processus long-running (FastAPI, WebSocket) | Hors mutualisé — infra séparée dès leur introduction |
| 4 | Corpus IA TFLite | Démarrage parallèle immédiat ; niveau 3 livré en incrément séparé des niveaux 1/2 |
| 5 | APIs tierces payantes/à validation | Démarches administratives lancées dès Phase 1, en parallèle du dev |
| 6 | Séquencement modules | #1 GUARD ENDPOINT (niveaux 1-2), #2 GUARD WEB, puis SOCIAL/ID/CODE selon capacité |

## Sources consultées

- [Hébergement Mutualisé de Qualité au Cameroun — Camoo Hosting](https://www.camoo.hosting/share-hosting)
- [Comment installer une application Laravel avec un forfait Mutualisé chez CAMOO (support Camoo, non consultable en détail — 403)](https://community.camoo.hosting/hc/fr/articles/360016181677)
- [How to Enable Redis or Memcache on CPanel Shared Hosting](https://www.ewallzsolutions.com/enable-redis-memcache-cpanel-shared-hosting/)

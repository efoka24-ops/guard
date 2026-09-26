# Déploiement — guard.trugroup.cm (Camoo, FTP seul)

État au 2026-09-26 : **backend déployé et validé de bout en bout** (Laravel 10.50.3 / PHP 8.1.34, MySQL 8, HTTP uniquement).

## Contraintes de l'hébergement

- Pas de SSH, pas de Composer distant ; FTP **en clair** (pas de TLS) ; le compte FTP est chrooté : sa racine `/` **est** la racine web du sous-domaine.
- PHP figé en **8.1** → Laravel 10 (Laravel 11/12 exigent PHP ≥ 8.2, cf. research.md §4ter — dette de sécurité assumée).
- **HTTPS non disponible** (SSL différé) : tokens (organisation, terminal, Sanctum) circulent en clair tant que ce n'est pas résolu. Ne pas y faire transiter de données réelles sensibles.
- Le serveur FTP se bloque temporairement si on enchaîne des connexions rapprochées : regrouper les commandes dans **une session** (`curl -T "{a,b}"`, plusieurs `-Q`).
- Le `.htaccess` Laravel par défaut provoque un 500 (directive `Options`) : utiliser la version minimale ci-dessous.

## Arborescence sur le serveur

```
/                 racine web
├── index.php     front controller (pointe vers guard-app/, usePublicPath)
├── .htaccess     rewrite minimal + blocage de guard-app
├── robots.txt, favicon.ico
└── guard-app/    application Laravel complète (vendor sans dev, .env)
    └── .htaccess Require all denied  → aucun accès HTTP direct (403)
```

## Procédure (rejouable)

1. **Construire** : copier `app bootstrap config database resources routes artisan composer.*` dans `deploy-stage/`, y lancer `composer install --no-dev --optimize-autoloader` **sous PHP 8.1**, supprimer `bootstrap/cache/*.php`, zipper (PowerShell `Compress-Archive` écrit des `\` : le script d'extraction normalise).
2. **Envoyer** `guard-app.zip` + `deploy-extract.php` (token en dur, éphémère) à la racine FTP, exécuter `deploy-extract.php?token=…` (vide l'ancienne version sauf `.htaccess`/`.env`, extrait), **supprimer le script**.
3. Vérifier le verrou : `guard-app/composer.json` → 403, `guard-app/.env` → 404.
4. `.env` de production : `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` propre à la prod, **`DB_PASSWORD` entre guillemets** (un `#` non guillemeté est lu comme un commentaire), `QUEUE_CONNECTION=sync` (pas de cron), `CACHE_DRIVER`/`SESSION_DRIVER=database`.
5. **Migrations** via `deploy-migrate.php` (token = `MIGRATE_TOKEN` du `.env`, `hash_equals`, `Artisan::call('migrate')`), puis supprimer le script et vider `MIGRATE_TOKEN`.
6. Données initiales via un script one-off équivalent (organisation + signatures), supprimé ensuite.

## Pièges rencontrés

| Symptôme | Cause | Correctif |
|---|---|---|
| 500 dès le premier fichier PHP | `.htaccess` Laravel (`Options`) | `.htaccess` minimal |
| MySQL 1045 access denied | `#` du mot de passe pris pour un commentaire | guillemets dans `.env` |
| MySQL 1101 sur `modules_actifs` | défaut littéral interdit sur colonne JSON | colonne `nullable`, `[]` posé par le modèle |
| curl (26) sur `/tmp/...` | curl Windows ne résout pas les chemins MSYS | fichiers sous `C:\…` |

## Reste à faire

- Cron cPanel `* * * * * php /home/trugro9159/guard/guard-app/artisan schedule:run` si on repasse la queue en `database`.
- HTTPS (AutoSSL) dès que possible, puis `APP_URL=https://…` et cookies `secure`.
- Agents : cible `https://guard.trugroup.cm/api/v1` par défaut ; en attendant HTTPS, `GUARD_BACKEND_URL=http://…` (Windows) ; Android bloque le clair par défaut (network security config à décider).
- Changer les mots de passe MySQL et FTP (transmis en clair par FTP et collés dans le chat).
- Retour à Laravel 12 dès qu'un hébergement PHP ≥ 8.2 est disponible.

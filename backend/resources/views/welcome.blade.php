<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GUARD — Cybersécurité africaine</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #F7F6F2; --ink: #101828; --muted: #475467; --muted2: #667085;
            --line: #E6E3DA; --line2: #EFECE4; --accent: #E8590C; --accent-dark: #C2410C;
            --dark: #101828;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Manrope', system-ui, sans-serif; background: var(--bg); color: var(--ink); }
        a { color: var(--accent); }
        a:hover { color: var(--accent-dark); }

        header {
            background: var(--bg); border-bottom: 1px solid var(--line);
            position: sticky; top: 0; z-index: 5;
        }
        header .inner {
            max-width: 1160px; margin: 0 auto; padding: 14px 24px;
            display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;
        }
        .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit; }
        .brand img { width: 26px; height: 30px; }
        .brand strong { font-weight: 800; font-size: 18px; letter-spacing: 0.5px; }
        .brand span { font-size: 12px; color: var(--muted2); font-weight: 600; }
        .cta {
            background: var(--dark); color: #fff; border: none; border-radius: 999px;
            padding: 10px 18px; font-family: inherit; font-size: 14px; font-weight: 700;
            text-decoration: none; white-space: nowrap; display: inline-block;
        }
        .cta:hover { background: #1D2939; color: #fff; }

        .hero {
            max-width: 1160px; margin: 0 auto; padding: clamp(40px,7vw,96px) 24px 56px;
        }
        .hero h1 {
            font-size: clamp(32px,5vw,54px); font-weight: 800; line-height: 1.08;
            letter-spacing: -1.2px; margin: 0 0 22px; max-width: 780px;
        }
        .hero p { font-size: 18px; line-height: 1.6; color: var(--muted); margin: 0 0 30px; max-width: 620px; }
        .hero-cta { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn-outline {
            background: #fff; border: 1px solid #D0CCC0; color: var(--ink);
            border-radius: 999px; padding: 13px 22px; font-size: 15px; font-weight: 700; text-decoration: none;
        }

        section.modules { max-width: 1160px; margin: 0 auto; padding: 32px 24px 72px; }
        section.modules h2 { font-size: 28px; font-weight: 800; letter-spacing: -0.5px; margin: 0 0 8px; }
        section.modules > p { font-size: 15px; color: var(--muted2); margin: 0 0 24px; }
        .modlist {
            display: flex; flex-direction: column; background: #fff;
            border: 1px solid var(--line); border-radius: 16px; overflow: hidden;
        }
        .modrow {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%,220px), 1fr));
            gap: 12px 28px; padding: 22px 24px; border-top: 1px solid var(--line2); align-items: start;
        }
        .modrow:first-child { border-top: none; }
        .modrow .name { font-weight: 600; font-size: 15px; color: var(--accent); }
        .modrow .surface { font-size: 15px; font-weight: 600; }
        .modrow .probleme { font-size: 14px; color: var(--muted); line-height: 1.5; }
        .modrow .innovation { font-size: 13px; color: var(--muted2); line-height: 1.5; }

        .band {
            background: var(--dark); color: #fff;
        }
        .band .inner {
            max-width: 1160px; margin: 0 auto; padding: clamp(40px,6vw,72px) 24px;
            display: flex; justify-content: space-between; align-items: center; gap: 24px; flex-wrap: wrap;
        }
        .band h2 { font-size: clamp(22px,3vw,32px); font-weight: 800; letter-spacing: -0.5px; margin: 0 0 8px; }
        .band p { font-size: 15px; color: #98A2B3; margin: 0; }
        .band .cta { background: var(--accent); }
        .band .cta:hover { background: var(--accent-dark); }

        footer {
            max-width: 1160px; margin: 0 auto; padding: 22px 24px;
            display: flex; justify-content: space-between; flex-wrap: wrap; gap: 12px;
            font-size: 13px; color: var(--muted2);
        }
    </style>
</head>
<body>
    <header>
        <div class="inner">
            <a href="/" class="brand">
                <img src="/guard-logo.png" alt="GUARD">
                <strong>GUARD</strong>
                <span>by TRU GROUP</span>
            </a>
            <a class="cta" href="mailto:contact@trugroup.cm?subject=GUARD%20-%20Demande%20d%27acc%C3%A8s">Nous contacter</a>
        </div>
    </header>

    <section class="hero">
        <h1>La cybersécurité conçue pour le contexte africain.</h1>
        <p>GUARD est une suite de cybersécurité pensée pour les réalités du terrain : clés USB, APKs hors Play Store, arnaques Mobile Money, phishing SMS, défacements de sites.</p>
        <div class="hero-cta">
            <a href="#modules" class="btn-outline">Découvrir les modules</a>
        </div>
    </section>

    <section id="modules" class="modules">
        <h2>Les modules de la suite</h2>
        <p>Offline-first · Légèreté pensée pour les terminaux d'entrée de gamme · Alertes en français</p>
        <div class="modlist">
            <div class="modrow">
                <div class="name">GUARD ENDPOINT</div>
                <div class="surface">Terminaux Android, Windows, clés USB</div>
                <div class="probleme">Malwares via USB, APKs piratées, arnaques Mobile Money, phishing SMS</div>
                <div class="innovation">Détection hors ligne · analyse locale · quarantaine automatique</div>
            </div>
            <div class="modrow">
                <div class="name">GUARD WEB</div>
                <div class="surface">Sites web, applications web</div>
                <div class="probleme">Défacement, injection SQL, SSL expiré, plugins vulnérables</div>
                <div class="innovation">Surveillance périodique · détection de défacement</div>
            </div>
        </div>
    </section>

    <div class="band">
        <div class="inner">
            <div>
                <h2>Envie d'en savoir plus ?</h2>
                <p>Écrivez-nous : nous configurons votre espace selon vos besoins.</p>
            </div>
            <a class="cta" href="mailto:contact@trugroup.cm?subject=GUARD%20-%20Demande%20d%27acc%C3%A8s">Nous contacter</a>
        </div>
    </div>

    <footer>
        <span>GUARD Platform · TRU GROUP © {{ date('Y') }}</span>
    </footer>
</body>
</html>

# Feature Specification: GUARD Platform — Suite de Cybersécurité Africaine v2.0

**Feature Branch**: `001-guard-platform`

**Created**: 2026-09-17

**Status**: Draft

**Input**: User description: "Suite de cybersécurité africaine GUARD Platform v2.0 (SFD-GUARD-2026-v2.0) — 5 modules : GUARD ENDPOINT, GUARD SOCIAL, GUARD WEB, GUARD ID, GUARD CODE, agrégés par le GUARD COMMAND CENTER, avec module transversal GUARD AI ANALYST."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Protection des terminaux contre les menaces USB, APK et Mobile Money (Priority: P1)

Un dirigeant PME ou un employé branche une clé USB ou installe une application Android hors Play Store sur un terminal protégé par GUARD ENDPOINT. La menace potentielle (malware, APK piratée, overlay attack Mobile Money) doit être détectée et neutralisée offline, sans dépendre d'une connexion internet stable.

**Why this priority**: C'est le socle de la suite et le vecteur de menace le plus répandu et le plus coûteux (fraude Mobile Money) en Afrique subsaharienne. Sans ce module, aucune autre protection n'a de valeur immédiate pour l'utilisateur final non technicien.

**Independent Test**: Peut être testé en connectant une clé USB contenant un fichier de test EICAR et un binaire simulant un ransomware, puis en vérifiant la mise en quarantaine automatique et la génération du rapport d'incident — sans connexion réseau active durant le test.

**Acceptance Scenarios**:

1. **Given** une clé USB contenant un fichier dont le hash SHA-256 correspond à la base de signatures locale, **When** l'utilisateur connecte la clé, **Then** le fichier est mis en quarantaine automatiquement en moins de 1 seconde et une alerte critique est affichée.
2. **Given** un fichier inconnu (hash non répertorié) présentant une entropie de Shannon > 7.5 et classé "probable malware" par le moteur IA (score > 85 %), **When** le scan à 3 niveaux se termine, **Then** le fichier est mis en quarantaine automatiquement sans interaction utilisateur.
3. **Given** une session Mobile Money active sur une application officielle whitelistée, **When** une application non whitelistée tente d'afficher une fenêtre en superposition (overlay) ou de lire les SMS entrants, **Then** GUARD bloque l'action immédiatement et alerte l'utilisateur.
4. **Given** une tentative d'installation d'un APK hors Play Store avec un score de confiance < 40, **When** l'utilisateur tente l'installation, **Then** l'installation est bloquée définitivement et l'événement est signalé à la base TRU GROUP.

---

### User Story 2 - Détection de piratage et d'usurpation sur les réseaux sociaux (Priority: P1)

Un community manager gère les pages Facebook/Instagram/LinkedIn de l'entreprise. GUARD SOCIAL doit détecter en temps réel toute connexion suspecte, tout ajout d'administrateur non autorisé, et toute fausse page créée au nom de l'entreprise, puis permettre un signalement rapide aux plateformes.

**Why this priority**: Le piratage de page (perte de contrôle total, potentiellement irréversible en 14 jours chez Meta) et l'usurpation d'identité pour escroquer les clients sont des risques réputationnels et financiers immédiats pour les PME sans équipe IT dédiée.

**Independent Test**: Peut être testé en simulant une connexion depuis un pays et un appareil jamais vus sur un compte de test lié, et en vérifiant que l'alerte (score de risque > 60) et la notification multi-canaux sont déclenchées en moins de 5 minutes.

**Acceptance Scenarios**:

1. **Given** un compte avec un profil de connexion appris sur 30 jours, **When** une connexion survient depuis un pays inconnu à une heure inhabituelle (23h–5h) avec une IP blacklistée, **Then** le score de risque dépasse 80 et la session est déconnectée automatiquement si l'API de la plateforme le permet.
2. **Given** une page d'entreprise surveillée, **When** une nouvelle page est créée avec un nom similaire (distance de Levenshtein < 3) ou un logo visuellement identique (pHash), **Then** une alerte de similarité > 75 % est générée et un signalement structuré peut être soumis en un clic.
3. **Given** une page surveillée, **When** la liste des administrateurs est modifiée, **Then** tous les administrateurs actuels reçoivent une alerte immédiate incluant l'auteur, l'IP et l'heure du changement.

---

### User Story 3 - Détection de défacement et de vulnérabilités sur les sites web (Priority: P1)

Un responsable informatique doit être alerté immédiatement si le site web de l'organisation est défacé, indisponible, ou expose une vulnérabilité OWASP Top 10 critique, avec preuve légale horodatée exploitable auprès des autorités.

**Why this priority**: Le défacement de site et les vulnérabilités web exploitées sont des incidents visibles publiquement, avec impact réputationnel immédiat, et nécessitent une réaction en minutes plutôt qu'en heures.

**Independent Test**: Peut être testé en modifiant délibérément le contenu de la page d'accueil d'un site de test et en vérifiant que l'écart de hash est détecté en moins de 5 minutes, avec capture d'écran horodatée générée automatiquement.

**Acceptance Scenarios**:

1. **Given** un site avec un hash de référence de page d'accueil enregistré, **When** le contenu change de façon non autorisée, **Then** une alerte de défacement est déclenchée sous 5 minutes avec capture d'écran signée numériquement (SHA-256 + RFC 3161).
2. **Given** un scan hebdomadaire OWASP Top 10, **When** une injection SQL ou une XSS est détectée sur un formulaire, **Then** un rapport avec score CVSS 3.1 et guide de remédiation en français est généré.
3. **Given** un certificat SSL arrivant à expiration, **When** l'échéance atteint J-30, J-14, J-7 et J-1, **Then** des alertes successives sont envoyées avec un guide de renouvellement adapté à l'hébergeur détecté.

---

### User Story 4 - Veille de l'identité numérique et des fuites de données (Priority: P2)

Un dirigeant ou responsable conformité doit savoir rapidement si les emails, domaines ou identifiants de l'organisation apparaissent dans une fuite de données, ou si un domaine typosquatté a été enregistré pour usurper l'identité de l'organisation.

**Why this priority**: Moins immédiat qu'un piratage actif, mais critique pour la prévention et la conformité réglementaire (déclaration aux autorités). Dépend des données déjà collectées par les autres modules (email, domaine) donc suit en priorité P2.

**Independent Test**: Peut être testé en interrogeant l'API Have I Been Pwned avec un email de test connu pour être dans une fuite publique et en vérifiant la génération de l'alerte avec détail de la fuite.

**Acceptance Scenarios**:

1. **Given** un domaine d'organisation enregistré dans GUARD ID, **When** un nouveau domaine similaire (typosquatting) est enregistré publiquement, **Then** une alerte est générée avec analyse de contenu et vérification DNS/MX.
2. **Given** un incident de sécurité confirmé (piratage, défacement, fuite), **When** l'utilisateur déclenche la génération du dossier légal, **Then** GUARD LEGAL PACK produit un dossier complet (preuves horodatées, timeline, rapport au format ANTIC ou RCA) en moins de quelques heures.

---

### User Story 5 - Analyse de sécurité du code source avant livraison (Priority: P2)

Un développeur ou une équipe tech doit pouvoir scanner un projet (SAST, SCA, secrets, IaC) directement depuis la CI/CD, l'IDE ou la CLI, et obtenir un score de sécurité actionnable avant chaque livraison client.

**Why this priority**: Cible un profil utilisateur différent (développeur) et un cas d'usage préventif en amont du cycle de vie logiciel ; a une valeur autonome forte mais dépend moins de la réactivité temps réel que P1.

**Independent Test**: Peut être testé en exécutant `guard scan` en CLI sur un dépôt de test contenant une clé API hardcodée et une dépendance avec CVE connue, et en vérifiant que les deux findings apparaissent dans le rapport avec les commandes de correction suggérées.

**Acceptance Scenarios**:

1. **Given** un projet contenant une clé API hardcodée dans un fichier commité (y compris dans l'historique Git), **When** un scan `secrets` est exécuté, **Then** le secret est détecté, masqué dans le rapport, et un guide de remédiation en 4 étapes est fourni.
2. **Given** une dépendance avec une CVE connue, **When** un scan SCA est exécuté, **Then** la CVE est reportée avec score CVSS et commande de mise à jour précise générée.
3. **Given** un projet avec un score de sécurité < seuil configuré (`fail-on: critical`), **When** le scan s'exécute dans une Pull Request, **Then** le merge est bloqué automatiquement.

---

### Edge Cases

- Que se passe-t-il si la mise à jour des signatures est interrompue par une coupure réseau à mi-parcours ? → La base précédente reste active ; la mise à jour reprend depuis le dernier point de contrôle.
- Comment le système gère-t-il un faux positif critique (fichier légitime mis en quarantaine) ? → L'utilisateur doit pouvoir restaurer le fichier avec confirmation explicite, et l'incident est journalisé pour amélioration du modèle.
- Que se passe-t-il si une organisation n'a souscrit qu'à un seul module (ex: GUARD CODE seul) ? → Le COMMAND CENTER doit afficher uniquement les tuiles des modules actifs, sans erreur ni dépendance croisée.
- Comment gérer un signalement de fausse page rejeté par la plateforme (Meta, LinkedIn) ? → Le statut est mis à jour, une relance automatique est programmée à 7 jours, et l'organisation est notifiée du rejet avec motif.
- Que se passe-t-il si deux modules détectent un même incident sous des angles différents (ex: fuite de données ET vulnérabilité web corrélée) ? → Le COMMAND CENTER doit permettre une corrélation manuelle ou assistée (GUARD AI ANALYST) sans dupliquer les alertes dans le compteur global.
- Comment le système se comporte-t-il pour un terminal avec une connexion 2G intermittente pendant plusieurs jours ? → Les scans locaux (niveaux 1 à 3) continuent de fonctionner ; seule la synchronisation des alertes et les mises à jour de signatures sont différées.

## Requirements *(mandatory)*

### Functional Requirements

**GUARD ENDPOINT**
- **FR-001**: Le système DOIT scanner automatiquement tout support externe connecté en 3 niveaux successifs (hash SHA-256, règles YARA, modèle IA TFLite) avec mise en quarantaine automatique au-delà d'un score de confiance de 85 %.
- **FR-002**: Le système DOIT fonctionner en mode détection complet sans connexion réseau active (base de signatures et modèle IA embarqués localement).
- **FR-003**: Le système DOIT intercepter toute tentative d'installation d'APK hors Play Store et bloquer les installations avec un score de confiance global inférieur à 40.
- **FR-004**: Le système DOIT détecter et bloquer les tentatives d'overlay attack et de capture clavier/SMS pendant une session Mobile Money active sur une application whitelistée.
- **FR-005**: Le système DOIT analyser les SMS, emails et liens WhatsApp entrants pour détecter le phishing et bloquer l'ouverture d'URLs malveillantes confirmées.
- **FR-006**: Le système DOIT mettre à jour ses signatures via des deltas hebdomadaires inférieurs à 50 Ko, avec reprise incrémentale en cas d'échec.

**GUARD SOCIAL**
- **FR-007**: Le système DOIT calculer un score de risque de connexion (0–100) pour chaque session sur les comptes liés (Facebook/Instagram, LinkedIn, Twitter/X) et alerter au-delà d'un seuil de 60.
- **FR-008**: Le système DOIT détecter automatiquement les pages similaires (nom, logo, description) créées sur les plateformes surveillées et permettre un signalement structuré en un clic.
- **FR-009**: Le système DOIT alerter immédiatement tous les administrateurs d'une page lors de toute modification de la liste des administrateurs.
- **FR-010**: Le système DOIT apprendre le profil de connexion habituel de chaque compte sur une fenêtre glissante de 30 jours.

**GUARD WEB**
- **FR-011**: Le système DOIT vérifier la disponibilité de chaque site surveillé toutes les 2 minutes depuis au moins 3 points de présence géographiquement distincts.
- **FR-012**: Le système DOIT détecter un défacement par comparaison de hash de la page d'accueil toutes les 5 minutes, avec capture d'écran horodatée et signée en cas de détection.
- **FR-013**: Le système DOIT exécuter hebdomadairement un scan des 10 catégories OWASP Top 10 et produire un score CVSS 3.1 avec guide de remédiation par vulnérabilité.
- **FR-014**: Le système DOIT surveiller les certificats SSL/TLS de tous les sites et alerter à J-30, J-14, J-7 et J-1 avant expiration.

**GUARD ID**
- **FR-015**: Le système DOIT vérifier hebdomadairement les emails et domaines de l'organisation contre les bases de fuites de données connues et alerter immédiatement en cas de nouvelle fuite.
- **FR-016**: Le système DOIT surveiller quotidiennement l'enregistrement de nouveaux domaines similaires (typosquatting) au domaine de l'organisation.
- **FR-017**: Le système DOIT générer un dossier légal complet (preuves horodatées et signées, timeline, rapport formaté par autorité) en cas d'incident avéré.

**GUARD CODE**
- **FR-018**: Le système DOIT analyser statiquement (SAST) le code source dans les langages PHP, JavaScript/TypeScript, Dart/Flutter, Python et Kotlin/Java, avec détection des catégories OWASP principales (injection, XSS, auth, fonctions dangereuses, cryptographie faible).
- **FR-019**: Le système DOIT analyser les dépendances (SCA) de chaque projet contre les bases CVE (NVD, OSV, GitHub Advisory) et générer une commande de correction précise pour chaque vulnérabilité.
- **FR-020**: Le système DOIT détecter les secrets hardcodés dans le code et dans l'historique Git, et les masquer systématiquement dans tout rapport généré.
- **FR-021**: Le système DOIT calculer un score de sécurité de projet (0–100) selon une formule pondérée par criticité, et permettre le blocage automatique d'une Pull Request selon un seuil configurable.
- **FR-022**: Le système DOIT être intégrable en CI/CD (GitHub Actions, GitLab CI), en CLI, et via un plugin IDE (VS Code).

**GUARD COMMAND CENTER (transversal)**
- **FR-023**: Le système DOIT agréger un score de sécurité global de l'organisation à partir des scores de chaque module actif souscrit.
- **FR-024**: Le système DOIT permettre le traitement des alertes (accusé de réception, assignation, résolution, ignorance justifiée) avec suivi de SLA par niveau de criticité.
- **FR-025**: Le système DOIT supporter un mode multi-organisations (MSP) avec vue agrégée et rapports mensuels automatiques par client.

### Key Entities

- **Organisation**: Entité cliente GUARD (PME, ONG, institution). Possède un ou plusieurs modules actifs, un score de sécurité global, une liste d'utilisateurs et un pays de conformité réglementaire.
- **Terminal**: Appareil Android ou Windows protégé par GUARD ENDPOINT. Associé à une organisation, un statut de connectivité, un score de risque comportemental.
- **Alerte**: Événement de sécurité détecté par un module. Possède un niveau de criticité, un module source, un statut de traitement, un SLA, et peut être liée à un ou plusieurs incidents.
- **Incident**: Regroupement d'une ou plusieurs alertes corrélées constituant un événement de sécurité significatif, pouvant générer un dossier légal (GUARD LEGAL PACK).
- **Compte social surveillé**: Page ou profil d'une plateforme sociale lié à une organisation via GUARD SOCIAL, avec historique de connexions et liste d'administrateurs.
- **Site web surveillé**: URL ou domaine suivi par GUARD WEB, avec hash de référence, historique de disponibilité, certificat SSL et vulnérabilités connues.
- **Identité numérique surveillée**: Ensemble des emails, domaines et numéros de téléphone d'une organisation suivis par GUARD ID pour la détection de fuites et de typosquatting.
- **Projet de code**: Dépôt ou pipeline CI/CD analysé par GUARD CODE, avec score de sécurité, historique de scans et findings associés (SAST, SCA, secrets, IaC).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un fichier malveillant connu (hash répertorié) introduit via un support externe est détecté et mis en quarantaine en moins de 1 seconde, sans connexion réseau.
- **SC-002**: 95 % des tentatives d'overlay attack Mobile Money simulées sont bloquées avant toute saisie de code PIN.
- **SC-003**: Une connexion suspecte sur un compte social surveillé génère une alerte à l'administrateur en moins de 5 minutes dans 95 % des cas.
- **SC-004**: Un défacement de site web est détecté et notifié (avec preuve horodatée) en moins de 5 minutes dans 95 % des cas.
- **SC-005**: Un dossier légal complet pour un incident avéré est généré en moins de 2 heures, contre plusieurs semaines pour une constitution manuelle équivalente.
- **SC-006**: Un scan GUARD CODE complet (SAST + SCA + secrets) sur un projet de taille moyenne (< 50 000 lignes) s'exécute en moins de 5 minutes en pipeline CI/CD.
- **SC-007**: Le score de sécurité global de l'organisation est visible et compréhensible (avec tendance) par un utilisateur non technicien en moins de 10 secondes après connexion au COMMAND CENTER.
- **SC-008**: 100 % des rapports d'incident générés sont conformes aux formats de déclaration exigés par au moins une autorité cible (ANTIC Cameroun ou RCA Rwanda) sans retouche manuelle.
- **SC-009**: L'agent terminal GUARD ENDPOINT consomme moins de 80 Mo de RAM et moins de 5 % de CPU en surveillance continue sur un appareil Android de milieu de gamme.
- **SC-010**: La mise à jour hebdomadaire des signatures se termine avec succès sur une connexion simulée à 2G (< 50 Ko transférés) dans 99 % des cas.

## Assumptions

- Les cinq modules sont commercialisés indépendamment ou en suite ; toute organisation peut souscrire à un sous-ensemble de modules sans dépendance technique bloquante entre eux.
- Les intégrations tierces (Meta Graph API, LinkedIn API, X API v2, Have I Been Pwned, VirusTotal, AbuseIPDB, NVD/OSV) sont supposées disponibles selon leurs conditions d'utilisation standard ; toute limitation de quota ou de coût sera traitée au niveau du plan technique (Phase suivante), pas dans ce cadrage.
- Le périmètre v2.0 couvre les plateformes listées comme "✅ v1.0" dans la SFD (Facebook/Instagram, LinkedIn, Twitter/X pour GUARD SOCIAL) ; TikTok, WhatsApp Business et YouTube (marqués v1.5/v2.0 dans la SFD) sont hors périmètre de ce cadrage initial et feront l'objet de spécifications incrémentales.
- L'hébergement de référence est AWS af-south-1 (Cape Town), conformément au principe de souveraineté des données défini dans la constitution GUARD.
- Le GUARD AI ANALYST (module transversal mentionné en section 10 de la SFD) est traité comme une fonctionnalité d'assistance à la corrélation d'alertes et non comme un module commercial autonome à ce stade ; son périmètre détaillé sera cadré séparément.
- Les exigences non fonctionnelles détaillées (section 11 de la SFD), la sécurité/conformité approfondie (section 12), l'architecture des données (section 13), le plan de tests (section 14), la roadmap (section 15) et le modèle commercial (sections 16–17) seront traités dans des spécifications et plans dérivés, ce cadrage se concentrant sur le périmètre fonctionnel des 5 modules et du Command Center.

package africa.trugroup.guard.scanner

/**
 * T037 — Détecte la connexion d'un support externe (USB/carte SD) ou une
 * nouvelle tentative d'installation d'APK, et déclenche le scan niveau 1.
 * Implémentation à venir : BroadcastReceiver sur ACTION_MEDIA_MOUNTED et
 * interception de l'intent INSTALL_PACKAGE (EF-EP-11).
 */
class DetectionTrigger

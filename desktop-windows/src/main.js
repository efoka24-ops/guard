const { app } = require('electron');

// Point d'entrée de l'agent GUARD ENDPOINT (Windows).
// Détection USB → scanner niveau 1/2 → quarantaine → synchronisation (T044-T047).

app.whenReady().then(() => {
  // TODO T044 : brancher UsbWatcher ici
  console.log('GUARD ENDPOINT (Windows) démarré');
});

app.on('window-all-closed', () => {
  // Agent en tâche de fond : ne pas quitter à la fermeture d'une fenêtre.
});

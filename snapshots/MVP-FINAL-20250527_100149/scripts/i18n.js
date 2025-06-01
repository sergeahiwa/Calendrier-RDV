/**
 * Script pour gérer les traductions du plugin
 * 
 * Commandes disponibles :
 * - npm run i18n:make-pot    : Crée le fichier .pot
 * - npm run i18n:update-fr   : Met à jour la traduction française
 */
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

// Configuration
const config = {
  textDomain: 'calendrier-rdv',
  potFile: 'languages/calendrier-rdv.pot',
  phpSources: [
    '*.php',
    'includes/**/*.php',
    'admin/**/*.php',
    'public/**/*.php',
    'src/**/*.php'
  ],
  jsSources: [
    'assets/js/**/*.js',
    '!assets/js/**/*.min.js'
  ]
};

/**
 * Exécute une commande shell
 */
function runCommand(command) {
  try {
    console.log(`Exécution : ${command}`);
    execSync(command, { stdio: 'inherit' });
  } catch (error) {
    console.error(`Erreur lors de l'exécution de la commande: ${command}`);
    process.exit(1);
  }
}

/**
 * Crée le fichier .pot
 */
function makePot() {
  console.log('Création du fichier .pot...');
  
  // Créer le répertoire s'il n'existe pas
  const potDir = path.dirname(config.potFile);
  if (!fs.existsSync(potDir)) {
    fs.mkdirSync(potDir, { recursive: true });
  }

  // Construire la commande WP-CLI
  const phpFiles = config.phpSources.join(' ');
  const cmd = `wp i18n make-pot . ${config.potFile} \
    --domain=${config.textDomain} \
    --exclude="node_modules,vendor" \
    --headers='{"Project-Id-Version":"Calendrier RDV 1.3.0","Report-Msgid-Bugs-To":"https://wordpress.org/support/plugin/calendrier-rdv"}' \
    --allow-root`;
  
  runCommand(cmd);
  
  console.log(`Fichier ${config.potFile} créé avec succès.`);
}

/**
 * Met à jour une traduction
 */
function updateTranslation(lang) {
  console.log(`Mise à jour de la traduction ${lang}...`);
  
  const poFile = `languages/${config.textDomain}-${lang}.po`;
  
  // Créer le fichier .po s'il n'existe pas
  if (!fs.existsSync(poFile)) {
    console.log(`Création du fichier ${poFile}...`);
    fs.copyFileSync(config.potFile, poFile);
  }
  
  // Mettre à jour le fichier .po
  runCommand(`wp i18n update-po ${config.potFile} ${poFile} --allow-root`);
  
  // Générer le fichier .mo
  const moFile = poFile.replace(/\.po$/, '.mo');
  runCommand(`msgfmt ${poFile} -o ${moFile}`);
  
  console.log(`Traduction ${lang} mise à jour avec succès.`);
}

// Exécution des commandes
const command = process.argv[2];

switch (command) {
  case 'make-pot':
    makePot();
    break;
    
  case 'update-fr':
    updateTranslation('fr_FR');
    break;
    
  default:
    console.log('Utilisation : node scripts/i18n.js [make-pot|update-fr]');
    break;
}

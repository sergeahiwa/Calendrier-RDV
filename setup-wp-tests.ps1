# Script PowerShell pour configurer les tests WordPress manuellement

# Fonction pour télécharger un fichier
function Download-File {
    param (
        [string]$url,
        [string]$output
    )
    Write-Host "Téléchargement de $url vers $output" -ForegroundColor Cyan
    Invoke-WebRequest -Uri $url -OutFile $output
}

# Créer les dossiers nécessaires
$directories = @("tests/wordpress", "tests/wordpress-tests-lib", "tests/tmp")
foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}

# Télécharger WordPress
$wpZip = "tests/tmp/wordpress.zip"
if (-not (Test-Path $wpZip)) {
    Download-File -url "https://wordpress.org/latest.zip" -output $wpZip
}

# Extraire WordPress
if (-not (Test-Path "tests/wordpress/wp-settings.php")) {
    Write-Host "Extraction de WordPress..." -ForegroundColor Cyan
    Expand-Archive -Path $wpZip -DestinationPath "tests/tmp" -Force
    Copy-Item -Path "tests/tmp/wordpress/*" -Destination "tests/wordpress" -Recurse -Force
}

# Télécharger les tests WordPress
$wpTestsZip = "tests/tmp/wordpress-tests-lib.zip"
if (-not (Test-Path $wpTestsZip)) {
    Download-File -url "https://develop.svn.wordpress.org/trunk/" -output $wpTestsZip
}

# Extraire les tests
if (-not (Test-Path "tests/wordpress-tests-lib/includes/bootstrap.php")) {
    Write-Host "Extraction des tests WordPress..." -ForegroundColor Cyan
    Expand-Archive -Path $wpTestsZip -DestinationPath "tests/wordpress-tests-lib" -Force
}

# Créer le fichier de configuration des tests
$wpTestsConfig = @'
<?php
// Configuration de test pour WordPress
define( 'DB_NAME', 'wordpress_test' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// Configuration de débogage
define( 'WP_DEBUG', true );
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Calendrier RDV Tests' );

// Configuration des chemins
if ( ! defined( 'WP_TESTS_DIR' ) ) {
    define( 'WP_TESTS_DIR', dirname( __FILE__ ) . '/wordpress-tests-lib' );
}

if ( ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
    define( 'WP_TESTS_CONFIG_FILE_PATH', __FILE__ );
}

// Charger les tests
require_once WP_TESTS_DIR . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', function() {
    // Charger le plugin
    require dirname( dirname( __FILE__ ) ) . '/calendrier-rdv.php';
} );

// Démarrer les tests
require WP_TESTS_DIR . '/includes/bootstrap.php';
'@

# Écrire la configuration
$wpTestsConfig | Out-File -FilePath "tests/wordpress-tests-config.php" -Encoding utf8 -Force

Write-Host "Configuration des tests terminée !" -ForegroundColor Green
Write-Host "Vous pouvez maintenant exécuter les tests avec la commande :" -ForegroundColor Cyan
Write-Host "  vendor\bin\phpunit" -ForegroundColor Yellow

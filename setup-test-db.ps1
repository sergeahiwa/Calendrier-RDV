# Configuration de la base de données
$dbName = "wordpress_test"
$dbUser = "root"
$dbPass = ""
$dbHost = "localhost"

# Commande pour créer la base de données
$createDbCommand = "CREATE DATABASE IF NOT EXISTS $dbName;"

# Exécuter la commande MySQL
& mysql -u $dbUser -h $dbHost -e $createDbCommand

# Vérifier si la création a réussi
if ($LASTEXITCODE -eq 0) {
    Write-Host "Base de données de test créée avec succès : $dbName"
} else {
    Write-Error "Erreur lors de la création de la base de données de test"
    exit 1
}

# Télécharger WordPress pour les tests
$wpTestsDir = "$PSScriptRoot/tests/wordpress-tests-lib"
if (-not (Test-Path $wpTestsDir)) {
    New-Item -ItemType Directory -Path $wpTestsDir -Force | Out-Null
    
    # Télécharger la dernière version des tests WordPress
    $testsZipUrl = "https://github.com/WordPress/wordpress-develop/archive/refs/heads/trunk.zip"
    $testsZipPath = "$env:TEMP/wordpress-tests.zip"
    
    Write-Host "Téléchargement des tests WordPress..."
    Invoke-WebRequest -Uri $testsZipUrl -OutFile $testsZipPath
    
    # Extraire uniquement le dossier des tests
    Write-Host "Extraction des fichiers de test..."
    Expand-Archive -Path $testsZipPath -DestinationPath $env:TEMP -Force
    
    # Copier les fichiers nécessaires
    Copy-Item -Path "$env:TEMP/wordpress-develop-trunk/tests/phpunit/includes/*" -Destination $wpTestsDir -Recurse -Force
    Copy-Item -Path "$env:TEMP/wordpress-develop-trunk/tests/phpunit/data/*" -Destination "$wpTestsDir/data" -Recurse -Force
    
    # Nettoyer
    Remove-Item -Path $testsZipPath -Force
    Remove-Item -Path "$env:TEMP/wordpress-develop-trunk" -Recurse -Force
    
    Write-Host "Tests WordPress installés avec succès"
}

# Créer le fichier de configuration wp-tests-config.php
$wpTestConfigPath = "$PSScriptRoot/wp-tests-config.php"
$wpTestConfigContent = @"
<?php
define( 'DB_NAME', '$dbName' );
define( 'DB_USER', '$dbUser' );
define( 'DB_PASSWORD', '$dbPass' );
define( 'DB_HOST', '$dbHost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// Configuration de test
if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
    define( 'WP_TESTS_DOMAIN', 'example.org' );
}
if ( ! defined( 'WP_TESTS_EMAIL' ) ) {
    define( 'WP_TESTS_EMAIL', 'admin@example.org' );
}
if ( ! defined( 'WP_TESTS_TITLE' ) ) {
    define( 'WP_TESTS_TITLE', 'Calendrier RDV Tests' );
}

// Configuration de débogage
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', true );

// Clés de sécurité
define( 'AUTH_KEY', 'test-key-1234567890' );
define( 'SECURE_AUTH_KEY', 'test-secure-key-1234567890' );
define( 'LOGGED_IN_KEY', 'test-logged-in-key-1234567890' );
define( 'NONCE_KEY', 'test-nonce-key-1234567890' );
define( 'AUTH_SALT', 'test-auth-salt-1234567890' );
define( 'SECURE_AUTH_SALT', 'test-secure-auth-salt-1234567890' );
define( 'LOGGED_IN_SALT', 'test-logged-in-salt-1234567890' );
define( 'NONCE_SALT', 'test-nonce-salt-1234567890' );

// Préfixe des tables
\$table_prefix = 'wptests_';

// Activer le mode test
if ( ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
    define( 'WP_TESTS_CONFIG_FILE_PATH', __FILE__ );
}

require_once ABSPATH . 'wp-settings.php';
"@

Set-Content -Path $wpTestConfigPath -Value $wpTestConfigContent
Write-Host "Configuration de test créée : $wpTestConfigPath"

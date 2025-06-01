# Configuration de l'environnement de test WordPress pour Windows

# Variables de configuration
$dbName = "wordpress_test"
$dbUser = "root"
$dbPass = ""
$dbHost = "localhost"
$wpVersion = "latest"

# Créer la base de données si elle n'existe pas
$mysqlPath = "C:\xampp\mysql\bin\mysql.exe"  # Ajustez ce chemin selon votre installation
if (Test-Path $mysqlPath) {
    $createDbCommand = "`"$mysqlPath`" -u$dbUser -h$dbHost -e \"CREATE DATABASE IF NOT EXISTS $dbName;\""
    Invoke-Expression $createDbCommand
} else {
    Write-Host "MySQL n'est pas trouvé à $mysqlPath. Veuillez installer XAMPP ou ajuster le chemin."
    exit 1
}

# Télécharger et configurer WordPress
$wpTestsDir = "$PSScriptRoot/wordpress-tests-lib"
$wpDir = "$PSScriptRoot/wordpress"

# Créer les répertoires
New-Item -ItemType Directory -Force -Path $wpTestsDir | Out-Null
New-Item -ItemType Directory -Force -Path $wpDir | Out-Null

# Télécharger WordPress
$wpDownloadUrl = "https://wordpress.org/wordpress-$wpVersion.zip"
$wpZipPath = "$env:TEMP/wordpress-$wpVersion.zip"
Write-Host "Téléchargement de WordPress $wpVersion..."
Invoke-WebRequest -Uri $wpDownloadUrl -OutFile $wpZipPath

# Extraire WordPress
Write-Host "Extraction de WordPress..."
Expand-Archive -Path $wpZipPath -DestinationPath $env:TEMP -Force
Move-Item -Path "$env:TEMP/wordpress/*" -Destination $wpDir -Force

# Télécharger les tests
$wpTestsZipUrl = "https://develop.svn.wordpress.org/tags/$wpVersion/tests/phpunit/includes/"
$wpTestsZipPath = "$env:TEMP/wordpress-tests-lib-$wpVersion.zip"
Write-Host "Téléchargement des tests WordPress..."
Invoke-WebRequest -Uri $wpTestsZipUrl -OutFile $wpTestsZipPath

# Extraire les tests
Write-Host "Extraction des tests..."
Expand-Archive -Path $wpTestsZipPath -DestinationPath $env:TEMP -Force
Move-Item -Path "$env:TEMP/includes" -Destination $wpTestsDir -Force

# Créer le fichier de configuration de test
$wpConfigTestPath = "$PSScriptRoot/wp-tests-config.php"
$wpConfigContent = @"
<?php
define( 'DB_NAME', '$dbName' );
define( 'DB_USER', '$dbUser' );
define( 'DB_PASSWORD', '$dbPass' );
define( 'DB_HOST', '$dbHost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
"@

Set-Content -Path $wpConfigTestPath -Value $wpConfigContent

Write-Host "Configuration terminée. Vous pouvez maintenant exécuter les tests avec 'vendor\bin\phpunit'."

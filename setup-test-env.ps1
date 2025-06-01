# Script PowerShell pour configurer l'environnement de test sous Windows

# Vérifier si WordPress est installé
if (-not (Test-Path "wordpress")) {
    Write-Host "Téléchargement de WordPress..."
    Invoke-WebRequest -Uri "https://wordpress.org/latest.zip" -OutFile "wordpress.zip"
    Expand-Archive -Path "wordpress.zip" -DestinationPath "." -Force
    Remove-Item "wordpress.zip" -Force
}

# Vérifier si les tests WordPress sont installés
if (-not (Test-Path "tests/wordpress-tests-lib")) {
    Write-Host "Configuration des tests WordPress..."
    
    # Créer le répertoire des tests
    New-Item -ItemType Directory -Path "tests/wordpress-tests-lib" -Force
    
    # Télécharger les fichiers de test
    $testFiles = @(
        "https://develop.svn.wordpress.org/trunk/tests/phpunit/includes/",
        "https://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php"
    )
    
    foreach ($file in $testFiles) {
        $fileName = [System.IO.Path]::GetFileName($file)
        if ($file.EndsWith("/")) {
            $fileName = $file.Split("/")[-2] + ".php"
        }
        $output = "tests/wordpress-tests-lib/$fileName"
        Invoke-WebRequest -Uri $file -OutFile $output
    }
    
    # Copier le fichier de configuration des tests
    Copy-Item "tests/wordpress-tests-lib/wp-tests-config-sample.php" "tests/wordpress-tests-lib/wp-tests-config.php" -Force
    
    # Mettre à jour la configuration des tests
    $config = Get-Content "tests/wordpress-tests-lib/wp-tests-config.php" -Raw
    $config = $config -replace "youremptytestdbnamehere", "wordpress_test"
    $config = $config -replace "yourusernamehere", "root"
    $config = $config -replace "yourpasswordhere", "root"
    $config = $config -replace "localhost", "127.0.0.1"
    $config | Set-Content "tests/wordpress-tests-lib/wp-tests-config.php" -Force
}

Write-Host "Environnement de test configuré avec succès !" -ForegroundColor Green
Write-Host "Vous pouvez maintenant exécuter les tests avec la commande :"
Write-Host "  vendor\bin\phpunit" -ForegroundColor Cyan

<#
.SYNOPSIS
    Génère des notes de version à partir du fichier CHANGELOG.md
.DESCRIPTION
    Ce script extrait les notes de version pour une version spécifique du fichier CHANGELOG.md
    et les enregistre dans un fichier séparé.
.PARAMETER Version
    La version pour laquelle générer les notes (ex: 1.5.0)
.PARAMETER OutputFile
    Le fichier de sortie (par défaut: RELEASE-{version}.md dans le dossier docs/)
#>

param(
    [Parameter(Mandatory=$true)]
    [string]$Version,
    
    [string]$OutputFile = "docs/RELEASE-$Version.md"
)

# Vérifier que le fichier CHANGELOG.md existe
$changelogPath = "CHANGELOG.md"
if (-not (Test-Path $changelogPath)) {
    Write-Error "Le fichier CHANGELOG.md est introuvable"
    exit 1
}

# Lire le contenu du fichier
$content = Get-Content -Path $changelogPath -Raw

# Expression régulière pour extraire la section de la version
$pattern = "## \[$Version\].*?((?=## \[)|$)"
$match = [regex]::Match($content, $pattern, [System.Text.RegularExpressions.RegexOptions]::Singleline)

if (-not $match.Success) {
    Write-Error "Aucune entrée trouvée pour la version $Version dans le CHANGELOG.md"
    exit 1
}

# Préparer le contenu des notes de version
$releaseNotes = @"
# Notes de Version $Version

**Date de sortie :** $(Get-Date -Format "dd MMMM yyyy")
**Version minimale de WordPress :** 5.8  
**Version minimale de PHP :** 7.4

$($match.Groups[1].Value.Trim())

## Mise à Jour

### Depuis la version précédente
1. Sauvegardez votre base de données et vos fichiers
2. Mettez à jour via le gestionnaire de plugins WordPress
3. Videz les caches de votre site
4. Vérifiez que toutes les fonctionnalités fonctionnent correctement

## Remarques importantes
- Consultez le guide de migration pour les changements majeurs
- Signalez tout problème sur notre système de suivi

## Remerciements
Merci à tous les contributeurs et testeurs qui ont aidé à améliorer cette version.
"@

# Créer le répertoire de sortie si nécessaire
$outputDir = [System.IO.Path]::GetDirectoryName($OutputFile)
if (-not [string]::IsNullOrEmpty($outputDir) -and -not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
}

# Écrire le fichier de sortie
$releaseNotes.Trim() | Out-File -FilePath $OutputFile -Encoding utf8

Write-Host "Notes de version générées avec succès : $OutputFile" -ForegroundColor Green

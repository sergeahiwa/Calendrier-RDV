# Script d'audit Git pour Windows PowerShell

Write-Host "📦 Audit Git du projet Calendrier RDV" -ForegroundColor Cyan
Write-Host "====================================="

# 1. Afficher l'état Git
Write-Host "🔍 État du dépôt Git :" -ForegroundColor Yellow
git status
Write-Host ""

# 2. Afficher les 10 derniers fichiers modifiés
Write-Host "🕒 Derniers fichiers modifiés (dans Git) :" -ForegroundColor Yellow
git log --name-only --pretty=format: --since="7 days ago" | Select-Object -Unique | Select-Object -Last 10
Write-Host ""

# 3. Fichiers modifiés non commités
Write-Host "📝 Fichiers modifiés non commités :" -ForegroundColor Yellow
git diff --name-only
Write-Host ""

# 4. Vérification des fichiers IA critiques
Write-Host "🤖 Fichiers IA modifiés récemment :" -ForegroundColor Yellow
$iaFiles = git diff --name-only | Select-String -Pattern 'ia|intelligence|ml|ai'
if ($iaFiles) {
    $iaFiles
} else {
    Write-Host "Aucun fichier IA modifié." -ForegroundColor Green
}
Write-Host ""

# 5. Dernier commit
Write-Host "📌 Dernier commit :" -ForegroundColor Yellow
git log -1 --oneline
Write-Host ""

Write-Host "✅ Audit terminé." -ForegroundColor Green

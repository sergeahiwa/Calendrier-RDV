# Script d'analyse des résultats de test de charge
# Nécessite le module PSWriteHTML pour les graphiques
# Installer avec: Install-Module -Name PSWriteHTML -Force -AllowClobber -Scope CurrentUser

param(
    [string]$resultsDir = "load-test-results"
)

# Vérifier si le module PSWriteHTML est installé
if (-not (Get-Module -ListAvailable -Name PSWriteHTML)) {
    Write-Host "Installation du module PSWriteHTML..." -ForegroundColor Yellow
    Install-Module -Name PSWriteHTML -Force -AllowClobber -Scope CurrentUser -SkipPublisherCheck
    Import-Module PSWriteHTML
}

# Charger les données
$userFiles = Get-ChildItem -Path $resultsDir -Filter "user_*.json"
$summaryFile = Join-Path $resultsDir "summary.json"

if (-not (Test-Path $summaryFile)) {
    Write-Error "Fichier de synthèse non trouvé: $summaryFile"
    exit 1
}

# Compatibilité avec les anciennes versions de PowerShell
function ConvertFrom-JsonToHashtable {
    param([string]$json)
    $obj = $json | ConvertFrom-Json
    $ht = @{}
    $obj.PSObject.Properties | ForEach-Object { $ht[$_.Name] = $_.Value }
    return $ht
}

$summary = ConvertFrom-JsonToHashtable (Get-Content $summaryFile -Raw)
$userData = $userFiles | ForEach-Object { 
    $content = Get-Content $_.FullName -Raw
    ConvertFrom-JsonToHashtable $content 
}

# Créer un rapport HTML
$reportPath = Join-Path $resultsDir "load-test-report_$(Get-Date -Format 'yyyyMMdd_HHmmss').html"

$html = New-Html -Title "Rapport de Test de Charge - Calendrier RDV" -FilePath $reportPath {
    
    New-HTMLHeader {
        New-HTMLText -Text "Rapport de Test de Charge" -Alignment center -FontSize 24 -FontWeight bold
        New-HTMLText -Text "Généré le $(Get-Date -Format 'dd/MM/yyyy à HH:mm')" -Alignment center -FontSize 12 -Color Gray
    }
    
    New-HTMLSection -HeaderBackGroundColor Teal -HeaderText "Résumé du Test" -HeaderTextColor White -HeaderTextSize 20 {
        New-HTMLPanel {
            New-HTMLTable -DataTable @(
                [PSCustomObject]@{
                    "Métrique" = "Date du test"
                    "Valeur" = $summary.testStart
                },
                [PSCustomObject]@{
                    "Métrique" = "Durée du test"
                    "Valeur" = ($summary.testEnd - $summary.testStart).ToString("hh\:mm\:ss")
                },
                [PSCustomObject]@{
                    "Métrique" = "Utilisateurs simulés"
                    "Valeur" = "$($summary.completedUsers)/$($summary.totalUsers)"
                },
                [PSCustomObject]@{
                    "Métrique" = "Requêtes totales"
                    "Valeur" = $summary.totalRequests
                },
                [PSCustomObject]@{
                    "Métrique" = "Taux d'échec"
                    "Valeur" = "$([math]::Round(($summary.failedRequests / [math]::Max(1, $summary.totalRequests)) * 100, 2))%"
                },
                [PSCustomObject]@{
                    "Métrique" = "Temps de réponse moyen"
                    "Valeur" = "$([math]::Round($summary.avgResponseTime, 2))s"
                },
                [PSCustomObject]@{
                    "Métrique" = "Temps de réponse (min/max)"
                    "Valeur" = "$([math]::Round($summary.minResponseTime, 2))s / $([math]::Round($summary.maxResponseTime, 2))s"
                },
                [PSCustomObject]@{
                    "Métrique" = "95e percentile"
                    "Valeur" = "$([math]::Round($summary.percentile95, 2))s"
                }
            ) -Border 1 -Style cell-border -DisablePaging
        }
    }
    
    # Graphique des temps de réponse
    New-HTMLSection -HeaderBackGroundColor SteelBlue -HeaderText "Temps de Réponse" -HeaderTextColor White -HeaderTextSize 20 {
        New-HTMLPanel {
            $responseTimes = $userData | ForEach-Object { $_.requests } | Where-Object { $_.success -eq $true } | Select-Object -First 1000
            $responseTimes = $responseTimes | Sort-Object { [datetime]::Parse($_.timestamp) }
            
            $data = $responseTimes | ForEach-Object {
                [PSCustomObject]@{
                    Timestamp = [datetime]::Parse($_.timestamp).ToString("HH:mm:ss")
                    "Temps de réponse (s)" = [math]::Round($_.duration, 3)
                }
            }
            
            if ($data.Count -gt 0) {
                New-ChartToolbar -Download
                New-ChartLine -Title "Temps de réponse par requête (premières 1000 requêtes)" -DataTable $data -XField "Timestamp" -YField @("Temps de réponse (s)")
            } else {
                New-HTMLAlert -Type warning -Text "Aucune donnée de temps de réponse disponible"
            }
        }
    }
    
    # Répartition des types de requêtes
    New-HTMLSection -HeaderBackGroundColor DarkSlateGray -HeaderText "Répartition des Requêtes" -HeaderTextColor White -HeaderTextSize 20 {
        New-HTMLPanel {
            $requestTypes = $userData | ForEach-Object { $_.requests } | Group-Object action | Select-Object @{
                Name = 'Type';
                Expression = { $_.Name }
            }, @{
                Name = 'Count';
                Expression = { $_.Count }
            }
            
            if ($requestTypes.Count -gt 0) {
                New-ChartToolbar -Download
                New-ChartPie -Title "Répartition par type de requête" -DataTable $requestTypes -Name "Type" -Value "Count"
            } else {
                New-HTMLAlert -Type warning -Text "Aucune donnée de type de requête disponible"
            }
        }
    }
    
    # Détails des erreurs
    $errors = $userData | ForEach-Object { $_.requests } | Where-Object { $_.success -eq $false }
    if ($errors.Count -gt 0) {
        New-HTMLSection -HeaderBackGroundColor FireBrick -HeaderText "Erreurs ($($errors.Count))" -HeaderTextColor White -HeaderTextSize 20 {
            New-HTMLPanel {
                $errorDetails = $errors | Group-Object action | Select-Object @{
                    Name = 'Type';
                    Expression = { $_.Name }
                }, @{
                    Name = 'Count';
                    Expression = { $_.Count }
                } | Sort-Object Count -Descending
                
                New-HTMLTable -DataTable $errorDetails -Title "Répartition des erreurs par type" -PagingOptions @(50, 100, 200)
            }
        }
    }
    
    # Détails des utilisateurs
    New-HTMLSection -HeaderBackGroundColor DarkGreen -HeaderText "Détails par Utilisateur" -HeaderTextColor White -HeaderTextSize 20 -Collapsible {
        $userStats = $userData | ForEach-Object {
            $successRate = if ($_.requestCount -gt 0) { [math]::Round(($_.requestCount - $_.failedCount) / $_.requestCount * 100, 2) } else { 0 }
            
            [PSCustomObject]@{
                "ID Utilisateur" = $_.userId
                "Début" = $_.startTime
                "Fin" = $_.endTime
                "Durée" = ([datetime]$_.endTime - [datetime]$_.startTime).ToString("hh\:mm\:ss")
                "Requêtes" = $_.requestCount
                "Échecs" = $_.failedCount
                "Taux de réussite" = "$successRate%"
                "Temps moyen (s)" = if ($_.requests.Count -gt 0) { [math]::Round(($_.requests | Measure-Object -Property duration -Average).Average, 3) } else { 0 }
            }
        }
        
        New-HTMLTable -DataTable $userStats -PagingOptions @(10, 25, 50) -DefaultSortColumn "ID Utilisateur" -Buttons @('copy', 'csv', 'excel')
    }
}

Write-Host "Rapport généré avec succès: $reportPath" -ForegroundColor Green
# Ouvrir le rapport dans le navigateur par défaut
Start-Process $reportPath

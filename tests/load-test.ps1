# Script de test de charge pour Calendrier RDV
# Configuration
$baseUrl = "http://calendrier-rdv.local/"  # URL du site WordPress

# Vérifier la connexion au site
try {
    $response = Invoke-WebRequest -Uri $baseUrl -UseBasicParsing -ErrorAction Stop
    Write-Host "Connexion au site $baseUrl réussie (HTTP $($response.StatusCode))" -ForegroundColor Green
} catch {
    Write-Host "Impossible de se connecter à $baseUrl" -ForegroundColor Red
    Write-Host "Erreur: $_" -ForegroundColor Red
    Write-Host "Veuillez vérifier que le site est accessible et que l'URL est correcte." -ForegroundColor Yellow
    exit 1
}
$totalUsers = 50
$rampUp = 10  # Utilisateurs par seconde
$testDuration = 300  # 5 minutes en secondes
$thinkTimeMin = 1
$thinkTimeMax = 5
$resultsDir = "load-test-results"

# Créer le répertoire des résultats
if (-not (Test-Path $resultsDir)) {
    New-Item -ItemType Directory -Path $resultsDir | Out-Null
}

# Fonction pour générer un temps de réflexion aléatoire
function Get-RandomThinkTime {
    return Get-Random -Minimum ($thinkTimeMin * 1000) -Maximum ($thinkTimeMax * 1000) | ForEach-Object { $_ / 1000.0 }
}

# Fonction pour simuler un utilisateur
function Simulate-User {
    param (
        [int]$userId
    )
    
    $userResults = @{
        userId = $userId
        startTime = Get-Date
        requests = @()
        requestCount = 0
        failedCount = 0
    }
    
    $endTime = (Get-Date).AddSeconds($testDuration)
    
    try {
        while ((Get-Date) -lt $endTime) {
            # Temps de réflexion
            Start-Sleep -Seconds (Get-RandomThinkTime)
            
            # Choisir une action
            $action = Get-Random -Minimum 1 -Maximum 11  # 1-10
            $requestStart = Get-Date
            $success = $false
            
            try {
                if ($action -le 6) {
                    try {
                        # 60%: Voir le calendrier
                        $url = "${baseUrl}calendrier/"
                        $requestStart = Get-Date
                        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -ErrorAction Stop
                        $success = $response.StatusCode -eq 200
                        Write-Host "[$userId] Consultation du calendrier: $($response.StatusCode)" -ForegroundColor DarkGray
                    }
                    catch {
                        $success = $false
                        $userResults.failedCount++
                        $errorMsg = $_.Exception.Message
                        Write-Host "[$userId] ERREUR: $errorMsg" -ForegroundColor Red
                        
                        # Enregistrer les détails de l'erreur
                        $userResults.requests += @{
                            timestamp = Get-Date -Format 'o'
                            duration = 0
                            success = $false
                            action = 'view_calendar'
                            error = $errorMsg
                            statusCode = $_.Exception.Response.StatusCode.value__
                        }
                    }
                }
                elseif ($action -le 9) {
                    # 30%: Voir les disponibilités
                    $date = (Get-Date).AddDays((Get-Random -Minimum 0 -Maximum 31)).ToString("yyyy-MM-dd")
                    $url = "${baseUrl}api/disponibilites?date=$date"
                    try {
                        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -ErrorAction Stop
                        $success = $response.StatusCode -eq 200
                        Write-Host "[$userId] Vérification des disponibilités: $($response.StatusCode)" -ForegroundColor DarkGray
                    }
                    catch {
                        $success = $false
                        $userResults.failedCount++
                        $errorMsg = $_.Exception.Message
                        Write-Host "[$userId] ERREUR: $errorMsg" -ForegroundColor Red
                        
                        # Enregistrer les détails de l'erreur
                        $userResults.requests += @{
                            timestamp = Get-Date -Format 'o'
                            duration = 0
                            success = $false
                            action = 'check_availability'
                            error = $errorMsg
                            statusCode = $_.Exception.Response.StatusCode.value__
                        }
                    }
                }
                else {
                    # 10%: Prendre un rendez-vous
                    $url = "${baseUrl}wp-admin/admin-ajax.php"
                    $date = (Get-Date).AddDays((Get-Random -Minimum 1 -Maximum 31)).ToString("yyyy-MM-dd")
                    $time = "{0:D2}:{1:D2}:00" -f (Get-Random -Minimum 9 -Maximum 18), ((Get-Random -Minimum 0 -Maximum 2) * 30)
                    
                    $postData = @{
                        action = 'add_rdv_event'
                        nonce = 'test-nonce'  # Remplacez par un nonce valide
                        date = $date
                        time = $time
                        service_id = Get-Random -Minimum 1 -Maximum 6
                        provider_id = 1
                        client_name = "Utilisateur Test $userId"
                        client_email = "user${userId}@test.com"
                        client_phone = '0123456789'
                        notes = 'Test de charge'
                    }
                    
                    try {
                        $response = Invoke-WebRequest -Uri $url -Method Post -Body $postData -UseBasicParsing
                        $responseData = $response.Content | ConvertFrom-Json
                        $success = $response.StatusCode -eq 200 -and $responseData.success -eq $true
                        Write-Host "[$userId] Prise de rendez-vous: $($response.StatusCode)" -ForegroundColor DarkGray
                    }
                    catch {
                        $success = $false
                        $userResults.failedCount++
                        $errorMsg = $_.Exception.Message
                        Write-Host "[$userId] ERREUR: $errorMsg" -ForegroundColor Red
                        
                        # Enregistrer les détails de l'erreur
                        $userResults.requests += @{
                            timestamp = Get-Date -Format 'o'
                            duration = 0
                            success = $false
                            action = 'book_appointment'
                            error = $errorMsg
                            statusCode = $_.Exception.Response.StatusCode.value__
                        }
                    }
                }
                
                $userResults.requestCount++
            }
            catch {
                $success = $false
                $userResults.failedCount++
                $errorMsg = $_.Exception.Message
                Write-Host "[$userId] ERREUR: $errorMsg" -ForegroundColor Red
                
                # Enregistrer les détails de l'erreur
                $userResults.requests += @{
                    timestamp = Get-Date -Format 'o'
                    duration = 0
                    success = $false
                    action = if ($action -le 6) { 'view_calendar' } elseif ($action -le 9) { 'check_availability' } else { 'book_appointment' }
                    error = $errorMsg
                    statusCode = $_.Exception.Response.StatusCode.value__
                }
            }
            finally {
                $requestTime = ((Get-Date) - $requestStart).TotalSeconds
                $userResults.requests += @{
                    timestamp = Get-Date -Format 'o'
                    duration = $requestTime
                    success = $success
                    action = if ($action -le 6) { 'view_calendar' } elseif ($action -le 9) { 'check_availability' } else { 'book_appointment' }
                }
            }
        }
    }
    finally {
        $userResults.endTime = Get-Date
        
        # Sauvegarder les résultats
        $userResults | ConvertTo-Json -Depth 10 | Out-File "${resultsDir}/user_${userId}.json"
        
        Write-Host "Utilisateur $userId terminé. Requêtes: $($userResults.requestCount), Échecs: $($userResults.failedCount)"
    }
}

# Lancer les utilisateurs
Write-Host "Démarrage du test de charge avec $totalUsers utilisateurs..."

$startTime = Get-Date
$jobs = @()

# Lancer les utilisateurs par lots
for ($i = 0; $i -lt $totalUsers; $i += $rampUp) {
    $batchSize = [Math]::Min($rampUp, $totalUsers - $i)
    
    for ($j = 0; $j -lt $batchSize; $j++) {
        $userId = $i + $j + 1
        $scriptBlock = {
            param($uId)
            . "$PSScriptRoot\load-test.ps1"
            Simulate-User -userId $uId
        }
        
        $jobs += Start-Job -ScriptBlock $scriptBlock -ArgumentList $userId
    }
    
    Write-Host "Lot lancé : $batchSize utilisateurs (Total: $($i + $batchSize)/$totalUsers)"
    Start-Sleep -Seconds 1  # Attendre 1 seconde entre les lots
}

# Attendre la fin des tests ou la fin du temps imparti
$testEndTime = $startTime.AddSeconds($testDuration + 60)  # Marge de 60 secondes

while ((Get-Date) -lt $testEndTime -and ($jobs | Where-Object { $_.State -eq 'Running' })) {
    $running = @($jobs | Where-Object { $_.State -eq 'Running' }).Count
    Write-Host "En attente de la fin des tests... (En cours: $running)"
    Start-Sleep -Seconds 5
}

# Nettoyer les jobs
$jobs | Stop-Job -PassThru | Remove-Job -Force

# Générer un rapport de synthèse
$report = @{
    testStart = $startTime
    testEnd = Get-Date
    totalUsers = $totalUsers
    completedUsers = (Get-ChildItem $resultsDir | Measure-Object).Count
    totalRequests = 0
    failedRequests = 0
    responseTimes = @()
}

Get-ChildItem $resultsDir | ForEach-Object {
    $userData = Get-Content $_.FullName | ConvertFrom-Json -AsHashtable
    $report.totalRequests += $userData.requestCount
    $report.failedRequests += $userData.failedCount
    $report.responseTimes += $userData.requests | ForEach-Object { $_.duration }
}

# Calculer les statistiques
if ($report.responseTimes.Count -gt 0) {
    $report.avgResponseTime = ($report.responseTimes | Measure-Object -Average).Average
    $report.minResponseTime = ($report.responseTimes | Measure-Object -Minimum).Minimum
    $report.maxResponseTime = ($report.responseTimes | Measure-Object -Maximum).Maximum
    $report.percentile95 = $report.responseTimes | Sort-Object | Select-Object -Skip ([math]::Floor($report.responseTimes.Count * 0.95)) | Select-Object -First 1
}

# Enregistrer le rapport
$report | ConvertTo-Json -Depth 10 | Out-File "${resultsDir}/summary.json"

# Afficher un résumé
Write-Host "`n=== RAPPORT DE TEST DE CHARGE ==="
Write-Host "Durée du test: $($report.testEnd - $report.testStart)"
Write-Host "Utilisateurs: $($report.completedUsers)/$($report.totalUsers)"
Write-Host "Requêtes totales: $($report.totalRequests)"
Write-Host "Échecs: $($report.failedRequests) ($([math]::Round(($report.failedRequests / [math]::Max(1, $report.totalRequests)) * 100, 2))%)"
Write-Host "Temps de réponse moyen: $([math]::Round($report.avgResponseTime, 2))s"
Write-Host "Temps de réponse min/max: $([math]::Round($report.minResponseTime, 2))s / $([math]::Round($report.maxResponseTime, 2))s"
Write-Host "95e percentile: $([math]::Round($report.percentile95, 2))s"

Write-Host "`nTest de charge terminé. Les résultats détaillés sont disponibles dans le dossier $resultsDir"

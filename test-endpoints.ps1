#!/usr/bin/env pwsh
# ============================================
# Tests API Sandbox - PowerShell Script
# ============================================
#
# Utilisation:
#   powershell -ExecutionPolicy Bypass -File test-endpoints.ps1
#
# Affiche les résultats JSON en couleur
# ============================================

param(
    [string]$BaseUrl = "http://localhost:8000/MALOTY/backend/api/payment_sandbox",
    [int]$TestCount = 5,
    [switch]$All = $false
)

$ErrorActionPreference = "SilentlyContinue"

function Write-Title {
    Write-Host "`n" + ("=" * 50) -ForegroundColor Cyan
    Write-Host $args[0] -ForegroundColor Green -NoNewline
    Write-Host "`n" + ("=" * 50) -ForegroundColor Cyan
}

function Write-JsonResult {
    param([string]$Json)
    
    try {
        $obj = $Json | ConvertFrom-Json
        $Json | ConvertTo-Json -Depth 10 | Write-Host -ForegroundColor White
    } catch {
        Write-Host $Json -ForegroundColor Gray
    }
}

function Invoke-TestRequest {
    param(
        [string]$Name,
        [string]$Endpoint,
        [string]$Method = "GET",
        [hashtable]$Body = $null
    )
    
    Write-Title "🧪 $Name"
    
    try {
        $url = "$BaseUrl/$Endpoint"
        $params = @{
            Uri = $url
            Method = $Method
            Headers = @{ "Content-Type" = "application/json" }
            ErrorAction = "Stop"
        }
        
        if ($Body) {
            $params['Body'] = $Body | ConvertTo-Json
        }
        
        Write-Host "📡 $Method $url" -ForegroundColor Magenta
        
        $response = Invoke-WebRequest @params
        $result = $response.Content
        
        Write-JsonResult $result
        Write-Host ""
        
        return $result
    } catch {
        Write-Host "❌ Erreur: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host ""
        return $null
    }
}

# ============================================
# Menu Principal
# ============================================

Clear-Host
Write-Host ""
Write-Host "🧪 TESTS API SANDBOX PAIEMENT" -ForegroundColor Cyan
Write-Host "Base URL: $BaseUrl" -ForegroundColor Gray
Write-Host ""

# 1. Status
Invoke-TestRequest "Status du Sandbox" "status"

# 2. Création simple
$createResult = Invoke-TestRequest "Créer un Paiement" "test-create" "POST" @{
    type = "tithe"
    amount = 50000
    donor_name = "Test User"
}

# 3. Génération de données
Invoke-TestRequest "Générer $TestCount Paiements de Test" "generate-test-data" "POST" @{
    count = $TestCount
}

# 4. Liste des paiements
Invoke-TestRequest "Lister les Paiements" "test-list"

# 5. Workflow complet
Invoke-TestRequest "Simuler un Workflow Complet" "test-simulate" "POST" @{
    delay = 1
}

# 6. Logs
Write-Title "📊 Logs de Test"
$logDir = "tmp/sandbox-logs"
if (Test-Path $logDir) {
    Write-Host "Fichiers de logs:" -ForegroundColor Green
    Get-ChildItem $logDir -Filter "*.log" | Select-Object Name, @{Name="Size";Expression={"{0:N0} bytes" -f $_.Length}}, LastWriteTime | Format-Table
    
    $latestLog = Get-ChildItem $logDir -Filter "*.log" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    if ($latestLog) {
        Write-Host "`nDernières lignes du log:" -ForegroundColor Green
        Get-Content $latestLog | Select-Object -Last 10 | Write-Host -ForegroundColor Gray
    }
} else {
    Write-Host "Aucun log (dossier non créé)" -ForegroundColor Gray
}

# 7. Résumé
Write-Host ""
Write-Title "✅ Résumé des Tests"
Write-Host "
Les tests sont terminés ! 

Prochaines étapes:
  1️⃣  Ouvrez: http://localhost:8000/MALOTY/frontend/test-sandbox.html
  2️⃣  Testez via l'interface web
  3️⃣  Consultez les logs dans: tmp/sandbox-logs/

Intégration dans votre frontend:
  - Utilisez les endpoints POST pour créer des paiements
  - Stockez payment_ref et confirmation_code
  - Confirmez via test-confirm
  - Vérifiez le statut via test-status

Documentation complète:
  - SANDBOX_PAYMENT_GUIDE.md
  - QUICKSTART_SANDBOX.md
" -ForegroundColor Cyan

Write-Host ""
Read-Host "Appuyez sur Entrée pour terminer"

# ============================================
# Script de Démarrage Sandbox - MALOTY
# ============================================
# 
# Démarrage du serveur PHP avec Sandbox Mode activé
# 
# Utilisation:
#   powershell -ExecutionPolicy Bypass -File start-sandbox.ps1
#
# Windows 10+
# ============================================

# Couleurs pour l'affichage
function Write-Status { Write-Host $args[0] -ForegroundColor Cyan }
function Write-Success { Write-Host $args[0] -ForegroundColor Green }
function Write-Error-Custom { Write-Host $args[0] -ForegroundColor Red }

Write-Host ""
Write-Status "🧪 SANDBOX PAYMENT API - MALOTY"
Write-Status "================================="
Write-Host ""

# 1. Vérifier PHP
Write-Status "1️⃣  Vérification de PHP..."
$phpPath = (Get-Command php -ErrorAction SilentlyContinue).Source

if (-not $phpPath) {
    Write-Error-Custom "❌ PHP non trouvé!"
    Write-Host "Installez PHP ou ajoutez-le au PATH."
    exit 1
}

Write-Success "✅ PHP trouvé: $phpPath"
php -v

# 2. Déterminer le port
$port = 8000
if ($args.Count -gt 0) {
    $port = $args[0]
}

# Vérifier si le port est disponible
Write-Status "`n2️⃣  Vérification du port $port..."
$portInUse = netstat -an | Select-String ":$port" | Measure-Object

if ($portInUse.Count -gt 0) {
    Write-Error-Custom "⚠️  Le port $port est déjà utilisé!"
    Write-Host "Essayez un autre port: powershell -File start-sandbox.ps1 8001"
    exit 1
}

Write-Success "✅ Port $port disponible"

# 3. Définir l'environnement Sandbox
Write-Status "`n3️⃣  Configuration du mode Sandbox..."

$env:SANDBOX_MODE = $true
$env:SANDBOX_DEBUG = $true
$workingDir = Split-Path -Parent $MyInvocation.MyCommand.Path

Write-Success "✅ SANDBOX_MODE = true"
Write-Success "✅ Répertoire: $workingDir"

# 4. Afficher les informations
Write-Host ""
Write-Host "════════════════════════════════════════" -ForegroundColor Magenta
Write-Host "🚀 DÉMARRAGE DU SERVEUR" -ForegroundColor Green
Write-Host "════════════════════════════════════════" -ForegroundColor Magenta
Write-Host ""
Write-Success "📡 Serveur: http://localhost:$port"
Write-Success "🎯 Sandbox: http://localhost:$port/MALOTY/backend/api/payment_sandbox/status"
Write-Success "🧪 Tests: http://localhost:$port/MALOTY/frontend/test-sandbox.html"
Write-Host ""
Write-Status "💡 Appuyez sur Ctrl+C pour arrêter le serveur"
Write-Host ""

# 5. Démarrer le serveur
Set-Location $workingDir
php -S "localhost:$port" -t "."

Write-Error-Custom "`n❌ Serveur arrêté"

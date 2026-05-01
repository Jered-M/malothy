@echo off
REM ============================================
REM Script de Démarrage Sandbox - MALOTY
REM ============================================
REM Démarrage du serveur PHP avec Sandbox Mode
REM
REM Utilisation:
REM   start-sandbox.bat
REM   start-sandbox.bat 8001  (avec port personnalisé)
REM ============================================

setlocal enabledelayedexpansion

echo.
echo 🧪 SANDBOX PAYMENT API - MALOTY
echo =================================
echo.

REM 1. Vérifier PHP
echo 1️⃣  Vérification de PHP...
where php >nul 2>nul

if errorlevel 1 (
    echo ❌ PHP non trouvé!
    echo Installez PHP ou ajoutez-le au PATH.
    pause
    exit /b 1
)

for /f "tokens=*" %%a in ('where php') do (
    set "php_path=%%a"
)
echo ✅ PHP trouvé: !php_path!
php --version

REM 2. Déterminer le port
set PORT=8000
if not "%1"=="" (
    set PORT=%1
)

REM Vérifier si le port est disponible (Windows)
echo.
echo 2️⃣  Vérification du port !PORT!...
netstat -an | findstr ":!PORT!" >nul 2>nul

if not errorlevel 1 (
    echo ⚠️  Le port !PORT! est déjà utilisé!
    echo Essayez un autre port: start-sandbox.bat 8001
    pause
    exit /b 1
)

echo ✅ Port !PORT! disponible

REM 3. Définir l'environnement
echo.
echo 3️⃣  Configuration du mode Sandbox...
set SANDBOX_MODE=true
set SANDBOX_DEBUG=true

echo ✅ SANDBOX_MODE = true
echo ✅ Répertoire: %cd%

REM 4. Afficher les informations
echo.
echo ════════════════════════════════════════
echo 🚀 DÉMARRAGE DU SERVEUR
echo ════════════════════════════════════════
echo.
echo 📡 Serveur: http://localhost:!PORT!
echo 🎯 Sandbox: http://localhost:!PORT!/MALOTY/backend/api/payment_sandbox/status
echo 🧪 Tests: http://localhost:!PORT!/MALOTY/frontend/test-sandbox.html
echo.
echo 💡 Appuyez sur Ctrl+C pour arrêter le serveur
echo.

REM 5. Démarrer le serveur
php -S localhost:!PORT! -t .

echo.
echo ❌ Serveur arrêté
pause

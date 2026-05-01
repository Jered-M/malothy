@echo off
REM ============================================
REM Tests API Sandbox - Collection de cURL
REM ============================================
REM
REM Utilisation:
REM   test-endpoints.bat
REM
REM Nécessite: cURL (Windows 10+) ou Git Bash
REM ============================================

setlocal enabledelayedexpansion

echo.
echo 🧪 TESTS API SANDBOX PAIEMENT
echo ==============================
echo.

set BASE_URL=http://localhost:8000/MALOTY/backend/api/payment_sandbox
set TEST_COUNT=5

REM Couleurs via PowerShell n'est pas possible en batch pur
REM donc on utilisera du texte simple

echo.
echo [1/7] Test de Status
echo ────────────────────
curl -s "%BASE_URL%/status"
echo.

echo.
echo [2/7] Création d'un paiement
echo ────────────────────
for /f "delims=" %%A in ('curl -s -X POST "%BASE_URL%/test-create" -H "Content-Type: application/json" -d "{\"type\":\"tithe\",\"amount\":50000,\"donor_name\":\"Test\"}" ^| findstr payment_ref') do (
    set "PAYMENT_REF=%%A"
)
echo %PAYMENT_REF%
echo.

echo.
echo [3/7] Génération de 5 paiements de test
echo ────────────────────
curl -s -X POST "%BASE_URL%/generate-test-data" ^
  -H "Content-Type: application/json" ^
  -d "{\"count\": %TEST_COUNT%}"
echo.

echo.
echo [4/7] Liste des paiements
echo ────────────────────
curl -s "%BASE_URL%/test-list"
echo.

echo.
echo [5/7] Simulation d'un workflow (create-confirm)
echo ────────────────────
curl -s -X POST "%BASE_URL%/test-simulate" ^
  -H "Content-Type: application/json" ^
  -d "{\"delay\": 1}"
echo.

echo.
echo [6/7] Vérification des logs
echo ────────────────────
if exist tmp\sandbox-logs (
    dir tmp\sandbox-logs\
) else (
    echo Aucun log (dossier non créé)
)
echo.

echo.
echo [7/7] Réinitialisation des données (optionnel)
echo ────────────────────
echo Pour réinitialiser, exécutez:
echo curl -X POST "%BASE_URL%/reset-all" -H "Content-Type: application/json" -d "{\"confirm\": true}"
echo.

echo.
echo ✅ Tests terminés !
echo Pour plus d'infos, visitez: http://localhost:8000/MALOTY/frontend/test-sandbox.html
echo.

pause

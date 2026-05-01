# 📋 Résumé Installation Sandbox Paiement

**Date:** 30 Avril 2026  
**Projet:** MALOTY - Système de Paiement Local  
**Objectif:** Utiliser un sandbox pour développer/tester l'API de paiement sur réseau local

---

## ✨ Fichiers Créés/Modifiés

### Configuration
- ✅ **`backend/config/sandbox-config.php`** - Configuration complète du sandbox
  - Modes test/production
  - Réseaux locaux autorisés
  - Paramètres de test
  - Webhooks et logging

### Contrôleur API
- ✅ **`backend/api/controllers/PaymentSandboxController.php`** - API Sandbox
  - 8 endpoints de test
  - Création de paiements
  - Confirmation
  - Simulation de workflows
  - Génération de données

### Interface Web
- ✅ **`frontend/test-sandbox.html`** - Interface interactive
  - Dashboard visuel
  - Tests rapides
  - Formulaires avancés
  - Affichage des résultats en temps réel

### Scripts de Démarrage
- ✅ **`start-sandbox.ps1`** - PowerShell (Windows)
- ✅ **`start-sandbox.bat`** - Batch (Windows)
- ✅ **`test-endpoints.ps1`** - Tests PowerShell
- ✅ **`test-endpoints.bat`** - Tests Batch

### Vérification
- ✅ **`verify-sandbox.php`** - Script de vérification
  - Vérifie tous les fichiers
  - Teste les extensions PHP
  - Vérifie la BD
  - Vérifie les configurations

### Documentation
- ✅ **`SANDBOX_PAYMENT_GUIDE.md`** - Guide complet détaillé
- ✅ **`QUICKSTART_SANDBOX.md`** - Démarrage rapide
- ✅ **`INSTALLATION_SANDBOX.md`** - Ce fichier

---

## 🎯 Fonctionnalités

### ✅ Créées
1. **Création de Paiement de Test**
   - Données auto-générées
   - Types variés (dîme, offrande, etc.)
   - Références uniques (`TEST-PAY-...`)
   - Codes de confirmation (`TEST-...`)

2. **Confirmation de Paiement**
   - Statut tracking (pending → confirmed)
   - Codes de confirmation sécurisés
   - Horodatage automatique

3. **Simulation de Workflows**
   - Create → Verify → Confirm → Verify final
   - Délais configurables
   - Logs détaillés

4. **Gestion de Données**
   - Génération batch
   - Liste avec filtrage
   - Réinitialisation

5. **Logging & Debugging**
   - Logs horodatés
   - Niveaux debug/info/warning/error
   - Stockage dans `tmp/sandbox-logs/`

6. **Accès Réseau Local**
   - IP whitelist
   - Support CIDR (`192.168.1.0/24`)
   - Vérification source

---

## 📡 Endpoints API

### Publics (Tous accessibles)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/payment_sandbox/status` | Vérifier le statut du sandbox |
| POST | `/payment_sandbox/test-create` | Créer un paiement de test |
| POST | `/payment_sandbox/test-confirm` | Confirmer un paiement |
| GET | `/payment_sandbox/test-status` | Vérifier le statut d'un paiement |
| POST | `/payment_sandbox/test-simulate` | Simuler un workflow complet |
| GET | `/payment_sandbox/test-list` | Lister les paiements de test |
| POST | `/payment_sandbox/generate-test-data` | Générer N paiements de test |
| POST | `/payment_sandbox/reset-all` | Réinitialiser les données |

### Format d'Appel
```
http://localhost:8000/MALOTY/backend/api/payment_sandbox/{endpoint}
```

---

## 🚀 Démarrage

### 1. Vérification du Setup
```bash
# Vérifier que tout est bien configuré
php verify-sandbox.php
```

### 2. Démarrer le Serveur
```powershell
# PowerShell - Windows
powershell -ExecutionPolicy Bypass -File start-sandbox.ps1

# Ou CMD
start-sandbox.bat

# Ou Manuel
php -S localhost:8000
```

### 3. Accéder à l'Interface
```
http://localhost:8000/MALOTY/frontend/test-sandbox.html
```

### 4. Tester les Endpoints
```bash
# Vérifier le statut
curl http://localhost:8000/MALOTY/backend/api/payment_sandbox/status

# Créer un paiement
curl -X POST http://localhost:8000/MALOTY/backend/api/payment_sandbox/test-create \
  -H "Content-Type: application/json" \
  -d '{"type":"tithe","amount":50000}'
```

---

## 🔧 Configuration

### Mode Sandbox
**Fichier:** `backend/config/sandbox-config.php`

```php
define('SANDBOX_MODE', true);  // true = actif, false = désactivé
```

### Réseaux Autorisés
```php
'allowed_networks' => [
    '127.0.0.1',        // localhost
    '192.168.1.0/24',   // Sous-réseau local (A ADAPTER)
],
```

### Délai d'Expiration
```php
'expiration' => [
    'minutes' => 30,    // 30 minutes en test
    'seconds' => 1800,
],
```

### Logging
```php
'logging' => [
    'enabled' => true,
    'path' => PROJECT_ROOT . '/tmp/sandbox-logs',
    'level' => 'debug',  // debug, info, warning, error
],
```

---

## 📊 Réponses Exemples

### Créer un Paiement
```json
{
  "status": "success",
  "payment_ref": "TEST-PAY-2026-ABC123",
  "confirmation_code": "TEST-A1B2-C3D4-E5F6",
  "amount": 50000,
  "currency": "CDF",
  "expires_at": "2026-04-30 16:30:45"
}
```

### Confirmer un Paiement
```json
{
  "status": "success",
  "payment_ref": "TEST-PAY-2026-ABC123",
  "amount": 50000,
  "donor_name": "Jean Doe",
  "confirmed_at": "2026-04-30 16:30:45",
  "message": "Paiement confirmé avec succès ✅"
}
```

### Vérifier le Statut
```json
{
  "status": "success",
  "payment_ref": "TEST-PAY-2026-ABC123",
  "current_status": "confirmed",
  "amount": 50000,
  "type": "tithe",
  "confirmed_at": "2026-04-30 16:30:45"
}
```

---

## 🎯 Cas d'Usage

### Développement Frontend
1. Créer paiement via `/test-create`
2. Obtenir `payment_ref` et `confirmation_code`
3. Afficher dans le frontend
4. Utilisateur confirme → Call `/test-confirm`
5. Vérifier statut → Call `/test-status`

### Tests Automatisés
```bash
# Générer 10 paiements
curl -X POST .../generate-test-data -d '{"count":10}'

# Simuler workflow complet
curl -X POST .../test-simulate -d '{"delay":1}'

# Réinitialiser après tests
curl -X POST .../reset-all -d '{"confirm":true}'
```

### Debugging
1. Consultez les logs: `tmp/sandbox-logs/sandbox-YYYY-MM-DD.log`
2. Vérifiez l'IP: `curl .../status` → `client_ip`
3. Testez manuellement via l'interface HTML

---

## ✅ Checklist d'Installation

- [ ] PHP 7.4+ installé
- [ ] Extensions PDO activées
- [ ] `backend/config/sandbox-config.php` créé
- [ ] `backend/api/controllers/PaymentSandboxController.php` créé
- [ ] `frontend/test-sandbox.html` créé
- [ ] `tmp/sandbox-logs/` répertoire accessible
- [ ] Table `payments` créée en BD
- [ ] `verify-sandbox.php` retourne 100%
- [ ] Serveur démarre sans erreur
- [ ] Interface HTML accessible

---

## 🆘 Dépannage

| Problème | Solution |
|----------|----------|
| 404 Not Found | Vérifier que PaymentSandboxController.php existe |
| "Port already in use" | Utiliser un autre port: `php -S localhost:8001` |
| "Access denied" | Vérifier votre IP dans allowed_networks |
| "Table not found" | Exécuter `database_payments_migration.sql` |
| Logs vides | Vérifier les permissions du dossier `tmp/sandbox-logs` |
| Pas de données | Vérifier que `SANDBOX_MODE = true` |

---

## 📚 Documentation

| Document | Contenu |
|----------|---------|
| `SANDBOX_PAYMENT_GUIDE.md` | Guide détaillé - Tous les endpoints, exemples cURL, JS, etc. |
| `QUICKSTART_SANDBOX.md` | Démarrage rapide - 3 étapes pour commencer |
| `LOCAL_PAYMENT_SYSTEM_README.md` | Système de paiement - Architecture, BD, services |
| `FRONTEND_INTEGRATION_GUIDE.md` | Intégration UI - HTML, CSS, JavaScript |
| `INSTALLATION_SANDBOX.md` | Ce fichier - Vue d'ensemble de l'installation |

---

## 🎓 Exercices de Test

### Exercice 1: Créer et Confirmer
```bash
# 1. Créer
curl -X POST localhost:8000/.../test-create -d '{"type":"tithe","amount":50000}'
# → Récupérer payment_ref et confirmation_code

# 2. Confirmer
curl -X POST localhost:8000/.../test-confirm -d '{"payment_ref":"...","confirmation_code":"..."}'

# 3. Vérifier
curl localhost:8000/.../test-status?ref=...
```

### Exercice 2: Workflow Complet
```bash
curl -X POST localhost:8000/.../test-simulate -d '{"delay":2}'
# → Voir toutes les étapes exécutées
```

### Exercice 3: Test de Charge
```bash
curl -X POST localhost:8000/.../generate-test-data -d '{"count":100}'
# → Générer 100 paiements
```

---

## 🚀 Intégration Production

**⚠️ IMPORTANT**

Pour passer en production :

1. Désactiver le sandbox :
```php
define('SANDBOX_MODE', false);
```

2. Utiliser l'API de paiement réelle :
```php
// À la place de /payment_sandbox/*
// Utiliser /payment/* (endpoints de production)
```

3. Remplacer les données de test :
```php
// Supprimer les TEST-PAY-* de la BD
// Migrer vers les vrais paiements
```

---

## 📞 Support

### Vérification rapide
```bash
php verify-sandbox.php
```

### Tester directement
```bash
# Browser
http://localhost:8000/MALOTY/frontend/test-sandbox.html

# cURL
curl http://localhost:8000/MALOTY/backend/api/payment_sandbox/status
```

### Logs
```bash
cat tmp/sandbox-logs/sandbox-2026-04-30.log
tail -f tmp/sandbox-logs/sandbox-*.log
```

---

## 📝 Notes

- Les données de test sont automatiquement préfixées par `TEST-`
- Aucune API externe n'est requise
- Tous les paiements sont en BD locale (MySQL/PostgreSQL)
- Les logs sont horodatés et traçables
- Le mode sandbox est sécurisé (IP whitelist)
- Les workflows sont simulés (pas d'argent réel)

---

**Installation terminée! Vous êtes prêt à tester l'API de paiement en local! 🎉**

Commencez par:
```bash
php verify-sandbox.php
powershell -File start-sandbox.ps1
```

Puis visitez: `http://localhost:8000/MALOTY/frontend/test-sandbox.html`

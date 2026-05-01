# 📚 Index Sandbox Paiement MALOTY

## 🎯 Vous Venez d'Installer le Sandbox!

### Bienvenue dans votre système de paiement **100% LOCAL** 🎉

Ce dossier contient maintenant un **sandbox complet** pour tester l'API de paiement en réseau local, sans dépendances externes.

---

## 📖 Documentation - Oú Commencer?

### 1️⃣ **Je Veux Démarrer Vite** ⏱️
👉 Lisez: **[QUICKSTART_SANDBOX.md](QUICKSTART_SANDBOX.md)** (5 min)

- 3 étapes simples pour démarrer
- Commandes directes
- Tests immédiats

### 2️⃣ **Je Veux Comprendre Complètement** 📖
👉 Lisez: **[SANDBOX_PAYMENT_GUIDE.md](SANDBOX_PAYMENT_GUIDE.md)** (15 min)

- Guide détaillé de tous les endpoints
- Exemples cURL complets
- Intégration JavaScript
- Dépannage approfondi

### 3️⃣ **Je Veux Installer Correctement** 🔧
👉 Lisez: **[INSTALLATION_SANDBOX.md](INSTALLATION_SANDBOX.md)** (10 min)

- Vue d'ensemble de tous les fichiers créés
- Configuration détaillée
- Checklist d'installation
- Cas d'usage

### 4️⃣ **Je Veux Intégrer au Frontend** 💻
👉 Lisez: **[FRONTEND_INTEGRATION_GUIDE.md](FRONTEND_INTEGRATION_GUIDE.md)** (optionnel)

- Code HTML/CSS/JavaScript
- Exemples prêts à copier
- Design responsive

---

## 🚀 Démarrage Ultra-Rapide (30 secondes)

### Windows

```powershell
# 1. Ouvrir PowerShell dans le dossier MALOTY
cd C:\Users\HP\Documents\site\MALOTY

# 2. Démarrer le serveur
powershell -ExecutionPolicy Bypass -File start-sandbox.ps1

# 3. Ouvrir dans le navigateur
# http://localhost:8000/MALOTY/frontend/test-sandbox.html
```

### Mac/Linux

```bash
cd ~/Documents/site/MALOTY
export SANDBOX_MODE=true
php -S localhost:8000
# http://localhost:8000/MALOTY/frontend/test-sandbox.html
```

---

## 📁 Fichiers Créés/Modifiés

### Configuration
```
backend/
└── config/
    └── sandbox-config.php          ← Configuration du sandbox
```

### API
```
backend/
└── api/
    └── controllers/
        └── PaymentSandboxController.php  ← API Sandbox
```

### Frontend
```
frontend/
└── test-sandbox.html               ← Interface Web Interactive
```

### Scripts de Démarrage
```
├── start-sandbox.ps1               ← PowerShell (Windows)
├── start-sandbox.bat               ← Batch (Windows)
├── test-endpoints.ps1              ← Tests PowerShell
├── test-endpoints.bat              ← Tests Batch
└── verify-sandbox.php              ← Vérification
```

### Documentation
```
├── QUICKSTART_SANDBOX.md           ← Démarrage rapide (COMMENCEZ ICI!)
├── SANDBOX_PAYMENT_GUIDE.md        ← Guide complet
├── INSTALLATION_SANDBOX.md         ← Détails installation
├── LOCAL_PAYMENT_SYSTEM_README.md  ← Système paiement
├── FRONTEND_INTEGRATION_GUIDE.md   ← Intégration UI
└── README_SANDBOX.md               ← Ce fichier
```

---

## 🎯 Endpoints Disponibles

### Accès Direct
```
http://localhost:8000/MALOTY/backend/api/payment_sandbox/{action}
```

### Actions
| Action | Méthode | Description |
|--------|---------|-------------|
| `status` | GET | Vérifier le statut du sandbox |
| `test-create` | POST | Créer un paiement de test |
| `test-confirm` | POST | Confirmer un paiement |
| `test-status` | GET | Vérifier le statut |
| `test-list` | GET | Lister les paiements |
| `test-simulate` | POST | Simuler un workflow complet |
| `generate-test-data` | POST | Générer N paiements |
| `reset-all` | POST | Réinitialiser les données |

---

## ✨ Fonctionnalités

- ✅ **Création de paiement** - Avec références uniques
- ✅ **Confirmation** - Avec codes de validation
- ✅ **Tracking d'état** - pending → confirmed
- ✅ **Simulation de workflows** - Create → Verify → Confirm → Verify
- ✅ **Génération de données** - Batch de paiements de test
- ✅ **Logging complet** - Tous les événements tracés
- ✅ **Interface Web** - Dashboard interactif
- ✅ **Réseau local** - IP whitelist configurable
- ✅ **Zéro dépendance externe** - Fonctionne offline

---

## 🔍 Vérifier l'Installation

### Commande Simple
```bash
php verify-sandbox.php
```

Vous devez voir:
```
✅ TOUT EST CONFIGURÉ!
```

### Si Erreur?
Consultez la section dépannage du guide: [SANDBOX_PAYMENT_GUIDE.md#dépannage](SANDBOX_PAYMENT_GUIDE.md)

---

## 🧪 Tests Rapides

### Test 1: Status
```bash
curl http://localhost:8000/MALOTY/backend/api/payment_sandbox/status
```

### Test 2: Créer un Paiement
```bash
curl -X POST http://localhost:8000/MALOTY/backend/api/payment_sandbox/test-create \
  -H "Content-Type: application/json" \
  -d '{"type":"tithe","amount":50000}'
```

### Test 3: Interface Web
```
http://localhost:8000/MALOTY/frontend/test-sandbox.html
```

---

## 📊 Logs et Débogage

### Voir les Logs
```bash
# Dossier des logs
cat tmp/sandbox-logs/sandbox-2026-04-30.log

# Suivi en temps réel (Linux/Mac)
tail -f tmp/sandbox-logs/sandbox-*.log
```

### Niveau de Log
Modifiez dans `backend/config/sandbox-config.php`:
```php
'level' => 'debug',  // debug, info, warning, error
```

---

## 🔧 Configuration

### Changer le Mode
```php
// backend/config/sandbox-config.php
define('SANDBOX_MODE', true);  // true = sandbox, false = production
```

### Autoriser d'Autres Machines
```php
'allowed_networks' => [
    '127.0.0.1',       // localhost
    '192.168.1.0/24',  // Sous-réseau (adapter le vôtre)
],
```

### Changer le Port
```powershell
powershell -File start-sandbox.ps1 8001
```

---

## 📚 Documentation Complète

| Document | Pour Qui? | Durée |
|----------|-----------|-------|
| **QUICKSTART_SANDBOX.md** | Développeur impatient | 5 min |
| **SANDBOX_PAYMENT_GUIDE.md** | Tous les détails | 15 min |
| **INSTALLATION_SANDBOX.md** | Vue complète | 10 min |
| **FRONTEND_INTEGRATION_GUIDE.md** | Intégration UI | 20 min |
| **LOCAL_PAYMENT_SYSTEM_README.md** | Architecture paiement | 15 min |

---

## 🆘 Aide Rapide

### Problème: "Port déjà utilisé"
```powershell
powershell -File start-sandbox.ps1 8001
```

### Problème: "Access denied"
```bash
# Vérifier votre IP
ipconfig

# Ajouter à allowed_networks dans sandbox-config.php
```

### Problème: "Table not found"
```bash
# Créer la table
mysql -u user -p db < backend/database_payments_migration.sql
```

### Plus d'aide?
👉 Consultez [SANDBOX_PAYMENT_GUIDE.md#dépannage](SANDBOX_PAYMENT_GUIDE.md)

---

## ✅ Prochaines Étapes

### 1. Vérifier l'Installation
```bash
php verify-sandbox.php
```

### 2. Démarrer le Serveur
```bash
# Windows PowerShell
powershell -File start-sandbox.ps1

# Mac/Linux
php -S localhost:8000
```

### 3. Ouvrir l'Interface
```
http://localhost:8000/MALOTY/frontend/test-sandbox.html
```

### 4. Cliquer sur "Créer Paiement"
Et voir la magie opérer! ✨

---

## 📞 Support

- **Configuration?** → [INSTALLATION_SANDBOX.md](INSTALLATION_SANDBOX.md)
- **Erreur?** → [SANDBOX_PAYMENT_GUIDE.md#dépannage](SANDBOX_PAYMENT_GUIDE.md)
- **API?** → [SANDBOX_PAYMENT_GUIDE.md#api-endpoints](SANDBOX_PAYMENT_GUIDE.md)
- **Frontend?** → [FRONTEND_INTEGRATION_GUIDE.md](FRONTEND_INTEGRATION_GUIDE.md)

---

## 🎉 Vous Êtes Prêt!

Vous pouvez maintenant:
- ✅ Créer des paiements de test
- ✅ Les confirmer
- ✅ Vérifier les statuts
- ✅ Générer des données de test
- ✅ Simuler des workflows complets
- ✅ Intégrer au frontend
- ✅ Déboguer facilement

**Pas besoin d'API externes. Tout fonctionne en local!** 🚀

---

## 📝 Résumé

| Aspect | Statut |
|--------|--------|
| Installation | ✅ Complète |
| Documentation | ✅ Complète |
| API | ✅ 8 endpoints |
| Interface Web | ✅ Interactive |
| Tests | ✅ Scripts fournis |
| Logs | ✅ Complets |
| Support Réseau Local | ✅ Configurable |

**Bon développement! 💻**

# 🚀 Sandbox Paiement Local - Démarrage Rapide

**Répondez à votre question**: _"Comment utiliser sandbox pour faire l'API de paiement que je puisse payer avec nos réseaux locaux?"_

## ✨ Ce que vous avez maintenant

Un **système de paiement complet en mode TEST** qui fonctionne :
- ✅ **Sans API externes** (pas de Stripe, Paypal, etc.)
- ✅ **En réseau local** (127.0.0.1, 192.168.x.x)
- ✅ **Avec simulation complète** de workflows de paiement
- ✅ **Avec données de test** auto-générées
- ✅ **Loggé et débuggable** facilement

---

## 🎯 Démarrage en 3 étapes

### Étape 1️⃣ : Démarrer le Serveur Sandbox

**Windows (PowerShell) :**
```powershell
cd C:\Users\HP\Documents\site\MALOTY
powershell -ExecutionPolicy Bypass -File start-sandbox.ps1
```

**Windows (CMD) :**
```cmd
cd C:\Users\HP\Documents\site\MALOTY
start-sandbox.bat
```

**Mac/Linux :**
```bash
cd ~/Documents/site/MALOTY
php -S localhost:8000
export SANDBOX_MODE=true
```

Vous devez voir :
```
🚀 DÉMARRAGE DU SERVEUR
📡 Serveur: http://localhost:8000
🧪 Tests: http://localhost:8000/MALOTY/frontend/test-sandbox.html
```

### Étape 2️⃣ : Vérifier l'Accès

Ouvrez dans votre navigateur :
```
http://localhost:8000/MALOTY/backend/api/payment_sandbox/status
```

Vous devez voir :
```json
{
  "status": "success",
  "sandbox_mode": true,
  "local_network": true,
  "client_ip": "127.0.0.1"
}
```

### Étape 3️⃣ : Tester l'Interface

Ouvrez :
```
http://localhost:8000/MALOTY/frontend/test-sandbox.html
```

Cliquez sur les boutons pour tester !

---

## 📡 Endpoints API

### 1. Créer un Paiement (POST)

```bash
curl -X POST http://localhost:8000/MALOTY/backend/api/payment_sandbox/test-create \
  -H "Content-Type: application/json" \
  -d '{
    "type": "tithe",
    "amount": 50000,
    "donor_name": "Jean"
  }'
```

**Réponse:**
```json
{
  "status": "success",
  "payment_ref": "TEST-PAY-2026-XYZ123",
  "confirmation_code": "TEST-A1B2-C3D4-E5F6"
}
```

### 2. Confirmer un Paiement (POST)

```bash
curl -X POST http://localhost:8000/MALOTY/backend/api/payment_sandbox/test-confirm \
  -H "Content-Type: application/json" \
  -d '{
    "payment_ref": "TEST-PAY-2026-XYZ123",
    "confirmation_code": "TEST-A1B2-C3D4-E5F6"
  }'
```

### 3. Vérifier le Statut (GET)

```bash
curl http://localhost:8000/MALOTY/backend/api/payment_sandbox/test-status?ref=TEST-PAY-2026-XYZ123
```

### 4. Lister les Paiements (GET)

```bash
curl http://localhost:8000/MALOTY/backend/api/payment_sandbox/test-list
```

### 5. Générer des Données de Test (POST)

```bash
curl -X POST http://localhost:8000/MALOTY/backend/api/payment_sandbox/generate-test-data \
  -H "Content-Type: application/json" \
  -d '{"count": 10}'
```

### 6. Simuler un Workflow Complet (POST)

```bash
curl -X POST http://localhost:8000/MALOTY/backend/api/payment_sandbox/test-simulate \
  -H "Content-Type: application/json" \
  -d '{"delay": 1}'
```

### 7. Réinitialiser Toutes les Données (POST)

```bash
curl -X POST http://localhost:8000/MALOTY/backend/api/payment_sandbox/reset-all \
  -H "Content-Type: application/json" \
  -d '{"confirm": true}'
```

---

## 🧑‍💻 Intégration Frontend

```javascript
// Créer un paiement
fetch('/MALOTY/backend/api/payment_sandbox/test-create', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        type: 'tithe',
        amount: 50000,
        donor_name: 'Jean'
    })
})
.then(res => res.json())
.then(data => {
    console.log('Paiement créé:', data.payment_ref);
    console.log('Code:', data.confirmation_code);
})
.catch(error => console.error('Erreur:', error));
```

---

## 🔐 Configuration Réseau Local

### Autoriser d'autres machines du réseau

Modifiez `backend/config/sandbox-config.php` :

```php
'allowed_networks' => [
    '127.0.0.1',         // localhost
    '192.168.1.0/24',    // Votre sous-réseau (A ADAPTER)
    '192.168.0.0/24',    // Ou celui-ci
],
```

Trouvez votre sous-réseau :
```powershell
# Windows
ipconfig

# Linux/Mac
ifconfig
```

Cherchez `IPv4 Address` (par ex: `192.168.1.50`)

### Accéder depuis une autre machine

```bash
# Depuis une autre machine du réseau
curl http://192.168.1.100:8000/MALOTY/backend/api/payment_sandbox/status
```

---

## 📊 Voir les Logs

Les logs de test se trouvent dans :
```
tmp/sandbox-logs/sandbox-YYYY-MM-DD.log
```

Exemple :
```bash
cat tmp/sandbox-logs/sandbox-2026-04-30.log
```

---

## ✅ Checklist

Cochez au fur et à mesure :

- [ ] Serveur démarré sur `localhost:8000`
- [ ] Endpoint status retourne `"sandbox_mode": true`
- [ ] Interface HTML accessible
- [ ] Créer un paiement ✅
- [ ] Confirmer le paiement ✅
- [ ] Vérifier le statut ✅
- [ ] Générer 10 données de test
- [ ] Tester sur autre machine du réseau

---

## 🆘 Dépannage

### Erreur: "Port 8000 already in use"

**Solution:**
```powershell
# Utilisez un autre port
powershell -File start-sandbox.ps1 8001
# Puis accédez à: http://localhost:8001/...
```

### Erreur: "Access denied - Not on local network"

**Vérifiez:** Êtes-vous sur 127.0.0.1 ou 192.168.x.x ?

```bash
# Windows
ipconfig | findstr IPv4

# Linux/Mac
ifconfig | grep inet
```

Mettez à jour `allowed_networks` si nécessaire.

### API retourne 404

**Vérifiez:** Le fichier `PaymentSandboxController.php` existe dans `backend/api/controllers/`

```bash
dir backend/api/controllers/PaymentSandbox*
```

### Pas de données en BD

**Créez la table:**
```bash
# Allez à http://localhost:8000/MALOTY/test_payment_system.php
# Ou exécutez en MySQL:
mysql -u user -p database < backend/database_payments_migration.sql
```

---

## 🎓 Cas d'Usage

### Scenario 1️⃣ : Tester la Création de Paiement

```bash
# 1. Créer
curl -X POST http://localhost:8000/MALOTY/backend/api/payment_sandbox/test-create

# 2. Voir la réponse (payment_ref, confirmation_code)
# 3. Vérifier le statut : pending
curl http://localhost:8000/MALOTY/backend/api/payment_sandbox/test-status?ref=...
```

### Scenario 2️⃣ : Workflow Complet (Create → Confirm)

```bash
# Crée → Attend → Confirme → Vérifie
curl -X POST http://localhost:8000/MALOTY/backend/api/payment_sandbox/test-simulate
```

### Scenario 3️⃣ : Tester le Frontend

1. Ouvrez `http://localhost:8000/MALOTY/frontend/test-sandbox.html`
2. Créez un paiement
3. Confirmez-le
4. Vérifiez la base de données

---

## 📚 Documentation Complète

Pour plus de détails, consultez :
- [SANDBOX_PAYMENT_GUIDE.md](SANDBOX_PAYMENT_GUIDE.md) - Guide détaillé
- [LOCAL_PAYMENT_SYSTEM_README.md](LOCAL_PAYMENT_SYSTEM_README.md) - Système de paiement
- [FRONTEND_INTEGRATION_GUIDE.md](FRONTEND_INTEGRATION_GUIDE.md) - Intégration UI

---

## 🚀 Prêt à partir !

**Commencez maintenant:**

```powershell
# 1. Démarrer le serveur
start-sandbox.ps1

# 2. Dans un nouveau terminal
start http://localhost:8000/MALOTY/frontend/test-sandbox.html

# 3. Tester!
```

---

**Questions? Problèmes? Consultez SANDBOX_PAYMENT_GUIDE.md pour plus d'infos !**

Bon test! 🎉

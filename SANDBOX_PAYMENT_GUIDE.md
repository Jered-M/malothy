# 🧪 Guide Sandbox - API de Paiement Locaux

## 🎯 Qu'est-ce que le Sandbox ?

Le **Sandbox** est un environnement de test isolé qui vous permet de :
- ✅ Développer l'API de paiement sur votre réseau local
- ✅ Tester les workflows sans API externes
- ✅ Générer des données de test facilement
- ✅ Simuler des paiements complets
- ✅ Déboguer l'intégration frontend

## 🚀 Démarrage Rapide

### 1️⃣ Activer le Mode Sandbox

**Option A : Via variable d'environnement** (Windows - PowerShell)

```powershell
# Ajouter au fichier .env ou dans votre script de démarrage
$env:SANDBOX_MODE = 'true'
php -S localhost:8000
```

**Option B : Directement dans le code** 

Modifiez `backend/config/sandbox-config.php` :

```php
define('SANDBOX_MODE', true);  // true = sandbox actif, false = production
```

### 2️⃣ Vérifier l'Accès

Visitez votre endpoint de statut :

```bash
curl http://localhost:8000/backend/api/payment_sandbox/status
```

**Réponse réussie :**

```json
{
  "status": "success",
  "sandbox_mode": true,
  "local_network": true,
  "client_ip": "127.0.0.1",
  "config": {
    "mode": "test",
    "logging": true,
    "test_mode_settings": { ... }
  }
}
```

## 📡 API Endpoints Sandbox

### 1. Créer un Paiement de Test

```bash
POST http://localhost:8000/backend/api/payment_sandbox/test-create
Content-Type: application/json

{
  "type": "tithe",
  "amount": 50000,
  "currency": "CDF",
  "donor_name": "Jean Doe (Test)",
  "donor_email": "jean@test.local"
}
```

**Réponse :**

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

### 2. Confirmer un Paiement

```bash
POST http://localhost:8000/backend/api/payment_sandbox/test-confirm
Content-Type: application/json

{
  "payment_ref": "TEST-PAY-2026-ABC123",
  "confirmation_code": "TEST-A1B2-C3D4-E5F6"
}
```

**Réponse :**

```json
{
  "status": "success",
  "payment_ref": "TEST-PAY-2026-ABC123",
  "amount": 50000,
  "donor_name": "Jean Doe (Test)",
  "message": "Paiement confirmé avec succès ✅"
}
```

### 3. Vérifier le Statut

```bash
GET http://localhost:8000/backend/api/payment_sandbox/test-status?ref=TEST-PAY-2026-ABC123
```

**Réponse :**

```json
{
  "status": "success",
  "payment_ref": "TEST-PAY-2026-ABC123",
  "current_status": "confirmed",
  "amount": 50000,
  "confirmed_at": "2026-04-30 16:30:45"
}
```

### 4. Générer des Données de Test

Créer automatiquement 10 paiements de test :

```bash
POST http://localhost:8000/backend/api/payment_sandbox/generate-test-data
Content-Type: application/json

{
  "count": 10
}
```

**Réponse :**

```json
{
  "status": "success",
  "generated": 10,
  "total": 10,
  "results": [
    {
      "success": true,
      "payment_ref": "TEST-PAY-2026-XYZ789",
      "confirmation_code": "TEST-X1Y2-Z3A4-B5C6"
    },
    ...
  ]
}
```

### 5. Simuler un Workflow Complet

Crée → Vérification → Confirmation → Vérification finale :

```bash
POST http://localhost:8000/backend/api/payment_sandbox/test-simulate
Content-Type: application/json

{
  "delay": 2
}
```

**Réponse :**

```json
{
  "step_1_create": "Création d'un paiement de test...",
  "step_1_result": { ... },
  "step_2_check_pending": "Vérification du statut (pending)...",
  "step_2_result": { ... },
  "step_3_confirm": "Confirmation du paiement...",
  "step_3_result": { ... },
  "step_4_check_confirmed": "Vérification du statut final (confirmed)...",
  "step_4_result": { ... },
  "summary": {
    "status": "success",
    "message": "Workflow de test complet réussi ✅",
    "payment_ref": "TEST-PAY-2026-ABC123"
  }
}
```

### 6. Lister les Paiements de Test

```bash
GET http://localhost:8000/backend/api/payment_sandbox/test-list
```

**Réponse :**

```json
{
  "status": "success",
  "total": 15,
  "payments": [
    {
      "id": 1,
      "payment_ref": "TEST-PAY-2026-ABC123",
      "confirmation_code": "TEST-A1B2-C3D4-E5F6",
      "type": "tithe",
      "amount": 50000,
      "currency": "CDF",
      "status": "confirmed",
      "created_at": "2026-04-30 15:30:45"
    },
    ...
  ]
}
```

### 7. Réinitialiser Toutes les Données

⚠️ **Attention : Cela supprime tous les paiements de test !**

```bash
POST http://localhost:8000/backend/api/payment_sandbox/reset-all
Content-Type: application/json

{
  "confirm": true
}
```

**Réponse :**

```json
{
  "status": "success",
  "deleted": 15,
  "message": "15 paiements de test supprimés"
}
```

## 🛠️ Tests avec cURL

### Créer un paiement + Confirmer (complet)

```bash
# 1. Créer
$result = curl -s -X POST http://localhost:8000/backend/api/payment_sandbox/test-create \
  -H "Content-Type: application/json" \
  -d '{
    "type": "offering",
    "amount": 25000,
    "donor_name": "Alice Test"
  }'

echo $result | jq '.'

# 2. Extraire les données
# (À adapter selon votre système)

# 3. Confirmer
curl -X POST http://localhost:8000/backend/api/payment_sandbox/test-confirm \
  -H "Content-Type: application/json" \
  -d '{
    "payment_ref": "TEST-PAY-2026-...",
    "confirmation_code": "TEST-..."
  }'
```

## 🧑‍💻 Intégration Frontend (JavaScript)

```javascript
// Créer un paiement de test
async function testPayment() {
  try {
    // 1. Créer
    const createRes = await fetch('/backend/api/payment_sandbox/test-create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        type: 'tithe',
        amount: 50000,
        donor_name: 'Test User',
        donor_email: 'test@local.dev'
      })
    });

    const createData = await createRes.json();
    console.log('Paiement créé:', createData);

    // 2. Afficher les détails
    alert(`
      Paiement créé ! 
      Référence: ${createData.payment_ref}
      Code: ${createData.confirmation_code}
    `);

    // 3. Confirmer après 2 secondes
    setTimeout(async () => {
      const confirmRes = await fetch('/backend/api/payment_sandbox/test-confirm', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          payment_ref: createData.payment_ref,
          confirmation_code: createData.confirmation_code
        })
      });

      const confirmData = await confirmRes.json();
      console.log('Paiement confirmé:', confirmData);
      alert('Paiement confirmé avec succès! ✅');
    }, 2000);

  } catch (error) {
    console.error('Erreur:', error);
    alert('Erreur lors du test: ' + error.message);
  }
}

// Utiliser
testPayment();
```

## 📊 Affichage des Résultats (HTML)

```html
<!DOCTYPE html>
<html>
<head>
  <title>Test Paiement Sandbox</title>
  <style>
    body { font-family: Arial; max-width: 1200px; margin: 40px; }
    .button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    .result { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .success { border-left: 4px solid green; }
    .error { border-left: 4px solid red; }
    pre { background: #222; color: #0f0; padding: 10px; overflow-x: auto; }
  </style>
</head>
<body>
  <h1>🧪 Test API Sandbox de Paiement</h1>
  
  <div>
    <button class="button" onclick="testCreate()">✨ Créer Paiement</button>
    <button class="button" onclick="testWorkflow()">🔄 Workflow Complet</button>
    <button class="button" onclick="testList()">📋 Lister Paiements</button>
    <button class="button" onclick="testGenerate()">🎲 Générer 10 Tests</button>
  </div>

  <div id="result"></div>

  <script>
    async function makeRequest(endpoint, method = 'GET', body = null) {
      const opts = { method, headers: { 'Content-Type': 'application/json' } };
      if (body) opts.body = JSON.stringify(body);
      
      const res = await fetch(`/backend/api/payment_sandbox/${endpoint}`, opts);
      return res.json();
    }

    function displayResult(data, status = 'success') {
      const resultDiv = document.getElementById('result');
      resultDiv.innerHTML = `
        <div class="result ${status}">
          <pre>${JSON.stringify(data, null, 2)}</pre>
        </div>
      `;
    }

    async function testCreate() {
      displayResult({ loading: true });
      const data = await makeRequest('test-create', 'POST', {
        type: 'tithe',
        amount: 50000
      });
      displayResult(data, data.status);
    }

    async function testWorkflow() {
      displayResult({ loading: true });
      const data = await makeRequest('test-simulate', 'POST', { delay: 1 });
      displayResult(data, data.summary?.status);
    }

    async function testList() {
      const data = await makeRequest('test-list');
      displayResult(data);
    }

    async function testGenerate() {
      displayResult({ loading: true });
      const data = await makeRequest('generate-test-data', 'POST', { count: 10 });
      displayResult(data);
    }
  </script>
</body>
</html>
```

Accédez à : `http://localhost:8000/MALOTY/frontend/test-sandbox.html`

## 🔍 Logs de Sandbox

Les logs sont enregistrés dans : `tmp/sandbox-logs/`

```bash
# Voir les logs du jour
cat tmp/sandbox-logs/sandbox-2026-04-30.log

# Niveau de log : debug, info, warning, error
```

## 🛡️ Configuration de Sécurité

### Réseau Local Autorisé

Modifiez `backend/config/sandbox-config.php` pour autoriser d'autres machines :

```php
'allowed_networks' => [
    '127.0.0.1',        // localhost
    '192.168.1.0/24',   // Votre sous-réseau (adapter)
    '192.168.0.0/24',   // Autre sous-réseau
],
```

### Désactiver Sandbox en Production

```php
// JAMAIS en production !
define('SANDBOX_MODE', false);

// Ou avec une variable d'environnement
define('SANDBOX_MODE', getenv('SANDBOX_MODE') ?: false);
```

## ✅ Checklist de Test

- [ ] Mode sandbox activé
- [ ] Accès réseau local confirmé
- [ ] Créer un paiement de test
- [ ] Confirmer le paiement
- [ ] Vérifier le statut
- [ ] Lister les paiements
- [ ] Générer 10 paiements
- [ ] Simuler workflow complet
- [ ] Intégrer au frontend
- [ ] Tester sur autre machine du réseau

## 🆘 Dépannage

### Erreur : "Access denied - Not on local network"

**Solution :** Vérifiez votre IP et mettez à jour `allowed_networks`

```bash
# Voir votre IP
ipconfig getifaddr en0  # Mac
ipconfig              # Windows
```

### Les paiements ne s'enregistrent pas

Vérifiez que la table `payments` existe :

```bash
# Tester
curl http://localhost:8000/MALOTY/test_payment_system.php
```

### Pas d'accès au endpoint

Assurez-vous que le routeur API reconnaît le contrôleur :

```bash
# Test diagnostic
curl http://localhost:8000/MALOTY/backend/api/debug
```

---

**Prêt à tester votre API de paiement ! 🚀**

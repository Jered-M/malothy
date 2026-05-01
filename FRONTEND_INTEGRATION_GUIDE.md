# 📱 Guide d'Intégration Frontend - Système de Paiement Local

## Flux utilisateur

```
┌─────────────────────────────────────────────────────────────┐
│ 1️⃣ DONATEUR remplit le formulaire (Montant + Infos)        │
└────────────────┬────────────────────────────────────────────┘
                 │ POST /api/finance/public_tithe
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ 2️⃣ RÉPONSE AVEC RÉFÉRENCE + CODE (afficher à l'écran)     │
│   • Référence: PAY-2026-AB12C                              │
│   • Code: A1B2-C3D4-E5F6                                   │
│   • Valide jusqu'au: 13/05/2026                            │
└────────────────┬────────────────────────────────────────────┘
                 │ Imprimer /Screenshot
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ 3️⃣ DONATEUR effectue le paiement                           │
│   • Via Mobile Money (M-Pesa, Airtel, Orange)             │
│   • Montre la référence à l'agent                          │
│   • Reçoit la confirmation de paiement                     │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────┐
│ 4️⃣ TRÉSORIER confirme le paiement                          │
│   • POST /api/payment/confirm                              │
│   • Avec: Référence + Code                                 │
│   • Paiement marqué comme confirmé ✅                      │
└─────────────────────────────────────────────────────────────┘
```

---

## 📝 Formulaires HTML

### Formulaire de Dîme (Tithe)

```html
<form id="tithe-form" class="bg-white p-6 rounded-lg shadow">
  <h2 class="text-2xl font-bold mb-4">Enregistrer une Dîme</h2>
  
  <div class="mb-4">
    <label for="donor-name" class="block font-semibold">Nom du Donateur</label>
    <input type="text" id="donor-name" name="donor_name" placeholder="Jean Doe" required 
           class="w-full p-2 border rounded">
  </div>

  <div class="mb-4">
    <label for="amount" class="block font-semibold">Montant</label>
    <input type="number" id="amount" name="amount" placeholder="50000" min="1000" required 
           class="w-full p-2 border rounded">
  </div>

  <div class="mb-4">
    <label for="currency" class="block font-semibold">Devise</label>
    <select id="currency" name="currency" class="w-full p-2 border rounded">
      <option value="CDF">CDF (Franc Congolais)</option>
      <option value="USD">USD ($)</option>
      <option value="EUR">EUR (€)</option>
    </select>
  </div>

  <div class="mb-4">
    <label for="donor-email" class="block font-semibold">Email (optionnel)</label>
    <input type="email" id="donor-email" name="donor_email" class="w-full p-2 border rounded">
  </div>

  <div class="mb-4">
    <label for="donor-phone" class="block font-semibold">Téléphone (optionnel)</label>
    <input type="tel" id="donor-phone" name="donor_phone" placeholder="+223xxxxxxx" 
           class="w-full p-2 border rounded">
  </div>

  <div class="mb-4">
    <label for="tithe-date" class="block font-semibold">Date de la Dîme</label>
    <input type="date" id="tithe-date" name="tithe_date" required 
           class="w-full p-2 border rounded"
           value="<? echo date('Y-m-d'); ?>">
  </div>

  <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded font-bold hover:bg-blue-700">
    Créer la Demande de Paiement
  </button>
</form>

<!-- Réponse affichée après soumission -->
<div id="payment-result" class="hidden bg-green-50 p-6 rounded-lg mt-6 border border-green-200">
  <h3 class="text-xl font-bold text-green-700 mb-4">✅ Demande créée!</h3>
  
  <div class="bg-white p-4 rounded mb-4">
    <p class="mb-2"><strong>Référence de Paiement:</strong></p>
    <p class="text-lg font-mono bg-gray-100 p-2 rounded break-all" id="payment-ref">PAY-2026-AB12C</p>
    <button class="text-sm text-blue-600 hover:underline mt-1" onclick="copyToClipboard('payment-ref')">
      📋 Copier
    </button>
  </div>

  <div class="bg-white p-4 rounded mb-4">
    <p class="mb-2"><strong>Code de Confirmation:</strong></p>
    <p class="text-lg font-mono bg-gray-100 p-2 rounded" id="payment-code">A1B2-C3D4-E5F6</p>
    <button class="text-sm text-blue-600 hover:underline mt-1" onclick="copyToClipboard('payment-code')">
      📋 Copier
    </button>
  </div>

  <div class="bg-yellow-50 p-4 rounded mb-4 border border-yellow-200">
    <p class="font-semibold mb-2">📋 Instructions:</p>
    <ol class="list-decimal list-inside space-y-1 text-sm">
      <li>Imprimez ou prenez une capture d'écran</li>
      <li>Effectuez votre paiement via Mobile Money</li>
      <li>Montrez votre référence à l'agent</li>
      <li>Communiquez le code au trésorier</li>
      <li>Votre paiement sera confirmé rapidement</li>
    </ol>
  </div>

  <p class="text-gray-600 text-sm">⏰ Valide jusqu'au: <span id="expires-at">13/05/2026</span></p>
</div>
```

### JavaScript pour soumission

```javascript
document.getElementById('tithe-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  
  try {
    const response = await fetch('/MALOTY/backend/api/index.php?controller=finance&action=public_tithe', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        type: 'tithe',
        amount: formData.get('amount'),
        currency: formData.get('currency'),
        donor_name: formData.get('donor_name'),
        donor_email: formData.get('donor_email'),
        donor_phone: formData.get('donor_phone'),
        tithe_date: formData.get('tithe_date'),
        comment: ''
      })
    });

    const data = await response.json();
    
    if (data.success && data.payment) {
      // Afficher les résultats
      document.getElementById('payment-ref').textContent = data.payment.reference;
      document.getElementById('payment-code').textContent = data.payment.confirmation_code;
      document.getElementById('expires-at').textContent = new Date(data.payment.expires_at).toLocaleDateString('fr-FR');
      
      // Masquer le formulaire, afficher le résultat
      document.getElementById('tithe-form').style.display = 'none';
      document.getElementById('payment-result').classList.remove('hidden');
      
      // Afficher les instructions
      console.log('Paiement créé:', data.payment);
    } else {
      alert('Erreur: ' + (data.message || 'Erreur inconnue'));
    }
  } catch (error) {
    alert('Erreur: ' + error.message);
  }
});

function copyToClipboard(elementId) {
  const text = document.getElementById(elementId).textContent;
  navigator.clipboard.writeText(text);
  alert('Copié: ' + text);
}
```

---

## 🧑‍💼 Interface Trésorier - Confirmation de Paiement

```html
<div class="bg-white p-6 rounded-lg shadow">
  <h2 class="text-2xl font-bold mb-4">Confirmer un Paiement</h2>
  
  <div class="mb-4">
    <label for="ref-input" class="block font-semibold">Référence de Paiement</label>
    <input type="text" id="ref-input" placeholder="PAY-2026-AB12C" 
           class="w-full p-2 border rounded font-mono">
  </div>

  <div class="mb-4">
    <label for="code-input" class="block font-semibold">Code de Confirmation</label>
    <input type="text" id="code-input" placeholder="A1B2-C3D4-E5F6" 
           class="w-full p-2 border rounded font-mono">
  </div>

  <button onclick="confirmPayment()" class="w-full bg-green-600 text-white py-2 rounded font-bold hover:bg-green-700">
    Confirmer le Paiement ✅
  </button>
</div>

<script>
async function confirmPayment() {
  const ref = document.getElementById('ref-input').value;
  const code = document.getElementById('code-input').value;

  if (!ref || !code) {
    alert('Veuillez remplir tous les champs');
    return;
  }

  try {
    const response = await fetch('/MALOTY/backend/api/index.php?controller=payment&action=confirm', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        payment_ref: ref,
        confirmation_code: code
      })
    });

    const data = await response.json();

    if (data.status === 'success') {
      alert('✅ Paiement confirmé!' + '\n\n' +
            'Montant: ' + data.amount + ' ' + data.currency + '\n' +
            'Donateur: ' + data.donor_name);
      
      // Effacer les champs
      document.getElementById('ref-input').value = '';
      document.getElementById('code-input').value = '';
    } else {
      alert('❌ Erreur: ' + data.message);
    }
  } catch (error) {
    alert('Erreur de connexion: ' + error.message);
  }
}
</script>
```

---

## 📊 Dashboard Trésorier

```html
<div class="bg-white p-6 rounded-lg shadow">
  <h2 class="text-2xl font-bold mb-4">Statistiques des Paiements</h2>
  
  <div id="stats-container" class="space-y-4">
    <!-- Sera rempli par JavaScript -->
  </div>
</div>

<script>
async function loadStats() {
  try {
    const response = await fetch('/MALOTY/backend/api/index.php?controller=payment&action=stats');
    const data = await response.json();

    if (data.status === 'success') {
      const container = document.getElementById('stats-container');
      container.innerHTML = '';

      let totalAmount = 0;

      data.stats.forEach(stat => {
        totalAmount += parseFloat(stat.total || 0);
        
        const html = `
          <div class="bg-blue-50 p-4 rounded border border-blue-200">
            <h3 class="font-semibold mb-2">${stat.type} - ${stat.status}</h3>
            <p>Paiements: <strong>${stat.count}</strong></p>
            <p>Total: <strong>${stat.total} CDF</strong></p>
            <p>Moyenne: <strong>${(stat.average || 0).toFixed(2)} CDF</strong></p>
          </div>
        `;
        container.innerHTML += html;
      });

      // Total général
      const totalHtml = `
        <div class="bg-green-50 p-4 rounded border border-green-200 text-lg font-bold">
          Total général: ${totalAmount} CDF
        </div>
      `;
      container.innerHTML += totalHtml;
    }
  } catch (error) {
    alert('Erreur: ' + error.message);
  }
}

// Charger au démarrage
loadStats();
</script>
```

---

## 🔔 Messages de statut

```javascript
// Afficher le statut d'paiement via l'URL
const urlParams = new URLSearchParams(window.location.search);
const status = urlParams.get('status');

if (status === 'success') {
  showAlert('✅ Votre demande de paiement a été créée avec succès!', 'success');
} else if (status === 'cancel') {
  showAlert('❌ Opération annulée', 'error');
}

function showAlert(message, type = 'info') {
  const colors = {
    success: 'bg-green-100 text-green-800 border-green-300',
    error: 'bg-red-100 text-red-800 border-red-300',
    info: 'bg-blue-100 text-blue-800 border-blue-300'
  };

  const alert = document.createElement('div');
  alert.className = `fixed top-4 right-4 p-4 rounded border ${colors[type]}`;
  alert.textContent = message;
  document.body.appendChild(alert);

  setTimeout(() => alert.remove(), 5000);
}
```

---

## ✨ Bonnes pratiques

### 1. **Validation côté client**
```javascript
// Valider avant d'envoyer
if (amount < 1000) {
  alert('Montant minimum: 1000 CDF');
  return;
}
```

### 2. **Affichage du code lisible**
```javascript
// Couper le code en blocs pour plus de lisibilité
const code = "A1B2C3D4E5F6";
const readable = code.match(/.{1,4}/g).join('-'); // A1B2-C3D4-E5F6
```

### 3. **QR Code pour la référence** (optionnel)
```html
<!-- Générer un QR code avec: https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=PAY-2026-AB12C -->
<img src="https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=PAY-2026-AB12C" alt="QR">
```

### 4. **Imprimer les détails**
```javascript
function printPaymentDetails(ref, code, amount) {
  const html = `
    <h2>REÇU DE DEMANDE DE PAIEMENT</h2>
    <p>Référence: ${ref}</p>
    <p>Code: ${code}</p>
    <p>Montant: ${amount} CDF</p>
    <p>Date: ${new Date().toLocaleDateString('fr-FR')}</p>
  `;
  
  const win = window.open();
  win.document.write(html);
  win.print();
}
```

---

## 🚀 Tests

### Test dans la console du navigateur
```javascript
// Créer un paiement de test
fetch('/MALOTY/backend/api/index.php?controller=finance&action=public_tithe', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    type: 'tithe',
    amount: 10000,
    currency: 'CDF',
    donor_name: 'Test',
    tithe_date: new Date().toISOString().split('T')[0]
  })
}).then(r => r.json()).then(d => console.log(d));
```

---

## 📱 Responsive Design

Assurez-vous que vos formulaires sont adaptatifs (mobile-first) :

```html
<style>
  @media (max-width: 640px) {
    .payment-form {
      padding: 1rem;
    }
    
    .payment-ref {
      font-size: 0.875rem;
      word-break: break-all;
    }
  }
</style>
```

---

**Prêt à intégrer dans votre frontend ? 🎉**

Suivez ce guide et vos utilisateurs pourront faire des paiements simplement! ✨

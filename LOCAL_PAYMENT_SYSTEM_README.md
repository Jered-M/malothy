# 💳 Système de Paiement Local MALOTY

## Vue d'ensemble

Système de paiement **indépendant** et **sans API externe** pour gérer les Dîmes, Offrandes et Dépôts.

### ✨ Caractéristiques

✅ **Aucune API externe requise** - Fonctionne avec votre BD uniquement  
✅ **Références de paiement uniques** - Format : `PAY-2026-ABC123`  
✅ **Codes de confirmation personnalisés** - Format : `A1B2-C3D4-E5F6`  
✅ **Historique complet** - Tous les paiements sont tracés  
✅ **Statistiques intégrées** - Rapports par type/période  
✅ **Parfait pour TFC** - Fonctionne sans infrastructure externe  

---

## 🚀 Installation

### 1. Créer la table de paiements

Exécutez le script de migration :

```bash
# Console MySQL
mysql -u votre_user -p votre_db < backend/database_payments_migration.sql
```

Ou manuellement :
1. Ouvrez phpMyAdmin
2. Accédez à votre BD MALOTY
3. Onglet SQL
4. Copiez le contenu de `database_payments_migration.sql`
5. Exécutez

### 2. Initialiser via API (optionnel)

```bash
POST http://localhost/MALOTY/backend/api/index.php?controller=payment&action=init
Authorization: Bearer <admin_token>
```

---

## 📡 API Endpoints

### 🔓 Endpoints Publics (Aucune authentification)

#### 1. **Créer une demande de paiement**

```http
POST /backend/api/index.php?controller=payment&action=create
Content-Type: application/json

{
  "type": "tithe",
  "amount": 50000,
  "currency": "CDF",
  "donor_name": "Jean Doe",
  "donor_email": "jean@example.com",
  "donor_phone": "+223123456789",
  "member_id": 5,
  "description": "Dîme du mois d'avril"
}
```

**Réponse (201):**
```json
{
  "status": "success",
  "payment_ref": "PAY-2026-AB12C",
  "confirmation_code": "A1B2-C3D4-E5F6",
  "amount": 50000,
  "currency": "CDF",
  "expires_at": "2026-05-13 15:30:45",
  "message": "Demande de paiement créée avec succès"
}
```

#### 2. **Confirmer un paiement**

```http
POST /backend/api/index.php?controller=payment&action=confirm
Content-Type: application/json

{
  "payment_ref": "PAY-2026-AB12C",
  "confirmation_code": "A1B2-C3D4-E5F6"
}
```

**Réponse (200):**
```json
{
  "status": "success",
  "payment_ref": "PAY-2026-AB12C",
  "amount": 50000,
  "currency": "CDF",
  "donor_name": "Jean Doe",
  "message": "Paiement confirmé avec succès ✅"
}
```

#### 3. **Vérifier le statut d'un paiement**

```http
GET /backend/api/index.php?controller=payment&action=status&ref=PAY-2026-AB12C
```

**Réponse (200):**
```json
{
  "status": "success",
  "payment_ref": "PAY-2026-AB12C",
  "payment_status": "confirmed",
  "amount": 50000,
  "currency": "CDF",
  "donor_name": "Jean Doe",
  "created_at": "2026-04-13 15:30:45",
  "confirmed_at": "2026-04-13 15:35:10",
  "expires_at": "2026-05-13 15:30:45"
}
```

### 🔒 Endpoints Protégés (Admin uniquement)

#### 4. **Lister les paiements**

```http
GET /backend/api/index.php?controller=payment&action=list&status=confirmed&type=tithe
Authorization: Bearer <admin_token>
```

**Paramètres optionnels:**
- `status` : confirmed, pending, cancelled, expired
- `type` : tithe, offering, deposit, other
- `member_id` : ID du membre
- `start_date` : YYYY-MM-DD
- `end_date` : YYYY-MM-DD

**Réponse:**
```json
{
  "status": "success",
  "payments": [
    {
      "id": 1,
      "payment_ref": "PAY-2026-AB12C",
      "confirmation_code": "A1B2-C3D4-E5F6",
      "type": "tithe",
      "amount": 50000,
      "currency": "CDF",
      "donor_name": "Jean Doe",
      "status": "confirmed",
      "created_at": "2026-04-13 15:30:45",
      "confirmed_at": "2026-04-13 15:35:10"
    }
  ],
  "total": 1
}
```

#### 5. **Statistiques des paiements**

```http
GET /backend/api/index.php?controller=payment&action=stats&start_date=2026-01-01&end_date=2026-04-13
Authorization: Bearer <admin_token>
```

**Réponse:**
```json
{
  "status": "success",
  "stats": [
    {
      "status": "confirmed",
      "type": "tithe",
      "count": 25,
      "total": 1250000,
      "average": 50000
    },
    {
      "status": "pending",
      "type": "offering",
      "count": 5,
      "total": 125000,
      "average": 25000
    }
  ]
}
```

---

## 💻 Utilisation côté Frontend

### Exemple JavaScript

```javascript
// 1. Créer une demande de paiement
async function createPayment() {
  const response = await fetch('/backend/api/index.php?controller=payment&action=create', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      type: 'tithe',
      amount: 50000,
      currency: 'CDF',
      donor_name: 'Jean Doe',
      donor_email: 'jean@example.com',
      donor_phone: '+223123456789'
    })
  });
  
  const data = await response.json();
  if (data.status === 'success') {
    console.log('✅ Paiement créé!');
    console.log('Référence:', data.payment_ref);
    console.log('Code:', data.confirmation_code);
    console.log('Expire:', data.expires_at);
    
    // Afficher à l'utilisateur pour confirmation
    displayPaymentInfo(data);
  }
}

// 2. Confirmer le paiement
async function confirmPayment(paymentRef, confirmationCode) {
  const response = await fetch('/backend/api/index.php?controller=payment&action=confirm', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      payment_ref: paymentRef,
      confirmation_code: confirmationCode
    })
  });
  
  const data = await response.json();
  if (data.status === 'success') {
    alert('✅ Paiement confirmé!');
  }
}

// 3. Vérifier le statut
async function checkPaymentStatus(paymentRef) {
  const response = await fetch(
    `/backend/api/index.php?controller=payment&action=status&ref=${paymentRef}`
  );
  
  const data = await response.json();
  console.log('Statut:', data.payment_status);
}
```

---

## 🧪 Flux de Paiement Complet

```
┌─────────────────────────────────────────────┐
│ 1. CRÉER (Donateur + Backend)               │
│  - POST /create                             │
│  - Reçoit: payment_ref + confirmation_code │
└──────────┬──────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────┐
│ 2. AFFICHER (Frontend)                      │
│  - Montrer la référence au donateur         │
│  - Demander le code de confirmation        │
│  (Le donateur appelle ou vire via MB)      │
└──────────┬──────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────┐
│ 3. CONFIRMER (Admin/Trésorier)              │
│  - POST /confirm avec ref + code           │
│  - Paiement marqué comme 'confirmed'       │
└──────────┬──────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────────┐
│ 4. ENREGISTRER (Trésorier)                  │
│  - Paiement apparaît dans les rapports     │
│  - Peut imprimer les reçus                 │
│  - Mis à jour dans les statistiques        │
└─────────────────────────────────────────────┘
```

---

## 📊 Schéma de la Base de Données

```sql
payments
├── id (INT) - Clé primaire
├── payment_ref (VARCHAR) - PAY-2026-ABC123
├── confirmation_code (VARCHAR) - A1B2-C3D4-E5F6
├── type (VARCHAR) - tithe|offering|deposit|other
├── amount (DECIMAL) - Montant
├── currency (VARCHAR) - CDF/XAF/USD
├── donor_name (VARCHAR) - Nom du donateur
├── donor_email (VARCHAR) - Email
├── donor_phone (VARCHAR) - Téléphone
├── member_id (INT FK) - Référence au membre
├── description (TEXT) - Détails
├── status (VARCHAR) - pending|confirmed|cancelled|expired
├── confirmation_method (VARCHAR) - code|sms|email|manual
├── confirmed_at (DATETIME) - Quand confirmé
├── confirmed_by (VARCHAR) - Qui a confirmé
├── created_at (TIMESTAMP) - Date création
├── expires_at (DATETIME) - Date expiration
└── notes (TEXT) - Notes
```

---

## 🔧 Configuration

### Variables d'environnement (.env.php)

```php
// Déjà configuré, pas besoin d'API externe!
// Le système fonctionne avec votre BD uniquement
defineFromEnv('PAYMENT_SYSTEM', 'local'); // local (défaut)
defineFromEnv('PAYMENT_EXPIRY_DAYS', 30);  // Jours avant expiration
```

---

## 🛠️ Gestion Avancée

### Exporter les paiements en CSV

```php
$service = new LocalPaymentService($db);
$result = $service->exportToCSV([
  'status' => 'confirmed',
  'start_date' => '2026-04-01',
  'end_date' => '2026-04-30'
]);

// $result['csv'] contient le CSV
header('Content-Type: text/csv');
echo $result['csv'];
```

### Récupérer les statistiques par mois

```php
$service = new LocalPaymentService($db);
$stats = $service->getPaymentStats([
  'start_date' => '2026-01-01',
  'end_date' => '2026-12-31'
]);

foreach ($stats['stats'] as $stat) {
  echo $stat['type'] . ": " . $stat['total'] . " " . "CDF";
}
```

---

## ✅ Avantages pour votre TFC

✅ **Pas de dépendance externe** - Fonctionne offline si besoin  
✅ **Données sous contrôle** - Tout dans votre BD  
✅ **Traçabilité complète** - Qui a confirmé, quand, comment  
✅ **Flexible** - Adaptable à vos besoins  
✅ **Gratuit** - Aucun coût de transaction  
✅ **Simple** - Code PHP pur, pas de framework lourd  

---

## 🐛 Dépannage

**Q: Le code de confirmation n'est pas accepté?**  
R: Vérifiez que les majuscules/minuscules correspondent. Les codes sont sensibles à la casse.

**Q: Comment tester sans avoir les tables?**  
R: Exécutez le script de migration d'abord.

**Q: Puis-je exporter les paiements confirmés?**  
R: Oui, via la méthode `exportToCSV()`.

---

## 📝 Notes de développement

- Les références sont générées avec `uniqid()` + année
- Les codes de confirmation sont en base 36 (0-9, A-Z)
- Les paiements expirent après 30 jours
- Tous les horodatages sont en UTC+1 (temps serveur)
- Les montants sont en DECIMAL pour éviter les erreurs d'arrondi

---

## 🔐 Sécurité

✅ Protection CSRF via `$_SERVER['REQUEST_METHOD']`  
✅ Validation des entrées  
✅ Requêtes paramétrées (PDO::prepare)  
✅ Codes générés aléatoirement  
✅ Références uniques en DB  

---

**Prêt à commencer ? 🚀**

1. Exécutez la migration SQL
2. Testez via l'API
3. Intégrez au frontend
4. Présentez à votre jury !

Bon courage pour votre TFC ! 🎓

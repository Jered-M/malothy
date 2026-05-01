# 🚀 Système de Paiement Local MALOTY - Guide de Démarrage Rapide

## ✅ Ce qui a été créé

### 📦 Fichiers nouveaux

| Fichier | Description |
|---------|-------------|
| `backend/api/services/LocalPaymentService.php` | Service de gestion des paiements (CORE) |
| `backend/api/controllers/PaymentAPIController.php` | API endpoints pour paiements |
| `database_payments_migration.sql` | Script pour créer la table |
| `test_payment_system.php` | Script de test automatisé |
| `SETUP_PAYMENT_SYSTEM.sh` | Script d'installation (Linux/Mac) |
| `LOCAL_PAYMENT_SYSTEM_README.md` | Documentation complète |
| `FRONTEND_INTEGRATION_GUIDE.md` | Guide pour le frontend |

### 🔄 Fichiers modifiés

| Fichier | Changement |
|---------|-----------|
| `backend/api/controllers/FinanceController.php` | Intégration du LocalPaymentService pour les dons publics |
| `backend/.env.php` | Configuration mise à jour pour le mode TEST |

---

## ⚡ Installation en 3 étapes

### 1️⃣ Créer la table de paiements

**Option A: phpMyAdmin**
1. Ouvrez `phpMyAdmin`
2. Sélectionnez votre BD `maloty`
3. Allez dans l'onglet **SQL**
4. Copiez le contenu de `database_payments_migration.sql`
5. Cliquez sur **Exécuter**

**Option B: Ligne de commande**
```bash
mysql -u votre_user -p votre_db < backend/database_payments_migration.sql
```

**Option C: PHP (via navigateur)**
```php
// Accédez à: http://localhost/MALOTY/test_payment_system.php
// Le script sauvegardera qu'il a créé la table
```

### 2️⃣ Tester le système

```bash
# Terminal (PowerShell/Windows)
cd C:\Users\HP\Documents\site\MALOTY
php test_payment_system.php
```

Vous devez voir : `TOUS LES TESTS RÉUSSIS! ✨`

### 3️⃣ Intégrer au frontend

Voir `FRONTEND_INTEGRATION_GUIDE.md` pour les exemples HTML/JS.

---

## 📡 API Endpoints

### Créer un paiement
```bash
curl -X POST http://localhost/MALOTY/backend/api/index.php?controller=payment&action=create \
  -H "Content-Type: application/json" \
  -d '{
    "type": "tithe",
    "amount": 50000,
    "currency": "CDF",
    "donor_name": "Jean Doe",
    "donor_email": "jean@test.com"
  }'
```

**Réponse:**
```json
{
  "status": "success",
  "payment_ref": "PAY-2026-AB12C",
  "confirmation_code": "A1B2-C3D4-E5F6",
  "amount": 50000,
  "currency": "CDF",
  "expires_at": "2026-05-13 15:30:45"
}
```

### Confirmer un paiement
```bash
curl -X POST http://localhost/MALOTY/backend/api/index.php?controller=payment&action=confirm \
  -H "Content-Type: application/json" \
  -d '{
    "payment_ref": "PAY-2026-AB12C",
    "confirmation_code": "A1B2-C3D4-E5F6"
  }'
```

### Vérifier le statut
```bash
curl http://localhost/MALOTY/backend/api/index.php?controller=payment&action=status&ref=PAY-2026-AB12C
```

### Lister les paiements (Admin)
```bash
curl -H "Authorization: Bearer VOTRE_TOKEN" \
  http://localhost/MALOTY/backend/api/index.php?controller=payment&action=list&status=confirmed
```

### Statistiques (Admin)
```bash
curl -H "Authorization: Bearer VOTRE_TOKEN" \
  http://localhost/MALOTY/backend/api/index.php?controller=payment&action=stats
```

---

## 🧪 Exemple complet

### 1. Créer une demande
```javascript
// Le donateur submitonne le formulaire
const response = await fetch('/MALOTY/backend/api/index.php?controller=finance&action=public_tithe', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    type: 'tithe',
    amount: 50000,
    currency: 'CDF',
    donor_name: 'Jean Doe',
    tithe_date: '2026-04-13',
    comment: 'Dîme d\'avril'
  })
});

const data = await response.json();
// data.payment.reference: "PAY-2026-AB12C"
// data.payment.confirmation_code: "A1B2-C3D4-E5F6"
```

### 2. Afficher les infos
```javascript
console.log("✅ Référence:", data.payment.reference);
console.log("✅ Code:", data.payment.confirmation_code);
console.log("⏰ Valide jusqu'à:", data.payment.expires_at);

// Afficher à l'écran pour le donateur...
```

### 3. Donateur effectue le virement
- Via M-Pesa, Airtel Money, Orange Money, etc.
- Montre la référence à l'agent
- Effectue le virement
- Reçoit la confirmation

### 4. Trésorier confirme
```javascript
// Admin reçoit la référence + code du donateur
const confirmed = await fetch('/MALOTY/backend/api/index.php?controller=payment&action=confirm', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    payment_ref: 'PAY-2026-AB12C',
    confirmation_code: 'A1B2-C3D4-E5F6'
  })
});

const result = await confirmed.json();
// result.status: "success"
// Le paiement est marqué comme confirmé! ✅
```

---

## 🔒 Sécurité

✅ **Codes générés aléatoirement** - Impossible à prédire  
✅ **Références uniques** - Une seule par demande  
✅ **Expiration** - 30 jours par défaut  
✅ **Validation côté serveur** - Codes doivent correspondre  
✅ **Requêtes paramétrées** - Protection SQL injection  

---

## 📊 Structure BD

```sql
Table: payments
├── id (clé primaire)
├── payment_ref (PAY-2026-AB12C)
├── confirmation_code (A1B2-C3D4-E5F6)
├── type (tithe | offering | deposit | other)
├── amount (montant)
├── currency (CDF, USD, etc)
├── donor_name (nom du donateur)
├── donor_email (email)
├── donor_phone (téléphone)
├── member_id (FK vers members)
├── status (pending | confirmed | cancelled | expired)
├── confirmed_at (quand confirmé)
├── confirmed_by (qui a confirmé)
├── created_at (quand créé)
├── expires_at (expiration)
└── notes (commentaires)
```

---

## 🚨 Dépannage

### "Table not found"
→ Exécutez `database_payments_migration.sql`

### "Confirmation code invalid"
→ Les majuscules/minuscules doivent correspondre

### "Payment expired"
→ Augmentez PAYMENT_EXPIRY_DAYS dans `.env.php`

### Tests échouent
→ Vérifiez que `PDO` et votre BD sont accessibles

---

## 📚 Documentation complète

- **API Endpoints** → `LOCAL_PAYMENT_SYSTEM_README.md`
- **Frontend Integration** → `FRONTEND_INTEGRATION_GUIDE.md`
- **Code Source** → `backend/api/services/LocalPaymentService.php`

---

## 🎯 Cas d'usage

### ✅ Dîmes
```json
{
  "type": "tithe",
  "amount": 50000,
  "description": "Dîme du mois d'avril"
}
```

### ✅ Offrandes
```json
{
  "type": "offering",
  "amount": 25000,
  "description": "Offrande pour projet"
}
```

### ✅ Dépôts (Construction, etc)
```json
{
  "type": "deposit",
  "amount": 100000,
  "description": "Dépôt construction temple"
}
```

### ✅ Autres dons
```json
{
  "type": "other",
  "amount": 15000,
  "description": "Don pour les pauvres"
}
```

---

## 💡 Fonctionnalités avancées

### Export CSV
```php
$service->exportToCSV([
  'status' => 'confirmed',
  'start_date' => '2026-04-01',
  'end_date' => '2026-04-30'
]);
```

### Statistiques mensuelles
```php
$stats = $service->getPaymentStats([
  'start_date' => '2026-04-01',
  'end_date' => '2026-04-30'
]);
```

### Filtrer les paiements
```php
$payments = $service->listPayments([
  'status' => 'confirmed',
  'type' => 'tithe',
  'member_id' => 5,
  'start_date' => '2026-04-01'
]);
```

---

## ✨ Avantages pour votre TFC

✅ **Pas d'API externe** - Aucune clé requise  
✅ **Contrôle complet** - Tout dans votre BD  
✅ **Traçabilité** - Audit trail complet  
✅ **Flexible** - Adaptable à vos besoins  
✅ **Production-ready** - Code professionnel  
✅ **Documenté** - Guide complet inclus  
✅ **Testable** - Tests automatisés fournis  

---

## 🎓 Pour votre jury

**Montrer:**
1. ✅ Formulaire de don (public)
2. ✅ Génération référence + code
3. ✅ Confirmation par admin
4. ✅ Dashboard statistiques
5. ✅ Historique des paiements

**Expliquer:**
- Comment fonctionne le système (sans API externe)
- Sécurité (codes uniques, validation)
- Traçabilité (audit trail complet)
- Scalabilité (prêt pour production)

---

## 📞 Support

**Fichier de log:**
```php
// Les erreurs sont enregistrées dans la BD
// Voir la table 'payments' pour l'historique
```

**Test rapide:**
```bash
php test_payment_system.php
```

---

## 🎉 Vous êtes prêt!

1. ✅ Table créée
2. ✅ API fonctionnelle
3. ✅ Frontend prêt à intégrer
4. ✅ Tests passent

**Commencez à tester maintenant! 🚀**

```bash
# Dans votre navigateur:
http://localhost/MALOTY/test_payment_system.php
```

Bon courage pour votre TFC! 🎓

---

**Version: 1.0.0**  
**Date: 13 avril 2026**  
**Projet MALOTY - Système de Gestion d'Église**

# 🏗️ Architecture du Sandbox Paiement MALOTY

## Vue d'Ensemble du Système

```
┌──────────────────────────────────────────────────────────────────┐
│              🧪 SANDBOX PAYMENT API - MALOTY                    │
│                   (Système Local Isolé)                          │
└──────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                      🌐 CLIENT LAYER                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Frontend Web                    Browser                         │
│  (test-sandbox.html)            Curl/Postman                     │
│          │                            │                         │
│          └────────────────┬───────────┘                         │
│                           │                                      │
│                  HTTP Requests                                   │
│                    (JSON)                                        │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      🔗 API LAYER                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  PaymentSandboxController.php                                   │
│                                                                  │
│  ├─ POST   /test-create         ─► Créer paiement              │
│  ├─ POST   /test-confirm        ─► Confirmer paiement          │
│  ├─ GET    /test-status         ─► Vérifier statut             │
│  ├─ POST   /test-simulate       ─► Simuler workflow            │
│  ├─ GET    /test-list           ─► Lister paiements            │
│  ├─ POST   /generate-test-data  ─► Générer données             │
│  ├─ POST   /reset-all           ─► Réinitialiser               │
│  └─ GET    /status              ─► Info sandbox                │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                  🔐 SERVICE LAYER                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  LocalPaymentService.php                                        │
│                                                                  │
│  ├─ createPaymentRequest()                                      │
│  ├─ confirmPayment()                                            │
│  ├─ getPaymentStatus()                                          │
│  └─ listPayments()                                              │
│                                                                  │
│  Configuration: sandbox-config.php                              │
│  ├─ Mode (sandbox/production)                                   │
│  ├─ Réseaux locaux                                              │
│  ├─ Paramètres de test                                          │
│  └─ Logging                                                     │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                  💾 DATABASE LAYER                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  MySQL/PostgreSQL                                               │
│                                                                  │
│  Table: payments                                                │
│  ├─ id                                                          │
│  ├─ payment_ref      (TEST-PAY-2026-ABC123)                    │
│  ├─ confirmation_code (TEST-A1B2-C3D4-E5F6)                    │
│  ├─ type             (tithe, offering, donation, deposit)      │
│  ├─ amount                                                      │
│  ├─ currency         (CDF, USD, EUR)                           │
│  ├─ status           (pending, confirmed, expired)             │
│  ├─ created_at       (timestamp)                               │
│  ├─ confirmed_at     (timestamp)                               │
│  └─ expires_at       (timestamp)                               │
│                                                                  │
│  Table: logs (Optional)                                         │
│  ├─ timestamp                                                   │
│  ├─ level            (debug, info, warning, error)             │
│  └─ message                                                     │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

```

---

## 🔄 Workflow de Paiement

```
┌────────────────────────────────────────────────────────────────┐
│           LIFECYCLE D'UN PAIEMENT DE TEST                       │
└────────────────────────────────────────────────────────────────┘

1️⃣  CREATE (POST /test-create)
    ├─ Utilisateur crée une demande de paiement
    ├─ Système génère payment_ref unique (TEST-PAY-...)
    ├─ Système génère confirmation_code (TEST-...)
    ├─ État: PENDING
    ├─ Horodatage: created_at
    └─ Sauvegarde en BD

2️⃣  PENDING (GET /test-status)
    ├─ Paiement en attente de confirmation
    ├─ Utilisateur a reçu payment_ref + code
    ├─ Utilisateur a 30 minutes pour confirmer
    └─ État: PENDING

3️⃣  CONFIRM (POST /test-confirm)
    ├─ Utilisateur fournit payment_ref + confirmation_code
    ├─ Système valide le code
    ├─ État: CONFIRMED
    ├─ Horodatage: confirmed_at
    └─ Log enregistré

4️⃣  CONFIRMED (GET /test-status)
    ├─ Paiement confirmé avec succès
    ├─ Données complètes disponibles
    ├─ État: CONFIRMED
    └─ Archivé en BD

5️⃣  CLEANUP (POST /reset-all)
    ├─ Supprime tous les TEST-* de la BD
    ├─ Réinitialise pour nouveaux tests
    └─ À utiliser entre les test cycles

```

---

## 📊 Flux de Données

```
CLIENT (HTTP)
     │
     ▼
API Router (index.php)
     │
     ▼ (Reconnaît /payment_sandbox)
PaymentSandboxController
     │
     ├─► verifySandboxAccess()      ◄─── Vérifie IP locale
     │
     ├─► sandboxLog()                ◄─── Logging (debug)
     │
     ├─► generateTestPaymentData()   ◄─── Crée données mock
     │
     ▼
LocalPaymentService
     │
     ├─► generatePaymentRef()        (TEST-PAY-...)
     ├─► generateConfirmationCode()  (TEST-...)
     ├─► saveToDatabase()
     └─► getAllPayments()
     │
     ▼
Database
  │
  └─► Table: payments

Response JSON
     │
     ▼
CLIENT (Browser/Curl)
```

---

## 🔐 Sécurité Sandbox

```
REQUEST
  │
  ▼
┌─────────────────────────────────────┐
│  SANDBOX ACCESS VERIFICATION        │
├─────────────────────────────────────┤
│                                      │
│  1. Check SANDBOX_MODE = true?       │ ──► Non ─► 403 Error
│     │                                │
│     Yes ▼                            │
│                                      │
│  2. Check Client IP in Whitelist?    │ ──► Non ─► 403 Error
│     ├─ 127.0.0.1 (localhost)        │
│     ├─ 192.168.1.* (local network)   │
│     └─ gethostname()                 │
│     │                                │
│     Yes ▼                            │
│                                      │
│  3. Validate Request Body            │ ──► Invalid ─► 400 Error
│     ├─ JSON parsing OK?              │
│     └─ Required fields?              │
│     │                                │
│     Valid ▼                          │
│                                      │
│  4. Log Request                      │ ──► tmp/sandbox-logs/
│                                      │
│  ✅ ALLOW REQUEST                    │
│                                      │
└─────────────────────────────────────┘
```

---

## 📁 Structure des Fichiers

```
MALOTY/
├── backend/
│   ├── api/
│   │   ├── index.php                    (API Router)
│   │   └── controllers/
│   │       ├── PaymentAPIController.php (Production)
│   │       └── PaymentSandboxController.php ⭐ (NEW - Sandbox)
│   ├── config/
│   │   ├── api-config.php               (Config API)
│   │   ├── database.php                 (Config BD)
│   │   └── sandbox-config.php ⭐ (NEW - Sandbox)
│   └── services/
│       └── LocalPaymentService.php      (Core Service)
│
├── frontend/
│   ├── index.html
│   ├── test-sandbox.html ⭐ (NEW - Interface test)
│   └── ...
│
├── tmp/
│   └── sandbox-logs/ ⭐ (NEW - Logs sandbox)
│
├── start-sandbox.ps1 ⭐ (NEW - Start Script)
├── start-sandbox.bat ⭐ (NEW - Start Script)
├── test-endpoints.ps1 ⭐ (NEW - Test Script)
├── test-endpoints.bat ⭐ (NEW - Test Script)
├── verify-sandbox.php ⭐ (NEW - Verify)
│
├── QUICKSTART_SANDBOX.md ⭐ (NEW)
├── SANDBOX_PAYMENT_GUIDE.md ⭐ (NEW)
├── INSTALLATION_SANDBOX.md ⭐ (NEW)
└── README_SANDBOX.md ⭐ (NEW)

⭐ = Fichiers créés pour le Sandbox
```

---

## 🔄 Cycle de Développement

```
┌─────────────────────────────────────────┐
│  1. Démarrer Sandbox                    │
│     $ php -S localhost:8000             │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  2. Ouvrir Interface Web                 │
│     http://localhost:8000/.../...html   │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  3. Créer Paiement de Test              │
│     Cliquer "Créer Paiement"            │
│     Récupérer payment_ref + code        │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  4. Tester le Frontend                   │
│     Afficher payment_ref                 │
│     Permettre confirmation               │
│     Appeler /test-confirm                │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  5. Vérifier le Statut                   │
│     GET /test-status                    │
│     Voir: CONFIRMED                      │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  6. Examiner les Logs                    │
│     cat tmp/sandbox-logs/*.log           │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  7. Réinitialiser (Si besoin)            │
│     POST /reset-all {"confirm": true}   │
└─────────────────────────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  8. Répéter Tests                        │
│     Aller à l'étape 3                    │
└─────────────────────────────────────────┘
```

---

## 🎯 Caractéristiques

```
SANDBOX PAYMENT API
│
├─ 🏠 LOCAL
│  ├─ Aucune API externe
│  ├─ Aucune dépendance cloud
│  └─ Fonctionne offline
│
├─ 🔒 SÉCURISÉ
│  ├─ IP whitelist
│  ├─ Validation des données
│  └─ Logging complet
│
├─ 🧪 TESTABLE
│  ├─ Interface web
│  ├─ API cURL
│  ├─ Données mock
│  └─ Workflows simulés
│
├─ 📊 TRAÇABLE
│  ├─ Logging horodaté
│  ├─ Niveaux debug/info/warning/error
│  ├─ Stockage local
│  └─ Analysable
│
├─ 🚀 EXTENSIBLE
│  ├─ Architecture modulaire
│  ├─ Service layer
│  ├─ Configuration flexible
│  └─ Prêt pour production
│
└─ 📝 DOCUMENTÉ
   ├─ Guide complet
   ├─ Exemples code
   ├─ Dépannage
   └─ Cas d'usage
```

---

## 📈 Progression d'Apprentissage

```
Level 1: DÉBUTANT (5 min)
├─ Lire: QUICKSTART_SANDBOX.md
├─ Exécuter: start-sandbox.ps1
└─ Cliquer: test-sandbox.html

Level 2: INTERMÉDIAIRE (30 min)
├─ Lire: SANDBOX_PAYMENT_GUIDE.md
├─ Tester: cURL endpoints
└─ Examiner: Les logs

Level 3: AVANCÉ (1h)
├─ Lire: INSTALLATION_SANDBOX.md
├─ Modifier: sandbox-config.php
├─ Déboguer: Workflows complexes
└─ Intégrer: Frontend

Level 4: EXPERT (2h+)
├─ Modifier: PaymentSandboxController
├─ Ajouter: Endpoints personnalisés
├─ Créer: Cas de test
└─ Optimiser: Performance
```

---

## ✨ Résumé Visuel

```
╔════════════════════════════════════════════════════════════════╗
║                   🧪 SANDBOX PAYMENT API                      ║
║                                                                ║
║  ✅ Local Development       Sans dépendances externes         ║
║  ✅ Testing Framework       Interface web + CLI               ║
║  ✅ Data Generation         Paiements de test automatiques    ║
║  ✅ Workflow Simulation     Create → Confirm → Verify         ║
║  ✅ Logging & Debugging     Traçabilité complète              ║
║  ✅ Network Local           IP whitelist configurable         ║
║  ✅ Production Ready        Prêt pour migrer                   ║
║  ✅ Fully Documented        Documentation exhaustive          ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝

Démarrage: $ powershell -File start-sandbox.ps1
Web UI:    http://localhost:8000/MALOTY/frontend/test-sandbox.html
Logs:      cat tmp/sandbox-logs/sandbox-*.log
Docs:      README_SANDBOX.md

🚀 PRÊT À DÉVELOPPER!
```

---

**Votre système sandbox est 100% opérationnel!** 🎉

#!/bin/bash
# 🚀 Script de démarrage du système de paiement local MALOTY
# Ce script initialise tout ce qu'il faut pour commencer

echo "╔════════════════════════════════════════════════════════╗"
echo "║  MALOTY - Système de Paiement Local                    ║"
echo "║  Configuration automatique                             ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""

# 1. Vérifier que PHP est disponible
echo "[1/4] Vérification de l'environnement..."
if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé"
    exit 1
fi
echo "✅ PHP trouvé: $(php -v | head -n 1)"
echo ""

# 2. Créer la table
echo "[2/4] Initialisation de la base de données..."
mysql -u root -p < backend/database_payments_migration.sql 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✅ Table 'payments' créée/vérifiée"
else
    echo "⚠️  Impossible d'initialiser la BD automatiquement"
    echo "   Importez manuellement: backend/database_payments_migration.sql"
fi
echo ""

# 3. Vérifier les fichiers
echo "[3/4] Vérification des fichiers nécessaires..."
files=(
    "backend/api/services/LocalPaymentService.php"
    "backend/api/controllers/PaymentAPIController.php"
    "backend/api/controllers/FinanceController.php"
    "database_payments_migration.sql"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ $file (MANQUANT)"
    fi
done
echo ""

# 4. Afficher les prochaines étapes
echo "[4/4] Configuration terminée!"
echo ""
echo "╔════════════════════════════════════════════════════════╗"
echo "║  PROCHAINES ÉTAPES                                     ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""
echo "📚 Documentation complète:"
echo "   → Voir: LOCAL_PAYMENT_SYSTEM_README.md"
echo ""
echo "🧪 Test rapide:"
echo "   curl -X POST http://localhost/MALOTY/backend/api/index.php?controller=payment&action=create \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"type\":\"tithe\",\"amount\":50000,\"currency\":\"CDF\",\"donor_name\":\"Jean Doe\"}'"
echo ""
echo "💻 Endpoints disponibles:"
echo "   POST   /backend/api/index.php?controller=payment&action=create"
echo "   POST   /backend/api/index.php?controller=payment&action=confirm"
echo "   GET    /backend/api/index.php?controller=payment&action=status&ref=PAY-XXXX-XXXXX"
echo "   GET    /backend/api/index.php?controller=payment&action=list (Admin)"
echo "   GET    /backend/api/index.php?controller=payment&action=stats (Admin)"
echo ""
echo "✨ Vous êtes prêt à tester! 🎉"
echo ""

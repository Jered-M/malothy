<?php
/**
 * Service de Paiement Local - Système de paiement indépendant
 * 
 * Permet de gérer les paiements (Dîmes, Offrandes, etc.) sans API externe
 * Système de références + codes de confirmation personnalisés
 * 
 * Fonctionnalités :
 * - Génération de références de paiement uniques
 * - Codes de confirmation par SMS/Email
 * - Historique des transactions
 * - Suivi du statut des paiements
 * - Rappels de paiement en attente
 */

class LocalPaymentService {
    
    private $db;
    private $tableName = 'payments';

    public function __construct($database = null) {
        // Si pas de DB passée, utiliser la DB globale
        global $db;
        $this->db = $database ?? $db;
    }

    /**
     * Initialisez la table de paiement (migration)
     */
    public function initializeTable() {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'pgsql') {
            $sql = "
            CREATE TABLE IF NOT EXISTS {$this->tableName} (
                id SERIAL PRIMARY KEY,
                payment_ref VARCHAR(50) UNIQUE NOT NULL,
                confirmation_code VARCHAR(50) UNIQUE NOT NULL,
                type VARCHAR(50) NOT NULL,
                amount DECIMAL(10, 2) NOT NULL,
                currency VARCHAR(3) DEFAULT 'CDF',
                donor_name VARCHAR(255),
                donor_email VARCHAR(255),
                donor_phone VARCHAR(20),
                member_id INT,
                description TEXT,
                status VARCHAR(50) DEFAULT 'pending',
                confirmation_method VARCHAR(50) DEFAULT 'code',
                confirmed_at TIMESTAMP,
                confirmed_by VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP,
                notes TEXT,
                FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
            );
            CREATE INDEX IF NOT EXISTS idx_payments_ref ON {$this->tableName}(payment_ref);
            CREATE INDEX IF NOT EXISTS idx_payments_status ON {$this->tableName}(status);
            ";
        } else {
            $sql = "
            CREATE TABLE IF NOT EXISTS {$this->tableName} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                payment_ref VARCHAR(50) UNIQUE NOT NULL,
                confirmation_code VARCHAR(50) UNIQUE NOT NULL,
                type VARCHAR(50) NOT NULL,
                amount DECIMAL(10, 2) NOT NULL,
                currency VARCHAR(3) DEFAULT 'CDF',
                donor_name VARCHAR(255),
                donor_email VARCHAR(255),
                donor_phone VARCHAR(20),
                member_id INT,
                description TEXT,
                status VARCHAR(50) DEFAULT 'pending',
                confirmation_method VARCHAR(50) DEFAULT 'code',
                confirmed_at DATETIME,
                confirmed_by VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME,
                notes TEXT,
                INDEX (payment_ref),
                INDEX (status),
                FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
        }
        
        try {
            $this->db->exec($sql);
            return ['status' => 'success', 'message' => 'Table de paiement créée/vérifiée'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Créer une demande de paiement
     * 
     * @param array $data Données du paiement
     * @return array ['status' => 'success|error', 'payment_ref' => '...', 'confirmation_code' => '...']
     */
    public function createPaymentRequest($data) {
        try {
            // Validation
            if (empty($data['amount']) || !is_numeric($data['amount'])) {
                return ['status' => 'error', 'message' => 'Montant invalide'];
            }

            if (empty($data['type'])) {
                return ['status' => 'error', 'message' => 'Type de paiement requis'];
            }

            // Générer références
            $paymentRef = $this->generatePaymentReference();
            $confirmationCode = $this->generateConfirmationCode();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

            // Préparer l'insertion
            $stmt = $this->db->prepare("
                INSERT INTO {$this->tableName} (
                    payment_ref, confirmation_code, type, amount, currency, 
                    donor_name, donor_email, donor_phone, member_id, 
                    description, expires_at, status
                ) VALUES (
                    :ref, :code, :type, :amount, :currency,
                    :donor_name, :donor_email, :donor_phone, :member_id,
                    :description, :expires_at, 'pending'
                )
            ");

            $stmt->execute([
                ':ref' => $paymentRef,
                ':code' => $confirmationCode,
                ':type' => $data['type'],
                ':amount' => floatval($data['amount']),
                ':currency' => $data['currency'] ?? 'CDF',
                ':donor_name' => $data['donor_name'] ?? 'Anonyme',
                ':donor_email' => $data['donor_email'] ?? '',
                ':donor_phone' => $data['donor_phone'] ?? '',
                ':member_id' => $data['member_id'] ?? null,
                ':description' => $data['description'] ?? '',
                ':expires_at' => $expiresAt
            ]);

            return [
                'status' => 'success',
                'payment_ref' => $paymentRef,
                'confirmation_code' => $confirmationCode,
                'amount' => floatval($data['amount']),
                'currency' => $data['currency'] ?? 'CDF',
                'expires_at' => $expiresAt,
                'message' => 'Demande de paiement créée avec succès'
            ];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Confirmer un paiement avec le code de confirmation
     * 
     * @param string $paymentRef Référence de paiement
     * @param string $confirmationCode Code de confirmation fourni par le donateur
     * @param string $confirmedBy Qui confirme (admin, automatic, etc.)
     * @return array ['status' => 'success|error', ...]
     */
    public function confirmPayment($paymentRef, $confirmationCode, $confirmedBy = 'automatic') {
        try {
            // Vérifier que la codes correspondent
            $stmt = $this->db->prepare("
                SELECT * FROM {$this->tableName} 
                WHERE payment_ref = :ref AND confirmation_code = :code
            ");
            $stmt->execute([':ref' => $paymentRef, ':code' => $confirmationCode]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                return ['status' => 'error', 'message' => 'Référence ou code invalide'];
            }

            if ($payment['status'] !== 'pending') {
                return ['status' => 'error', 'message' => 'Ce paiement a déjà été ' . $payment['status']];
            }

            if (strtotime($payment['expires_at']) < time()) {
                return ['status' => 'error', 'message' => 'Cette demande de paiement a expiré'];
            }

            // Mettre à jour le statut
            $stmt = $this->db->prepare("
                UPDATE {$this->tableName}
                SET status = 'confirmed', confirmed_at = NOW(), confirmed_by = :confirmed_by
                WHERE payment_ref = :ref
            ");
            $stmt->execute([':ref' => $paymentRef, ':confirmed_by' => $confirmedBy]);

            return [
                'status' => 'success',
                'payment_ref' => $paymentRef,
                'amount' => $payment['amount'],
                'currency' => $payment['currency'],
                'donor_name' => $payment['donor_name'],
                'message' => 'Paiement confirmé avec succès ✅'
            ];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Obtenir les détails d'un paiement
     */
    public function getPaymentDetails($paymentRef) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM {$this->tableName}
                WHERE payment_ref = :ref
            ");
            $stmt->execute([':ref' => $paymentRef]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                return ['status' => 'error', 'message' => 'Paiement non trouvé'];
            }

            return ['status' => 'success', 'payment' => $payment];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Lister les paiements avec filtrage
     */
    public function listPayments($filters = []) {
        try {
            $where = "1=1";
            $params = [];

            // Filtres optionnels
            if (!empty($filters['status'])) {
                $where .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['type'])) {
                $where .= " AND type = :type";
                $params[':type'] = $filters['type'];
            }

            if (!empty($filters['member_id'])) {
                $where .= " AND member_id = :member_id";
                $params[':member_id'] = $filters['member_id'];
            }

            if (!empty($filters['start_date'])) {
                $where .= " AND created_at >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $where .= " AND created_at <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }

            $sql = "
                SELECT * FROM {$this->tableName}
                WHERE {$where}
                ORDER BY created_at DESC
                LIMIT 100
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['status' => 'success', 'payments' => $payments, 'total' => count($payments)];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Statistiques des paiements
     */
    public function getPaymentStats($filters = []) {
        try {
            $where = "1=1";
            $params = [];

            if (!empty($filters['start_date'])) {
                $where .= " AND created_at >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $where .= " AND created_at <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }

            // Total des montants par statut
            $sql = "
                SELECT 
                    status,
                    type,
                    COUNT(*) as count,
                    SUM(amount) as total,
                    AVG(amount) as average
                FROM {$this->tableName}
                WHERE {$where}
                GROUP BY status, type
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['status' => 'success', 'stats' => $stats];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Générer une référence de paiement unique
     * Format: PAY-YYYY-XXXXX (ex: PAY-2026-AB12C)
     */
    private function generatePaymentReference() {
        $year = date('Y');
        $random = strtoupper(bin2hex(random_bytes(3)));
        return "PAY-{$year}-{$random}";
    }

    /**
     * Générer un code de confirmation personnel
     * Format: XXXX-XXXX-XXXX (ex: A1B2-C3D4-E5F6)
     * Plus facile à communiquer qu'un nombre aléatoire
     */
    private function generateConfirmationCode() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 12; $i++) {
            if ($i > 0 && $i % 4 === 0) {
                $code .= '-';
            }
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $code;
    }

    /**
     * Exporter les paiements en CSV (pour rapports)
     */
    public function exportToCSV($filters = []) {
        $result = $this->listPayments($filters);
        if ($result['status'] !== 'success') {
            return $result;
        }

        $payments = $result['payments'];
        if (empty($payments)) {
            return ['status' => 'error', 'message' => 'Aucun paiement à exporter'];
        }

        // Créer le CSV
        $csv = "Référence,Code,Type,Montant,Devise,Donateur,Email,Téléphone,Statut,Date,Confirmé le\n";
        foreach ($payments as $payment) {
            $csv .= implode(',', [
                $payment['payment_ref'],
                $payment['confirmation_code'],
                $payment['type'],
                $payment['amount'],
                $payment['currency'],
                $payment['donor_name'],
                $payment['donor_email'],
                $payment['donor_phone'],
                $payment['status'],
                $payment['created_at'],
                $payment['confirmed_at'] ?? ''
            ]) . "\n";
        }

        return ['status' => 'success', 'csv' => $csv];
    }
}

-- Migration: Créer la table de paiements locaux
-- Exécutez ce script une fois pour initialiser le système de paiement

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_ref VARCHAR(50) UNIQUE NOT NULL COMMENT 'Référence unique du paiement (PAY-YYYY-XXXXX)',
    confirmation_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Code de confirmation personnel (XXXX-XXXX-XXXX)',
    type VARCHAR(50) NOT NULL COMMENT 'tithe (dième), offering (offrande), deposit (dépôt), other',
    amount DECIMAL(10, 2) NOT NULL COMMENT 'Montant du paiement',
    currency VARCHAR(3) DEFAULT 'CDF' COMMENT 'Devise (CDF, XAF, USD, etc)',
    donor_name VARCHAR(255) COMMENT 'Nom du donateur',
    donor_email VARCHAR(255) COMMENT 'Email du donateur',
    donor_phone VARCHAR(20) COMMENT 'Téléphone du donateur',
    member_id INT COMMENT 'ID du membre si enregistré',
    description TEXT COMMENT 'Description/Détails du paiement',
    status VARCHAR(50) DEFAULT 'pending' COMMENT 'pending (en attente), confirmed (confirmé), cancelled (annulé), expired (expiré)',
    confirmation_method VARCHAR(50) DEFAULT 'code' COMMENT 'Méthode de confirmation: code, sms, email, manual',
    confirmed_at DATETIME COMMENT 'Date/heure de confirmation',
    confirmed_by VARCHAR(255) COMMENT 'Qui a confirmé (user, admin, automatic)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    expires_at DATETIME COMMENT 'Date d\'expiration (default: +30 jours)',
    notes TEXT COMMENT 'Notes additionnelles',
    
    -- Indices pour performance
    INDEX idx_payment_ref (payment_ref),
    INDEX idx_confirmation_code (confirmation_code),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at),
    INDEX idx_member_id (member_id),
    
    -- Clé étrangère vers members
    CONSTRAINT fk_member_payment FOREIGN KEY (member_id) 
        REFERENCES members(id) ON DELETE SET NULL
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer quelques paiements de test
INSERT INTO payments (payment_ref, confirmation_code, type, amount, currency, donor_name, donor_email, status, confirmed_at, confirmed_by)
VALUES
    ('PAY-2026-TEST01', 'A1B2-C3D4-E5F6', 'tithe', 50000, 'CDF', 'Jean Doe', 'jean@example.com', 'confirmed', NOW(), 'admin'),
    ('PAY-2026-TEST02', 'G7H8-I9J0-K1L2', 'offering', 25000, 'CDF', 'Marie Smith', 'marie@example.com', 'confirmed', NOW(), 'admin'),
    ('PAY-2026-TEST03', 'M3N4-O5P6-Q7R8', 'deposit', 100000, 'CDF', 'Anonyme', '', 'pending', NULL, NULL);

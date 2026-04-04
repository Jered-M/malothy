-- ============================================================================
-- MALOTY - Gestion Administrative et Financière d'Église
-- Structure de Base de Données MySQL
-- ============================================================================

-- Créer la base de données
DROP DATABASE IF EXISTS eglise_m;

CREATE DATABASE eglise_m CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE eglise_m;

-- ============================================================================
-- TABLE: users (Authentification et gestion des utilisateurs)
-- ============================================================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM(
        'admin',
        'trésorier',
        'secrétaire'
    ) NOT NULL DEFAULT 'secrétaire',
    status ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif',
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: members (Gestion des membres)
-- ============================================================================
CREATE TABLE members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(120),
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    department VARCHAR(100),
    join_date DATE NOT NULL,
    photo VARCHAR(255),
    status ENUM(
        'actif',
        'inactif',
        'suspendu'
    ) NOT NULL DEFAULT 'actif',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (first_name, last_name),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_department (department),
    FULLTEXT INDEX fts_search (first_name, last_name, email)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: tithes (Enregistrement des dîmes)
-- ============================================================================
CREATE TABLE tithes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT,
    amount DECIMAL(10, 2) NOT NULL,
    tithe_date DATE NOT NULL,
    comment TEXT,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    recorded_by INT,
    FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_member (member_id),
    INDEX idx_date (tithe_date),
    INDEX idx_year_month (tithe_date)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: offerings (Enregistrement des offrandes)
-- ============================================================================
CREATE TABLE offerings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM(
        'culte',
        'evenement',
        'mission',
        'cotisation',
        'autre'
    ) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    offering_date DATE NOT NULL,
    description TEXT,
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    recorded_by INT,
    FOREIGN KEY (recorded_by) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_type (type),
    INDEX idx_date (offering_date),
    INDEX idx_year_month (offering_date)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: expenses (Enregistrement des dépenses)
-- ============================================================================
CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    expense_date DATE NOT NULL,
    description TEXT,
    document_path VARCHAR(255),
    status ENUM(
        'en attente',
        'approuvée',
        'rejetée'
    ) NOT NULL DEFAULT 'en attente',
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    recorded_by INT,
    approved_by INT,
    approval_date DATETIME,
    rejection_reason TEXT,
    FOREIGN KEY (recorded_by) REFERENCES users (id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_date (expense_date),
    INDEX idx_status (status),
    INDEX idx_year_month (expense_date)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: audit_logs (Journalisation des actions)
-- ============================================================================
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_table (table_name),
    INDEX idx_date (created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: settings (Configuration de l'application)
-- ============================================================================
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ============================================================================
-- DONNÉES INITIALES
-- ============================================================================

-- Administrateur (password: admin123)
INSERT INTO
    users (
        name,
        email,
        password,
        role,
        status
    )
VALUES (
        'Administrateur',
        'admin@maloty.com',
        '$2y$12$5RRkMsNu7e1Sx8fxFUM/i.5tPT6mv2D97UWiCpEXlJB5xRqYYy7j.',
        'admin',
        'actif'
    );

-- Trésorier (password: treas123)
INSERT INTO
    users (
        name,
        email,
        password,
        role,
        status
    )
VALUES (
        'Trésorier',
        'treasure@maloty.com',
        '$2y$12$X94B/Wo.RvINSjw3oT7kT.myY5N4ld44pjHqVhp4BJVgQJEY6efxm',
        'trésorier',
        'actif'
    );

-- Secrétaire (password: sec123)
INSERT INTO
    users (
        name,
        email,
        password,
        role,
        status
    )
VALUES (
        'Secrétaire',
        'secretary@maloty.com',
        '$2y$12$o7V7U/0x7vk7jlQCm8lZeuZY/dJlyp7d2j.EOnbZAHU4GWLT7ihn6',
        'secrétaire',
        'actif'
    );

-- Membres de démo
INSERT INTO
    members (
        first_name,
        last_name,
        email,
        phone,
        address,
        department,
        join_date,
        status
    )
VALUES (
        'Jean',
        'Dupont',
        'jean.dupont@email.com',
        '06 12 34 56 78',
        '123 Rue de Paris',
        'Culte',
        '2024-01-15',
        'actif'
    ),
    (
        'Marie',
        'Martin',
        'marie.martin@email.com',
        '06 23 45 67 89',
        '456 Avenue des Champs',
        'Jeunesse',
        '2024-02-20',
        'actif'
    ),
    (
        'Pierre',
        'Bernard',
        'pierre.bernard@email.com',
        '06 34 56 78 90',
        '789 Boulevard Saint-Jacques',
        'Diaconie',
        '2024-03-10',
        'actif'
    ),
    (
        'Sophie',
        'Lefevre',
        'sophie.lefevre@email.com',
        '07 12 34 56 78',
        '321 Chemin Rural',
        'Communion',
        '2024-04-05',
        'actif'
    ),
    (
        'Thomas',
        'Moreau',
        'thomas.moreau@email.com',
        '07 23 45 67 89',
        '654 Rue de l''Église',
        'Culte',
        '2024-05-12',
        'actif'
    );

-- Dîmes de démo
INSERT INTO
    tithes (
        member_id,
        amount,
        tithe_date,
        comment,
        recorded_by
    )
VALUES (
        1,
        100.00,
        '2026-03-01',
        'Dîme régulière',
        1
    ),
    (
        2,
        50.00,
        '2026-03-05',
        'Dîme régulière',
        1
    ),
    (
        3,
        75.00,
        '2026-03-08',
        'Dîme régulière',
        1
    ),
    (
        4,
        120.00,
        '2026-03-15',
        'Dîme régulière',
        1
    ),
    (
        5,
        100.00,
        '2026-03-10',
        'Dîme régulière',
        1
    ),
    (
        1,
        200.00,
        '2026-02-15',
        'Offrande spéciale',
        1
    );

-- Offrandes de démo
INSERT INTO
    offerings (
        type,
        amount,
        offering_date,
        description,
        recorded_by
    )
VALUES (
        'culte',
        250.00,
        '2026-03-01',
        'Offrande du dimanche',
        1
    ),
    (
        'mission',
        500.00,
        '2026-03-10',
        'Collecte pour missions',
        1
    ),
    (
        'evenement',
        300.00,
        '2026-03-15',
        'Événement spécial',
        1
    ),
    (
        'culte',
        180.00,
        '2026-03-08',
        'Offrande du dimanche',
        1
    ),
    (
        'autre',
        150.00,
        '2026-03-12',
        'Contribution libre',
        1
    );

-- Dépenses de démo
INSERT INTO
    expenses (
        category,
        amount,
        expense_date,
        description,
        status,
        recorded_by,
        approved_by
    )
VALUES (
        'loyer',
        1000.00,
        '2026-03-01',
        'Loyer du bâtiment',
        'approuvée',
        2,
        1
    ),
    (
        'salaire',
        500.00,
        '2026-03-01',
        'Salaire assistant pastoral',
        'approuvée',
        2,
        1
    ),
    (
        'entretien',
        150.00,
        '2026-03-05',
        'Réparation toit',
        'en attente',
        2,
        NULL
    ),
    (
        'communion',
        75.00,
        '2026-03-12',
        'Achat pain et raisin',
        'approuvée',
        2,
        1
    ),
    (
        'mission',
        300.00,
        '2026-03-18',
        'Projet missionnaire',
        'en attente',
        2,
        NULL
    );

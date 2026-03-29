-- ============================================================================
-- MALOTY - PostgreSQL Schema for Supabase
-- ============================================================================

-- Types ENUM personnalisés
CREATE TYPE user_role AS ENUM ('admin', 'trésorier', 'secrétaire');
CREATE TYPE user_status AS ENUM ('actif', 'inactif');
CREATE TYPE member_status AS ENUM ('actif', 'inactif', 'suspendu');
CREATE TYPE expense_status AS ENUM ('en attente', 'approuvee', 'rejetee');
CREATE TYPE offering_type AS ENUM ('culte', 'evenement', 'mission', 'autre');

-- TABLE: users
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role user_role DEFAULT 'secrétaire',
    status user_status DEFAULT 'actif',
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_user_role ON users(role);

-- TABLE: members
CREATE TABLE members (
    id SERIAL PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(120),
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    department VARCHAR(100),
    join_date DATE NOT NULL,
    photo VARCHAR(255),
    status member_status DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_member_name ON members(first_name, last_name);
CREATE INDEX idx_member_status ON members(status);

-- TABLE: tithes
CREATE TABLE tithes (
    id SERIAL PRIMARY KEY,
    member_id INTEGER REFERENCES members(id) ON DELETE SET NULL,
    amount DECIMAL(10, 2) NOT NULL,
    tithe_date DATE NOT NULL,
    comment TEXT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recorded_by INTEGER REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_tithe_date ON tithes(tithe_date);

-- TABLE: offerings
CREATE TABLE offerings (
    id SERIAL PRIMARY KEY,
    type offering_type NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    offering_date DATE NOT NULL,
    description TEXT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recorded_by INTEGER REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_offering_date ON offerings(offering_date);

-- TABLE: expenses
CREATE TABLE expenses (
    id SERIAL PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    expense_date DATE NOT NULL,
    description TEXT,
    document_path VARCHAR(255),
    status expense_status DEFAULT 'en attente',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recorded_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    approved_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    approval_date TIMESTAMP,
    rejection_reason TEXT
);

CREATE INDEX idx_expense_status ON expenses(status);
CREATE INDEX idx_expense_date ON expenses(expense_date);

-- TABLE: audit_logs
CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(100),
    record_id INTEGER,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABLE: settings
CREATE TABLE settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- DONNÉES INITIALES (Exemple)
INSERT INTO users (name, email, password, role, status)
VALUES ('Administrateur', 'admin@maloty.com', '$2y$12$5RRkMsNu7e1Sx8fxFUM/i.5tPT6mv2D97UWiCpEXlJB5xRqYYy7j.', 'admin', 'actif');

INSERT INTO users (name, email, password, role, status)
VALUES ('Trésorier', 'treasure@maloty.com', '$2y$12$X94B/Wo.RvINSjw3oT7kT.myY5N4ld44pjHqVhp4BJVgQJEY6efxm', 'trésorier', 'actif');

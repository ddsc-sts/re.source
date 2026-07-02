-- Dia 1: empresas pendentes, papeis internos e aprovacao administrativa.
-- Compativel com MariaDB 10.4+ / MySQL 8+.

ALTER TABLE companies
    MODIFY COLUMN status ENUM('pending','active','suspended','inactive')
    NOT NULL DEFAULT 'pending';

ALTER TABLE companies
    ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL AFTER email_verified_at,
    ADD COLUMN IF NOT EXISTS approved_by_user_id INT UNSIGNED NULL AFTER approved_at;

ALTER TABLE users
    MODIFY COLUMN role ENUM('admin','staff','admin_company','operator')
    NOT NULL DEFAULT 'admin_company';

-- Corrige cadastros feitos quando o banco ainda nao aceitava o valor pending.
UPDATE companies
SET status = 'pending'
WHERE status = '' OR status IS NULL;

-- Empresas existentes continuam aprovadas. Novos cadastros passam a pending.
UPDATE companies
SET approved_at = COALESCE(approved_at, created_at)
WHERE status = 'active';

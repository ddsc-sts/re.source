-- ============================================================
-- Re.Source — Administrador local do MVP academico
-- Execute depois de re.sourcebanco.sql.
-- Login: admin@resource.com.br
-- Senha inicial: Admin@2026!
-- Credencial ficticia para a demonstracao academica.
-- ============================================================

SET @ADMIN_ADDRESS_ID = 9000;

INSERT INTO addresses
    (id, zip_code, street, number, district, city, state)
VALUES
    (@ADMIN_ADDRESS_ID, '00000-000', 'Ambiente interno', 'S/N', 'Administracao', 'Joinville', 'SC')
ON DUPLICATE KEY UPDATE
    street = VALUES(street), city = VALUES(city), state = VALUES(state);

INSERT INTO companies
    (id, cnpj, razao_social, nome_fantasia, slug, email, phone,
     responsible_name, address_id, status, plan_id, email_verified_at,
     approved_at, onboarding_completed)
VALUES
    (9000, '00000000000000', 'Re.Source Administracao',
     'Re.Source Admin', 'resource-admin', 'admin@resource.com.br', '',
     'Administrador Re.Source', @ADMIN_ADDRESS_ID, 'active', 1, NOW(), NOW(), 1)
ON DUPLICATE KEY UPDATE
    status = 'active',
    email_verified_at = COALESCE(email_verified_at, NOW()),
    approved_at = COALESCE(approved_at, NOW());

SET @ADMIN_COMPANY_ID = (
    SELECT id FROM companies WHERE cnpj = '00000000000000' LIMIT 1
);

INSERT INTO users
    (id, company_id, name, email, password_hash, role, is_active)
VALUES
    (9000, @ADMIN_COMPANY_ID, 'Administrador Re.Source',
     'admin@resource.com.br',
     '$2y$12$q/9JdIU6xVfhupNqQfMrQuww2mA8wVbrHb/XQGYAU4Ocw1BQf8Ynm',
     'admin', 1)
ON DUPLICATE KEY UPDATE
    company_id = VALUES(company_id),
    name = VALUES(name),
    password_hash = VALUES(password_hash),
    role = 'admin',
    is_active = 1,
    deleted_at = NULL;

SET @ADMIN_USER_ID = (
    SELECT id FROM users WHERE email = 'admin@resource.com.br' LIMIT 1
);

UPDATE companies
SET approved_by_user_id = @ADMIN_USER_ID
WHERE id = @ADMIN_COMPANY_ID;

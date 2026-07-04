-- ============================================================
-- Re.Source — Promover uma conta cadastrada para administrador
--
-- Use somente no ambiente academico/local.
-- 1. Cadastre e confirme a conta normalmente pelo site.
-- 2. Troque o e-mail abaixo pelo e-mail cadastrado.
-- 3. Execute este arquivo no banco resource.
-- 4. Saia do site e entre novamente para renovar a sessao.
-- ============================================================

SET @ADMIN_EMAIL = 'seu-email@exemplo.com';

SET @ADMIN_USER_ID = (
    SELECT id
    FROM users
    WHERE email = @ADMIN_EMAIL
    LIMIT 1
);

SET @ADMIN_COMPANY_ID = (
    SELECT company_id
    FROM users
    WHERE id = @ADMIN_USER_ID
    LIMIT 1
);

UPDATE users
SET role = 'admin',
    is_active = 1,
    deleted_at = NULL
WHERE id = @ADMIN_USER_ID;

UPDATE companies
SET status = 'active',
    email_verified_at = COALESCE(email_verified_at, NOW()),
    approved_at = COALESCE(approved_at, NOW()),
    approved_by_user_id = @ADMIN_USER_ID
WHERE id = @ADMIN_COMPANY_ID;

SELECT
    u.id AS user_id,
    u.email,
    u.role,
    c.id AS company_id,
    c.nome_fantasia,
    c.status AS company_status
FROM users u
INNER JOIN companies c ON c.id = u.company_id
WHERE u.id = @ADMIN_USER_ID;

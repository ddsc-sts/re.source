    -- ============================================================
    -- Re.Source — Empresas e usuarios para demonstracao
    -- Execute depois de re.sourcebanco.sql e antes de produto.sql.
    -- Senha de todos os usuarios abaixo: Resource@2026
    -- Credenciais ficticias para a demonstracao academica.
    -- ============================================================

    INSERT INTO addresses (id, zip_code, street, number, district, city, state, lat, lng) VALUES
    (1, '89219-050', 'Rua Albano Schmidt', '3400', 'Zona Industrial Norte', 'Joinville', 'SC', -26.2890, -48.8490),
    (2, '89201-700', 'Av. Getulio Vargas', '1200', 'Anita Garibaldi', 'Joinville', 'SC', -26.3045, -48.8456),
    (3, '89218-105', 'Rua Jose Vieira', '800', 'Distrito Industrial', 'Joinville', 'SC', -26.2750, -48.8600),
    (4, '89204-310', 'Rua XV de Novembro', '500', 'Centro', 'Joinville', 'SC', -26.3039, -48.8496),
    (5, '89220-000', 'Rua da Aprovacao', '100', 'America', 'Joinville', 'SC', -26.2900, -48.8500)
    ON DUPLICATE KEY UPDATE city = VALUES(city), state = VALUES(state);

    INSERT INTO companies
        (id, cnpj, razao_social, nome_fantasia, slug, email, phone,
        responsible_name, address_id, segment, status, plan_id,
        email_verified_at, approved_at, onboarding_completed)
    VALUES
    (1, '12345678000195', 'Metalurgica Joinville Ltda', 'MetalJoin', 'metaljoin',
    'contato@metaljoin.com.br', '47991110001', 'Carlos Mendes', 1,
    'Metalurgia', 'active', 2, NOW(), NOW(), 1),
    (2, '98765432000110', 'Madeireira Sul Catarinense SA', 'MadeiraSul', 'madeirasul',
    'contato@madeirasul.com.br', '47992220002', 'Ana Paula Lima', 2,
    'Madeireiro', 'active', 1, NOW(), NOW(), 1),
    (3, '11222333000181', 'Plasticos Nordicos Ltda', 'PlasticoNord', 'plasticonord',
    'contato@plasticonord.com.br', '47993330003', 'Roberto Steiner', 3,
    'Plasticos e Borracha', 'active', 3, NOW(), NOW(), 1),
    (4, '44555666000174', 'Textil Catarinense Eireli', 'TextilCat', 'textilcat',
    'contato@textilcat.com.br', '47994440004', 'Fernanda Souza', 4,
    'Textil', 'active', 1, NOW(), NOW(), 1),
    (5, '55443322000190', 'Empresa Aguardando Aprovacao Ltda', 'Empresa Pendente', 'empresa-pendente',
    'contato@empresapendente.com.br', '47995550005', 'Marina Teste', 5,
    'Reciclagem', 'pending', 1, NOW(), NULL, 0)
    ON DUPLICATE KEY UPDATE
        razao_social = VALUES(razao_social),
        nome_fantasia = VALUES(nome_fantasia),
        status = VALUES(status),
        email_verified_at = VALUES(email_verified_at),
        approved_at = VALUES(approved_at);

    INSERT INTO users
        (id, company_id, name, email, password_hash, role, is_active)
    VALUES
    (1, 1, 'Carlos Mendes', 'carlos@metaljoin.com.br',
    '$2y$12$TPA1ZCb9amNhvc6xGBWqHO0X52mDVklu75163uesPM6Vtz9gNqZlS', 'admin_company', 1),
    (2, 2, 'Ana Paula Lima', 'ana@madeirasul.com.br',
    '$2y$12$TPA1ZCb9amNhvc6xGBWqHO0X52mDVklu75163uesPM6Vtz9gNqZlS', 'admin_company', 1),
    (3, 3, 'Roberto Steiner', 'roberto@plasticonord.com.br',
    '$2y$12$TPA1ZCb9amNhvc6xGBWqHO0X52mDVklu75163uesPM6Vtz9gNqZlS', 'admin_company', 1),
    (4, 4, 'Fernanda Souza', 'fernanda@textilcat.com.br',
    '$2y$12$TPA1ZCb9amNhvc6xGBWqHO0X52mDVklu75163uesPM6Vtz9gNqZlS', 'admin_company', 1),
    (5, 5, 'Marina Teste', 'marina@empresapendente.com.br',
    '$2y$12$TPA1ZCb9amNhvc6xGBWqHO0X52mDVklu75163uesPM6Vtz9gNqZlS', 'admin_company', 1)
    ON DUPLICATE KEY UPDATE
        company_id = VALUES(company_id),
        name = VALUES(name),
        password_hash = VALUES(password_hash),
        role = VALUES(role),
        is_active = 1,
        deleted_at = NULL;

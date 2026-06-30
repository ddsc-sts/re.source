-- ============================================================
--  Re.Source — Dados de Exemplo (Sprint 2)
--  Execute após rodar o SQL principal (v3.1)
-- ============================================================

USE resource;

-- ------------------------------------------------------------
-- Endereços das empresas de exemplo
-- ------------------------------------------------------------
INSERT IGNORE INTO addresses (id, zip_code, street, number, district, city, state, lat, lng) VALUES
(1, '89219-050', 'Rua Albano Schmidt', '3400', 'Zona Industrial Norte', 'Joinville', 'SC', -26.2890, -48.8490),
(2, '89201-700', 'Av. Getúlio Vargas',  '1200', 'Anita Garibaldi',       'Joinville', 'SC', -26.3045, -48.8456),
(3, '89218-105', 'Rua José Vieira',      '800',  'Distrito Industrial',   'Joinville', 'SC', -26.2750, -48.8600),
(4, '89204-310', 'Rua XV de Novembro',   '500',  'Centro',               'Joinville', 'SC', -26.3039, -48.8496);

-- ------------------------------------------------------------
-- Empresas de exemplo
-- ------------------------------------------------------------
INSERT IGNORE INTO companies (id, cnpj, razao_social, nome_fantasia, slug, email, phone, responsible_name, address_id, segment, status, plan_id, email_verified_at, onboarding_completed) VALUES
(1, '12345678000195', 'Metalúrgica Joinville Ltda',    'MetalJoin',    'metaljoin',    'contato@metaljoin.com.br',    '47991110001', 'Carlos Mendes',   1, 'Metalurgia',          'active', 2, NOW(), 1),
(2, '98765432000110', 'Madeireira Sul Catarinense SA', 'MadeiraSul',   'madeirasul',   'contato@madeirasul.com.br',   '47992220002', 'Ana Paula Lima',  2, 'Madeireiro',          'active', 1, NOW(), 1),
(3, '11222333000181', 'Plásticos Nórdicos Ltda',       'PlásticoNord', 'plasticonord', 'contato@plasticonord.com.br', '47993330003', 'Roberto Steiner', 3, 'Plásticos e Borracha','active', 3, NOW(), 1),
(4, '44555666000174', 'Têxtil Catarinense Eireli',     'TextilCat',    'textilcat',    'contato@textilcat.com.br',    '47994440004', 'Fernanda Souza',  4, 'Têxtil',              'active', 1, NOW(), 1);

-- ------------------------------------------------------------
-- Usuários das empresas de exemplo
-- (senha: Resource@2026 — bcrypt cost 12)
-- ------------------------------------------------------------
INSERT IGNORE INTO users (id, company_id, name, email, password_hash, role, is_active) VALUES
(1, 1, 'Carlos Mendes',   'carlos@metaljoin.com.br',    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiIJlTNV7NfY2Gy9l.XXXXXXXXX', 'admin_company', 1),
(2, 2, 'Ana Paula Lima',  'ana@madeirasul.com.br',       '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiIJlTNV7NfY2Gy9l.XXXXXXXXX', 'admin_company', 1),
(3, 3, 'Roberto Steiner', 'roberto@plasticonord.com.br', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiIJlTNV7NfY2Gy9l.XXXXXXXXX', 'admin_company', 1),
(4, 4, 'Fernanda Souza',  'fernanda@textilcat.com.br',   '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiIJlTNV7NfY2Gy9l.XXXXXXXXX', 'admin_company', 1);

-- ------------------------------------------------------------
-- Anúncios de exemplo
-- ------------------------------------------------------------
INSERT IGNORE INTO listings (id, company_id, created_by_user_id, type, title, description, category_id, quantity, unit, price, is_negotiable, status, location_state, location_city, expires_at) VALUES
-- Metal (category_id = 2)
(1, 1, 1, 'offer', 'Limalha de Ferro Limpa',
 'Limalha de ferro proveniente de usinagem CNC, livre de óleo e contaminantes. Ideal para reciclagem ou fabricação de peças sinterizadas. Embalada em bags de 50kg.',
 2, 500.000, 'kg', 2.50, 1, 'active', 'SC', 'Joinville',
 DATE_ADD(NOW(), INTERVAL 30 DAY)),

-- Madeira (category_id = 4)
(2, 2, 2, 'offer', 'Serragem de Pinus Seca',
 'Serragem fina de Pinus elliottii, sem tratamento químico, umidade inferior a 12%. Excelente para camas de animais, compostagem ou briquetes. Disponível em bags ou a granel.',
 4, 1200.000, 'kg', 0.30, 1, 'active', 'SC', 'Joinville',
 DATE_ADD(NOW(), INTERVAL 25 DAY)),

-- Plástico (category_id = 3)
(3, 3, 3, 'offer', 'Aparas de Plástico PET Transparente',
 'Aparas pós-industriais de PET cristal, provenientes de produção de embalagens. Material lavado, sem pigmento. Grau alimentício. Disponível imediatamente.',
 3, 800.000, 'kg', 1.80, 0, 'active', 'SC', 'Joinville',
 DATE_ADD(NOW(), INTERVAL 20 DAY)),

-- Madeira (category_id = 4)
(4, 2, 2, 'offer', 'Pallets de Madeira Usados',
 'Pallets PBR em madeira de pinus, usados em bom estado. Dimensões 1,00 x 1,20m. Pequenos reparos podem ser necessários. Retirada no local.',
 4, 50.000, 'unidade', 18.00, 1, 'active', 'SC', 'Joinville',
 DATE_ADD(NOW(), INTERVAL 15 DAY)),

-- Têxtil (category_id = 1)
(5, 4, 4, 'offer', 'Retalhos de Malha Algodão 100%',
 'Retalhos de malha piquê de algodão 100%, cor branca, tamanho variado (10 a 50cm). Ideal para estopas industriais, artesanato ou reciclagem têxtil. Prensados em fardos de 40kg.',
 1, 320.000, 'kg', 1.20, 1, 'active', 'SC', 'Joinville',
 DATE_ADD(NOW(), INTERVAL 28 DAY)),

-- Demanda de Metal
(6, 3, 3, 'demand', 'Procuro: Sucata de Alumínio 6061',
 'Compramos sucata e aparas de alumínio liga 6061, qualquer geometria. Pagamento à vista. Retirada por conta própria. Volume mínimo: 200kg/mês.',
 2, 200.000, 'kg', NULL, 1, 'active', 'SC', 'Joinville',
 DATE_ADD(NOW(), INTERVAL 60 DAY)),

-- Plástico
(7, 1, 1, 'offer', 'Cavacos de Nylon PA6',
 'Cavacos e aparas de Nylon PA6 natural (cor palha), provenientes de usinagem de peças automotivas. Material seco, acondicionado em sacos de 25kg.',
 3, 150.000, 'kg', 3.20, 0, 'active', 'SC', 'Joinville',
 DATE_ADD(NOW(), INTERVAL 22 DAY)),

-- Borracha (category_id = 7)
(8, 4, 4, 'offer', 'Aparas de Borracha EPDM',
 'Aparas e rejeitos de borracha EPDM provenientes de fabricação de vedações industriais. Material sem cura, pode ser reprocessado. Fardos de 20kg.',
 7, 600.000, 'kg', 0.90, 1, 'active', 'SC', 'Joinville',
 DATE_ADD(NOW(), INTERVAL 18 DAY));

-- ------------------------------------------------------------
-- Imagens dos anúncios
-- ------------------------------------------------------------
INSERT IGNORE INTO listing_images (listing_id, url, `order`) VALUES
(1, 'https://images.unsplash.com/photo-1764114440880-9bdbfe570a4d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 0),
(2, 'https://images.unsplash.com/photo-1760939858984-5dc76f0ea34a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 0),
(3, 'https://images.unsplash.com/photo-1682668373702-10e0eb560e44?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 0),
(4, 'https://images.unsplash.com/photo-1759300635757-19ab99f4cfed?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 0),
(5, 'https://images.unsplash.com/photo-1758264629814-44559c99e506?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 0),
(6, 'https://images.unsplash.com/photo-1722695510527-cc033e43be1b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 0),
(7, 'https://images.unsplash.com/photo-1606037150583-fb842a55bae7?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 0),
(8, 'https://images.unsplash.com/photo-1761765030682-26f51cfbc034?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&q=80&w=600', 0);
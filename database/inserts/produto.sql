-- ============================================================
-- Re.Source — Anuncios e imagens para demonstracao
-- Execute depois de empresa_demo.sql.
-- ============================================================

INSERT INTO listings
    (id, company_id, created_by_user_id, type, title, description,
     category_id, quantity, unit, price, is_negotiable, status,
     location_state, location_city, expires_at)
VALUES
(1, 1, 1, 'offer', 'Limalha de Ferro Limpa',
 'Limalha proveniente de usinagem CNC, livre de oleo e contaminantes.',
 2, 500.000, 'kg', 2.50, 1, 'active', 'SC', 'Joinville', DATE_ADD(NOW(), INTERVAL 30 DAY)),
(2, 2, 2, 'offer', 'Serragem de Pinus Seca',
 'Serragem fina sem tratamento quimico, adequada para compostagem e briquetes.',
 4, 1200.000, 'kg', 0.30, 1, 'active', 'SC', 'Joinville', DATE_ADD(NOW(), INTERVAL 25 DAY)),
(3, 3, 3, 'offer', 'Aparas de Plastico PET Transparente',
 'Aparas pos-industriais lavadas e sem pigmento, prontas para reciclagem.',
 3, 800.000, 'kg', 1.80, 0, 'active', 'SC', 'Joinville', DATE_ADD(NOW(), INTERVAL 20 DAY)),
(4, 2, 2, 'offer', 'Pallets de Madeira Usados',
 'Pallets PBR usados em bom estado, disponiveis para retirada no local.',
 4, 50.000, 'unidade', 18.00, 1, 'active', 'SC', 'Joinville', DATE_ADD(NOW(), INTERVAL 15 DAY)),
(5, 4, 4, 'offer', 'Retalhos de Malha de Algodao',
 'Retalhos prensados em fardos, ideais para estopas e reciclagem textil.',
 1, 320.000, 'kg', 1.20, 1, 'active', 'SC', 'Joinville', DATE_ADD(NOW(), INTERVAL 28 DAY)),
(6, 3, 3, 'demand', 'Procuro Sucata de Aluminio 6061',
 'Demanda recorrente de sucata e aparas de aluminio para reprocessamento.',
 2, 200.000, 'kg', NULL, 1, 'active', 'SC', 'Joinville', DATE_ADD(NOW(), INTERVAL 60 DAY))
ON DUPLICATE KEY UPDATE
    title = VALUES(title), description = VALUES(description), quantity = VALUES(quantity),
    price = VALUES(price), status = VALUES(status), expires_at = VALUES(expires_at);

INSERT INTO listing_images (id, listing_id, url, `order`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1764114440880-9bdbfe570a4d?fit=crop&w=800&q=80', 0),
(2, 2, 'https://images.unsplash.com/photo-1760939858984-5dc76f0ea34a?fit=crop&w=800&q=80', 0),
(3, 3, 'https://images.unsplash.com/photo-1682668373702-10e0eb560e44?fit=crop&w=800&q=80', 0),
(4, 4, 'https://images.unsplash.com/photo-1759300635757-19ab99f4cfed?fit=crop&w=800&q=80', 0),
(5, 5, 'https://images.unsplash.com/photo-1758264629814-44559c99e506?fit=crop&w=800&q=80', 0),
(6, 6, 'https://images.unsplash.com/photo-1722695510527-cc033e43be1b?fit=crop&w=800&q=80', 0)
ON DUPLICATE KEY UPDATE url = VALUES(url), `order` = VALUES(`order`);

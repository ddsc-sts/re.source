-- ============================================================
-- Re.Source — Negociacao concluida e saldo para demonstracao
-- Execute depois de empresa_demo.sql e produto.sql.
-- Vendedor: empresa 1 | Comprador: empresa 2 | Valor: R$ 1.000,00
-- ============================================================

INSERT INTO negotiations
    (id, listing_id, buyer_company_id, seller_company_id, status,
     protocol_number, proposed_quantity, proposed_price, proposed_total,
     concluded_at)
VALUES
    (1001, 1, 2, 1, 'concluded', 'DEMO-CONCLUIDA-001',
     400.000, 2.50, 1000.00, NOW())
ON DUPLICATE KEY UPDATE
    status = 'concluded', proposed_total = 1000.00, concluded_at = NOW();

INSERT INTO financial_transactions
    (id, company_id, negotiation_id, type, amount, status, description)
VALUES
    (1001, 1, 1001, 'sale', 1000.00, 'completed', 'Saldo da negociacao DEMO-CONCLUIDA-001')
ON DUPLICATE KEY UPDATE
    amount = VALUES(amount), status = 'completed', description = VALUES(description);

UPDATE companies
SET balance = 1000.00
WHERE id = 1;

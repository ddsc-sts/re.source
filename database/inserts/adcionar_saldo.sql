INSERT INTO negotiations (
    listing_id,
    buyer_company_id,
    seller_company_id,
    status,
    protocol_number,
    proposed_quantity,
    proposed_price,
    proposed_total,
    concluded_at
) VALUES (
    9,
    1,
    5,
    'concluded',
    CONCAT('SALDO-TESTE-5-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')),
    4.000,
    250.00,
    1000.00,
    NOW()
);
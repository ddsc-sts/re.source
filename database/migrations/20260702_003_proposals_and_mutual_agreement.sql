-- Dia 3: propostas, aceite mutuo, recusa e cancelamento.
-- Executar somente em bancos criados antes desta atualizacao.

ALTER TABLE negotiations
    MODIFY COLUMN status ENUM(
        'open','proposal_sent','buyer_accepted','seller_accepted','accepted',
        'awaiting_freight','shipping','delivered','concluded','cancelled'
    ) NOT NULL DEFAULT 'open',
    ADD COLUMN IF NOT EXISTS agreement_at TIMESTAMP NULL AFTER proposed_total;

ALTER TABLE proposals
    MODIFY COLUMN status ENUM('pending','accepted','refused','cancelled') NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS responsible_for_freight ENUM('buyer','seller','shared') NOT NULL DEFAULT 'buyer' AFTER delivery_deadline,
    ADD COLUMN IF NOT EXISTS buyer_accepted_at TIMESTAMP NULL AFTER status,
    ADD COLUMN IF NOT EXISTS seller_accepted_at TIMESTAMP NULL AFTER buyer_accepted_at,
    ADD COLUMN IF NOT EXISTS refused_by_company_id INT UNSIGNED NULL AFTER seller_accepted_at,
    ADD COLUMN IF NOT EXISTS refusal_reason TEXT NULL AFTER refused_by_company_id,
    ADD COLUMN IF NOT EXISTS cancelled_by_company_id INT UNSIGNED NULL AFTER refusal_reason,
    ADD COLUMN IF NOT EXISTS cancel_reason TEXT NULL AFTER cancelled_by_company_id,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
    ADD INDEX IF NOT EXISTS idx_prop_status (negotiation_id, status);


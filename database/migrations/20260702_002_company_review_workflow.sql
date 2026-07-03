-- Dia 2: revisao administrativa completa das empresas.
-- Executar somente em bancos criados antes desta atualizacao.

ALTER TABLE companies
    MODIFY COLUMN status
        ENUM('pending','changes_requested','active','suspended','rejected','inactive')
        NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS review_notes TEXT NULL AFTER approved_by_user_id,
    ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL AFTER review_notes,
    ADD COLUMN IF NOT EXISTS reviewed_by_user_id INT UNSIGNED NULL AFTER reviewed_at;

ALTER TABLE notifications
    MODIFY COLUMN type ENUM(
        'new_message',
        'proposal_received',
        'proposal_accepted',
        'proposal_refused',
        'negotiation_concluded',
        'negotiation_cancelled',
        'listing_expiring',
        'listing_expired',
        'freight_status_updated',
        'payment_due',
        'account_approved',
        'account_changes_requested',
        'account_rejected',
        'account_suspended',
        'account_reactivated'
    ) NOT NULL;


-- Dia 5: saldo interno, reserva de saque e aprovacao manual.

ALTER TABLE notifications MODIFY COLUMN type ENUM(
    'new_message','proposal_received','proposal_accepted','proposal_refused',
    'negotiation_concluded','negotiation_cancelled','listing_expiring','listing_expired',
    'freight_status_updated','payment_due','withdrawal_requested','withdrawal_approved',
    'withdrawal_rejected','account_approved','account_changes_requested','account_rejected',
    'account_suspended','account_reactivated'
) NOT NULL;

ALTER TABLE withdrawals
    ADD COLUMN IF NOT EXISTS method ENUM('pix','ted') NOT NULL DEFAULT 'pix' AFTER amount,
    MODIFY COLUMN pix_key VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS bank_code VARCHAR(10) NULL AFTER pix_key_type,
    ADD COLUMN IF NOT EXISTS bank_name VARCHAR(100) NULL AFTER bank_code,
    ADD COLUMN IF NOT EXISTS agency VARCHAR(20) NULL AFTER bank_name,
    ADD COLUMN IF NOT EXISTS account_number VARCHAR(30) NULL AFTER agency,
    ADD COLUMN IF NOT EXISTS account_digit VARCHAR(10) NULL AFTER account_number,
    ADD COLUMN IF NOT EXISTS account_type ENUM('checking','savings') NULL AFTER account_digit,
    ADD COLUMN IF NOT EXISTS request_token CHAR(64) NULL AFTER request_note,
    ADD COLUMN IF NOT EXISTS balance_before DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER request_token,
    ADD COLUMN IF NOT EXISTS balance_after DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER balance_before,
    ADD COLUMN IF NOT EXISTS reserved_at TIMESTAMP NULL AFTER balance_after,
    ADD COLUMN IF NOT EXISTS reviewed_by_user_id INT UNSIGNED NULL AFTER reviewed_at,
    ADD COLUMN IF NOT EXISTS admin_note VARCHAR(500) NULL AFTER rejection_reason,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

UPDATE withdrawals SET request_token = SHA2(CONCAT('legacy-', id, '-', company_id, '-', created_at), 256)
WHERE request_token IS NULL OR request_token = '';

UPDATE companies c
INNER JOIN (
    SELECT company_id, SUM(amount) amount FROM withdrawals
    WHERE status IN ('pending','completed') AND reserved_at IS NULL GROUP BY company_id
) legacy ON legacy.company_id = c.id
SET c.balance = GREATEST(0, c.balance - legacy.amount);

UPDATE withdrawals SET reserved_at = created_at
WHERE status IN ('pending','completed') AND reserved_at IS NULL;

ALTER TABLE withdrawals
    MODIFY COLUMN request_token CHAR(64) NOT NULL,
    ADD UNIQUE INDEX IF NOT EXISTS idx_withdrawals_request_token (request_token);

SET @sql_reviewer = IF(
    (SELECT COUNT(*) FROM information_schema.table_constraints
     WHERE constraint_schema = DATABASE() AND table_name = 'withdrawals'
       AND constraint_name = 'fk_withdrawals_reviewer') = 0,
    'ALTER TABLE withdrawals ADD CONSTRAINT fk_withdrawals_reviewer FOREIGN KEY (reviewed_by_user_id) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt_reviewer FROM @sql_reviewer; EXECUTE stmt_reviewer; DEALLOCATE PREPARE stmt_reviewer;

ALTER TABLE financial_transactions
    ADD COLUMN IF NOT EXISTS withdrawal_id INT UNSIGNED NULL AFTER negotiation_id,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
    ADD UNIQUE INDEX IF NOT EXISTS idx_financial_withdrawal (withdrawal_id);

SET @sql_finance = IF(
    (SELECT COUNT(*) FROM information_schema.table_constraints
     WHERE constraint_schema = DATABASE() AND table_name = 'financial_transactions'
       AND constraint_name = 'fk_financial_withdrawal') = 0,
    'ALTER TABLE financial_transactions ADD CONSTRAINT fk_financial_withdrawal FOREIGN KEY (withdrawal_id) REFERENCES withdrawals(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt_finance FROM @sql_finance; EXECUTE stmt_finance; DEALLOCATE PREPARE stmt_finance;

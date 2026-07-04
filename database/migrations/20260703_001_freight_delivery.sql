-- Dia 4: frete simulado, rastreamento, codigo de entrega e saldo.
-- Compativel com o schema v4 existente. Executar apenas em bancos antigos.

ALTER TABLE freights
    MODIFY COLUMN status ENUM(
        'quoted','contracted','preparing','in_transit','out_for_delivery',
        'delivered','concluded','cancelled'
    ) NOT NULL DEFAULT 'quoted',
    ADD COLUMN IF NOT EXISTS carrier_company_name VARCHAR(150) NULL AFTER carrier_id,
    ADD COLUMN IF NOT EXISTS service_name VARCHAR(100) NULL AFTER carrier_company_name,
    ADD COLUMN IF NOT EXISTS delivery_days SMALLINT UNSIGNED NULL AFTER total_value,
    ADD COLUMN IF NOT EXISTS estimated_pickup DATETIME NULL AFTER delivery_days,
    ADD COLUMN IF NOT EXISTS estimated_delivery DATETIME NULL AFTER estimated_pickup,
    ADD COLUMN IF NOT EXISTS picked_up_at DATETIME NULL AFTER contracted_at,
    ADD COLUMN IF NOT EXISTS delivered_at DATETIME NULL AFTER picked_up_at,
    ADD COLUMN IF NOT EXISTS delivery_code_hash VARCHAR(255) NULL AFTER delivered_at,
    ADD COLUMN IF NOT EXISTS delivery_code_expires_at DATETIME NULL AFTER delivery_code_hash,
    ADD COLUMN IF NOT EXISTS delivery_code_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER delivery_code_expires_at,
    ADD COLUMN IF NOT EXISTS delivery_code_used_at DATETIME NULL AFTER delivery_code_attempts,
    ADD COLUMN IF NOT EXISTS validated_at DATETIME NULL AFTER delivery_code_used_at,
    ADD COLUMN IF NOT EXISTS api_payload JSON NULL AFTER validated_at,
    ADD UNIQUE INDEX IF NOT EXISTS idx_freight_negotiation_unique (negotiation_id),
    ADD INDEX IF NOT EXISTS idx_freight_status (status),
    ADD INDEX IF NOT EXISTS idx_freight_tracking (tracking_code);

CREATE TABLE IF NOT EXISTS freight_quotes (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    negotiation_id  INT UNSIGNED NOT NULL,
    provider_name   VARCHAR(150) NOT NULL,
    service_name    VARCHAR(100) NOT NULL,
    modality        ENUM('rodoviario','expresso','dedicado','outro') NOT NULL,
    price           DECIMAL(10,2) NOT NULL,
    delivery_days   SMALLINT UNSIGNED NOT NULL,
    status          ENUM('active','selected','expired') NOT NULL DEFAULT 'active',
    expires_at      DATETIME NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_fq_negotiation_status (negotiation_id, status),
    CONSTRAINT fk_fq_negotiation FOREIGN KEY (negotiation_id)
        REFERENCES negotiations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS delivery_attempts (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    freight_id   INT UNSIGNED NOT NULL,
    company_id   INT UNSIGNED NULL,
    user_id      INT UNSIGNED NULL,
    ip_address   VARCHAR(45) NULL,
    success      TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_attempts_freight (freight_id, attempted_at),
    CONSTRAINT fk_attempt_freight FOREIGN KEY (freight_id)
        REFERENCES freights (id) ON DELETE CASCADE,
    CONSTRAINT fk_attempt_company FOREIGN KEY (company_id)
        REFERENCES companies (id) ON DELETE SET NULL,
    CONSTRAINT fk_attempt_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS freight_status_history (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    freight_id    INT UNSIGNED NOT NULL,
    status        ENUM('quoted','contracted','preparing','in_transit','out_for_delivery','delivered','concluded','cancelled') NOT NULL,
    description   VARCHAR(255) NOT NULL,
    created_by_user_id INT UNSIGNED NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_freight_history (freight_id, created_at),
    CONSTRAINT fk_freight_history_freight FOREIGN KEY (freight_id) REFERENCES freights(id) ON DELETE CASCADE,
    CONSTRAINT fk_freight_history_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE financial_transactions
    ADD COLUMN IF NOT EXISTS negotiation_id INT UNSIGNED NULL AFTER company_id,
    ADD UNIQUE INDEX IF NOT EXISTS idx_financial_negotiation_type (negotiation_id, type),
    ADD CONSTRAINT fk_financial_negotiation FOREIGN KEY (negotiation_id)
        REFERENCES negotiations (id) ON DELETE SET NULL;

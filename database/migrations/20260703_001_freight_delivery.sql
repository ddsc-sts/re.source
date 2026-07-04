-- ============================================================
-- RE.SOURCE
-- Migration: 20260703_001_freight_delivery.sql
-- Dia 4 - Frete e Entrega
-- ============================================================

START TRANSACTION;

-- ============================================================
-- ALTERAÇÕES NA TABELA FREIGHTS
-- ============================================================

ALTER TABLE freights
    ADD COLUMN carrier_company_name VARCHAR(150) NULL AFTER carrier_id,
    ADD COLUMN tracking_code VARCHAR(100) NULL AFTER carrier_company_name,
    ADD COLUMN tracking_url VARCHAR(500) NULL AFTER tracking_code,

    ADD COLUMN quoted_price DECIMAL(12,2) NULL AFTER tracking_url,
    ADD COLUMN contracted_price DECIMAL(12,2) NULL AFTER quoted_price,

    ADD COLUMN estimated_pickup DATETIME NULL AFTER contracted_price,
    ADD COLUMN estimated_delivery DATETIME NULL AFTER estimated_pickup,

    ADD COLUMN picked_up_at DATETIME NULL,
    ADD COLUMN delivered_at DATETIME NULL,

    ADD COLUMN delivery_code_hash CHAR(64) NULL,
    ADD COLUMN delivery_code_expires_at DATETIME NULL,

    ADD COLUMN validated_at DATETIME NULL,

    ADD COLUMN attempts SMALLINT NOT NULL DEFAULT 0,

    ADD COLUMN api_payload JSON NULL;

CREATE INDEX idx_freights_tracking
ON freights(tracking_code);

CREATE INDEX idx_freights_status
ON freights(status);

-- ============================================================
-- CÓDIGOS DE ENTREGA
-- ============================================================

CREATE TABLE delivery_codes (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    freight_id BIGINT UNSIGNED NOT NULL,

    code_hash CHAR(64) NOT NULL,

    generated_by BIGINT UNSIGNED NOT NULL,

    expires_at DATETIME NOT NULL,

    validated_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_delivery_code_freight
        FOREIGN KEY (freight_id)
        REFERENCES freights(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_delivery_code_user
        FOREIGN KEY (generated_by)
        REFERENCES users(id)
        ON DELETE RESTRICT

);

CREATE INDEX idx_delivery_code
ON delivery_codes(freight_id);

-- ============================================================
-- TENTATIVAS DE VALIDAÇÃO
-- ============================================================

CREATE TABLE delivery_attempts (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    freight_id BIGINT UNSIGNED NOT NULL,

    company_id BIGINT UNSIGNED NULL,

    user_id BIGINT UNSIGNED NULL,

    ip_address VARCHAR(45),

    success TINYINT(1) NOT NULL,

    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_attempt_freight
        FOREIGN KEY (freight_id)
        REFERENCES freights(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_attempt_company
        FOREIGN KEY (company_id)
        REFERENCES companies(id)
        ON DELETE SET NULL,

    CONSTRAINT fk_attempt_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL

);

CREATE INDEX idx_attempts_freight
ON delivery_attempts(freight_id);

CREATE INDEX idx_attempts_date
ON delivery_attempts(attempted_at);

COMMIT;
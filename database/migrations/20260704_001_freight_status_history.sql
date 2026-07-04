-- Complemento do Dia 4: historico detalhado do rastreamento.
-- Seguro para bancos que ja executaram 20260703_001_freight_delivery.sql.

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

-- Registra o estado atual de fretes criados antes desta migration.
INSERT INTO freight_status_history (freight_id, status, description, created_by_user_id)
SELECT f.id, f.status, 'Estado inicial importado para o historico.', NULL
FROM freights f
WHERE NOT EXISTS (
    SELECT 1 FROM freight_status_history h WHERE h.freight_id = f.id
);

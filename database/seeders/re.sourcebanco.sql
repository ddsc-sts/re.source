-- ============================================================
--  Re.Source — Marketplace B2B de Materiais Industriais
--  SQL FINAL CONSOLIDADO — v3.1
--  MySQL 8.0+
-- ============================================================

-- ============================================================
--  Re.Source — Marketplace B2B de Materiais Industriais
--  SQL FINAL CONSOLIDADO — v4.0 (MVP academico)
--  MariaDB 10.4+ / MySQL 8.0+
--  Script autocontido para criacao do banco academico local.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `resource`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `resource`;

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- ============================================================
-- 1. PLANOS
-- ============================================================
CREATE TABLE IF NOT EXISTS plans (
    id                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    name                VARCHAR(60)      NOT NULL,
    max_active_listings INT              NOT NULL DEFAULT 3,
    transaction_fee_pct DECIMAL(5,2)     NOT NULL DEFAULT 5.00,
    freight_fee_pct     DECIMAL(5,2)     NOT NULL DEFAULT 8.00,
    monthly_price       DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    created_at          TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO plans (id, name, max_active_listings, transaction_fee_pct, freight_fee_pct, monthly_price) VALUES
    (1, 'Freemium',  3,   5.00, 8.00,   0.00),
    (2, 'Basic',     15,  4.00, 7.00, 149.00),
    (3, 'Pro',       999, 3.00, 5.00, 399.00);


-- ============================================================
-- 2. ENDEREÇOS
-- ============================================================
CREATE TABLE IF NOT EXISTS addresses (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    zip_code    VARCHAR(9)    NOT NULL DEFAULT '',
    street      VARCHAR(200)  NOT NULL DEFAULT '',
    number      VARCHAR(20)   NOT NULL DEFAULT '',
    complement  VARCHAR(100)      NULL,
    district    VARCHAR(100)  NOT NULL DEFAULT '',
    city        VARCHAR(100)  NOT NULL DEFAULT '',
    state       CHAR(2)       NOT NULL,
    lat         DECIMAL(10,7)     NULL,
    lng         DECIMAL(10,7)     NULL,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_city_state (state, city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. EMPRESAS
-- ============================================================
CREATE TABLE IF NOT EXISTS companies (
    id                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    cnpj                  CHAR(14)      NOT NULL,
    razao_social          VARCHAR(200)  NOT NULL,
    nome_fantasia         VARCHAR(200)      NULL,
    slug                  VARCHAR(150)      NULL,
    email                 VARCHAR(150)  NOT NULL,
    phone                 VARCHAR(20)   NOT NULL DEFAULT '',
    responsible_name      VARCHAR(150)  NOT NULL,
    address_id            INT UNSIGNED      NULL,
    logo_url              VARCHAR(500)      NULL,
    segment               VARCHAR(100)      NULL,
    status                ENUM('pending','changes_requested','active','suspended','rejected','inactive') NOT NULL DEFAULT 'pending',
    plan_id               INT UNSIGNED  NOT NULL DEFAULT 1,
    email_verified_at     TIMESTAMP         NULL,
    approved_at           TIMESTAMP         NULL,
    approved_by_user_id   INT UNSIGNED      NULL,
    review_notes          TEXT              NULL,
    reviewed_at           TIMESTAMP         NULL,
    reviewed_by_user_id   INT UNSIGNED      NULL,
    theme                 VARCHAR(20)    NOT NULL DEFAULT 'system',
    notify_proposals      TINYINT(1)     NOT NULL DEFAULT 1,
    notify_chat           TINYINT(1)     NOT NULL DEFAULT 1,
    balance               DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    onboarding_completed  TINYINT(1)    NOT NULL DEFAULT 0,
    suspended_at          TIMESTAMP         NULL,
    deactivated_at        TIMESTAMP         NULL,
    created_at            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  idx_cnpj          (cnpj),
    UNIQUE  idx_company_email (email),
    UNIQUE  idx_company_slug  (slug),
    INDEX   idx_company_status (status),
    CONSTRAINT fk_companies_address FOREIGN KEY (address_id) REFERENCES addresses (id) ON DELETE SET NULL,
    CONSTRAINT fk_companies_plan    FOREIGN KEY (plan_id)    REFERENCES plans (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. USUÁRIOS
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    company_id      INT UNSIGNED  NOT NULL,
    name            VARCHAR(120)  NOT NULL,
    email           VARCHAR(150)  NOT NULL,
    password_hash   VARCHAR(255)  NOT NULL,
    role            ENUM('admin','staff','admin_company','operator') NOT NULL DEFAULT 'admin_company',
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    last_login_at   TIMESTAMP         NULL,
    deleted_at      TIMESTAMP         NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  idx_user_email   (email),
    INDEX   idx_user_login   (email, is_active),
    INDEX   idx_user_company (company_id),
    CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. VERIFICAÇÕES DE E-MAIL
--    (mantida no banco — usada como fallback/auditoria)
-- ============================================================
CREATE TABLE IF NOT EXISTS email_verifications (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    token_hash  CHAR(64)        NOT NULL,
    expires_at  TIMESTAMP       NOT NULL,
    used_at     TIMESTAMP           NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  idx_ev_token (token_hash),
    INDEX   idx_ev_user  (user_id),
    CONSTRAINT fk_ev_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. SESSÕES DE LOGIN
-- ============================================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    token_hash  CHAR(64)        NOT NULL,
    ip_address  VARCHAR(45)         NULL,
    user_agent  TEXT                NULL,
    expires_at  TIMESTAMP       NOT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  idx_session_token (token_hash),
    INDEX   idx_session_user  (user_id),
    INDEX   idx_session_exp   (expires_at),
    CONSTRAINT fk_session_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. RECUPERAÇÃO DE SENHA
-- ============================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    token_hash  CHAR(64)        NOT NULL,
    expires_at  TIMESTAMP       NOT NULL,
    used_at     TIMESTAMP           NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  idx_reset_token (token_hash),
    INDEX   idx_reset_user  (user_id),
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. DOMÍNIOS DE E-MAIL BLOQUEADOS
-- ============================================================
CREATE TABLE IF NOT EXISTS blocked_email_domains (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    domain  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE idx_domain (domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO blocked_email_domains (domain) VALUES
    ('gmail.com'),
    ('hotmail.com'),
    ('outlook.com'),
    ('yahoo.com'),
    ('yahoo.com.br'),
    ('bol.com.br'),
    ('uol.com.br'),
    ('ig.com.br'),
    ('live.com'),
    ('icloud.com'),
    ('protonmail.com'),
    ('me.com');


-- ============================================================
-- 9. CATEGORIAS DE MATERIAIS
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id   INT UNSIGNED     NULL,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(110) NOT NULL,
    icon        VARCHAR(100)     NULL,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  idx_category_slug   (slug),
    INDEX   idx_category_parent (parent_id),
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categories (parent_id, name, slug) VALUES
    (NULL, 'Têxtil',        'textil'),
    (NULL, 'Metal',         'metal'),
    (NULL, 'Plástico',      'plastico'),
    (NULL, 'Madeira',       'madeira'),
    (NULL, 'Papel/Papelão', 'papel-papelao'),
    (NULL, 'Vidro',         'vidro'),
    (NULL, 'Borracha',      'borracha'),
    (NULL, 'Eletrônico',    'eletronico'),
    (NULL, 'Químico',       'quimico'),
    (NULL, 'Outros',        'outros');


-- ============================================================
-- 9A. FATORES DE EMISSÃO PARA INDICADORES ESG
-- ============================================================
CREATE TABLE IF NOT EXISTS emission_factors (
    id                         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    category_id                INT UNSIGNED      NULL,
    factor_kg_co2e_per_kg      DECIMAL(10,4) NOT NULL,
    source_name                VARCHAR(200)  NOT NULL,
    source_url                 VARCHAR(500)      NULL,
    methodology_version        VARCHAR(80)   NOT NULL,
    valid_from                 DATE          NOT NULL,
    created_at                 TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_emission_category (category_id),
    CONSTRAINT fk_factor_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO emission_factors
    (category_id, factor_kg_co2e_per_kg, source_name, source_url, methodology_version, valid_from)
SELECT NULL, 2.5000, 'Fator acadêmico provisório do MVP', NULL, 'MVP-2026.1', '2026-01-01'
WHERE NOT EXISTS (SELECT 1 FROM emission_factors);


-- ============================================================
-- 10. ANÚNCIOS
-- ============================================================
CREATE TABLE IF NOT EXISTS listings (
    id                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    company_id          INT UNSIGNED  NOT NULL,
    created_by_user_id  INT UNSIGNED      NULL,
    type                ENUM('offer','demand') NOT NULL,
    title               VARCHAR(200)  NOT NULL,
    description         TEXT              NULL,
    category_id         INT UNSIGNED  NOT NULL,
    quantity            DECIMAL(12,3) NOT NULL,
    unit                ENUM('kg','ton','m2','m3','unidade','litro','outro') NOT NULL DEFAULT 'kg',
    price               DECIMAL(12,2)     NULL,
    is_negotiable       TINYINT(1)    NOT NULL DEFAULT 0,
    status              ENUM('draft','active','paused','negotiating','concluded','expired') NOT NULL DEFAULT 'draft',
    location_state      CHAR(2)           NULL,
    location_city       VARCHAR(100)      NULL,
    expires_at          TIMESTAMP         NULL,
    expiry_notified     TINYINT(1)    NOT NULL DEFAULT 0,
    views_count         INT UNSIGNED  NOT NULL DEFAULT 0,
    deleted_at          TIMESTAMP         NULL,
    created_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX    idx_listing_company   (company_id),
    INDEX    idx_listing_category  (category_id),
    INDEX    idx_listing_status    (status),
    INDEX    idx_listing_type      (type),
    INDEX    idx_listing_location  (location_state, location_city),
    INDEX    idx_listing_expires   (expires_at),
    INDEX    idx_listing_deleted   (deleted_at),
    FULLTEXT idx_listing_fts       (title, description),
    CONSTRAINT chk_offer_price CHECK (
        (type = 'offer' AND price IS NOT NULL) OR (type = 'demand')
    ),
    CONSTRAINT fk_listings_company  FOREIGN KEY (company_id)         REFERENCES companies (id) ON DELETE CASCADE,
    CONSTRAINT fk_listings_category FOREIGN KEY (category_id)        REFERENCES categories (id),
    CONSTRAINT fk_listing_creator   FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 11. IMAGENS DOS ANÚNCIOS
-- ============================================================
CREATE TABLE IF NOT EXISTS listing_images (
    id          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    listing_id  INT UNSIGNED     NOT NULL,
    url         VARCHAR(500)     NOT NULL,
    `order`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_li_listing (listing_id),
    CONSTRAINT fk_listing_images_listing FOREIGN KEY (listing_id) REFERENCES listings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 12. FAVORITOS
-- ============================================================
CREATE TABLE IF NOT EXISTS favorites (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id  INT UNSIGNED NOT NULL,
    listing_id  INT UNSIGNED NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  idx_fav_unique (company_id, listing_id),
    CONSTRAINT fk_favorites_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_listing FOREIGN KEY (listing_id) REFERENCES listings  (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 13. NEGOCIAÇÕES
-- ============================================================
CREATE TABLE IF NOT EXISTS negotiations (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    listing_id        INT UNSIGNED  NOT NULL,
    buyer_company_id  INT UNSIGNED  NOT NULL,
    seller_company_id INT UNSIGNED  NOT NULL,
    status            ENUM('open','proposal_sent','buyer_accepted','seller_accepted','accepted','awaiting_freight','shipping','delivered','concluded','cancelled') NOT NULL DEFAULT 'open',
    protocol_number   VARCHAR(30)       NULL UNIQUE,
    proposed_quantity DECIMAL(12,3)     NULL,
    proposed_price    DECIMAL(12,2)     NULL,
    proposed_total    DECIMAL(12,2)     NULL,
    agreement_at      TIMESTAMP         NULL,
    concluded_at      TIMESTAMP         NULL,
    cancelled_by      INT UNSIGNED      NULL,
    cancel_reason     TEXT              NULL,
    created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  idx_neg_unique_pair (listing_id, buyer_company_id, seller_company_id),
    INDEX   idx_neg_buyer       (buyer_company_id),
    INDEX   idx_neg_seller      (seller_company_id),
    INDEX   idx_neg_status      (status),
    CONSTRAINT chk_self_negotiation CHECK (buyer_company_id <> seller_company_id),
    CONSTRAINT fk_neg_listing FOREIGN KEY (listing_id)        REFERENCES listings  (id),
    CONSTRAINT fk_neg_buyer   FOREIGN KEY (buyer_company_id)  REFERENCES companies (id),
    CONSTRAINT fk_neg_seller  FOREIGN KEY (seller_company_id) REFERENCES companies (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 13A. PASSAPORTES DIGITAIS DE MATERIAIS
-- ============================================================
CREATE TABLE IF NOT EXISTS material_passports (
    id                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    negotiation_id      INT UNSIGNED  NOT NULL,
    passport_code       VARCHAR(30)   NOT NULL,
    public_token        CHAR(64)      NOT NULL,
    material_name       VARCHAR(200)  NOT NULL,
    quantity_kg         DECIMAL(14,3) NOT NULL,
    origin_company      VARCHAR(200)  NOT NULL,
    destination_company VARCHAR(200)  NOT NULL,
    reused_at           DATETIME      NOT NULL,
    created_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE idx_passport_negotiation (negotiation_id),
    UNIQUE idx_passport_code (passport_code),
    UNIQUE idx_passport_token (public_token),
    CONSTRAINT fk_passport_negotiation FOREIGN KEY (negotiation_id) REFERENCES negotiations (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 14. PROPOSTAS FORMAIS
-- ============================================================
CREATE TABLE IF NOT EXISTS proposals (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    negotiation_id    INT UNSIGNED  NOT NULL,
    sender_company_id INT UNSIGNED  NOT NULL,
    quantity          DECIMAL(12,3) NOT NULL,
    unit_price        DECIMAL(12,2) NOT NULL,
    total_price       DECIMAL(12,2) NOT NULL,
    delivery_deadline DATE              NULL,
    responsible_for_freight ENUM('buyer','seller','shared') NOT NULL DEFAULT 'buyer',
    notes             TEXT              NULL,
    status            ENUM('pending','accepted','refused','cancelled') NOT NULL DEFAULT 'pending',
    buyer_accepted_at TIMESTAMP         NULL,
    seller_accepted_at TIMESTAMP        NULL,
    refused_by_company_id INT UNSIGNED  NULL,
    refusal_reason    TEXT              NULL,
    cancelled_by_company_id INT UNSIGNED NULL,
    cancel_reason     TEXT              NULL,
    responded_at      TIMESTAMP         NULL,
    created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_prop_negotiation (negotiation_id),
    INDEX idx_prop_status (negotiation_id, status),
    CONSTRAINT fk_proposals_negotiation FOREIGN KEY (negotiation_id)    REFERENCES negotiations (id) ON DELETE CASCADE,
    CONSTRAINT fk_proposals_sender      FOREIGN KEY (sender_company_id) REFERENCES companies (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 15. MENSAGENS DO CHAT
-- ============================================================
CREATE TABLE IF NOT EXISTS messages (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    negotiation_id  INT UNSIGNED    NOT NULL,
    sender_user_id  INT UNSIGNED    NOT NULL,
    content         TEXT                NULL,
    file_url        VARCHAR(500)        NULL,
    file_type       ENUM('pdf','jpeg','png','xlsx') NULL,
    read_at         TIMESTAMP           NULL,
    edited_at       TIMESTAMP           NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_msg_negotiation (negotiation_id),
    INDEX idx_msg_polling     (negotiation_id, id),
    INDEX idx_msg_sender      (sender_user_id),
    CONSTRAINT chk_message_content CHECK (content IS NOT NULL OR file_url IS NOT NULL),
    CONSTRAINT fk_messages_negotiation FOREIGN KEY (negotiation_id) REFERENCES negotiations (id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender      FOREIGN KEY (sender_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 16. TRANSPORTADORAS
-- ============================================================
CREATE TABLE IF NOT EXISTS carriers (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name              VARCHAR(150) NOT NULL,
    api_url           VARCHAR(500) NOT NULL,
    api_key_encrypted VARCHAR(500) NOT NULL,
    is_active         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 17. COTACOES DE FRETE
-- ============================================================
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
    CONSTRAINT fk_fq_negotiation FOREIGN KEY (negotiation_id) REFERENCES negotiations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 18. FRETES
-- ============================================================
CREATE TABLE IF NOT EXISTS freights (
    id                      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    negotiation_id          INT UNSIGNED  NOT NULL,
    carrier_id              INT UNSIGNED      NULL,
    carrier_company_name    VARCHAR(150)      NULL,
    service_name            VARCHAR(100)      NULL,
    origin_address_id       INT UNSIGNED      NULL,
    destination_address_id  INT UNSIGNED      NULL,
    modality                ENUM('rodoviario','expresso','dedicado','outro') NULL,
    quote_value             DECIMAL(10,2)     NULL,
    platform_fee            DECIMAL(10,2)     NULL,
    total_value             DECIMAL(10,2)     NULL,
    delivery_days           SMALLINT UNSIGNED NULL,
    estimated_pickup        DATETIME          NULL,
    estimated_delivery      DATETIME          NULL,
    tracking_code           VARCHAR(100)      NULL,
    tracking_url            VARCHAR(500)      NULL,
    status                  ENUM('quoted','contracted','preparing','in_transit','out_for_delivery','delivered','concluded','cancelled') NOT NULL DEFAULT 'quoted',
    contracted_at           TIMESTAMP         NULL,
    picked_up_at            DATETIME          NULL,
    delivered_at            DATETIME          NULL,
    delivery_code_hash      VARCHAR(255)      NULL,
    delivery_code_expires_at DATETIME         NULL,
    delivery_code_attempts  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    delivery_code_used_at   DATETIME          NULL,
    validated_at            DATETIME          NULL,
    api_payload             JSON              NULL,
    created_at              TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE INDEX idx_freight_negotiation_unique (negotiation_id),
    INDEX idx_freight_negotiation (negotiation_id),
    INDEX idx_freight_status (status),
    INDEX idx_freight_tracking (tracking_code),
    CONSTRAINT fk_freight_negotiation  FOREIGN KEY (negotiation_id)         REFERENCES negotiations (id),
    CONSTRAINT fk_freight_carrier      FOREIGN KEY (carrier_id)             REFERENCES carriers (id) ON DELETE SET NULL,
    CONSTRAINT fk_freight_origin       FOREIGN KEY (origin_address_id)      REFERENCES addresses (id) ON DELETE SET NULL,
    CONSTRAINT fk_freight_destination  FOREIGN KEY (destination_address_id) REFERENCES addresses (id) ON DELETE SET NULL
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
    CONSTRAINT fk_attempt_freight FOREIGN KEY (freight_id) REFERENCES freights (id) ON DELETE CASCADE,
    CONSTRAINT fk_attempt_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE SET NULL,
    CONSTRAINT fk_attempt_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
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


-- ============================================================
-- 18. TRANSAÇÕES FINANCEIRAS
-- ============================================================
CREATE TABLE IF NOT EXISTS transactions (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    negotiation_id  INT UNSIGNED      NULL,
    company_id      INT UNSIGNED  NOT NULL,
    type            ENUM('platform_fee','freight_fee','plan_subscription','listing_boost') NOT NULL,
    amount          DECIMAL(12,2) NOT NULL,
    status          ENUM('pending','paid','overdue','cancelled') NOT NULL DEFAULT 'pending',
    due_date        DATE              NULL,
    paid_at         TIMESTAMP         NULL,
    gateway_ref     VARCHAR(200)      NULL,
    receipt_url     VARCHAR(500)      NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_tx_company     (company_id),
    INDEX idx_tx_negotiation (negotiation_id),
    INDEX idx_tx_status      (status),
    INDEX idx_tx_due         (due_date),
    CONSTRAINT fk_tx_company     FOREIGN KEY (company_id)     REFERENCES companies    (id),
    CONSTRAINT fk_tx_negotiation FOREIGN KEY (negotiation_id) REFERENCES negotiations (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 19. NOTIFICAÇÕES
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id  INT UNSIGNED    NOT NULL,
    user_id     INT UNSIGNED        NULL,
    type        ENUM(
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
                    'withdrawal_requested',
                    'withdrawal_approved',
                    'withdrawal_rejected',
                    'account_approved',
                    'account_changes_requested',
                    'account_rejected',
                    'account_suspended',
                    'account_reactivated'
                ) NOT NULL,
    title       VARCHAR(200)    NOT NULL,
    body        TEXT                NULL,
    data_json   JSON                NULL,
    is_seen     TINYINT(1)      NOT NULL DEFAULT 0,
    read_at     TIMESTAMP           NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_notif_company (company_id),
    INDEX idx_notif_user    (user_id),
    INDEX idx_notif_unseen  (company_id, is_seen),
    CONSTRAINT fk_notif_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_user    FOREIGN KEY (user_id)    REFERENCES users     (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- CONFIGURAÇÕES DO SISTEMA
-- ============================================================
CREATE TABLE IF NOT EXISTS system_settings (
    setting_key        VARCHAR(100) NOT NULL,
    setting_value      TEXT         NULL,
    updated_by_user_id INT UNSIGNED NULL,
    updated_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key),
    CONSTRAINT fk_system_settings_user FOREIGN KEY (updated_by_user_id)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES
('platform_name', 'Re.Source'),
('support_email', 'contato@resource.com.br'),
('support_whatsapp', '5547999999999'),
('maintenance_message', ''),
('demo_mode', '1');

-- ============================================================
-- 20. UPLOADS CENTRALIZADOS
-- ============================================================
CREATE TABLE IF NOT EXISTS uploads (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id       INT UNSIGNED        NULL,
    user_id          INT UNSIGNED        NULL,
    entity_type      VARCHAR(60)         NULL,
    entity_id        INT UNSIGNED        NULL,
    original_name    VARCHAR(255)    NOT NULL,
    stored_name      VARCHAR(255)    NOT NULL,
    url              VARCHAR(500)    NOT NULL,
    storage_provider ENUM('s3','r2','minio','local') NOT NULL DEFAULT 's3',
    checksum_sha256  CHAR(64)            NULL,
    mime_type        VARCHAR(100)    NOT NULL,
    size_bytes       INT UNSIGNED    NOT NULL,
    is_public        TINYINT(1)      NOT NULL DEFAULT 0,
    created_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_upload_entity  (entity_type, entity_id),
    INDEX idx_upload_company (company_id),
    CONSTRAINT fk_upload_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE SET NULL,
    CONSTRAINT fk_upload_user    FOREIGN KEY (user_id)    REFERENCES users     (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 21. CACHE DE VALIDAÇÕES DE CNPJ
-- ============================================================
CREATE TABLE IF NOT EXISTS cnpj_validations (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cnpj              CHAR(14)     NOT NULL,
    status            VARCHAR(30)  NOT NULL,
    razao_social      VARCHAR(200)     NULL,
    api_response_json JSON             NULL,
    validated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_cnpj_val (cnpj, validated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 22. LOG DE AUDITORIA
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED        NULL,
    company_id      INT UNSIGNED        NULL,
    action          VARCHAR(100)    NOT NULL,
    severity        ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
    entity_type     VARCHAR(60)         NULL,
    entity_id       INT UNSIGNED        NULL,
    old_values_json JSON                NULL,
    new_values_json JSON                NULL,
    ip_address      VARCHAR(45)         NULL,
    user_agent      TEXT                NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_audit_user     (user_id),
    INDEX idx_audit_company  (company_id),
    INDEX idx_audit_entity   (entity_type, entity_id),
    INDEX idx_audit_action   (action),
    INDEX idx_audit_severity (severity, created_at),
    INDEX idx_audit_date     (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 23. HISTÓRICO DE STATUS DOS ANÚNCIOS
-- ============================================================
CREATE TABLE IF NOT EXISTS listing_status_history (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    listing_id  INT UNSIGNED NOT NULL,
    from_status VARCHAR(30)      NULL,
    to_status   VARCHAR(30)  NOT NULL,
    changed_by  INT UNSIGNED     NULL,
    note        TEXT             NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_lsh_listing (listing_id),
    CONSTRAINT fk_lsh_listing FOREIGN KEY (listing_id) REFERENCES listings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 24. ALERTAS DE BUSCA
-- ============================================================
CREATE TABLE IF NOT EXISTS search_alerts (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id       INT UNSIGNED NOT NULL,
    filters_json     JSON         NOT NULL,
    label            VARCHAR(100)     NULL,
    is_active        TINYINT(1)   NOT NULL DEFAULT 1,
    last_notified_at TIMESTAMP        NULL,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_sa_company (company_id),
    CONSTRAINT fk_sa_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 25. HISTORICO DE VISUALIZACOES
-- ============================================================
CREATE TABLE IF NOT EXISTS views_history (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    listing_id  INT UNSIGNED NOT NULL,
    company_id  INT UNSIGNED     NULL,
    session_id  VARCHAR(128) NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_views_listing_date (listing_id, created_at),
    INDEX idx_views_company (company_id),
    CONSTRAINT fk_views_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    CONSTRAINT fk_views_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 26. MOVIMENTACOES FINANCEIRAS DO SALDO
-- ============================================================
CREATE TABLE IF NOT EXISTS financial_transactions (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id  INT UNSIGNED NOT NULL,
    negotiation_id INT UNSIGNED NULL,
    withdrawal_id INT UNSIGNED NULL,
    type        ENUM('deposit','withdrawal','sale','refund') NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    status      ENUM('pending','completed','failed','canceled') NOT NULL DEFAULT 'pending',
    description VARCHAR(255) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_financial_company_status (company_id, status),
    UNIQUE INDEX idx_financial_negotiation_type (negotiation_id, type),
    UNIQUE INDEX idx_financial_withdrawal (withdrawal_id),
    CONSTRAINT fk_financial_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_financial_negotiation FOREIGN KEY (negotiation_id) REFERENCES negotiations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 27. SOLICITACOES DE SAQUE
CREATE TABLE IF NOT EXISTS withdrawals (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id INT UNSIGNED NOT NULL,
    amount     DECIMAL(10,2) NOT NULL,
    method     ENUM('pix','ted') NOT NULL DEFAULT 'pix',
    pix_key    VARCHAR(255) NULL,
    pix_key_type VARCHAR(20) NULL,
    bank_code VARCHAR(10) NULL,
    bank_name VARCHAR(100) NULL,
    agency VARCHAR(20) NULL,
    account_number VARCHAR(30) NULL,
    account_digit VARCHAR(10) NULL,
    account_type ENUM('checking','savings') NULL,
    account_holder_name VARCHAR(150) NULL,
    account_holder_document VARCHAR(20) NULL,
    request_note VARCHAR(500) NULL,
    request_token CHAR(64) NOT NULL,
    balance_before DECIMAL(10,2) NOT NULL DEFAULT 0,
    balance_after DECIMAL(10,2) NOT NULL DEFAULT 0,
    reserved_at TIMESTAMP NULL,
    terms_accepted_at TIMESTAMP NULL,
    reviewed_at TIMESTAMP NULL,
    reviewed_by_user_id INT UNSIGNED NULL,
    rejection_reason VARCHAR(500) NULL,
    admin_note VARCHAR(500) NULL,
    status     ENUM('pending', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_withdrawals_company_status (company_id, status),
    UNIQUE INDEX idx_withdrawals_request_token (request_token),
    CONSTRAINT fk_withdrawals_company FOREIGN KEY (company_id)
        REFERENCES companies (id) ON DELETE CASCADE,
    CONSTRAINT fk_withdrawals_reviewer FOREIGN KEY (reviewed_by_user_id)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE financial_transactions
    ADD CONSTRAINT fk_financial_withdrawal FOREIGN KEY (withdrawal_id)
        REFERENCES withdrawals(id) ON DELETE SET NULL;


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- RESUMO — v4.0
-- 30 tabelas: plans, addresses, companies, users,
--   email_verifications, user_sessions, password_resets,
--   blocked_email_domains, categories, listings,
--   listing_images, favorites, negotiations, proposals,
--   messages, carriers, freight_quotes, freights, delivery_attempts,
--   freight_status_history, transactions,
--   notifications, uploads, cnpj_validations,
--   audit_logs, listing_status_history, search_alerts,
--   views_history, financial_transactions, withdrawals
--
-- Fluxo de cadastro:
--   Dados ficam em $_SESSION até confirmação do e-mail.
--   Só após clicar no link o usuário é gravado no banco
--   via AuthController::processVerificar().
-- ============================================================


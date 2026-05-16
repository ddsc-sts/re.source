-- ============================================================
--  Re.Source — Marketplace B2B de Materiais Industriais
--  SQL FINAL CONSOLIDADO — v3.0
--  Inclui: estrutura base + melhorias + ajustes de produção
--  MySQL 8.0+
-- ============================================================


use resource;

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- ============================================================
-- 1. PLANOS
-- ============================================================
CREATE TABLE plans (
    id                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    name                VARCHAR(60)      NOT NULL,
    max_active_listings INT              NOT NULL DEFAULT 3,
    transaction_fee_pct DECIMAL(5,2)     NOT NULL DEFAULT 5.00,
    freight_fee_pct     DECIMAL(5,2)     NOT NULL DEFAULT 8.00,
    monthly_price       DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
    created_at          TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO plans (name, max_active_listings, transaction_fee_pct, freight_fee_pct, monthly_price) VALUES
    ('Freemium',  3,   5.00, 8.00,   0.00),
    ('Basic',     15,  4.00, 7.00, 149.00),
    ('Pro',       999, 3.00, 5.00, 399.00);


-- ============================================================
-- 2. ENDEREÇOS
-- ============================================================
CREATE TABLE addresses (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    zip_code    VARCHAR(9)    NOT NULL,
    street      VARCHAR(200)  NOT NULL,
    number      VARCHAR(20)   NOT NULL,
    complement  VARCHAR(100)      NULL,
    district    VARCHAR(100)  NOT NULL,
    city        VARCHAR(100)  NOT NULL,
    state       CHAR(2)       NOT NULL,
    lat         DECIMAL(10,7)     NULL,
    lng         DECIMAL(10,7)     NULL,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_city_state (state, city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

select * from users;
-- ============================================================
-- 3. EMPRESAS
-- ============================================================
CREATE TABLE companies (
    id                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    cnpj                  CHAR(14)      NOT NULL,
    razao_social          VARCHAR(200)  NOT NULL,
    nome_fantasia         VARCHAR(200)      NULL,
    slug                  VARCHAR(150)      NULL,           -- URLs amigáveis
    email                 VARCHAR(150)  NOT NULL,           -- RN-04: e-mail corporativo
    phone                 VARCHAR(20)   NOT NULL,
    address_id            INT UNSIGNED      NULL,
    logo_url              VARCHAR(500)      NULL,
    segment               VARCHAR(100)      NULL,
    status                ENUM('active','suspended','inactive') NOT NULL DEFAULT 'active',
    plan_id               INT UNSIGNED  NOT NULL DEFAULT 1,
    email_verified_at     TIMESTAMP         NULL,           -- RF-03
    onboarding_completed  TINYINT(1)    NOT NULL DEFAULT 0, -- controle de funil
    suspended_at          TIMESTAMP         NULL,           -- RN-24
    deactivated_at        TIMESTAMP         NULL,           -- RF-06
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
CREATE TABLE users (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    company_id      INT UNSIGNED  NOT NULL,
    name            VARCHAR(120)  NOT NULL,
    email           VARCHAR(150)  NOT NULL,
    password_hash   VARCHAR(255)  NOT NULL,                 -- RNF-03: bcrypt cost 12+
    role            ENUM('admin_company','operator') NOT NULL DEFAULT 'admin_company',
    is_active       TINYINT(1)    NOT NULL DEFAULT 1,
    last_login_at   TIMESTAMP         NULL,
    deleted_at      TIMESTAMP         NULL,                 -- soft delete (LGPD)
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  idx_user_email  (email),
    INDEX   idx_user_login  (email, is_active),             -- performance no login
    INDEX   idx_user_company (company_id),
    CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. SESSÕES DE LOGIN
-- ============================================================
CREATE TABLE user_sessions (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    token_hash  CHAR(64)        NOT NULL,                   -- SHA-256 do token real
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
-- 6. TOKENS DE RECUPERAÇÃO DE SENHA
-- ============================================================
CREATE TABLE password_resets (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    token_hash  CHAR(64)        NOT NULL,                   -- SHA-256 do token real
    expires_at  TIMESTAMP       NOT NULL,                   -- RF-04: 1 hora
    used_at     TIMESTAMP           NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  idx_reset_token (token_hash),
    INDEX   idx_reset_user  (user_id),
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. DOMÍNIOS DE E-MAIL BLOQUEADOS (RN-04)
-- ============================================================
CREATE TABLE blocked_email_domains (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    domain  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE idx_domain (domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO blocked_email_domains (domain) VALUES
    ('gmail.com'), ('hotmail.com'), ('outlook.com'), ('yahoo.com'),
    ('yahoo.com.br'), ('bol.com.br'), ('uol.com.br'), ('ig.com.br'),
    ('live.com'), ('icloud.com'), ('protonmail.com'), ('me.com');


-- ============================================================
-- 8. CATEGORIAS DE MATERIAIS (RF-09)
-- ============================================================
CREATE TABLE categories (
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

INSERT INTO categories (parent_id, name, slug) VALUES
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
-- 9. ANÚNCIOS (RF-07/08)
-- ============================================================
CREATE TABLE listings (
    id                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    company_id          INT UNSIGNED  NOT NULL,
    created_by_user_id  INT UNSIGNED      NULL,             -- quem na empresa criou
    type                ENUM('offer','demand') NOT NULL,
    title               VARCHAR(200)  NOT NULL,
    description         TEXT              NULL,
    category_id         INT UNSIGNED  NOT NULL,
    quantity            DECIMAL(12,3) NOT NULL,
    unit                ENUM('kg','ton','m2','m3','unidade','litro','outro') NOT NULL DEFAULT 'kg',
    price               DECIMAL(12,2)     NULL,             -- RN-11: obrigatório em offer
    is_negotiable       TINYINT(1)    NOT NULL DEFAULT 0,
    status              ENUM('draft','active','paused','negotiating','concluded','expired') NOT NULL DEFAULT 'draft',
    location_state      CHAR(2)           NULL,
    location_city       VARCHAR(100)      NULL,
    expires_at          TIMESTAMP         NULL,             -- RF-12
    expiry_notified     TINYINT(1)    NOT NULL DEFAULT 0,
    views_count         INT UNSIGNED  NOT NULL DEFAULT 0,
    deleted_at          TIMESTAMP         NULL,             -- soft delete
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
    FULLTEXT idx_listing_fts       (title, description),    -- RF-15: busca full-text
    CONSTRAINT chk_offer_price CHECK (
        (type = 'offer' AND price IS NOT NULL) OR (type = 'demand')
    ),
    CONSTRAINT fk_listings_company  FOREIGN KEY (company_id)         REFERENCES companies (id) ON DELETE CASCADE,
    CONSTRAINT fk_listings_category FOREIGN KEY (category_id)        REFERENCES categories (id),
    CONSTRAINT fk_listing_creator   FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 10. IMAGENS DOS ANÚNCIOS (RF-10)
-- ============================================================
CREATE TABLE listing_images (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    listing_id  INT UNSIGNED  NOT NULL,
    url         VARCHAR(500)  NOT NULL,
    `order`     TINYINT UNSIGNED NOT NULL DEFAULT 0,        -- até 10 imagens
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_li_listing (listing_id),
    CONSTRAINT fk_listing_images_listing FOREIGN KEY (listing_id) REFERENCES listings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 11. FAVORITOS (RF-20)
-- ============================================================
CREATE TABLE favorites (
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
-- 12. NEGOCIAÇÕES (RF-21/25)
-- ============================================================
CREATE TABLE negotiations (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    listing_id        INT UNSIGNED  NOT NULL,
    buyer_company_id  INT UNSIGNED  NOT NULL,
    seller_company_id INT UNSIGNED  NOT NULL,
    status            ENUM('open','proposal_sent','accepted','concluded','cancelled') NOT NULL DEFAULT 'open',
    protocol_number   VARCHAR(30)       NULL UNIQUE,        -- RN-14: gerado ao aceitar
    proposed_quantity DECIMAL(12,3)     NULL,
    proposed_price    DECIMAL(12,2)     NULL,
    proposed_total    DECIMAL(12,2)     NULL,
    concluded_at      TIMESTAMP         NULL,
    cancelled_by      INT UNSIGNED      NULL,
    cancel_reason     TEXT              NULL,
    created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  idx_neg_unique_pair (listing_id, buyer_company_id), -- RN-12
    INDEX   idx_neg_buyer       (buyer_company_id),
    INDEX   idx_neg_seller      (seller_company_id),
    INDEX   idx_neg_status      (status),
    CONSTRAINT chk_self_negotiation CHECK (buyer_company_id <> seller_company_id), -- RN-08
    CONSTRAINT fk_neg_listing FOREIGN KEY (listing_id)        REFERENCES listings  (id),
    CONSTRAINT fk_neg_buyer   FOREIGN KEY (buyer_company_id)  REFERENCES companies (id),
    CONSTRAINT fk_neg_seller  FOREIGN KEY (seller_company_id) REFERENCES companies (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 13. PROPOSTAS FORMAIS (RF-24/25)
-- ============================================================
CREATE TABLE proposals (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    negotiation_id    INT UNSIGNED  NOT NULL,
    sender_company_id INT UNSIGNED  NOT NULL,
    quantity          DECIMAL(12,3) NOT NULL,
    unit_price        DECIMAL(12,2) NOT NULL,
    total_price       DECIMAL(12,2) NOT NULL,
    delivery_deadline DATE              NULL,
    notes             TEXT              NULL,
    status            ENUM('pending','accepted','refused') NOT NULL DEFAULT 'pending',
    responded_at      TIMESTAMP         NULL,
    created_at        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_prop_negotiation (negotiation_id),
    CONSTRAINT fk_proposals_negotiation FOREIGN KEY (negotiation_id)    REFERENCES negotiations (id) ON DELETE CASCADE,
    CONSTRAINT fk_proposals_sender      FOREIGN KEY (sender_company_id) REFERENCES companies (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 14. MENSAGENS DO CHAT (RF-22/23)
-- ============================================================
CREATE TABLE messages (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    negotiation_id  INT UNSIGNED    NOT NULL,
    sender_user_id  INT UNSIGNED    NOT NULL,
    content         TEXT                NULL,
    file_url        VARCHAR(500)        NULL,
    file_type       ENUM('pdf','jpeg','png','xlsx') NULL,
    read_at         TIMESTAMP           NULL,
    edited_at       TIMESTAMP           NULL,               -- edição de mensagem
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_msg_negotiation (negotiation_id),
    INDEX idx_msg_sender      (sender_user_id),
    CONSTRAINT chk_message_content CHECK (content IS NOT NULL OR file_url IS NOT NULL),
    CONSTRAINT fk_messages_negotiation FOREIGN KEY (negotiation_id) REFERENCES negotiations (id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender      FOREIGN KEY (sender_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 15. TRANSPORTADORAS (RF-44)
-- ============================================================
CREATE TABLE carriers (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name              VARCHAR(150) NOT NULL,
    api_url           VARCHAR(500) NOT NULL,
    api_key_encrypted VARCHAR(500) NOT NULL,
    is_active         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 16. FRETES (RF-29 a RF-33)
-- ============================================================
CREATE TABLE freights (
    id                      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    negotiation_id          INT UNSIGNED  NOT NULL,
    carrier_id              INT UNSIGNED      NULL,
    origin_address_id       INT UNSIGNED      NULL,
    destination_address_id  INT UNSIGNED      NULL,
    modality                ENUM('rodoviario','expresso','dedicado','outro') NULL,
    quote_value             DECIMAL(10,2)     NULL,
    platform_fee            DECIMAL(10,2)     NULL,         -- RN-19
    total_value             DECIMAL(10,2)     NULL,
    tracking_code           VARCHAR(100)      NULL,         -- RF-32
    tracking_url            VARCHAR(500)      NULL,
    status                  ENUM('quoted','contracted','in_transit','delivered','cancelled') NOT NULL DEFAULT 'quoted',
    contracted_at           TIMESTAMP         NULL,
    created_at              TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_freight_negotiation (negotiation_id),
    CONSTRAINT fk_freight_negotiation  FOREIGN KEY (negotiation_id)         REFERENCES negotiations (id),
    CONSTRAINT fk_freight_carrier      FOREIGN KEY (carrier_id)             REFERENCES carriers (id) ON DELETE SET NULL,
    CONSTRAINT fk_freight_origin       FOREIGN KEY (origin_address_id)      REFERENCES addresses (id) ON DELETE SET NULL,
    CONSTRAINT fk_freight_destination  FOREIGN KEY (destination_address_id) REFERENCES addresses (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 17. TRANSAÇÕES FINANCEIRAS (RF-34 a RF-38)
-- ============================================================
CREATE TABLE transactions (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    negotiation_id  INT UNSIGNED      NULL,
    company_id      INT UNSIGNED  NOT NULL,
    type            ENUM('platform_fee','freight_fee','plan_subscription','listing_boost') NOT NULL,
    amount          DECIMAL(12,2) NOT NULL,
    status          ENUM('pending','paid','overdue','cancelled') NOT NULL DEFAULT 'pending',
    due_date        DATE              NULL,
    paid_at         TIMESTAMP         NULL,
    gateway_ref     VARCHAR(200)      NULL,                 -- RF-36
    receipt_url     VARCHAR(500)      NULL,                 -- RF-37
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_tx_company     (company_id),
    INDEX idx_tx_negotiation (negotiation_id),
    INDEX idx_tx_status      (status),
    INDEX idx_tx_due         (due_date),
    CONSTRAINT fk_tx_company     FOREIGN KEY (company_id)     REFERENCES companies     (id),
    CONSTRAINT fk_tx_negotiation FOREIGN KEY (negotiation_id) REFERENCES negotiations  (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 18. NOTIFICAÇÕES (RF-27)
-- ============================================================
CREATE TABLE notifications (
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
                    'account_suspended'
                ) NOT NULL,
    title       VARCHAR(200)    NOT NULL,
    body        TEXT                NULL,
    data_json   JSON                NULL,
    is_seen     TINYINT(1)      NOT NULL DEFAULT 0,         -- flag rápida para badge
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
-- 19. UPLOADS CENTRALIZADOS
-- ============================================================
CREATE TABLE uploads (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id       INT UNSIGNED        NULL,
    user_id          INT UNSIGNED        NULL,
    entity_type      VARCHAR(60)         NULL,  -- listing, message, company, negotiation
    entity_id        INT UNSIGNED        NULL,
    original_name    VARCHAR(255)    NOT NULL,
    stored_name      VARCHAR(255)    NOT NULL,
    url              VARCHAR(500)    NOT NULL,
    storage_provider ENUM('s3','r2','minio','local') NOT NULL DEFAULT 's3',
    checksum_sha256  CHAR(64)            NULL,  -- integridade + deduplicação
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
-- 20. CACHE DE VALIDAÇÕES DE CNPJ (RF-02)
-- ============================================================
CREATE TABLE cnpj_validations (
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
-- 21. LOG DE AUDITORIA (RF-45)
-- ============================================================
CREATE TABLE audit_logs (
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
-- 22. HISTÓRICO DE STATUS DOS ANÚNCIOS (RF-11)
-- ============================================================
CREATE TABLE listing_status_history (
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
-- 23. ALERTAS DE BUSCA (pós-MVP)
-- ============================================================
CREATE TABLE search_alerts (
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


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- RESUMO FINAL
-- 23 tabelas:
--   plans, addresses, companies, users,
--   user_sessions, password_resets, blocked_email_domains,
--   categories, listings, listing_images, favorites,
--   negotiations, proposals, messages,
--   carriers, freights, transactions,
--   notifications, uploads, cnpj_validations,
--   audit_logs, listing_status_history, search_alerts
-- ============================================================
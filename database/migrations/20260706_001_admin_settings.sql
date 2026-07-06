-- Configurações simples do painel administrativo.
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

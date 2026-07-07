<?php

declare(strict_types=1);

final class MySqlTestDatabase
{
    private PDO $adminPdo;
    private PDO $pdo;
    private string $databaseName;

    public function __construct()
    {
        $this->databaseName = 're_source_test_' . str_replace('.', '_', uniqid('', true));
        $this->adminPdo = new PDO(
            'mysql:host=127.0.0.1;port=3306;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        $this->adminPdo->exec('CREATE DATABASE `' . $this->databaseName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->pdo = new PDO(
            'mysql:host=127.0.0.1;port=3306;dbname=' . $this->databaseName . ';charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function installFinanceSchema(): void
    {
        $schema = [
            "CREATE TABLE companies (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                razao_social VARCHAR(150) NULL,
                nome_fantasia VARCHAR(150) NULL,
                balance DECIMAL(10,2) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE withdrawals (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                company_id INT UNSIGNED NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                method ENUM('pix','ted') NOT NULL DEFAULT 'pix',
                pix_key VARCHAR(255) NULL,
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
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                reviewed_at TIMESTAMP NULL,
                reviewed_by_user_id INT UNSIGNED NULL,
                rejection_reason VARCHAR(500) NULL,
                admin_note VARCHAR(500) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY idx_withdrawals_request_token (request_token)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE financial_transactions (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                company_id INT UNSIGNED NOT NULL,
                withdrawal_id INT UNSIGNED NULL,
                negotiation_id INT UNSIGNED NULL,
                type VARCHAR(20) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                status VARCHAR(20) NOT NULL,
                description VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_financial_withdrawal (withdrawal_id),
                UNIQUE KEY idx_financial_negotiation_type (negotiation_id, type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE audit_logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                company_id INT UNSIGNED NOT NULL,
                action VARCHAR(100) NOT NULL,
                severity VARCHAR(20) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id INT UNSIGNED NOT NULL,
                old_values_json JSON NULL,
                new_values_json JSON NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE notifications (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                company_id INT UNSIGNED NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(150) NOT NULL,
                body TEXT NOT NULL,
                data_json JSON NULL,
                is_seen TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE negotiations (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                seller_company_id INT UNSIGNED NOT NULL,
                proposed_total DECIMAL(10,2) NOT NULL DEFAULT 0,
                status VARCHAR(30) NOT NULL,
                concluded_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];

        foreach ($schema as $sql) {
            $this->pdo->exec($sql);
        }
    }

    public function installRouteSmokeSchema(): void
    {
        $schema = [
            "CREATE TABLE companies (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                razao_social VARCHAR(150) NULL,
                nome_fantasia VARCHAR(150) NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'active',
                theme VARCHAR(20) NOT NULL DEFAULT 'light',
                logo_url VARCHAR(255) NULL,
                review_notes TEXT NULL,
                reviewed_at DATETIME NULL,
                address_id INT UNSIGNED NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE users (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                company_id INT UNSIGNED NOT NULL,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(190) NOT NULL,
                role VARCHAR(30) NOT NULL DEFAULT 'admin_company',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                deleted_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE listings (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                company_id INT UNSIGNED NOT NULL,
                title VARCHAR(200) NOT NULL,
                type VARCHAR(20) NOT NULL DEFAULT 'offer',
                unit VARCHAR(20) NOT NULL DEFAULT 'kg',
                status VARCHAR(30) NOT NULL DEFAULT 'active',
                deleted_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE negotiations (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                listing_id INT UNSIGNED NOT NULL,
                buyer_company_id INT UNSIGNED NOT NULL,
                seller_company_id INT UNSIGNED NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'open',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE messages (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                negotiation_id INT UNSIGNED NOT NULL,
                sender_user_id INT UNSIGNED NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                read_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE categories (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE notifications (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                company_id INT UNSIGNED NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(150) NOT NULL,
                body TEXT NOT NULL,
                data_json JSON NULL,
                is_seen TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE addresses (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                city VARCHAR(100) NULL,
                state VARCHAR(10) NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];

        foreach ($schema as $sql) {
            $this->pdo->exec($sql);
        }
    }

    public function drop(): void
    {
        try {
            $this->adminPdo->exec('DROP DATABASE IF EXISTS `' . $this->databaseName . '`');
        } catch (Throwable) {
        }
    }
}

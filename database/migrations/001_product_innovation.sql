CREATE TABLE IF NOT EXISTS emission_factors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NULL,
  factor_kg_co2e_per_kg DECIMAL(10,4) NOT NULL,
  source_name VARCHAR(200) NOT NULL,
  source_url VARCHAR(500) NULL,
  methodology_version VARCHAR(80) NOT NULL,
  valid_from DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_factor_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS material_passports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  negotiation_id INT UNSIGNED NOT NULL UNIQUE,
  passport_code VARCHAR(30) NOT NULL UNIQUE,
  public_token CHAR(64) NOT NULL UNIQUE,
  material_name VARCHAR(200) NOT NULL,
  quantity_kg DECIMAL(14,3) NOT NULL,
  origin_company VARCHAR(200) NOT NULL,
  destination_company VARCHAR(200) NOT NULL,
  reused_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_passport_negotiation FOREIGN KEY (negotiation_id) REFERENCES negotiations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO emission_factors(category_id,factor_kg_co2e_per_kg,source_name,source_url,methodology_version,valid_from)
SELECT NULL,2.5000,'Fator acadêmico provisório do MVP',NULL,'MVP-2026.1','2026-01-01'
WHERE NOT EXISTS (SELECT 1 FROM emission_factors);

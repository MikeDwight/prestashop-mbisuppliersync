CREATE TABLE IF NOT EXISTS `PREFIX_mbisuppliersync_run` (
  `id_run` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `started_at` DATETIME NOT NULL,
  `ended_at` DATETIME NULL,
  `status` ENUM('success','partial','failed') NOT NULL DEFAULT 'failed',
  `items_total` INT UNSIGNED NOT NULL DEFAULT 0,
  `items_updated` INT UNSIGNED NOT NULL DEFAULT 0,
  `items_failed` INT UNSIGNED NOT NULL DEFAULT 0,
  `message` VARCHAR(255) NULL,
  `execution_ms` INT UNSIGNED NULL,
  PRIMARY KEY (`id_run`),
  KEY `idx_started_at` (`started_at`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_mbisuppliersync_run_item` (
  `id_run_item` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_run` INT UNSIGNED NOT NULL,
  `sku` VARCHAR(64) NOT NULL,
  `id_product` INT UNSIGNED NULL,
  `old_stock` INT NULL,
  `new_stock` INT NULL,
  `old_price` DECIMAL(20,6) NULL,
  `new_price` DECIMAL(20,6) NULL,
  `status` ENUM('updated','skipped','error') NOT NULL DEFAULT 'error',
  `error_code` VARCHAR(64) NULL,
  `error_message` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_run_item`),
  UNIQUE KEY `uniq_run_sku` (`id_run`, `sku`),
  KEY `idx_run` (`id_run`),
  KEY `idx_sku` (`sku`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

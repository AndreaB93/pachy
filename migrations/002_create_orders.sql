-- migrations/002_create_orders.sql
CREATE TABLE IF NOT EXISTS orders (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL,
    reference   VARCHAR(100)    NOT NULL UNIQUE,
    status      ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
    total       DECIMAL(10, 2)  NOT NULL DEFAULT 0.00,
    notes       TEXT            NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

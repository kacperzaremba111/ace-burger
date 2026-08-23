-- ACE BURGER — schemat bazy danych (v1)
-- Ceny wszędzie w groszach (INT), żeby uniknąć błędów zaokrągleń
-- i być zgodnym z formatem kwot wymaganym przez API Przelewy24.

CREATE DATABASE IF NOT EXISTS ace_burger
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE ace_burger;

-- ---------- Konta panelu (admin + kuchnia) ----------
CREATE TABLE admin_users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username       VARCHAR(64) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  role           ENUM('admin', 'kuchnia') NOT NULL,
  full_name      VARCHAR(128) NOT NULL,
  is_active      TINYINT(1) NOT NULL DEFAULT 1,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at  DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Kategorie menu ----------
CREATE TABLE categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(32) NOT NULL UNIQUE,
  name        VARCHAR(64) NOT NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Pozycje menu ----------
CREATE TABLE menu_items (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id   INT UNSIGNED NOT NULL,
  name          VARCHAR(96) NOT NULL,
  description   TEXT NULL,
  price_cents   INT UNSIGNED NOT NULL,
  tag           ENUM('none', 'hot', 'bbq') NOT NULL DEFAULT 'none',
  is_featured   TINYINT(1) NOT NULL DEFAULT 0,
  is_available  TINYINT(1) NOT NULL DEFAULT 1,
  sort_order    INT NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_menu_items_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE RESTRICT,
  INDEX idx_menu_items_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Dodatki (wybieralne per pozycja w koszyku) ----------
CREATE TABLE addons (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(96) NOT NULL,
  price_cents   INT UNSIGNED NOT NULL,
  sort_order    INT NOT NULL DEFAULT 0,
  is_available  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Ustawienia globalne ----------
CREATE TABLE settings (
  `key`   VARCHAR(64) PRIMARY KEY,
  `value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Zamówienia ----------
CREATE TABLE orders (
  id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number           VARCHAR(32) NOT NULL UNIQUE,
  status                 ENUM('nowe', 'oplacone', 'w_przygotowaniu', 'gotowe', 'w_dostawie', 'wydane', 'anulowane')
                           NOT NULL DEFAULT 'nowe',
  payment_method         ENUM('przelewy24', 'na_miejscu') NOT NULL,
  fulfillment            ENUM('na_miejscu', 'na_wynos', 'dostawa') NOT NULL,
  customer_name          VARCHAR(96) NOT NULL,
  customer_phone         VARCHAR(24) NOT NULL,
  customer_email         VARCHAR(128) NULL,
  delivery_street        VARCHAR(128) NULL,
  delivery_city          VARCHAR(64) NULL,
  delivery_postal_code   VARCHAR(12) NULL,
  delivery_notes         VARCHAR(255) NULL,
  delivery_fee_cents     INT UNSIGNED NOT NULL DEFAULT 0,
  notes                  TEXT NULL,
  subtotal_cents         INT UNSIGNED NOT NULL,
  packaging_fee_cents    INT UNSIGNED NOT NULL DEFAULT 0,
  total_cents            INT UNSIGNED NOT NULL,
  requested_pickup_at    DATETIME NULL,
  created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  paid_at                DATETIME NULL,
  client_ip              VARCHAR(45) NULL,
  INDEX idx_orders_status (status),
  INDEX idx_orders_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Pozycje zamówienia (snapshot ceny/nazwy) ----------
CREATE TABLE order_items (
  id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id               INT UNSIGNED NOT NULL,
  menu_item_id           INT UNSIGNED NULL,
  name_snapshot          VARCHAR(96) NOT NULL,
  price_cents_snapshot   INT UNSIGNED NOT NULL,
  quantity               INT UNSIGNED NOT NULL DEFAULT 1,
  line_total_cents       INT UNSIGNED NOT NULL,
  item_notes             VARCHAR(255) NULL,
  CONSTRAINT fk_order_items_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_order_items_menu_item
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
    ON DELETE SET NULL,
  INDEX idx_order_items_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Dodatki przypięte do konkretnej pozycji zamówienia ----------
CREATE TABLE order_item_addons (
  id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_item_id          INT UNSIGNED NOT NULL,
  addon_id               INT UNSIGNED NULL,
  name_snapshot          VARCHAR(96) NOT NULL,
  price_cents_snapshot   INT UNSIGNED NOT NULL,
  quantity               INT UNSIGNED NOT NULL DEFAULT 1,
  CONSTRAINT fk_order_item_addons_order_item
    FOREIGN KEY (order_item_id) REFERENCES order_items(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_order_item_addons_addon
    FOREIGN KEY (addon_id) REFERENCES addons(id)
    ON DELETE SET NULL,
  INDEX idx_order_item_addons_order_item (order_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Transakcje Przelewy24 ----------
CREATE TABLE payments (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id             INT UNSIGNED NOT NULL,
  p24_session_id       VARCHAR(64) NOT NULL UNIQUE,
  p24_order_id         VARCHAR(64) NULL,
  amount_cents         INT UNSIGNED NOT NULL,
  currency             CHAR(3) NOT NULL DEFAULT 'PLN',
  status               ENUM('utworzona', 'przekierowano', 'potwierdzona', 'odrzucona') NOT NULL DEFAULT 'utworzona',
  raw_notify_payload   TEXT NULL,
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  confirmed_at         DATETIME NULL,
  CONSTRAINT fk_payments_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE,
  INDEX idx_payments_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

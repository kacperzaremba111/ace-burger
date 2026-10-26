-- FAST FOOD — strefy lokalu + konta pracowników przypisane do stref
--
-- Klient przy zamówieniu wybiera strefę lokalu, z której korzysta.
-- Pracownik (rola "pracownik") widzi i obsługuje WYŁĄCZNIE zamówienia
-- ze swojej strefy; admin widzi wszystkie strefy i wszystkich pracowników.
--
-- Konta pracowników i hasła NIE są tutaj zapisane (żeby hasła nie trafiły
-- do repozytorium) — tworzy je skrypt CLI: php bin/utworz-konta.php

USE ace_burger;

CREATE TABLE zones (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(32) NOT NULL UNIQUE,
  name         VARCHAR(64) NOT NULL,
  description  VARCHAR(160) NULL,
  sort_order   INT NOT NULL DEFAULT 0,
  is_active    TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO zones (slug, name, description, sort_order) VALUES
  ('sala',      'Sala główna', 'Parter, stoliki przy barze i oknach', 1),
  ('ogrodek',   'Ogródek',     'Stoliki na zewnątrz, od strony podwórza', 2),
  ('antresola', 'Antresola',   'Piętro nad salą, wejście schodami przy kasie', 3);

-- Rola "kuchnia" zostaje zastąpiona przez "pracownik" (przypisany do strefy).
ALTER TABLE admin_users MODIFY role ENUM('admin', 'kuchnia', 'pracownik') NOT NULL;
UPDATE admin_users SET role = 'pracownik' WHERE role = 'kuchnia';
ALTER TABLE admin_users MODIFY role ENUM('admin', 'pracownik') NOT NULL;

ALTER TABLE admin_users
  ADD COLUMN zone_id INT UNSIGNED NULL AFTER role,
  ADD CONSTRAINT fk_admin_users_zone FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL;

-- Starsze zamówienia (sprzed tej zmiany) zostają bez strefy — widzi je tylko admin.
ALTER TABLE orders
  ADD COLUMN zone_id INT UNSIGNED NULL AFTER fulfillment,
  ADD CONSTRAINT fk_orders_zone FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL,
  ADD INDEX idx_orders_zone_status (zone_id, status);

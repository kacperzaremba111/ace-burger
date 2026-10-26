-- FAST FOOD — konto startowe panelu admina
--
-- !!! HASŁO PLACEHOLDER — DO NATYCHMIASTOWEJ ZMIANY !!!
-- Login:  admin
-- Hasło:  zmien-to-haslo-2026   (WYŁĄCZNIE do pierwszego logowania lokalnie/testowo)
--
-- Zmień hasło od razu po pierwszym zalogowaniu (funkcja zmiany hasła
-- powstaje w Fazie 2 razem z panel/login.php) — NIGDY nie używaj tego
-- hasła na środowisku produkcyjnym / publicznie dostępnym.

USE ace_burger;

INSERT INTO admin_users (username, password_hash, role, full_name, is_active) VALUES
  ('admin', '$2y$10$1L12ZHCzt3wHg2lqUbJBV./BdE1LrkC/o3BBnwcrQegFHJu5cri46', 'admin', 'Administrator', 1);

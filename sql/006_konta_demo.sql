-- FAST FOOD — konta DEMO panelu (do prezentacji projektu, np. w portfolio)
--
-- !!! HASŁA SĄ JAWNE (wypisane w README.md, sekcja "Demo") !!!
-- Używać WYŁĄCZNIE lokalnie / na instancji demonstracyjnej. Na produkcji
-- NIE importować tego pliku — zamiast tego: php bin/utworz-konta.php
-- (losowe hasła, nigdzie niezapisane).
--
-- Login                 Hasło            Dostęp
-- admin                 vmP3-yEdJ-2XME   wszystkie strefy, zamówienia, pracownicy, menu
-- pracownik-sala        wanw-ec5y-u7LR   tylko strefa: Sala główna
-- pracownik-ogrodek     dtxh-BUgh-fGk9   tylko strefa: Ogródek
-- pracownik-antresola   gf25-BDtR-UH4h   tylko strefa: Antresola
--
-- Wymaga wcześniejszego importu 001–005. Bezpieczne do ponownego uruchomienia
-- (istniejące konta dostają z powrotem hasła demo).

USE ace_burger;

INSERT INTO admin_users (username, password_hash, role, zone_id, full_name, is_active) VALUES
  ('admin',               '$2y$10$m9tmcV0T.gXxdmky7P6WMuFUi6qBN.VGxYHcRiWY6ER3PB4Z.bEVK', 'admin',     NULL,                                           'Administrator',             1),
  ('pracownik-sala',      '$2y$10$Gz0O2aHxELoPbGV7ZznXvOUveD2Cvl0k1CodXbpnpRxLDv6sQeEy2', 'pracownik', (SELECT id FROM zones WHERE slug = 'sala'),      'Pracownik — Sala główna',   1),
  ('pracownik-ogrodek',   '$2y$10$0/ACCbN7S0LnCXExHI8XeuQSEVGx/wUq5PWbTbVQgJ6IXKQ0hKPxm', 'pracownik', (SELECT id FROM zones WHERE slug = 'ogrodek'),   'Pracownik — Ogródek',       1),
  ('pracownik-antresola', '$2y$10$Hu7xdidUx34sA2SwKOzkp.kZxCFilsc0hhYJYCLuWvX8HPeYHU0Wu', 'pracownik', (SELECT id FROM zones WHERE slug = 'antresola'), 'Pracownik — Antresola',     1)
AS demo
ON DUPLICATE KEY UPDATE
  password_hash = demo.password_hash,
  role          = demo.role,
  zone_id       = demo.zone_id,
  full_name     = demo.full_name,
  is_active     = 1;

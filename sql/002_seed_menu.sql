-- ACE BURGER — dane startowe menu, 1:1 z obecnym index.html
-- (patrz sekcje #smash, #klasyczne, #doladuj)

USE ace_burger;

-- ---------- Kategorie ----------
INSERT INTO categories (slug, name, sort_order, is_active) VALUES
  ('smash',     'Burgery Smash',     1, 1),
  ('klasyczne', 'Burgery Klasyczne', 2, 1);

-- ---------- Burgery Smash ----------
INSERT INTO menu_items (category_id, name, description, price_cents, tag, is_featured, sort_order) VALUES
  ((SELECT id FROM categories WHERE slug = 'smash'), 'Smash Delux',
    'Wołowina x3 / cheddar x3 / bekon / cebula / pikle / sos ACE / sałata', 3700, 'none', 0, 1),
  ((SELECT id FROM categories WHERE slug = 'smash'), 'Smash Oklahoma',
    'Wołowina / cheddar / sos ACE / cebula', 2700, 'none', 0, 2),
  ((SELECT id FROM categories WHERE slug = 'smash'), 'Ace Klasyk',
    'Wołowina / cheddar / pomidor / sos ACE / cebula / pikle / sałata', 3100, 'none', 0, 3),
  ((SELECT id FROM categories WHERE slug = 'smash'), 'Smash BBQ',
    'Wołowina / bekon / cheddar / sos ACE / sos BBQ / cebula / pikle', 3000, 'bbq', 0, 4),
  ((SELECT id FROM categories WHERE slug = 'smash'), 'Smash Jalapeño',
    'Wołowina / cheddar / jalapeño / cebula / sos jalapeño', 2900, 'hot', 0, 5),
  ((SELECT id FROM categories WHERE slug = 'smash'), 'Smash Wiejski',
    'Wołowina / placek ziemniaczany / cebula / sos wiejski / bekon / cheddar / pikle', 3300, 'none', 0, 6),
  ((SELECT id FROM categories WHERE slug = 'smash'), 'Smash Amigo',
    'Wołowina / nachosy / sos sriracha-mayo / chorizo / salsa / cheddar / cebula / sałata / jalapeño', 3600, 'hot', 0, 7),
  ((SELECT id FROM categories WHERE slug = 'smash'), 'Smash Serowiec',
    'Wołowina / cheddar x3 / krążki cebulowe x3 / sos ACE / bekon / jalapeño / cebula / pikle / sałata', 3500, 'none', 0, 8),
  ((SELECT id FROM categories WHERE slug = 'smash'), 'Smash El Picante',
    'Wołowina / jalapeño / sos ostry / sos ACE / pikle / cebula / cheddar / sałata', 3100, 'hot', 0, 9);

-- ---------- Burgery Klasyczne ----------
INSERT INTO menu_items (category_id, name, description, price_cents, tag, is_featured, sort_order) VALUES
  ((SELECT id FROM categories WHERE slug = 'klasyczne'), 'Big Boy',
    'Szarpana wieprzowina / wołowina / cheddar / sos BBQ / sos ACE / krążki cebulowe / sałata / pikle / cebula / jalapeño',
    4100, 'none', 1, 1),
  ((SELECT id FROM categories WHERE slug = 'klasyczne'), 'Bekon',
    'Bekon / wołowina / sos ACE / sos BBQ / cheddar / pikle / cebula / sałata', 3300, 'none', 0, 2),
  ((SELECT id FROM categories WHERE slug = 'klasyczne'), 'Teksański Poszarpany',
    'Szarpana wieprzowina / bekon / sos BBQ / sos ACE / cheddar / krążki cebulowe / pikle', 3000, 'none', 0, 3);

-- ---------- Dodatki (#doladuj) ----------
INSERT INTO addons (name, price_cents, sort_order) VALUES
  ('Kotlet wołowy', 1100, 1),
  ('Dodatkowy placek ziemniaczany', 400, 2),
  ('Dodatkowy panierowany ser', 900, 3),
  ('Dodatkowe warzywa', 300, 4),
  ('Dodatkowy bekon / jalapeño / ser', 300, 5),
  ('Dodatkowy sos (ACE, wiejski, sriracha, BBQ)', 200, 6),
  ('Sos serowy', 600, 7);

-- ---------- Ustawienia startowe ----------
-- packaging_fee_cents = 100 odzwierciedla obecną notatkę na stronie:
-- "Do każdego zamówienia na wynos doliczamy 1 zł (koszt opakowania)".
-- delivery_fee_cents to wartość startowa — do weryfikacji/zmiany w panelu
-- admina (ustawienia.php) przed uruchomieniem dostawy na produkcji.
INSERT INTO settings (`key`, `value`) VALUES
  ('orders_enabled', '1'),
  ('packaging_fee_cents', '100'),
  ('delivery_fee_cents', '1000');

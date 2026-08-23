<?php

declare(strict_types=1);

/**
 * Dostęp do menu (kategorie, pozycje, dodatki) + renderowanie markupu
 * identycznego z oryginalnym statycznym index.html — patrz renderTicket()
 * i renderAddonListItem(). main.css/main.js nie wymagają żadnych zmian,
 * bo wygenerowany DOM ma dokładnie te same klasy/strukturę co wcześniej.
 */
final class MenuRepository
{
    // ---------- Odczyt (strona publiczna) ----------

    /** @return array<int, array<string, mixed>> */
    public static function categories(bool $onlyActive = true): array
    {
        $sql = 'SELECT id, slug, name, sort_order, is_active FROM categories';
        if ($onlyActive) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order, id';

        return Database::pdo()->query($sql)->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public static function menuItemsByCategory(int $categoryId, bool $onlyAvailable = true): array
    {
        $sql = 'SELECT * FROM menu_items WHERE category_id = ?';
        if ($onlyAvailable) {
            $sql .= ' AND is_available = 1';
        }
        $sql .= ' ORDER BY sort_order, id';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([$categoryId]);

        return $stmt->fetchAll();
    }

    /** Wszystkie pozycje menu z nazwą kategorii — do listy w panelu admina. */
    public static function allMenuItemsWithCategory(): array
    {
        return Database::pdo()->query(
            'SELECT mi.*, c.name AS category_name
             FROM menu_items mi
             JOIN categories c ON c.id = mi.category_id
             ORDER BY c.sort_order, mi.sort_order, mi.id'
        )->fetchAll();
    }

    public static function menuItem(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM menu_items WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function addons(bool $onlyAvailable = true): array
    {
        $sql = 'SELECT * FROM addons';
        if ($onlyAvailable) {
            $sql .= ' WHERE is_available = 1';
        }
        $sql .= ' ORDER BY sort_order, id';

        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function addon(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM addons WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function category(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** Liczba pozycji menu w kategorii — do blokady usuwania niepustej kategorii. */
    public static function categoryItemCount(int $categoryId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) AS c FROM menu_items WHERE category_id = ?');
        $stmt->execute([$categoryId]);

        return (int) $stmt->fetch()['c'];
    }

    // ---------- Renderowanie (identyczne z oryginalnym index.html) ----------

    public static function renderTicket(array $item): string
    {
        $classes = 'ticket' . ((int) $item['is_featured'] === 1 ? ' ticket--feature' : '') . ' reveal';

        $tag = '';
        if ($item['tag'] === 'hot') {
            $tag = ' <span class="ticket__tag ticket__tag--hot">OSTRY</span>';
        } elseif ($item['tag'] === 'bbq') {
            $tag = ' <span class="ticket__tag ticket__tag--bbq">BBQ</span>';
        }

        $price      = price_display((int) $item['price_cents']);
        $name       = h($item['name']);
        $nameAttr   = h((string) $item['name']);
        $desc       = h($item['description'] ?? '');
        $id         = (int) $item['id'];
        $priceCents = (int) $item['price_cents'];

        return <<<HTML
        <article class="{$classes}">
          <div class="ticket__row">
            <h3 class="ticket__name">{$name}{$tag}</h3>
            <span class="ticket__price">{$price}<span class="visually-hidden"> zł</span></span>
          </div>
          <p class="ticket__desc">{$desc}</p>
          <button type="button" class="btn btn--mustard ticket__add"
            data-menu-item-id="{$id}" data-price-cents="{$priceCents}" data-name="{$nameAttr}">
            Dodaj do koszyka
          </button>
        </article>
HTML;
    }

    public static function renderAddonListItem(array $addon): string
    {
        $name       = h($addon['name']);
        $priceLabel = money_pln((int) $addon['price_cents']);

        return "          <li><span>{$name}</span><span class=\"addons__price\">{$priceLabel}</span></li>";
    }

    // ---------- Zapis (panel admina) ----------

    public static function createCategory(string $slug, string $name, int $sortOrder): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO categories (slug, name, sort_order) VALUES (?, ?, ?)'
        );
        $stmt->execute([$slug, $name, $sortOrder]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateCategory(int $id, string $slug, string $name, int $sortOrder, bool $isActive): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE categories SET slug = ?, name = ?, sort_order = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([$slug, $name, $sortOrder, $isActive ? 1 : 0, $id]);
    }

    /** @throws RuntimeException jeśli kategoria wciąż ma przypisane pozycje menu */
    public static function deleteCategory(int $id): void
    {
        if (self::categoryItemCount($id) > 0) {
            throw new RuntimeException('Nie można usunąć kategorii, która ma przypisane pozycje menu.');
        }

        $stmt = Database::pdo()->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** @param array{category_id:int,name:string,description:string,price_cents:int,tag:string,is_featured:bool,is_available:bool,sort_order:int} $data */
    public static function createMenuItem(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO menu_items (category_id, name, description, price_cents, tag, is_featured, is_available, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['category_id'],
            $data['name'],
            $data['description'],
            $data['price_cents'],
            $data['tag'],
            $data['is_featured'] ? 1 : 0,
            $data['is_available'] ? 1 : 0,
            $data['sort_order'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateMenuItem(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE menu_items
             SET category_id = ?, name = ?, description = ?, price_cents = ?, tag = ?,
                 is_featured = ?, is_available = ?, sort_order = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['category_id'],
            $data['name'],
            $data['description'],
            $data['price_cents'],
            $data['tag'],
            $data['is_featured'] ? 1 : 0,
            $data['is_available'] ? 1 : 0,
            $data['sort_order'],
            $id,
        ]);
    }

    public static function deleteMenuItem(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM menu_items WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function createAddon(string $name, int $priceCents, int $sortOrder): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO addons (name, price_cents, sort_order) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $priceCents, $sortOrder]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateAddon(int $id, string $name, int $priceCents, int $sortOrder, bool $isAvailable): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE addons SET name = ?, price_cents = ?, sort_order = ?, is_available = ? WHERE id = ?'
        );
        $stmt->execute([$name, $priceCents, $sortOrder, $isAvailable ? 1 : 0, $id]);
    }

    public static function deleteAddon(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM addons WHERE id = ?');
        $stmt->execute([$id]);
    }
}

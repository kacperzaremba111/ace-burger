<?php

declare(strict_types=1);

/**
 * Strefy lokalu (np. sala, ogródek, antresola). Klient wybiera strefę przy
 * zamówieniu, pracownik panelu jest przypisany do jednej strefy.
 */
final class ZoneRepository
{
    /** @return array<int, array<string, mixed>> */
    public static function all(bool $onlyActive = true): array
    {
        $sql = 'SELECT id, slug, name, description, sort_order, is_active FROM zones';
        if ($onlyActive) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order, id';

        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM zones WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function isActive(int $id): bool
    {
        $zone = self::find($id);

        return $zone !== null && (bool) $zone['is_active'];
    }
}

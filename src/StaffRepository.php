<?php

declare(strict_types=1);

/**
 * Konta panelu (admin + pracownicy stref) — podgląd i zarządzanie dla admina
 * (www/panel/pracownicy.php) oraz dla skryptu bin/utworz-konta.php.
 */
final class StaffRepository
{
    /**
     * Wszystkie konta ze strefą i statystyką zamówień w tej strefie.
     * @return array<int, array<string, mixed>>
     */
    public static function allWithStats(): array
    {
        return Database::pdo()->query(
            "SELECT u.id, u.username, u.role, u.full_name, u.zone_id, u.is_active, u.last_login_at,
                    z.name AS zone_name,
                    (SELECT COUNT(*) FROM orders o
                      WHERE o.zone_id = u.zone_id
                        AND o.status IN ('nowe', 'oplacone', 'w_przygotowaniu', 'gotowe', 'w_dostawie')) AS active_orders,
                    (SELECT COUNT(*) FROM orders o
                      WHERE o.zone_id = u.zone_id AND o.created_at >= CURDATE()) AS today_orders
             FROM admin_users u
             LEFT JOIN zones z ON z.id = u.zone_id
             ORDER BY u.role = 'admin' DESC, z.sort_order, u.username"
        )->fetchAll();
    }

    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM admin_users WHERE username = ?');
        $stmt->execute([$username]);

        return $stmt->fetch() ?: null;
    }

    /** Tworzy konto albo — jeśli login już istnieje — aktualizuje rolę/strefę/hasło. */
    public static function upsert(string $username, string $fullName, string $role, ?int $zoneId, string $password): void
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $existing = self::findByUsername($username);

        if ($existing) {
            $stmt = Database::pdo()->prepare(
                'UPDATE admin_users SET full_name = ?, role = ?, zone_id = ?, password_hash = ?, is_active = 1 WHERE id = ?'
            );
            $stmt->execute([$fullName, $role, $zoneId, $hash, $existing['id']]);
            return;
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO admin_users (username, password_hash, role, zone_id, full_name, is_active) VALUES (?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$username, $hash, $role, $zoneId, $fullName]);
    }

    /** Zmiana strefy i aktywności konta pracownika (konta admina nie dotyczy). */
    public static function updateEmployee(int $id, int $zoneId, bool $isActive): void
    {
        if (!ZoneRepository::find($zoneId)) {
            throw new InvalidArgumentException('Nieprawidłowa strefa.');
        }

        $stmt = Database::pdo()->prepare(
            "UPDATE admin_users SET zone_id = ?, is_active = ? WHERE id = ? AND role = 'pracownik'"
        );
        $stmt->execute([$zoneId, $isActive ? 1 : 0, $id]);
    }

    /** Ustawia nowe losowe hasło i zwraca je (pokazywane adminowi jednorazowo). */
    public static function resetPassword(int $id): string
    {
        $password = self::generatePassword();
        $stmt = Database::pdo()->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);

        if ($stmt->rowCount() === 0) {
            throw new InvalidArgumentException('Nie znaleziono konta.');
        }

        return $password;
    }

    /**
     * Losowe hasło w formacie xxxx-xxxx-xxxx (~60 bitów entropii), bez znaków
     * łatwych do pomylenia przy przepisywaniu z kartki (0/O, 1/l/I).
     */
    public static function generatePassword(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;

        $groups = [];
        for ($g = 0; $g < 3; $g++) {
            $group = '';
            for ($i = 0; $i < 4; $i++) {
                $group .= $alphabet[random_int(0, $max)];
            }
            $groups[] = $group;
        }

        return implode('-', $groups);
    }
}

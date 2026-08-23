<?php

declare(strict_types=1);

/**
 * Tworzenie i odczyt zamówień. Kluczowa zasada bezpieczeństwa: WSZYSTKIE
 * ceny są przeliczane tutaj z bazy danych — ceny przesłane przez klienta
 * (localStorage, JS) są całkowicie ignorowane. Klient przesyła tylko
 * identyfikatory (menu_item_id/addon_id) i ilości.
 */
final class OrderRepository
{
    // ---------- Ustawienia (settings) ----------

    public static function setting(string $key, ?string $default = null): ?string
    {
        $stmt = Database::pdo()->prepare('SELECT `value` FROM settings WHERE `key` = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        return $row ? $row['value'] : $default;
    }

    public static function ordersEnabled(): bool
    {
        return self::setting('orders_enabled', '1') === '1';
    }

    // ---------- Tworzenie zamówienia ----------

    /**
     * @param array{customer_name:string,customer_phone:string,customer_email:?string,
     *              fulfillment:string,payment_method:string,notes:?string,
     *              delivery_street:?string,delivery_city:?string,delivery_postal_code:?string,
     *              delivery_notes:?string,client_ip:?string} $customer
     * @param array<int, array{menuItemId:mixed,qty:mixed,notes?:mixed,addons?:array}> $cartItems
     * @return array{orderId:int,orderNumber:string,totalCents:int}
     * @throws InvalidArgumentException dane wejściowe nieprawidłowe (koszyk pusty,
     *         pozycja/dodatek niedostępny...) — bezpieczne do pokazania klientowi.
     */
    public static function createOrder(array $customer, array $cartItems, string $orderNumberPrefix): array
    {
        if (empty($cartItems)) {
            throw new InvalidArgumentException('Koszyk jest pusty.');
        }
        if (count($cartItems) > 50) {
            throw new InvalidArgumentException('Zbyt wiele pozycji w zamówieniu.');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            [$preparedItems, $subtotalCents] = self::pricePreparedItems($pdo, $cartItems);

            $isDelivery = $customer['fulfillment'] === 'dostawa';
            $isTakeaway = $customer['fulfillment'] === 'na_wynos';

            $packagingFeeCents = ($isTakeaway || $isDelivery) ? (int) self::setting('packaging_fee_cents', '0') : 0;
            $deliveryFeeCents  = $isDelivery ? (int) self::setting('delivery_fee_cents', '0') : 0;
            $totalCents        = $subtotalCents + $packagingFeeCents + $deliveryFeeCents;

            $orderNumber = self::generateOrderNumber($pdo, $orderNumberPrefix);

            $stmt = $pdo->prepare(
                'INSERT INTO orders (
                    order_number, status, payment_method, fulfillment, customer_name, customer_phone,
                    customer_email, delivery_street, delivery_city, delivery_postal_code, delivery_notes,
                    delivery_fee_cents, notes, subtotal_cents, packaging_fee_cents, total_cents, client_ip
                 ) VALUES (?, "nowe", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $orderNumber,
                $customer['payment_method'],
                $customer['fulfillment'],
                $customer['customer_name'],
                $customer['customer_phone'],
                $customer['customer_email'] ?: null,
                $customer['delivery_street'] ?: null,
                $customer['delivery_city'] ?: null,
                $customer['delivery_postal_code'] ?: null,
                $customer['delivery_notes'] ?: null,
                $deliveryFeeCents,
                $customer['notes'] ?: null,
                $subtotalCents,
                $packagingFeeCents,
                $totalCents,
                $customer['client_ip'] ?? null,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            foreach ($preparedItems as $item) {
                $itemStmt = $pdo->prepare(
                    'INSERT INTO order_items
                        (order_id, menu_item_id, name_snapshot, price_cents_snapshot, quantity, line_total_cents, item_notes)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $itemStmt->execute([
                    $orderId, $item['menu_item_id'], $item['name'], $item['price_cents'],
                    $item['qty'], $item['line_total_cents'], $item['notes'] ?: null,
                ]);
                $orderItemId = (int) $pdo->lastInsertId();

                foreach ($item['addons'] as $addon) {
                    $addonStmt = $pdo->prepare(
                        'INSERT INTO order_item_addons
                            (order_item_id, addon_id, name_snapshot, price_cents_snapshot, quantity)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $addonStmt->execute([
                        $orderItemId, $addon['addon_id'], $addon['name'], $addon['price_cents'], $addon['qty'],
                    ]);
                }
            }

            $pdo->commit();

            return ['orderId' => $orderId, 'orderNumber' => $orderNumber, 'totalCents' => $totalCents];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Przelicza koszyk z bazy danych — jedyne miejsce, gdzie liczą się ceny.
     * @return array{0: array<int,array>, 1: int} [preparedItems, subtotalCents]
     */
    private static function pricePreparedItems(PDO $pdo, array $cartItems): array
    {
        $preparedItems = [];
        $subtotalCents = 0;

        foreach ($cartItems as $cartItem) {
            $menuItemId = (int) ($cartItem['menuItemId'] ?? 0);
            $qty        = max(1, min(50, (int) ($cartItem['qty'] ?? 1)));

            $stmt = $pdo->prepare('SELECT id, name, price_cents, is_available FROM menu_items WHERE id = ?');
            $stmt->execute([$menuItemId]);
            $menuItem = $stmt->fetch();

            if (!$menuItem || !$menuItem['is_available']) {
                throw new InvalidArgumentException('Jedna z pozycji w koszyku jest już niedostępna — odśwież menu.');
            }

            [$lineAddons, $addonsCents] = self::priceAddons($pdo, (array) ($cartItem['addons'] ?? []));

            $lineTotalCents = ((int) $menuItem['price_cents'] + $addonsCents) * $qty;
            $subtotalCents += $lineTotalCents;

            $preparedItems[] = [
                'menu_item_id'     => $menuItem['id'],
                'name'             => $menuItem['name'],
                'price_cents'      => (int) $menuItem['price_cents'],
                'qty'              => $qty,
                'line_total_cents' => $lineTotalCents,
                'notes'            => mb_substr(trim((string) ($cartItem['notes'] ?? '')), 0, 255),
                'addons'           => $lineAddons,
            ];
        }

        return [$preparedItems, $subtotalCents];
    }

    /** @return array{0: array<int,array>, 1: int} [lineAddons, addonsCentsPerUnit] */
    private static function priceAddons(PDO $pdo, array $addonsInput): array
    {
        $lineAddons  = [];
        $addonsCents = 0;

        foreach ($addonsInput as $addonInput) {
            $addonId  = (int) ($addonInput['addonId'] ?? 0);
            $addonQty = max(1, min(20, (int) ($addonInput['qty'] ?? 1)));

            $stmt = $pdo->prepare('SELECT id, name, price_cents, is_available FROM addons WHERE id = ?');
            $stmt->execute([$addonId]);
            $addon = $stmt->fetch();

            if (!$addon || !$addon['is_available']) {
                throw new InvalidArgumentException('Jeden z wybranych dodatków jest już niedostępny — odśwież koszyk.');
            }

            $addonsCents += (int) $addon['price_cents'] * $addonQty;
            $lineAddons[] = [
                'addon_id'    => $addon['id'],
                'name'        => $addon['name'],
                'price_cents' => (int) $addon['price_cents'],
                'qty'         => $addonQty,
            ];
        }

        return [$lineAddons, $addonsCents];
    }

    private static function generateOrderNumber(PDO $pdo, string $prefix): string
    {
        $date = date('Ymd');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = sprintf('%s-%s-%s', $prefix, $date, strtoupper(bin2hex(random_bytes(3))));
            $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM orders WHERE order_number = ?');
            $stmt->execute([$candidate]);
            if ((int) $stmt->fetch()['c'] === 0) {
                return $candidate;
            }
        }

        throw new RuntimeException('Nie udało się wygenerować unikalnego numeru zamówienia.');
    }

    // ---------- Odczyt ----------

    public static function findByNumber(string $orderNumber): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM orders WHERE order_number = ?');
        $stmt->execute([$orderNumber]);

        return $stmt->fetch() ?: null;
    }

    /** @return array<int,array<string,mixed>> zamówienia z filtrem statusu, najnowsze pierwsze */
    public static function listForKitchen(array $statuses): array
    {
        if (empty($statuses)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM orders WHERE status IN ($placeholders) ORDER BY created_at DESC"
        );
        $stmt->execute($statuses);

        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public static function itemsForOrder(int $orderId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            $addonsStmt = Database::pdo()->prepare('SELECT * FROM order_item_addons WHERE order_item_id = ? ORDER BY id');
            $addonsStmt->execute([$item['id']]);
            $item['addons'] = $addonsStmt->fetchAll();
        }

        return $items;
    }

    public static function updateStatus(int $orderId, string $status): void
    {
        $allowed = ['nowe', 'oplacone', 'w_przygotowaniu', 'gotowe', 'w_dostawie', 'wydane', 'anulowane'];
        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Nieprawidłowy status zamówienia.');
        }

        $stmt = Database::pdo()->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, $orderId]);
    }

    // ---------- Płatności (Przelewy24) ----------

    public static function createPaymentRecord(int $orderId, string $sessionId, int $amountCents): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO payments (order_id, p24_session_id, amount_cents, status) VALUES (?, ?, ?, "utworzona")'
        );
        $stmt->execute([$orderId, $sessionId, $amountCents]);
    }

    public static function findPaymentBySessionId(string $sessionId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM payments WHERE p24_session_id = ?');
        $stmt->execute([$sessionId]);

        return $stmt->fetch() ?: null;
    }

    /** Idempotentne — bezpieczne przy powtórzonym notify od Przelewy24. */
    public static function markPaymentConfirmed(string $sessionId, string $p24OrderId, string $rawPayload): bool
    {
        $stmt = Database::pdo()->prepare(
            "UPDATE payments SET status = 'potwierdzona', p24_order_id = ?, raw_notify_payload = ?, confirmed_at = NOW()
             WHERE p24_session_id = ? AND status != 'potwierdzona'"
        );
        $stmt->execute([$p24OrderId, $rawPayload, $sessionId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Oznacza zamówienie jako opłacone — TYLKO jeśli kwota się zgadza z tym,
     * co faktycznie zarejestrowano (dodatkowa obrona, niezależna od tego, że
     * verifyTransaction() już potwierdził płatność po stronie Przelewy24).
     * Idempotentne — bezpieczne przy powtórzonym notify.
     */
    public static function markPaidBySessionId(string $orderNumber, int $amountCents): bool
    {
        $stmt = Database::pdo()->prepare(
            "UPDATE orders SET status = 'oplacone', paid_at = NOW()
             WHERE order_number = ? AND status != 'oplacone' AND total_cents = ?"
        );
        $stmt->execute([$orderNumber, $amountCents]);

        return $stmt->rowCount() > 0;
    }
}

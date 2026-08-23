<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

/**
 * Powiadomienie (notify) server-to-server z Przelewy24. WAŻNE: sama treść
 * tego requestu NIGDY nie jest wystarczającym dowodem płatności — to tylko
 * sygnał "sprawdź teraz". Jedynym autorytatywnym potwierdzeniem jest
 * wywołanie Przelewy24Client::verifyTransaction() (nasze CRC, ich API).
 * Patrz komentarz w src/Przelewy24Client.php.
 *
 * Zawsze odpowiadamy szybko — P24 ponawia próbę przy braku/błędnej
 * odpowiedzi, więc każda awaria po naszej stronie i tak zostanie naprawiona
 * kolejnym powtórzeniem notify, o ile odpowiemy kodem błędu (nie 200) w
 * takim wypadku.
 */

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    exit;
}

$sessionId = (string) ($data['sessionId'] ?? '');
$orderId   = (string) ($data['orderId'] ?? '');
$amount    = (int) ($data['amount'] ?? 0);
$currency  = (string) ($data['currency'] ?? 'PLN');

if ($sessionId === '' || $orderId === '') {
    http_response_code(400);
    exit;
}

$payment = OrderRepository::findPaymentBySessionId($sessionId);
if (!$payment) {
    http_response_code(404);
    exit;
}

// Kwota z notify musi się zgadzać z tym, co faktycznie zarejestrowaliśmy —
// dodatkowa obrona, niezależna od wyniku verifyTransaction().
if ($amount !== (int) $payment['amount_cents']) {
    http_response_code(400);
    exit;
}

try {
    $p24      = Przelewy24Client::fromAppConfig();
    $verified = $p24->verifyTransaction($sessionId, $orderId, $amount, $currency);
} catch (Throwable $e) {
    // Błąd połączenia z API P24 przy weryfikacji — NIE oznaczamy jako
    // opłacone. P24 samo ponowi notify (kod inny niż 2xx wymusza retry).
    http_response_code(502);
    exit;
}

if ($verified) {
    OrderRepository::markPaymentConfirmed($sessionId, $orderId, $raw);
    OrderRepository::markPaidBySessionId($sessionId, $amount);
}

http_response_code(200);
echo 'OK';

<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function respond_error(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_error(405, 'Method not allowed.');
}

$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    respond_error(400, 'Nieprawidłowe dane.');
}

if (!Csrf::verify($payload['csrf_token'] ?? null)) {
    respond_error(403, 'Sesja wygasła — odśwież stronę i spróbuj ponownie.');
}

// Honeypot: pole niewidoczne dla ludzi. Wypełnione == prawie na pewno bot —
// udajemy sukces (nie dajemy botowi sygnału, że coś jest nie tak), ale
// NIC nie zapisujemy do bazy.
if (!empty($payload['website'])) {
    echo json_encode(['orderNumber' => 'IGNORED'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!OrderRepository::ordersEnabled()) {
    respond_error(503, 'Przepraszamy, w tej chwili nie przyjmujemy zamówień online. Zadzwoń: 729 110 496.');
}

$ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$bucket = 'order:' . $ip;
if (Throttle::tooMany($bucket, maxAttempts: 8, windowSeconds: 600)) {
    respond_error(429, 'Zbyt wiele zamówień z tego adresu w krótkim czasie. Spróbuj później albo zadzwoń: 729 110 496.');
}
Throttle::hit($bucket);

$customerName  = trim((string) ($payload['customer_name'] ?? ''));
$customerPhone = trim((string) ($payload['customer_phone'] ?? ''));
$fulfillment   = (string) ($payload['fulfillment'] ?? '');
$paymentMethod = (string) ($payload['payment_method'] ?? '');
$zoneId        = (int) ($payload['zone_id'] ?? 0);

if ($customerName === '' || $customerPhone === '') {
    respond_error(422, 'Podaj imię, nazwisko i telefon.');
}
if ($zoneId <= 0 || !ZoneRepository::isActive($zoneId)) {
    respond_error(422, 'Wybierz strefę lokalu, z której korzystasz.');
}
if (!in_array($fulfillment, ['na_miejscu', 'na_wynos', 'dostawa'], true)) {
    respond_error(422, 'Nieprawidłowy sposób odbioru.');
}
if (!in_array($paymentMethod, ['przelewy24', 'na_miejscu'], true)) {
    respond_error(422, 'Nieprawidłowa metoda płatności.');
}
if ($fulfillment === 'dostawa' && trim((string) ($payload['delivery_street'] ?? '')) === '') {
    respond_error(422, 'Podaj adres dostawy (ulica i numer).');
}

$customer = [
    'customer_name'         => mb_substr($customerName, 0, 96),
    'customer_phone'        => mb_substr($customerPhone, 0, 24),
    'customer_email'        => (mb_substr(trim((string) ($payload['customer_email'] ?? '')), 0, 128)) ?: null,
    'fulfillment'           => $fulfillment,
    'zone_id'               => $zoneId,
    'payment_method'        => $paymentMethod,
    'notes'                 => (mb_substr(trim((string) ($payload['notes'] ?? '')), 0, 2000)) ?: null,
    'delivery_street'       => (mb_substr(trim((string) ($payload['delivery_street'] ?? '')), 0, 128)) ?: null,
    'delivery_city'         => (mb_substr(trim((string) ($payload['delivery_city'] ?? '')), 0, 64)) ?: null,
    'delivery_postal_code'  => (mb_substr(trim((string) ($payload['delivery_postal_code'] ?? '')), 0, 12)) ?: null,
    'delivery_notes'        => (mb_substr(trim((string) ($payload['delivery_notes'] ?? '')), 0, 255)) ?: null,
    'client_ip'             => $ip,
];

$config = Database::loadConfig();

try {
    $order = OrderRepository::createOrder($customer, (array) ($payload['items'] ?? []), $config['app']['order_number_prefix'] ?? 'ACE');
} catch (InvalidArgumentException $e) {
    respond_error(422, $e->getMessage());
}

if ($paymentMethod === 'przelewy24') {
    $baseUrl = rtrim((string) $config['app']['base_url'], '/');

    try {
        $p24 = Przelewy24Client::fromAppConfig();
        $redirectUrl = $p24->registerTransaction([
            'sessionId'   => $order['orderNumber'],
            'amount'      => $order['totalCents'],
            'description' => 'Zamówienie ' . $order['orderNumber'] . ' — FAST FOOD',
            'email'       => $customer['customer_email'] ?? 'zamowienia@ace-burger.pl',
            'urlReturn'   => $baseUrl . '/zamowienie/platnosc-powrot.php?nr=' . urlencode($order['orderNumber']),
            'urlStatus'   => $baseUrl . '/api/p24-webhook.php',
        ]);
        OrderRepository::createPaymentRecord($order['orderId'], $order['orderNumber'], $order['totalCents']);
    } catch (Throwable $e) {
        // Zamówienie JUŻ istnieje w bazie (status "nowe") mimo błędu bramki —
        // klient może zadzwonić z numerem zamówienia, obsługa widzi je w panelu.
        respond_error(
            502,
            'Zamówienie zostało zapisane (nr ' . $order['orderNumber'] . '), ale nie udało się połączyć ' .
            'z bramką płatności. Zadzwoń: 729 110 496, podając numer zamówienia.'
        );
    }

    echo json_encode(['orderNumber' => $order['orderNumber'], 'redirectUrl' => $redirectUrl], JSON_UNESCAPED_UNICODE);
    exit;
}

// payment_method === 'na_miejscu' — zamówienie od razu widoczne w panelu kuchni.
echo json_encode(['orderNumber' => $order['orderNumber']], JSON_UNESCAPED_UNICODE);

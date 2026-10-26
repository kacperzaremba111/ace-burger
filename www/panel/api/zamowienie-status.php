<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/src/bootstrap.php';

Auth::requireRole(['admin', 'pracownik']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../zamowienia.php');
    exit;
}

if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
    Flash::set('error', 'Sesja wygasła — spróbuj ponownie.');
    header('Location: ../zamowienia.php');
    exit;
}

$orderId = (int) ($_POST['order_id'] ?? 0);
$status  = (string) ($_POST['status'] ?? '');

try {
    // Auth::zoneScope() — pracownik może zmienić status tylko w swojej strefie.
    OrderRepository::updateStatus($orderId, $status, Auth::zoneScope());
} catch (InvalidArgumentException $e) {
    Flash::set('error', $e->getMessage());
}

// Powrót do tego samego widoku/filtra strefy, z którego przyszło kliknięcie
// (tylko query string ze strony zamówień — nie przekierowujemy na obce adresy).
$referer = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''));
$back    = '../zamowienia.php';
if (($referer['path'] ?? '') === '/panel/zamowienia.php' && isset($referer['query'])) {
    parse_str($referer['query'], $params);
    $back .= '?' . http_build_query(array_intersect_key($params, ['view' => 1, 'strefa' => 1]));
}

header('Location: ' . $back);
exit;

<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/src/bootstrap.php';

Auth::requireRole(['admin', 'kuchnia']);

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
    OrderRepository::updateStatus($orderId, $status);
} catch (InvalidArgumentException $e) {
    Flash::set('error', $e->getMessage());
}

header('Location: ../zamowienia.php');
exit;

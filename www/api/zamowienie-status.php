<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$orderNumber = trim((string) ($_GET['nr'] ?? ''));
$order       = $orderNumber !== '' ? OrderRepository::findByNumber($orderNumber) : null;

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Nie znaleziono zamówienia.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'orderNumber' => $order['order_number'],
    'status'      => $order['status'],
], JSON_UNESCAPED_UNICODE);

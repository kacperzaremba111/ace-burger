<?php

declare(strict_types=1);

/**
 * Zadanie cron (OVH Manager → Cron, co 10–15 min):
 *   php cron/oczysc-zamowienia.php
 *
 * 1. Anuluje porzucone zamówienia Przelewy24: status "nowe" (płatność
 *    nigdy nie została potwierdzona), starsze niż 30 minut — klient
 *    prawdopodobnie zamknął stronę bramki w trakcie płatności.
 *    NIE dotyczy zamówień payment_method="na_miejscu" (te są ważne
 *    od razu, bez oczekiwania na płatność online).
 * 2. Czyści stare wpisy throttlingu (rate_limit_hits) — bez tego tabela
 *    rosłaby bezterminowo.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ten skrypt można uruchomić tylko z linii komend (cron).');
}

require_once __DIR__ . '/../src/bootstrap.php';

$pdo = Database::pdo();

$cancelStmt = $pdo->prepare(
    "UPDATE orders SET status = 'anulowane'
     WHERE status = 'nowe' AND payment_method = 'przelewy24'
       AND created_at < (NOW() - INTERVAL 30 MINUTE)"
);
$cancelStmt->execute();
$cancelledCount = $cancelStmt->rowCount();

$purgeStmt = $pdo->prepare('DELETE FROM rate_limit_hits WHERE created_at < (NOW() - INTERVAL 1 DAY)');
$purgeStmt->execute();
$purgedCount = $purgeStmt->rowCount();

printf(
    "[%s] Anulowano %d porzuconych zamówień Przelewy24, wyczyszczono %d starych wpisów throttlingu.\n",
    date('Y-m-d H:i:s'),
    $cancelledCount,
    $purgedCount
);

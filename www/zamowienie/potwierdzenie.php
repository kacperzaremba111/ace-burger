<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

$orderNumber = trim((string) ($_GET['nr'] ?? ''));
$order       = $orderNumber !== '' ? OrderRepository::findByNumber($orderNumber) : null;
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Zamówienie przyjęte — FAST FOOD</title>
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/checkout.css">
</head>
<body>
  <div class="grain" aria-hidden="true"></div>
  <main class="checkout">
    <div class="checkout__inner panel-block" style="text-align:center">
      <?php if (!$order): ?>
        <h1 class="section-title section-title--sm">Nie znaleziono zamówienia</h1>
        <p class="section-lede">Sprawdź, czy link jest poprawny, albo zadzwoń: <a href="tel:123456789" style="color:var(--color-mustard)">123 456 789</a>.</p>
      <?php else: ?>
        <h1 class="section-title section-title--sm">Dzięki! Zamówienie przyjęte</h1>
        <p class="section-lede">
          Numer zamówienia: <strong><?= h($order['order_number']) ?></strong><br>
          <?php if (!empty($order['zone_name'])): ?>Strefa: <strong><?= h($order['zone_name']) ?></strong><br><?php endif; ?>
          Płatność przy odbiorze — do zapłaty <strong><?= h(money_pln((int) $order['total_cents'])) ?></strong>.
        </p>
        <p class="checkout__status" style="margin-top:1.5rem">
          W razie pytań zadzwoń: <a href="tel:123456789" style="color:var(--color-mustard)">123 456 789</a>.
        </p>
      <?php endif; ?>
      <a href="../index.php" class="btn btn--mustard" style="margin-top:1.5rem; display:inline-block">Wróć na stronę główną</a>
    </div>
  </main>
</body>
</html>

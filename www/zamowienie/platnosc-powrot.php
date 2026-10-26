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
  <title>Sprawdzamy płatność — FAST FOOD</title>
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
        <h1 class="section-title section-title--sm">Numer zamówienia: <?= h($order['order_number']) ?></h1>
        <p id="statusText" class="section-lede" data-order-number="<?= h($order['order_number']) ?>">
          Sprawdzamy status płatności…
        </p>
        <p class="checkout__status" style="margin-top:1.5rem">
          Jeśli status nie zmieni się przez dłuższą chwilę, a płatność została pobrana —
          zamówienie i tak trafiło do naszej kuchni. W razie wątpliwości zadzwoń:
          <a href="tel:123456789" style="color:var(--color-mustard)">123 456 789</a>.
        </p>
      <?php endif; ?>
    </div>
  </main>
  <script src="../assets/js/order-status.js" defer></script>
</body>
</html>

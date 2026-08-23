<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

Auth::requireRole(['admin', 'kuchnia']);

$view = ($_GET['view'] ?? '') === 'wszystkie' ? 'wszystkie' : 'aktywne';
$activeStatuses = ['nowe', 'oplacone', 'w_przygotowaniu', 'gotowe', 'w_dostawie'];
$allStatuses    = array_keys(OrderRepository::STATUS_LABELS);
$statuses       = $view === 'wszystkie' ? $allStatuses : $activeStatuses;

$user      = Auth::user();
$csrfToken = Csrf::token();
$fragment  = OrderRepository::renderOrdersFragment($statuses, $csrfToken);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Zamówienia — Panel ACE BURGER</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="panel.css">
</head>
<body>
  <div class="panel-shell">
    <header class="panel-header">
      <span class="panel-header__brand">ACE BURGER — Panel</span>
      <span class="panel-header__user">
        <?= h($user['full_name']) ?> (<?= h($user['role']) ?>)
        <a href="logout.php">Wyloguj</a>
      </span>
    </header>

    <nav class="panel-nav">
      <a href="index.php">Start</a>
      <a href="zamowienia.php" class="is-active">Zamówienia</a>
      <?php if ($user['role'] === 'admin'): ?>
        <a href="kategorie.php">Kategorie</a>
        <a href="menu.php">Menu</a>
        <a href="dodatki.php">Dodatki</a>
      <?php endif; ?>
    </nav>

    <nav class="panel-nav">
      <a href="zamowienia.php?view=aktywne" <?= $view === 'aktywne' ? 'class="is-active"' : '' ?>>Aktywne</a>
      <a href="zamowienia.php?view=wszystkie" <?= $view === 'wszystkie' ? 'class="is-active"' : '' ?>>Wszystkie</a>
      <button type="button" id="refreshBtn" class="btn-sm" style="margin-left:auto">Odśwież teraz</button>
    </nav>

    <div id="ordersList" class="orders-list" data-view="<?= h($view) ?>">
      <?= $fragment ?>
    </div>
  </div>

  <script src="orders-poll.js" defer></script>
</body>
</html>

<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

Auth::requireLogin();
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel — ACE BURGER</title>
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

    <!-- Link do zamowienia.php doljczy w Fazie 6 (panel kuchni). -->
    <nav class="panel-nav">
      <?php if ($user['role'] === 'admin'): ?>
        <a href="kategorie.php">Kategorie</a>
        <a href="menu.php">Menu</a>
        <a href="dodatki.php">Dodatki</a>
      <?php endif; ?>
    </nav>

    <div class="panel-card">
      <p>Zalogowano jako <strong><?= h($user['username']) ?></strong>, rola: <strong><?= h($user['role']) ?></strong>.</p>
    </div>
  </div>
</body>
</html>

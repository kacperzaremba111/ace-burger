<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

Auth::requireRole(['admin', 'pracownik']);
$user    = Auth::user();
$isAdmin = $user['role'] === 'admin';

$zones        = ZoneRepository::all(onlyActive: false);
$activeCounts = OrderRepository::activeCountByZone();
$myZoneId     = Auth::zoneScope();
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
    <?= PanelView::header($user, 'start') ?>

    <?php if ($isAdmin): ?>
      <div class="panel-card">
        <p>Zalogowano jako <strong><?= h($user['username']) ?></strong> — podgląd ogólny wszystkich stref.</p>
      </div>

      <div class="zone-grid">
        <?php foreach ($zones as $zone): $zid = (int) $zone['id']; ?>
          <a class="zone-tile" href="zamowienia.php?strefa=<?= $zid ?>">
            <span class="zone-tile__name"><?= h($zone['name']) ?></span>
            <span class="zone-tile__count"><?= $activeCounts[$zid] ?? 0 ?></span>
            <span class="zone-tile__label">aktywnych zamówień</span>
          </a>
        <?php endforeach; ?>
        <?php if (!empty($activeCounts[0])): ?>
          <a class="zone-tile" href="zamowienia.php">
            <span class="zone-tile__name">Bez strefy (starsze)</span>
            <span class="zone-tile__count"><?= $activeCounts[0] ?></span>
            <span class="zone-tile__label">aktywnych zamówień</span>
          </a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="panel-card">
        <p>Zalogowano jako <strong><?= h($user['username']) ?></strong>.</p>
        <p>Twoja strefa: <strong><?= h($user['zone_name']) ?></strong> —
          aktywnych zamówień: <strong><?= $activeCounts[$myZoneId] ?? 0 ?></strong>.</p>
        <p><a class="btn" href="zamowienia.php">Przejdź do zamówień</a></p>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>

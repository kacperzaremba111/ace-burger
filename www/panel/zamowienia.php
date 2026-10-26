<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

Auth::requireRole(['admin', 'pracownik']);

$view = ($_GET['view'] ?? '') === 'wszystkie' ? 'wszystkie' : 'aktywne';
$activeStatuses = ['nowe', 'oplacone', 'w_przygotowaniu', 'gotowe', 'w_dostawie'];
$allStatuses    = array_keys(OrderRepository::STATUS_LABELS);
$statuses       = $view === 'wszystkie' ? $allStatuses : $activeStatuses;

// Pracownik: zawsze tylko jego strefa (parametr ?strefa jest ignorowany).
// Admin: wszystkie strefy albo jedna wybrana filtrem.
$scope   = Auth::zoneScope();
$isAdmin = $scope === null;
$zoneId  = $isAdmin ? (((int) ($_GET['strefa'] ?? 0)) ?: null) : $scope;
$zones   = $isAdmin ? ZoneRepository::all(onlyActive: false) : [];

$user      = Auth::user();
$flash     = Flash::take();
$csrfToken = Csrf::token();
$fragment  = OrderRepository::renderOrdersFragment($statuses, $zoneId, $csrfToken);

$query = static fn (array $params): string => 'zamowienia.php?' . http_build_query(array_filter($params));
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
    <?= PanelView::header($user, 'zamowienia') ?>

    <?php if ($flash): ?>
      <p class="alert alert--<?= h($flash['type']) ?>"><?= h($flash['message']) ?></p>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
      <nav class="panel-nav">
        <a href="<?= h($query(['view' => $view])) ?>" <?= $zoneId === null ? 'class="is-active"' : '' ?>>Wszystkie strefy</a>
        <?php foreach ($zones as $zone): $zid = (int) $zone['id']; ?>
          <a href="<?= h($query(['view' => $view, 'strefa' => $zid])) ?>" <?= $zoneId === $zid ? 'class="is-active"' : '' ?>><?= h($zone['name']) ?></a>
        <?php endforeach; ?>
      </nav>
    <?php else: ?>
      <p class="zone-banner">Strefa: <strong><?= h($user['zone_name']) ?></strong> — widzisz tylko zamówienia z tej strefy.</p>
    <?php endif; ?>

    <nav class="panel-nav">
      <a href="<?= h($query(['view' => 'aktywne', 'strefa' => $isAdmin ? $zoneId : null])) ?>" <?= $view === 'aktywne' ? 'class="is-active"' : '' ?>>Aktywne</a>
      <a href="<?= h($query(['view' => 'wszystkie', 'strefa' => $isAdmin ? $zoneId : null])) ?>" <?= $view === 'wszystkie' ? 'class="is-active"' : '' ?>>Wszystkie</a>
      <button type="button" id="refreshBtn" class="btn-sm" style="margin-left:auto">Odśwież teraz</button>
    </nav>

    <div id="ordersList" class="orders-list" data-view="<?= h($view) ?>" data-zone="<?= $isAdmin ? (int) $zoneId : '' ?>">
      <?= $fragment ?>
    </div>
  </div>

  <script src="orders-poll.js" defer></script>
</body>
</html>

<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

Auth::requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        Flash::set('error', 'Sesja wygasła — spróbuj ponownie.');
        header('Location: pracownicy.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'update') {
            StaffRepository::updateEmployee($id, (int) ($_POST['zone_id'] ?? 0), isset($_POST['is_active']));
            Flash::set('success', 'Zapisano zmiany.');
        } elseif ($action === 'reset_password') {
            $password = StaffRepository::resetPassword($id);
            $username = (string) ($_POST['username'] ?? '');
            // Hasło pokazywane jednorazowo (flash) — w bazie zostaje tylko hash.
            Flash::set('success', "Nowe hasło dla „{$username}”: {$password} — zapisz je teraz, nie będzie pokazane ponownie.");
        }
    } catch (PDOException $e) {
        Flash::set('error', 'Błąd bazy danych.');
    } catch (Throwable $e) {
        Flash::set('error', $e->getMessage());
    }

    header('Location: pracownicy.php');
    exit;
}

$staff     = StaffRepository::allWithStats();
$zones     = ZoneRepository::all(onlyActive: false);
$user      = Auth::user();
$flash     = Flash::take();
$csrfToken = Csrf::token();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pracownicy — Panel FAST FOOD</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="panel.css">
  <script src="panel-confirm.js" defer></script>
</head>
<body>
  <div class="panel-shell">
    <?= PanelView::header($user, 'pracownicy') ?>

    <?php if ($flash): ?>
      <p class="alert alert--<?= h($flash['type']) ?>"><?= h($flash['message']) ?></p>
    <?php endif; ?>

    <!-- Formularze per-wiersz poza <table>, powiązane atrybutem form="..."
         — patrz komentarz w kategorie.php po wyjaśnienie dlaczego. -->
    <?php foreach ($staff as $s): $id = (int) $s['id']; ?>
      <form id="st-upd-<?= $id ?>" method="post" action="pracownicy.php">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $id ?>">
      </form>
      <form id="st-pwd-<?= $id ?>" method="post" action="pracownicy.php"
            class="confirm-delete" data-confirm-message="Wygenerować nowe hasło dla „<?= h($s['username']) ?>”? Stare przestanie działać.">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="username" value="<?= h($s['username']) ?>">
      </form>
    <?php endforeach; ?>

    <div class="panel-card" style="overflow-x:auto">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Login</th><th>Imię / opis</th><th>Strefa</th><th>Aktywne zam.</th>
            <th>Dziś</th><th>Ostatnie logowanie</th><th>Aktywny</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($staff as $s): $id = (int) $s['id']; $isEmployee = $s['role'] === 'pracownik'; ?>
          <tr>
            <td><strong><?= h($s['username']) ?></strong></td>
            <td><?= h($s['full_name']) ?></td>
            <td>
              <?php if ($isEmployee): ?>
                <select name="zone_id" form="st-upd-<?= $id ?>">
                  <?php foreach ($zones as $z): ?>
                    <option value="<?= (int) $z['id'] ?>" <?= (int) $z['id'] === (int) $s['zone_id'] ? 'selected' : '' ?>><?= h($z['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <em>wszystkie (admin)</em>
              <?php endif; ?>
            </td>
            <td><?= $isEmployee ? (int) $s['active_orders'] : '—' ?></td>
            <td><?= $isEmployee ? (int) $s['today_orders'] : '—' ?></td>
            <td><?= $s['last_login_at'] ? h(date('H:i, d.m.Y', strtotime((string) $s['last_login_at']))) : 'nigdy' ?></td>
            <td>
              <?php if ($isEmployee): ?>
                <input type="checkbox" name="is_active" form="st-upd-<?= $id ?>" <?= $s['is_active'] ? 'checked' : '' ?>>
              <?php else: ?>
                tak
              <?php endif; ?>
            </td>
            <td class="col-actions">
              <?php if ($isEmployee): ?>
                <button type="submit" form="st-upd-<?= $id ?>" class="btn-sm">Zapisz</button>
              <?php endif; ?>
              <button type="submit" form="st-pwd-<?= $id ?>" class="btn-sm btn-sm--danger">Nowe hasło</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="panel-hint">
      Pracownik widzi i obsługuje wyłącznie zamówienia ze swojej strefy. Zmiana strefy działa od
      następnego odświeżenia jego panelu. Hasła są zapisane w bazie tylko jako hash — „Nowe hasło”
      generuje losowe hasło i pokazuje je jeden raz.
    </p>
  </div>
</body>
</html>

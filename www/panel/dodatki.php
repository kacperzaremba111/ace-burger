<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

Auth::requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        Flash::set('error', 'Sesja wygasła — spróbuj ponownie.');
        header('Location: dodatki.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'create') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            if ($name === '') {
                throw new InvalidArgumentException('Podaj nazwę dodatku.');
            }
            $priceCents = parse_price_to_cents((string) ($_POST['price'] ?? ''));
            MenuRepository::createAddon($name, $priceCents, $sortOrder);
            Flash::set('success', 'Dodano dodatek „' . $name . '”.');
        } elseif ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim((string) ($_POST['name'] ?? ''));
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            $isAvailable = isset($_POST['is_available']);
            if ($name === '') {
                throw new InvalidArgumentException('Podaj nazwę dodatku.');
            }
            $priceCents = parse_price_to_cents((string) ($_POST['price'] ?? ''));
            MenuRepository::updateAddon($id, $name, $priceCents, $sortOrder, $isAvailable);
            Flash::set('success', 'Zapisano zmiany.');
        } elseif ($action === 'delete') {
            MenuRepository::deleteAddon((int) ($_POST['id'] ?? 0));
            Flash::set('success', 'Usunięto dodatek.');
        }
    } catch (PDOException $e) {
        Flash::set('error', 'Błąd bazy danych.');
    } catch (Throwable $e) {
        Flash::set('error', $e->getMessage());
    }

    header('Location: dodatki.php');
    exit;
}

$addons    = MenuRepository::addons(onlyAvailable: false);
$user      = Auth::user();
$flash     = Flash::take();
$csrfToken = Csrf::token();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dodatki — Panel ACE BURGER</title>
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
      <a href="kategorie.php">Kategorie</a>
      <a href="menu.php">Menu</a>
      <a href="dodatki.php">Dodatki</a>
    </nav>

    <?php if ($flash): ?>
      <p class="alert alert--<?= h($flash['type']) ?>"><?= h($flash['message']) ?></p>
    <?php endif; ?>

    <!-- Formularze per-wiersz poza <table>, powiązane atrybutem form="..."
         — patrz komentarz w kategorie.php po wyjaśnienie dlaczego. -->
    <?php foreach ($addons as $a): $id = (int) $a['id']; ?>
      <form id="ad-upd-<?= $id ?>" method="post" action="dodatki.php">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $id ?>">
      </form>
      <form id="ad-del-<?= $id ?>" method="post" action="dodatki.php"
            onsubmit="return confirm('Usunąć dodatek &quot;<?= h($a['name']) ?>&quot;?');">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= $id ?>">
      </form>
    <?php endforeach; ?>

    <div class="panel-card">
      <table class="panel-table">
        <thead>
          <tr><th>Nazwa</th><th>Cena (zł)</th><th>Dostępny</th><th>Kolejność</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($addons as $a): $id = (int) $a['id']; ?>
          <tr>
            <td><input type="text" name="name" form="ad-upd-<?= $id ?>" value="<?= h($a['name']) ?>" required style="min-width:20ch"></td>
            <td><input type="text" name="price" form="ad-upd-<?= $id ?>" value="<?= h(price_display((int) $a['price_cents'])) ?>" style="width:7ch" required></td>
            <td><input type="checkbox" name="is_available" form="ad-upd-<?= $id ?>" <?= $a['is_available'] ? 'checked' : '' ?>></td>
            <td><input type="number" name="sort_order" form="ad-upd-<?= $id ?>" value="<?= (int) $a['sort_order'] ?>" style="width:5ch"></td>
            <td class="col-actions">
              <button type="submit" form="ad-upd-<?= $id ?>" class="btn-sm">Zapisz</button>
              <button type="submit" form="ad-del-<?= $id ?>" class="btn-sm btn-sm--danger">Usuń</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="panel-section">
      <h2>Dodaj dodatek</h2>
      <form method="post" action="dodatki.php" class="inline-form panel-card">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <input type="hidden" name="action" value="create">
        <div class="field"><label>Nazwa</label><input type="text" name="name" style="min-width:20ch" required></div>
        <div class="field"><label>Cena (zł)</label><input type="text" name="price" placeholder="np. 5" style="width:8ch" required></div>
        <div class="field"><label>Kolejność</label><input type="number" name="sort_order" value="0" style="width:6ch"></div>
        <button type="submit" class="btn">Dodaj</button>
      </form>
    </div>
  </div>
</body>
</html>

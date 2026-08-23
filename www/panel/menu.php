<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

Auth::requireRole(['admin']);

/** Wyciąga i waliduje wspólne pola formularza pozycji menu z $_POST. */
function menu_item_form_data(): array
{
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $name       = trim((string) ($_POST['name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $tag        = (string) ($_POST['tag'] ?? 'none');
    $sortOrder  = (int) ($_POST['sort_order'] ?? 0);

    if ($categoryId <= 0) {
        throw new InvalidArgumentException('Wybierz kategorię.');
    }
    if ($name === '') {
        throw new InvalidArgumentException('Podaj nazwę pozycji.');
    }
    if (!in_array($tag, ['none', 'hot', 'bbq'], true)) {
        throw new InvalidArgumentException('Nieprawidłowy tag.');
    }

    return [
        'category_id'  => $categoryId,
        'name'         => $name,
        'description'  => $description,
        'price_cents'  => parse_price_to_cents((string) ($_POST['price'] ?? '')),
        'tag'          => $tag,
        'is_featured'  => isset($_POST['is_featured']),
        'is_available' => isset($_POST['is_available']),
        'sort_order'   => $sortOrder,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        Flash::set('error', 'Sesja wygasła — spróbuj ponownie.');
        header('Location: menu.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'create') {
            MenuRepository::createMenuItem(menu_item_form_data());
            Flash::set('success', 'Dodano pozycję menu.');
        } elseif ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            MenuRepository::updateMenuItem($id, menu_item_form_data());
            Flash::set('success', 'Zapisano zmiany.');
        } elseif ($action === 'delete') {
            MenuRepository::deleteMenuItem((int) ($_POST['id'] ?? 0));
            Flash::set('success', 'Usunięto pozycję menu.');
        }
    } catch (PDOException $e) {
        Flash::set('error', 'Błąd bazy danych.');
    } catch (Throwable $e) {
        Flash::set('error', $e->getMessage());
    }

    header('Location: menu.php');
    exit;
}

$items      = MenuRepository::allMenuItemsWithCategory();
$categories = MenuRepository::categories(onlyActive: false);
$user       = Auth::user();
$flash      = Flash::take();
$csrfToken  = Csrf::token();

$tagLabel = ['none' => '—', 'hot' => 'OSTRY', 'bbq' => 'BBQ'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu — Panel ACE BURGER</title>
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
      <a href="zamowienia.php">Zamówienia</a>
      <a href="kategorie.php">Kategorie</a>
      <a href="menu.php" class="is-active">Menu</a>
      <a href="dodatki.php">Dodatki</a>
    </nav>

    <?php if ($flash): ?>
      <p class="alert alert--<?= h($flash['type']) ?>"><?= h($flash['message']) ?></p>
    <?php endif; ?>

    <!-- Formularze per-wiersz poza <table>, powiązane atrybutem form="..."
         — patrz komentarz w kategorie.php po wyjaśnienie dlaczego. -->
    <?php foreach ($items as $item): $id = (int) $item['id']; ?>
      <form id="mi-upd-<?= $id ?>" method="post" action="menu.php">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $id ?>">
      </form>
      <form id="mi-del-<?= $id ?>" method="post" action="menu.php"
            onsubmit="return confirm('Usunąć pozycję &quot;<?= h($item['name']) ?>&quot;?');">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= $id ?>">
      </form>
    <?php endforeach; ?>

    <div class="panel-card" style="overflow-x:auto">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Kategoria</th><th>Nazwa</th><th>Opis</th><th>Cena (zł)</th><th>Tag</th>
            <th>Wyróżniona</th><th>Dostępna</th><th>Kolejność</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): $id = (int) $item['id']; ?>
          <tr>
            <td>
              <select name="category_id" form="mi-upd-<?= $id ?>">
                <?php foreach ($categories as $c): ?>
                  <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $item['category_id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input type="text" name="name" form="mi-upd-<?= $id ?>" value="<?= h($item['name']) ?>" required style="min-width:12ch"></td>
            <td><input type="text" name="description" form="mi-upd-<?= $id ?>" value="<?= h($item['description'] ?? '') ?>" style="min-width:22ch"></td>
            <td><input type="text" name="price" form="mi-upd-<?= $id ?>" value="<?= h(price_display((int) $item['price_cents'])) ?>" style="width:7ch" required></td>
            <td>
              <select name="tag" form="mi-upd-<?= $id ?>">
                <?php foreach ($tagLabel as $val => $label): ?>
                  <option value="<?= h($val) ?>" <?= $item['tag'] === $val ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input type="checkbox" name="is_featured" form="mi-upd-<?= $id ?>" <?= $item['is_featured'] ? 'checked' : '' ?>></td>
            <td><input type="checkbox" name="is_available" form="mi-upd-<?= $id ?>" <?= $item['is_available'] ? 'checked' : '' ?>></td>
            <td><input type="number" name="sort_order" form="mi-upd-<?= $id ?>" value="<?= (int) $item['sort_order'] ?>" style="width:5ch"></td>
            <td class="col-actions">
              <button type="submit" form="mi-upd-<?= $id ?>" class="btn-sm">Zapisz</button>
              <button type="submit" form="mi-del-<?= $id ?>" class="btn-sm btn-sm--danger">Usuń</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="panel-section">
      <h2>Dodaj pozycję menu</h2>
      <form method="post" action="menu.php" class="inline-form panel-card">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <input type="hidden" name="action" value="create">
        <div class="field">
          <label>Kategoria</label>
          <select name="category_id">
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int) $c['id'] ?>"><?= h($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Nazwa</label><input type="text" name="name" required></div>
        <div class="field"><label>Opis (składniki)</label><input type="text" name="description" style="min-width:22ch"></div>
        <div class="field"><label>Cena (zł)</label><input type="text" name="price" placeholder="np. 32" style="width:8ch" required></div>
        <div class="field">
          <label>Tag</label>
          <select name="tag">
            <option value="none">—</option>
            <option value="hot">OSTRY</option>
            <option value="bbq">BBQ</option>
          </select>
        </div>
        <div class="field"><label class="checkbox-field"><input type="checkbox" name="is_featured"> wyróżniona</label></div>
        <div class="field"><label class="checkbox-field"><input type="checkbox" name="is_available" checked> dostępna</label></div>
        <div class="field"><label>Kolejność</label><input type="number" name="sort_order" value="0" style="width:6ch"></div>
        <button type="submit" class="btn">Dodaj</button>
      </form>
    </div>
  </div>
</body>
</html>

<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

Auth::requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        Flash::set('error', 'Sesja wygasła — spróbuj ponownie.');
        header('Location: kategorie.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'create') {
            $slug = trim((string) ($_POST['slug'] ?? ''));
            $name = trim((string) ($_POST['name'] ?? ''));
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            if ($slug === '' || $name === '') {
                throw new InvalidArgumentException('Podaj slug i nazwę kategorii.');
            }
            MenuRepository::createCategory($slug, $name, $sortOrder);
            Flash::set('success', 'Dodano kategorię „' . $name . '”.');
        } elseif ($action === 'update') {
            $id        = (int) ($_POST['id'] ?? 0);
            $slug      = trim((string) ($_POST['slug'] ?? ''));
            $name      = trim((string) ($_POST['name'] ?? ''));
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            $isActive  = isset($_POST['is_active']);
            if ($slug === '' || $name === '') {
                throw new InvalidArgumentException('Podaj slug i nazwę kategorii.');
            }
            MenuRepository::updateCategory($id, $slug, $name, $sortOrder, $isActive);
            Flash::set('success', 'Zapisano zmiany.');
        } elseif ($action === 'delete') {
            MenuRepository::deleteCategory((int) ($_POST['id'] ?? 0));
            Flash::set('success', 'Usunięto kategorię.');
        }
    } catch (PDOException $e) {
        Flash::set('error', $e->getCode() === '23000' ? 'Taki slug już istnieje.' : 'Błąd bazy danych.');
    } catch (Throwable $e) {
        Flash::set('error', $e->getMessage());
    }

    header('Location: kategorie.php');
    exit;
}

$categories = MenuRepository::categories(onlyActive: false);
$user       = Auth::user();
$flash      = Flash::take();
$csrfToken  = Csrf::token();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kategorie — Panel ACE BURGER</title>
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
      <a href="kategorie.php" class="is-active">Kategorie</a>
      <a href="menu.php">Menu</a>
      <a href="dodatki.php">Dodatki</a>
    </nav>

    <?php if ($flash): ?>
      <p class="alert alert--<?= h($flash['type']) ?>"><?= h($flash['message']) ?></p>
    <?php endif; ?>

    <!--
      Formularze per-wiersz zadeklarowane TUTAJ (poza <table>), a powiązane
      z kontrolkami wewnątrz komórek przez atrybut form="...". Celowo NIE
      owijamy <tr> w <form> — przeglądarka "wyfosterowuje" (foster-parenting)
      element <form> otwarty bezpośrednio wewnątrz wiersza tabeli poza całą
      tabelę, co rozwala layout. To jest poprawny, wspierany wzorzec HTML5.
    -->
    <?php foreach ($categories as $c): $id = (int) $c['id']; ?>
      <form id="cat-upd-<?= $id ?>" method="post" action="kategorie.php">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $id ?>">
      </form>
      <form id="cat-del-<?= $id ?>" method="post" action="kategorie.php"
            onsubmit="return confirm('Usunąć kategorię &quot;<?= h($c['name']) ?>&quot;?');">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= $id ?>">
      </form>
    <?php endforeach; ?>

    <div class="panel-card">
      <table class="panel-table">
        <thead>
          <tr>
            <th>Slug</th><th>Nazwa</th><th>Kolejność</th><th>Aktywna</th><th>Pozycji</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $c): $id = (int) $c['id']; $count = MenuRepository::categoryItemCount($id); ?>
          <tr>
            <td><input type="text" name="slug" form="cat-upd-<?= $id ?>" value="<?= h($c['slug']) ?>" required></td>
            <td><input type="text" name="name" form="cat-upd-<?= $id ?>" value="<?= h($c['name']) ?>" required></td>
            <td><input type="number" name="sort_order" form="cat-upd-<?= $id ?>" value="<?= (int) $c['sort_order'] ?>" style="width:5ch"></td>
            <td><label class="checkbox-field"><input type="checkbox" name="is_active" form="cat-upd-<?= $id ?>" <?= $c['is_active'] ? 'checked' : '' ?>> aktywna</label></td>
            <td><?= $count ?></td>
            <td class="col-actions">
              <button type="submit" form="cat-upd-<?= $id ?>" class="btn-sm">Zapisz</button>
              <button type="submit" form="cat-del-<?= $id ?>" class="btn-sm btn-sm--danger"
                <?= $count > 0 ? 'disabled title="Kategoria ma przypisane pozycje menu"' : '' ?>>Usuń</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="panel-section">
      <h2>Dodaj kategorię</h2>
      <form method="post" action="kategorie.php" class="inline-form panel-card">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
        <input type="hidden" name="action" value="create">
        <div class="field"><label>Slug</label><input type="text" name="slug" placeholder="np. sezonowe" required></div>
        <div class="field"><label>Nazwa</label><input type="text" name="name" placeholder="np. Burgery Sezonowe" required></div>
        <div class="field"><label>Kolejność</label><input type="number" name="sort_order" value="0" style="width:6ch"></div>
        <button type="submit" class="btn">Dodaj</button>
      </form>
    </div>
  </div>
</body>
</html>

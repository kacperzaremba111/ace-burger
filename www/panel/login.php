<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

Session::start();

// Już zalogowany? Nie ma po co pokazywać formularza logowania ponownie.
if (Auth::isLoggedIn()) {
    header('Location: /panel/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfOk    = Csrf::verify($_POST['csrf_token'] ?? null);
    $username  = trim((string) ($_POST['username'] ?? ''));
    $password  = (string) ($_POST['password'] ?? '');
    $ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (!$csrfOk) {
        $error = 'Sesja wygasła — odśwież stronę i spróbuj ponownie.';
    } elseif ($username === '' || $password === '') {
        $error = 'Podaj login i hasło.';
    } elseif (!Auth::attempt($username, $password, $ip)) {
        $error = 'Błędny login/hasło (lub zbyt wiele prób — spróbuj za chwilę).';
    } else {
        header('Location: /panel/index.php');
        exit;
    }
}

$csrfField = Csrf::field();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logowanie — Panel ACE BURGER</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="panel.css">
</head>
<body>
  <div class="login-form">
    <h1>Panel ACE BURGER</h1>

    <?php if ($error !== null): ?>
      <p class="alert alert--error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= $csrfField ?>
      <div class="field">
        <label for="username">Login</label>
        <input type="text" id="username" name="username" autocomplete="username" required autofocus>
      </div>
      <div class="field">
        <label for="password">Hasło</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn">Zaloguj</button>
    </form>
  </div>
</body>
</html>

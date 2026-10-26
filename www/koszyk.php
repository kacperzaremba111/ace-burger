<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$csrfField = Csrf::field();
$zones     = ZoneRepository::all();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Koszyk i zamówienie — FAST FOOD</title>
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/checkout.css">
</head>
<body>
  <div class="grain" aria-hidden="true"></div>

  <header class="site-header">
    <div class="site-header__inner">
      <a href="index.php" class="logo" aria-label="FAST FOOD — strona główna">
        <span class="logo__text">FAST<em>FOOD</em></span>
      </a>
      <a href="index.php#smash" class="btn btn--outline">← Wróć do menu</a>
    </div>
  </header>

  <main id="main" class="checkout">
    <div class="checkout__inner">
      <h1 class="section-title">Twoje zamówienie</h1>

      <section class="checkout__summary panel-block" aria-labelledby="summaryHeading">
        <h2 id="summaryHeading" class="checkout__subtitle">Podsumowanie koszyka</h2>
        <div id="checkoutSummary"></div>
        <p id="checkoutEmpty" class="cart-empty" hidden>
          Koszyk jest pusty. <a href="index.php#smash">Wróć do menu</a>, żeby dodać burgery.
        </p>
        <div class="cart-total">
          <span>Razem</span>
          <span id="checkoutTotal">0 zł</span>
        </div>
      </section>

      <section class="checkout__form panel-block" aria-labelledby="formHeading">
        <h2 id="formHeading" class="checkout__subtitle">Dane do zamówienia</h2>

        <p id="checkoutStatus" class="checkout__status" role="status" aria-live="polite"></p>

        <form id="checkoutForm" novalidate>
          <?= $csrfField ?>

          <!-- Honeypot — niewidoczne dla ludzi pole; boty wypełniające formularz
               automatycznie zwykle je wypełniają, co pozwala odrzucić zgłoszenie
               po stronie serwera bez utrudniania życia prawdziwym klientom. -->
          <div class="field field--honeypot" aria-hidden="true">
            <label for="website">Zostaw puste</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
          </div>

          <div class="field">
            <label for="customer_name">Imię i nazwisko</label>
            <input type="text" id="customer_name" name="customer_name" required autocomplete="name">
          </div>

          <div class="field">
            <label for="customer_phone">Telefon</label>
            <input type="tel" id="customer_phone" name="customer_phone" required autocomplete="tel" placeholder="np. 600 123 456">
          </div>

          <div class="field">
            <label for="customer_email">E-mail (opcjonalnie)</label>
            <input type="email" id="customer_email" name="customer_email" autocomplete="email">
          </div>

          <fieldset class="field-group" id="zoneFieldset">
            <legend>Strefa lokalu, z której korzystasz</legend>
            <?php foreach ($zones as $zone): ?>
              <label class="radio-option">
                <input type="radio" name="zone_id" value="<?= (int) $zone['id'] ?>" required>
                <span><?= h($zone['name']) ?><?php if (!empty($zone['description'])): ?>
                  <small class="radio-option__hint"><?= h($zone['description']) ?></small><?php endif; ?></span>
              </label>
            <?php endforeach; ?>
          </fieldset>

          <fieldset class="field-group">
            <legend>Sposób odbioru</legend>
            <label class="radio-option"><input type="radio" name="fulfillment" value="na_miejscu" checked> Na miejscu</label>
            <label class="radio-option"><input type="radio" name="fulfillment" value="na_wynos"> Na wynos</label>
            <label class="radio-option"><input type="radio" name="fulfillment" value="dostawa"> Dostawa</label>
          </fieldset>

          <div id="deliveryFields" class="delivery-fields" hidden>
            <div class="field">
              <label for="delivery_street">Ulica i numer</label>
              <input type="text" id="delivery_street" name="delivery_street" data-required-for-delivery="true">
            </div>
            <div class="field">
              <label for="delivery_city">Miejscowość</label>
              <input type="text" id="delivery_city" name="delivery_city" value="Radom" data-required-for-delivery="true">
            </div>
            <div class="field">
              <label for="delivery_postal_code">Kod pocztowy</label>
              <input type="text" id="delivery_postal_code" name="delivery_postal_code" placeholder="26-600">
            </div>
            <div class="field">
              <label for="delivery_notes">Uwagi do dostawy (piętro, domofon...)</label>
              <input type="text" id="delivery_notes" name="delivery_notes">
            </div>
          </div>

          <fieldset class="field-group">
            <legend>Płatność</legend>
            <label class="radio-option"><input type="radio" name="payment_method" value="przelewy24" checked> Przelewy24 (online)</label>
            <label class="radio-option"><input type="radio" name="payment_method" value="na_miejscu"> Przy odbiorze</label>
          </fieldset>

          <div class="field">
            <label for="notes">Uwagi do zamówienia (opcjonalnie)</label>
            <input type="text" id="notes" name="notes">
          </div>

          <button type="submit" id="checkoutSubmit" class="btn btn--fire checkout__submit" disabled>
            Złóż zamówienie
          </button>
        </form>
      </section>
    </div>
  </main>

  <script src="assets/js/cart.js" defer></script>
  <script src="assets/js/checkout.js" defer></script>
</body>
</html>

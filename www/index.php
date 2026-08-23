<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$categories = [];
foreach (MenuRepository::categories() as $category) {
    $category['items'] = MenuRepository::menuItemsByCategory((int) $category['id']);
    $categories[$category['slug']] = $category;
}

$smash     = $categories['smash']     ?? null;
$klasyczne = $categories['klasyczne'] ?? null;
$addons    = MenuRepository::addons();

// Dane dla assets/js/cart.js — lista dodatków do wyboru przy dodawaniu
// burgera do koszyka (te same dane, co w sekcji #doladuj, żeby cena
// zawsze była zgodna z tym, co widzi kuchnia po stronie serwera).
$addonsForJs = array_map(static fn (array $a): array => [
    'id'         => (int) $a['id'],
    'name'       => $a['name'],
    'priceCents' => (int) $a['price_cents'],
], $addons);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ACE BURGER — Smash burgery w Sokołowie Podlaskim | ul. Wolności 47/2</title>
  <meta name="description" content="ACE BURGER — smash burgery robione na gorącej blasze w Sokołowie Podlaskim. Zamów telefonicznie: 729 110 496. Otwarte codziennie, ul. Wolności 47/2.">
  <meta name="theme-color" content="#15110d">

  <!-- Open Graph -->
  <meta property="og:title" content="ACE BURGER — Sokołów Podlaski">
  <meta property="og:description" content="Smash burgery robione na gorącej blasze. Zamów: 729 110 496.">
  <meta property="og:type" content="website">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="assets/css/main.css">

  <!-- Włącza efekty scroll-reveal TYLKO jeśli JS działa; bez tego cała
       treść (patrz .reveal w main.css) jest w pełni widoczna od razu. -->
  <script>document.documentElement.classList.add('js');</script>

  <!-- Structured data: LocalBusiness -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "FastFoodRestaurant",
    "name": "ACE BURGER",
    "servesCuisine": "Burgers",
    "priceRange": "27-41 PLN",
    "telephone": "+48729110496",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "ul. Wolności 47/2",
      "addressLocality": "Sokołów Podlaski",
      "addressCountry": "PL"
    },
    "openingHoursSpecification": [
      { "@type": "OpeningHoursSpecification", "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday"], "opens": "11:00", "closes": "22:00" },
      { "@type": "OpeningHoursSpecification", "dayOfWeek": "Friday", "opens": "11:00", "closes": "23:00" },
      { "@type": "OpeningHoursSpecification", "dayOfWeek": "Saturday", "opens": "12:00", "closes": "23:00" },
      { "@type": "OpeningHoursSpecification", "dayOfWeek": "Sunday", "opens": "12:00", "closes": "22:00" }
    ]
  }
  </script>
</head>
<body>

  <!-- Film grain overlay (dekoracyjny, pomijany przez czytniki ekranu) -->
  <div class="grain" aria-hidden="true"></div>

  <a class="skip-link" href="#main">Przejdź do treści</a>

  <!-- ============ NAGŁÓWEK / NAWIGACJA ============ -->
  <header class="site-header" id="top">
    <div class="site-header__inner">
      <a href="#top" class="logo" aria-label="ACE BURGER — strona główna">
        <span class="logo__mark" aria-hidden="true">
          <svg viewBox="0 0 48 48" width="34" height="34">
            <circle cx="24" cy="24" r="22" fill="var(--color-mustard)"/>
            <path d="M10 20 h28 a2 2 0 0 1 2 2 v1 a2 2 0 0 1 -2 2 H10 a2 2 0 0 1 -2 -2 v-1 a2 2 0 0 1 2 -2 Z" fill="var(--color-ink)"/>
            <path d="M11 26 h26 c0 4 -5.8 7 -13 7 s-13 -3 -13 -7 Z" fill="var(--color-ink)"/>
            <path d="M13 18 c1 -6 8 -9 11 -9 s10 3 11 9 Z" fill="var(--color-fire)"/>
          </svg>
        </span>
        <span class="logo__text">ACE<em>BURGER</em></span>
      </a>

      <nav class="primary-nav" id="primary-nav" aria-label="Nawigacja główna">
        <ul class="primary-nav__list">
          <li><a href="#smash" class="primary-nav__link">Smash</a></li>
          <li><a href="#klasyczne" class="primary-nav__link">Klasyczne</a></li>
          <li><a href="#doladuj" class="primary-nav__link">Dodatki</a></li>
          <li><a href="#zamow" class="primary-nav__link">Zamów online</a></li>
          <li><a href="#godziny" class="primary-nav__link">Godziny</a></li>
          <li><a href="#kontakt" class="primary-nav__link">Kontakt</a></li>
        </ul>
        <div class="primary-nav__ctas">
          <a href="#zamow" class="btn btn--mustard primary-nav__cta">Zamów online</a>
          <a href="tel:729110496" class="btn btn--fire primary-nav__cta">Zadzwoń: 729 110 496</a>
        </div>
      </nav>

      <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Otwórz menu nawigacji">
        <span class="nav-toggle__bar"></span>
        <span class="nav-toggle__bar"></span>
        <span class="nav-toggle__bar"></span>
      </button>
    </div>
  </header>

  <main id="main">

    <!-- ============ HERO ============ -->
    <section class="hero">
      <div class="hero__grill" aria-hidden="true"></div>
      <div class="hero__inner">
        <p class="eyebrow reveal">Sokołów Podlaski · ul. Wolności 47/2</p>
        <h1 class="hero__title reveal">
          SMASHED.<br>
          STACKED.<br>
          <span class="hero__title--accent">SPALONE&nbsp;NA&nbsp;BLASZE.</span>
        </h1>
        <p class="hero__lede reveal">
          Wołowina smażona na maksymalnym ogniu, aż zrobi się chrupiąca krawędź.
          Bez kompromisów, bez fikuśnych dodatków — tylko to, co ma być w dobrym burgerze.
        </p>
        <div class="hero__actions reveal">
          <a href="#smash" class="btn btn--mustard">Zobacz menu</a>
          <a href="tel:729110496" class="btn btn--outline">Zamów: 729 110 496</a>
        </div>
      </div>
      <svg class="hero__burger" viewBox="0 0 400 340" aria-hidden="true">
        <ellipse cx="200" cy="300" rx="150" ry="22" fill="var(--color-mustard)"/>
        <path d="M55 240 q145 -70 290 0 q0 26 -145 26 t-145 -26 Z" fill="#3a2314"/>
        <path d="M60 235 Q200 150 340 235 Q340 205 200 205 Q60 205 60 235Z" fill="#c98a2b"/>
        <g class="hero__sesame">
          <ellipse cx="140" cy="185" rx="5" ry="3" fill="#f7e6c4"/>
          <ellipse cx="200" cy="170" rx="5" ry="3" fill="#f7e6c4"/>
          <ellipse cx="260" cy="185" rx="5" ry="3" fill="#f7e6c4"/>
          <ellipse cx="170" cy="195" rx="5" ry="3" fill="#f7e6c4"/>
          <ellipse cx="230" cy="195" rx="5" ry="3" fill="#f7e6c4"/>
        </g>
        <path d="M60 233 Q200 260 340 233 L340 236 Q200 263 60 236 Z" fill="#8fbf3a"/>
        <path d="M65 244 q135 24 270 0 l0 14 q-135 24 -270 0 Z" fill="#5a3418"/>
        <path d="M65 258 q135 20 270 0 l0 10 q-135 20 -270 0 Z" fill="#d94b30"/>
        <path d="M65 268 q135 22 270 0 l0 16 q-135 24 -270 0 Z" fill="#f0c33e"/>
        <path d="M75 284 q125 26 250 0 q0 16 -125 16 t-125 -16 Z" fill="#e8dcc0"/>
      </svg>
    </section>

    <!-- ============ MARQUEE ============ -->
    <div class="marquee" aria-hidden="true">
      <div class="marquee__track">
        <span>WOŁOWINA 100%</span><span>•</span><span>SMAŻONE NA OSTRO</span><span>•</span>
        <span>SOS ACE NA WSZYSTKO</span><span>•</span><span>FRYTKI I BATATY</span><span>•</span>
        <span>ZAMÓWIENIA NA BARZE</span><span>•</span><span>SOKOŁÓW PODLASKI</span><span>•</span>
        <span>WOŁOWINA 100%</span><span>•</span><span>SMAŻONE NA OSTRO</span><span>•</span>
        <span>SOS ACE NA WSZYSTKO</span><span>•</span><span>FRYTKI I BATATY</span><span>•</span>
        <span>ZAMÓWIENIA NA BARZE</span><span>•</span><span>SOKOŁÓW PODLASKI</span><span>•</span>
      </div>
    </div>

    <!-- ============ BURGERY SMASH ============ -->
    <section class="menu-section" id="smash">
      <div class="menu-section__head reveal">
        <p class="eyebrow">Menu — część 1</p>
        <h2 class="section-title">Burgery Smash</h2>
        <p class="section-lede">Kotlety rozgniatane na blasze do cienkiej, chrupiącej skorupki. Klasyka smashu, po naszemu.</p>
      </div>

      <div class="ticket-grid">
<?php if ($smash): foreach ($smash['items'] as $item): ?>
<?= MenuRepository::renderTicket($item) . "\n" ?>
<?php endforeach; endif; ?>
      </div>
    </section>

    <!-- ============ PROMO FRYTKI ============ -->
    <section class="promo-splat reveal">
      <div class="promo-splat__blob" aria-hidden="true"></div>
      <div class="promo-splat__inner">
        <p class="promo-splat__title">Frytki / Bataty w zestawie</p>
        <p class="promo-splat__price">6<span>zł</span> / 12<span>zł</span></p>
      </div>
    </section>

    <!-- ============ BURGERY KLASYCZNE ============ -->
    <section class="menu-section" id="klasyczne">
      <div class="menu-section__head reveal">
        <p class="eyebrow">Menu — część 2</p>
        <h2 class="section-title">Burgery Klasyczne</h2>
        <p class="section-lede">Grubsze kotlety, dłużej duszona wieprzowina, więcej BBQ. Dla tych, co lubią porządny stack.</p>
      </div>

      <div class="ticket-grid">
<?php if ($klasyczne): foreach ($klasyczne['items'] as $item): ?>
<?= MenuRepository::renderTicket($item) . "\n" ?>
<?php endforeach; endif; ?>
      </div>
    </section>

    <!-- ============ DOŁADUJ BURGERA ============ -->
    <section class="addons" id="doladuj">
      <div class="addons__inner">
        <div class="menu-section__head reveal">
          <p class="eyebrow">Dodatki</p>
          <h2 class="section-title">Doładuj swojego burgera</h2>
          <p class="section-lede">Więcej mięsa, więcej sera, więcej sosu — Twój burger, Twoje zasady.</p>
        </div>

        <ul class="addons__list reveal">
<?php foreach ($addons as $addon): ?>
<?= MenuRepository::renderAddonListItem($addon) . "\n" ?>
<?php endforeach; ?>
        </ul>
      </div>
    </section>

    <!-- ============ ZAMÓW ONLINE (Restaumatic) ============
      Integracja wzorowana na realnym wdrożeniu Restaumatic (widget "skubacz.pl"):
      iframe wskazujący na "https://<slug>.skubacz.pl/restauracje" + skrypt
      "https://<slug>.skubacz.pl/menu_widget.js" osadzone na stronie restauracji.

      Poniższy blok jest CELOWO zakomentowany i zastąpiony kartą-placeholderem —
      "<slug>" (tu, jako wzorzec, dosłowna domena z przykładowego wdrożenia:
      pizzeria-trzech-braci.skubacz.pl) NIE jest subdomeną ACE BURGER, więc
      wyświetlałby menu i przyjmował zamówienia innej restauracji.

      UWAGA: ta sekcja zostanie zastąpiona WŁASNYM systemem zamówień
      (koszyk + Przelewy24 / płatność przy odbiorze) w Fazie 4-5 tego planu —
      placeholder poniżej jest tymczasowy do czasu ukończenia tamtych faz.
    -->
    <section class="order-online" id="zamow">
      <div class="order-online__inner">
        <div class="menu-section__head reveal">
          <p class="eyebrow">Zamów online</p>
          <h2 class="section-title">Zamów przez internet</h2>
          <p class="section-lede">Zapłać online lub przy odbiorze — zamówienie trafi prosto do naszej kuchni.</p>
        </div>

        <div class="order-online__widget-wrap reveal">
          <div class="order-online__placeholder">
            <p class="order-online__placeholder-title">Zamawianie online — już wkrótce</p>
            <p class="order-online__placeholder-text">
              Własny system zamówień (koszyk + płatność) jest w budowie. Do czasu
              jego ukończenia zamówienia przyjmujemy telefonicznie i na barze.
            </p>
            <a href="tel:729110496" class="btn btn--mustard">Zamów telefonicznie: 729 110 496</a>
          </div>
        </div>
      </div>
    </section>

    <!-- ============ GODZINY + KONTAKT ============ -->
    <section class="info" id="godziny">
      <div class="info__grid">

        <div class="info__card reveal">
          <h2 class="section-title section-title--sm">Godziny otwarcia</h2>
          <dl class="hours">
            <div class="hours__row"><dt>Pon – Czw</dt><dd>11:00 – 22:00</dd></div>
            <div class="hours__row"><dt>Piątek</dt><dd>11:00 – 23:00</dd></div>
            <div class="hours__row"><dt>Sobota</dt><dd>12:00 – 23:00</dd></div>
            <div class="hours__row"><dt>Niedziela</dt><dd>12:00 – 22:00</dd></div>
          </dl>
        </div>

        <div class="info__card info__card--fire reveal" id="kontakt">
          <h2 class="section-title section-title--sm">Zamów</h2>
          <p class="info__text">Zamówienia przyjmujemy telefonicznie oraz na barze.</p>
          <a href="tel:729110496" class="info__phone">729 110 496</a>
          <address class="info__address">
            ACE BURGER<br>
            ul. Wolności 47/2<br>
            Sokołów Podlaski
          </address>
          <p class="info__note">Do każdego zamówienia na wynos doliczamy 1 zł (koszt opakowania).</p>
        </div>

        <div class="info__card info__card--map reveal">
          <h2 class="section-title section-title--sm">Jak trafić</h2>
          <div class="map-frame">
            <iframe
              src="https://www.google.com/maps?q=ul.+Wolno%C5%9Bci+47%2F2%2C+Sok%C3%B3%C5%82%C3%B3w+Podlaski&output=embed"
              title="Mapa dojazdu do ACE BURGER — ul. Wolności 47/2, Sokołów Podlaski"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              allowfullscreen>
            </iframe>
          </div>
          <a
            class="map-frame__link"
            href="https://www.google.com/maps/search/?api=1&query=ul.+Wolno%C5%9Bci+47%2F2%2C+Sok%C3%B3%C5%82%C3%B3w+Podlaski"
            target="_blank" rel="noopener noreferrer">
            Otwórz w Mapach Google ↗
          </a>
        </div>

      </div>
    </section>

  </main>

  <!-- ============ STOPKA ============ -->
  <footer class="site-footer">
    <div class="site-footer__inner">
      <a href="#top" class="logo logo--footer" aria-label="ACE BURGER — strona główna">
        <span class="logo__text">ACE<em>BURGER</em></span>
      </a>
      <nav aria-label="Nawigacja w stopce">
        <ul class="site-footer__list">
          <li><a href="#smash">Smash</a></li>
          <li><a href="#klasyczne">Klasyczne</a></li>
          <li><a href="#doladuj">Dodatki</a></li>
          <li><a href="#zamow">Zamów online</a></li>
          <li><a href="#godziny">Godziny</a></li>
          <li><a href="#kontakt">Kontakt</a></li>
        </ul>
      </nav>
      <p class="site-footer__meta">ul. Wolności 47/2, Sokołów Podlaski · tel. 729 110 496</p>
      <p class="site-footer__copy">© <span id="year"></span> ACE BURGER. Wszystkie ceny w PLN.</p>
    </div>
  </footer>

  <!-- ============ KOSZYK ============
       Dialogi obsługiwane przez assets/js/cart.js (koszyk w localStorage,
       suma po stronie klienta wyłącznie poglądowa — serwer w Fazie 5
       zawsze przelicza ceny samodzielnie z bazy). -->
  <button type="button" id="cartToggle" class="cart-toggle" aria-label="Otwórz koszyk">
    <span aria-hidden="true">🛒</span>
    <span id="cartCount" class="cart-toggle__count" hidden>0</span>
  </button>

  <dialog id="addonDialog" class="app-dialog addon-dialog">
    <form method="dialog" class="app-dialog__form" id="addonForm">
      <h2 class="app-dialog__title" id="addonDialogTitle">Dodaj do koszyka</h2>
      <div class="field">
        <label for="addonQty">Ilość</label>
        <input type="number" id="addonQty" min="1" value="1" style="width:6ch">
      </div>
      <div id="addonList" class="addon-list"></div>
      <div class="field">
        <label for="addonNotes">Uwagi (opcjonalnie)</label>
        <input type="text" id="addonNotes" placeholder="np. bez cebuli">
      </div>
      <div class="app-dialog__actions">
        <button type="button" class="btn btn--outline" id="addonCancel">Anuluj</button>
        <button type="submit" class="btn btn--mustard" id="addonConfirm">Dodaj do koszyka</button>
      </div>
    </form>
  </dialog>

  <dialog id="cartDialog" class="app-dialog cart-dialog">
    <div class="app-dialog__form">
      <h2 class="app-dialog__title">Twój koszyk</h2>
      <div id="cartItems" class="cart-items"></div>
      <p id="cartEmpty" class="cart-empty">Koszyk jest pusty.</p>
      <div class="cart-total">
        <span>Razem</span>
        <span id="cartTotal">0 zł</span>
      </div>
      <div class="app-dialog__actions">
        <button type="button" class="btn btn--outline" id="cartClose">Zamknij</button>
        <a href="koszyk.php" class="btn btn--mustard" id="cartCheckout">Przejdź do zamówienia</a>
      </div>
    </div>
  </dialog>

  <script type="application/json" id="addons-data"><?= json_encode($addonsForJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
  <script src="assets/js/main.js" defer></script>
  <script src="assets/js/cart.js" defer></script>
</body>
</html>

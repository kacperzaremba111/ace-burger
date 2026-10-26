# FAST FOOD — strona + system zamówień online

Strona restauracji FAST FOOD (Sokołów Podlaski) wraz z własnym systemem
zamówień online: koszyk, płatność przez Przelewy24 (lub przy odbiorze),
panel dla pracowników poszczególnych stref lokalu oraz panel admina.

## Demo — konta do panelu

Wersja demonstracyjna ma gotowe konta (import `sql/006_konta_demo.sql`).
Panel: `http://localhost:8000/panel/login.php`

| Login | Hasło | Co widzi |
|---|---|---|
| `admin` | `vmP3-yEdJ-2XME` | wszystkie strefy i zamówienia, zakładka Pracownicy, zarządzanie menu |
| `pracownik-sala` | `wanw-ec5y-u7LR` | tylko zamówienia ze strefy **Sala główna** |
| `pracownik-ogrodek` | `dtxh-BUgh-fGk9` | tylko zamówienia ze strefy **Ogródek** |
| `pracownik-antresola` | `gf25-BDtR-UH4h` | tylko zamówienia ze strefy **Antresola** |

Jak przetestować: złóż zamówienie na stronie (koszyk → wybór strefy lokalu
→ płatność „Przy odbiorze”), potem zaloguj się kolejno jako pracownik tej
strefy, pracownik innej strefy i admin — każdy widzi tylko to, do czego ma
uprawnienia (egzekwowane po stronie serwera, nie tylko w interfejsie).
Logowanie ma limit 5 prób na 15 minut z jednego IP.

> Hasła demo są celowo jawne. Na produkcji nie importuje się
> `006_konta_demo.sql` — konta tworzy `php bin/utworz-konta.php`
> z losowymi hasłami.

## Status projektu

Projekt jest w trakcie migracji ze statycznej strony (`index.html`) do
systemu z backendem PHP + MySQL. Pełny plan implementacji, schemat bazy
danych i uzasadnienie decyzji architektonicznych znajdują się w planie
wygenerowanym podczas planowania tej funkcji (zapisanym lokalnie u
właściciela repo) — poniżej skrót najważniejszych ustaleń.

## Stack

- **Frontend**: statyczny HTML/CSS/JS (bez frameworka, bez build toola) →
  docelowo strona główna renderowana server-side przez PHP na podstawie
  danych z bazy (żeby zmiany w panelu admina były widoczne od razu).
- **Backend**: PHP 8 + MySQL (PDO), bez Composera — celowo, żeby wdrożenie
  działało nawet zwykłym FTP na hostingu współdzielonym OVH.
- **Płatności**: Przelewy24 (REST API — rejestracja/weryfikacja transakcji,
  webhook potwierdzający płatność).
- **Hosting**: OVH, plan współdzielony (nie VPS) — mniejsze obciążenie
  utrzymaniowe dla małego zespołu restauracji.

## Struktura katalogów (docelowa)

```
config/     szablon i lokalna konfiguracja (sekrety — GITIGNORED)
src/        logika PHP (repozytoria, klient Przelewy24, auth), poza document root
sql/        schemat i dane startowe bazy
cron/       skrypty uruchamiane cyklicznie (porządkowanie porzuconych zamówień)
www/        document root domeny — strona, koszyk, API, panel admina/kuchni
```

## Uruchomienie lokalne

Zalecane: [Laragon](https://laragon.org/) (Windows) — daje PHP 8.x + MySQL
bez ręcznej konfiguracji.

1. Zaimportuj schemat i dane startowe (kolejność ma znaczenie):
   `mysql -u root --default-character-set=utf8mb4 < sql/001_init_schema.sql`
   (analogicznie 002, 003, 004, 005 — patrz sekcja "Import bazy danych" niżej).
2. Skopiuj `config/config.example.php` jako `config/config.local.php`
   i uzupełnij danymi do lokalnej bazy.
3. Konta panelu: dla wersji demo zaimportuj `sql/006_konta_demo.sql`
   (hasła w sekcji "Demo" wyżej); poza demo utwórz konta z losowymi
   hasłami: `php bin/utworz-konta.php`.
4. Uruchom wbudowany serwer PHP z katalogu repo:
   `php -S localhost:8000 -t www`
5. Strona: `http://localhost:8000/`, panel: `http://localhost:8000/panel/login.php`.

## Strefy lokalu i konta panelu

Lokal jest podzielony na strefy (tabela `zones`, `sql/005_strefy_lokalu.sql`):
Sala główna, Ogródek, Antresola. Klient w koszyku wybiera strefę, z której
korzysta — zamówienie trafia do tej strefy.

- **pracownik** (`pracownik-sala`, `pracownik-ogrodek`, `pracownik-antresola`)
  — widzi i obsługuje wyłącznie zamówienia ze swojej strefy. Ograniczenie
  jest egzekwowane po stronie serwera (`Auth::zoneScope()`), także przy
  zmianie statusu zamówienia.
- **admin** — podgląd ogólny: wszystkie zamówienia (z filtrem strefy),
  liczniki per strefa na stronie startowej, zakładka „Pracownicy” (strefa,
  aktywność konta, statystyki, generowanie nowego hasła) + zarządzanie menu.

Poza kontami demo (`006_konta_demo.sql`) hasła **nie** są w repozytorium.
`php bin/utworz-konta.php` tworzy konto
pracownika dla każdej aktywnej strefy oraz ustawia nowe hasło admina,
wypisując wylosowane hasła jeden raz na ekran (ponowne uruchomienie
generuje nowe hasła). Na OVH bez SSH: uruchom skrypt lokalnie na kopii bazy
albo użyj „Nowe hasło” w zakładce Pracownicy.

## Import bazy danych — pułapka z kodowaniem

Przy imporcie plików z `sql/` przez klienta `mysql` (CLI) **zawsze** dodawaj
`--default-character-set=utf8mb4`, inaczej polskie znaki (ł, ą, ń...) mogą
zostać zapisane błędnie mimo poprawnego UTF-8 w samym pliku `.sql`:

```
mysql -u root --default-character-set=utf8mb4 < sql/001_init_schema.sql
mysql -u root --default-character-set=utf8mb4 < sql/002_seed_menu.sql
mysql -u root --default-character-set=utf8mb4 < sql/003_seed_admin_user.sql
mysql -u root --default-character-set=utf8mb4 < sql/004_rate_limiting.sql
mysql -u root --default-character-set=utf8mb4 < sql/005_strefy_lokalu.sql
mysql -u root --default-character-set=utf8mb4 < sql/006_konta_demo.sql   # tylko demo
```

Import przez phpMyAdmin (tak jak na OVH — patrz plan wdrożenia) tego problemu
zwykle nie ma, ale warto zweryfikować polskie znaki po imporcie (np. "Kotlet
wołowy" w tabeli `addons`) zanim przejdzie się dalej.

## Bezpieczeństwo i sekrety

`config/config.local.php` (dane dostępowe do bazy, klucze Przelewy24 —
merchant ID, POS ID, CRC, klucz API) **nigdy** nie trafia do repozytorium —
patrz `.gitignore`. Plik tworzony ręcznie na podstawie `config/config.example.php`,
osobno lokalnie i na serwerze produkcyjnym.

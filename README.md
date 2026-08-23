# ACE BURGER — strona + system zamówień online

Strona restauracji ACE BURGER (Sokołów Podlaski) wraz z własnym systemem
zamówień online: koszyk, płatność przez Przelewy24 (lub przy odbiorze),
panel dla kuchni/obsługi oraz panel admina do zarządzania menu.

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
   (analogicznie 002, 003, 004 — patrz sekcja "Import bazy danych" niżej).
2. Skopiuj `config/config.example.php` jako `config/config.local.php`
   i uzupełnij danymi do lokalnej bazy.
3. Uruchom wbudowany serwer PHP z katalogu repo:
   `php -S localhost:8000 -t www`
4. Strona: `http://localhost:8000/`, panel: `http://localhost:8000/panel/login.php`
   (dane startowe do panelu — patrz `sql/003_seed_admin_user.sql`, zmień
   hasło od razu po pierwszym zalogowaniu).

## Import bazy danych — pułapka z kodowaniem

Przy imporcie plików z `sql/` przez klienta `mysql` (CLI) **zawsze** dodawaj
`--default-character-set=utf8mb4`, inaczej polskie znaki (ł, ą, ń...) mogą
zostać zapisane błędnie mimo poprawnego UTF-8 w samym pliku `.sql`:

```
mysql -u root --default-character-set=utf8mb4 < sql/001_init_schema.sql
mysql -u root --default-character-set=utf8mb4 < sql/002_seed_menu.sql
mysql -u root --default-character-set=utf8mb4 < sql/003_seed_admin_user.sql
```

Import przez phpMyAdmin (tak jak na OVH — patrz plan wdrożenia) tego problemu
zwykle nie ma, ale warto zweryfikować polskie znaki po imporcie (np. "Kotlet
wołowy" w tabeli `addons`) zanim przejdzie się dalej.

## Bezpieczeństwo i sekrety

`config/config.local.php` (dane dostępowe do bazy, klucze Przelewy24 —
merchant ID, POS ID, CRC, klucz API) **nigdy** nie trafia do repozytorium —
patrz `.gitignore`. Plik tworzony ręcznie na podstawie `config/config.example.php`,
osobno lokalnie i na serwerze produkcyjnym.

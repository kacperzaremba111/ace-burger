<?php

declare(strict_types=1);

/**
 * Tworzy (albo resetuje) konta panelu: jedno konto pracownika na każdą
 * aktywną strefę lokalu + nowe hasło dla admina. Hasła są losowane tutaj
 * i wypisywane JEDEN raz na ekran — w bazie zostaje tylko hash, w repo nic.
 *
 * Użycie (z katalogu repo, po imporcie sql/005_strefy_lokalu.sql):
 *   php bin/utworz-konta.php
 *
 * Ponowne uruchomienie generuje NOWE hasła dla tych samych loginów
 * (stare przestają działać) — tak samo jak "Nowe hasło" w panelu.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/src/bootstrap.php';

$accounts = [];

foreach (ZoneRepository::all() as $zone) {
    $username = 'pracownik-' . $zone['slug'];
    $password = StaffRepository::generatePassword();
    StaffRepository::upsert($username, 'Pracownik — ' . $zone['name'], 'pracownik', (int) $zone['id'], $password);
    $accounts[] = [$username, $password, 'pracownik, strefa: ' . $zone['name']];
}

$adminPassword = StaffRepository::generatePassword();
StaffRepository::upsert('admin', 'Administrator', 'admin', null, $adminPassword);
$accounts[] = ['admin', $adminPassword, 'admin, wszystkie strefy i pracownicy'];

echo "Konta panelu (hasła pokazywane tylko teraz — zapisz je):\n\n";
foreach ($accounts as [$username, $password, $description]) {
    printf("  %-22s %-16s %s\n", $username, $password, $description);
}
echo "\n";

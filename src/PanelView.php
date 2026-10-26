<?php

declare(strict_types=1);

/**
 * Wspólny nagłówek + nawigacja panelu. Pozycje menu zależą od roli —
 * pracownik widzi tylko Start i Zamówienia (swojej strefy).
 */
final class PanelView
{
    private const ADMIN_LINKS = [
        'zamowienia' => ['zamowienia.php', 'Zamówienia'],
        'pracownicy' => ['pracownicy.php', 'Pracownicy'],
        'kategorie'  => ['kategorie.php', 'Kategorie'],
        'menu'       => ['menu.php', 'Menu'],
        'dodatki'    => ['dodatki.php', 'Dodatki'],
    ];

    private const STAFF_LINKS = [
        'zamowienia' => ['zamowienia.php', 'Zamówienia'],
    ];

    /** @param string $active klucz aktywnej sekcji (np. "menu") albo "start" */
    public static function header(array $user, string $active): string
    {
        $roleLabel = $user['role'] === 'admin'
            ? 'admin — wszystkie strefy'
            : 'pracownik — ' . ($user['zone_name'] ?? 'brak strefy');

        $links = $user['role'] === 'admin' ? self::ADMIN_LINKS : self::STAFF_LINKS;

        $navHtml = '<a href="index.php"' . ($active === 'start' ? ' class="is-active"' : '') . '>Start</a>';
        foreach ($links as $key => [$href, $label]) {
            $class = $key === $active ? ' class="is-active"' : '';
            $navHtml .= "<a href=\"{$href}\"{$class}>{$label}</a>";
        }

        $fullName = h($user['full_name']);
        $roleLabel = h($roleLabel);

        return <<<HTML
            <header class="panel-header">
              <span class="panel-header__brand">ACE BURGER — Panel</span>
              <span class="panel-header__user">
                {$fullName} ({$roleLabel})
                <a href="logout.php">Wyloguj</a>
              </span>
            </header>

            <nav class="panel-nav">{$navHtml}</nav>
            HTML;
    }
}

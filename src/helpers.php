<?php

declare(strict_types=1);

/**
 * Escapuje wartość do bezpiecznego wypisania w HTML — ochrona przed XSS.
 * Używać przy KAŻDYM wypisaniu danych z bazy (nazwy/opisy z panelu admina,
 * notatki klienta widoczne w panelu kuchni itd.).
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Formatuje grosze jako cenę w PLN.
 * money_pln(3700) -> "37 zł", money_pln(3750) -> "37,50 zł".
 */
function money_pln(int $cents): string
{
    $zloty  = intdiv($cents, 100);
    $grosze = $cents % 100;

    return $grosze === 0
        ? sprintf('%d zł', $zloty)
        : sprintf('%d,%02d zł', $zloty, $grosze);
}

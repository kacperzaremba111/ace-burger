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

/**
 * Sama liczba (bez "zł"), do .ticket__price — jednostka dochodzi osobno
 * przez .visually-hidden. price_display(3700) -> "37", price_display(3750) -> "37,50".
 */
function price_display(int $cents): string
{
    $zloty  = intdiv($cents, 100);
    $grosze = $cents % 100;

    return $grosze === 0 ? (string) $zloty : sprintf('%d,%02d', $zloty, $grosze);
}

/**
 * Parsuje cenę wpisaną w formularzu (np. "37", "37,50", "37.5") na grosze.
 * Rzuca InvalidArgumentException przy niepoprawnym formacie/ujemnej wartości —
 * formularze admina łapią to i pokazują czytelny błąd zamiast zapisać śmieci.
 */
function parse_price_to_cents(string $input): int
{
    $normalized = str_replace(',', '.', trim($input));

    if (!preg_match('/^\d+(\.\d{1,2})?$/', $normalized)) {
        throw new InvalidArgumentException('Nieprawidłowa cena: "' . $input . '" (użyj np. 37 lub 37,50).');
    }

    return (int) round(((float) $normalized) * 100);
}

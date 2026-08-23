/* =========================================================
   ACE BURGER — order-status.js (tylko www/zamowienie/platnosc-powrot.php)
   Odpytuje status zamówienia po powrocie z Przelewy24. Return URL od P24
   NIE jest wiarygodnym dowodem płatności (klient mógł zamknąć kartę w
   trakcie) — to tylko UI-owa wygoda; prawdziwe potwierdzenie ustawia
   webhook (api/p24-webhook.php) niezależnie od tego, czy klient w ogóle
   wróci na tę stronę.
   ========================================================= */

(function initOrderStatusPolling() {
  const statusEl = document.getElementById('statusText');
  if (!statusEl) return;

  const orderNumber = statusEl.dataset.orderNumber;
  if (!orderNumber) return;

  const STATUS_LABELS = {
    nowe: 'Sprawdzamy status płatności…',
    oplacone: 'Płatność potwierdzona — zamówienie trafiło do kuchni! 🎉',
    w_przygotowaniu: 'Płatność potwierdzona — Twój burger jest już przygotowywany!',
    gotowe: 'Zamówienie gotowe do odbioru!',
    w_dostawie: 'Zamówienie jest w drodze!',
    wydane: 'Zamówienie zostało wydane. Smacznego!',
    anulowane: 'Zamówienie zostało anulowane. Zadzwoń, jeśli to pomyłka: 729 110 496.',
  };

  const MAX_ATTEMPTS = 40; // ~2 minuty przy 3s odstępach
  let attempts = 0;

  async function poll() {
    attempts += 1;

    try {
      const response = await fetch(`../api/zamowienie-status.php?nr=${encodeURIComponent(orderNumber)}`);
      if (response.ok) {
        const data = await response.json();
        statusEl.textContent = STATUS_LABELS[data.status] || STATUS_LABELS.nowe;

        if (data.status !== 'nowe' && data.status !== 'anulowane') {
          return; // stan końcowy (dla przepływu płatności) - koniec odpytywania
        }
        if (data.status === 'anulowane') {
          return;
        }
      }
    } catch {
      // Błąd sieci — spróbujemy ponownie przy kolejnym tyknięciu, jeśli
      // limit prób jeszcze nie wyczerpany.
    }

    if (attempts < MAX_ATTEMPTS) {
      setTimeout(poll, 3000);
    } else {
      statusEl.textContent =
        'Nie udało się potwierdzić statusu automatycznie. Jeśli płatność ' +
        'przeszła, zamówienie i tak trafiło do kuchni — w razie wątpliwości zadzwoń: 729 110 496.';
    }
  }

  poll();
})();

/* =========================================================
   FAST FOOD — orders-poll.js (tylko www/panel/zamowienia.php)
   Odświeża listę zamówień pollingiem co ~20s + przycisk ręcznego
   odświeżenia. Websockety niedostępne na hostingu współdzielonym —
   polling jest właściwym rozwiązaniem przy tej skali (patrz plan).

   Karty zamówień renderowane są po stronie serwera (ten sam markup co
   przy pierwszym załadowaniu strony) — przyciski zmiany statusu to
   zwykłe <form method="post">, więc działają natychmiast po wymianie
   innerHTML, bez potrzeby ponownego podpinania event listenerów.
   ========================================================= */

(function initOrdersPolling() {
  const listEl = document.getElementById('ordersList');
  const refreshBtn = document.getElementById('refreshBtn');
  if (!listEl) return;

  const view = listEl.dataset.view || 'aktywne';
  // Filtr strefy (tylko admin). Dla pracownika serwer i tak wymusza jego strefę.
  const zone = listEl.dataset.zone || '';
  const POLL_INTERVAL_MS = 20000;

  async function refresh() {
    try {
      const params = new URLSearchParams({ view });
      if (zone && zone !== '0') params.set('strefa', zone);
      const response = await fetch(`api/zamowienia-lista.php?${params}`);
      if (!response.ok) return;
      listEl.innerHTML = await response.text();
    } catch {
      // Błąd sieci — spróbujemy przy kolejnym tyknięciu pollingu.
    }
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', refresh);
  }

  setInterval(refresh, POLL_INTERVAL_MS);
})();

/* =========================================================
   FAST FOOD — checkout.js (tylko www/koszyk.php)
   Renderuje podsumowanie koszyka z localStorage i obsługuje formularz
   zamówienia. Zakłada, że cart.js jest załadowany wcześniej na tej
   samej stronie — korzysta z jego globalnych funkcji: money(),
   lineTotal(), cartTotal(), readCart(), escapeHtml().
   ========================================================= */

(function initCheckout() {
  const summaryEl   = document.getElementById('checkoutSummary');
  const totalEl     = document.getElementById('checkoutTotal');
  const emptyNotice = document.getElementById('checkoutEmpty');
  const form        = document.getElementById('checkoutForm');
  const submitBtn   = document.getElementById('checkoutSubmit');
  const statusEl    = document.getElementById('checkoutStatus');

  if (!summaryEl || !form) return;

  function renderSummary() {
    const items = readCart();

    if (items.length === 0) {
      summaryEl.innerHTML = '';
      if (emptyNotice) emptyNotice.hidden = false;
      if (submitBtn) submitBtn.disabled = true;
      totalEl.textContent = money(0);
      return;
    }

    if (emptyNotice) emptyNotice.hidden = true;
    if (submitBtn) submitBtn.disabled = false;

    summaryEl.innerHTML = items.map((item) => {
      const addonsText = item.addons.length
        ? `<p class="cart-item__addons">+ ${item.addons
            .map((a) => `${escapeHtml(a.name)}${a.qty > 1 ? ` x${a.qty}` : ''}`)
            .join(', ')}</p>`
        : '';
      const notesText = item.notes
        ? `<p class="cart-item__notes">Uwagi: ${escapeHtml(item.notes)}</p>`
        : '';

      return `
        <div class="cart-item">
          <div class="cart-item__row">
            <span class="cart-item__name">${item.qty}x ${escapeHtml(item.name)}</span>
            <span class="cart-item__price">${money(lineTotal(item))}</span>
          </div>
          ${addonsText}
          ${notesText}
        </div>
      `;
    }).join('');

    totalEl.textContent = money(cartTotal(items));
  }

  renderSummary();

  /* ---------- Pokaż/ukryj pola adresu dostawy ---------- */
  const deliveryFields = document.getElementById('deliveryFields');

  function updateDeliveryFieldsVisibility() {
    const selected = form.querySelector('input[name="fulfillment"]:checked');
    const isDelivery = !!selected && selected.value === 'dostawa';

    if (deliveryFields) {
      deliveryFields.hidden = !isDelivery;
      deliveryFields.querySelectorAll('input[data-required-for-delivery="true"]').forEach((input) => {
        input.required = isDelivery;
      });
    }
  }

  form.querySelectorAll('input[name="fulfillment"]').forEach((radio) => {
    radio.addEventListener('change', updateDeliveryFieldsVisibility);
  });
  updateDeliveryFieldsVisibility();

  /* ---------- Wysyłka zamówienia ----------
     Endpoint api/zamowienia.php powstaje w Fazie 5. Ten kod jest już
     docelowy — po dodaniu API zacznie działać bez żadnych zmian tutaj.
     Do tego czasu każda próba wysyłki przechodzi normalną ścieżką
     obsługi błędu (catch), co i tak jest poprawnym zachowaniem UI na
     wypadek realnej awarii sieci/serwera w przyszłości. */
  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const items = readCart();
    if (items.length === 0) return;

    const formData = new FormData(form);

    if (!formData.get('zone_id')) {
      if (statusEl) {
        statusEl.textContent = 'Wybierz strefę lokalu, z której korzystasz.';
        statusEl.className = 'checkout__status checkout__status--error';
      }
      document.getElementById('zoneFieldset')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }
    const payload = {
      csrf_token: formData.get('csrf_token'),
      website: formData.get('website'), // honeypot — puste u prawdziwego klienta
      customer_name: formData.get('customer_name'),
      customer_phone: formData.get('customer_phone'),
      customer_email: formData.get('customer_email'),
      fulfillment: formData.get('fulfillment'),
      zone_id: Number(formData.get('zone_id')),
      payment_method: formData.get('payment_method'),
      notes: formData.get('notes'),
      delivery_street: formData.get('delivery_street'),
      delivery_city: formData.get('delivery_city'),
      delivery_postal_code: formData.get('delivery_postal_code'),
      delivery_notes: formData.get('delivery_notes'),
      items,
    };

    if (submitBtn) submitBtn.disabled = true;
    if (statusEl) {
      statusEl.textContent = 'Wysyłanie zamówienia…';
      statusEl.className = 'checkout__status';
    }

    try {
      const response = await fetch('api/zamowienia.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      if (!response.ok) {
        // API zwraca {error: "..."} z komunikatem bezpiecznym do pokazania klientowi.
        const body = await response.json().catch(() => null);
        const error = new Error('Serwer odpowiedział błędem (' + response.status + ')');
        error.userMessage = body && body.error;
        throw error;
      }

      const result = await response.json();
      localStorage.removeItem('aceBurgerCart.v1');

      if (result.redirectUrl) {
        window.location.href = result.redirectUrl; // Przelewy24
      } else {
        window.location.href = 'zamowienie/potwierdzenie.php?nr=' + encodeURIComponent(result.orderNumber || '');
      }
    } catch (err) {
      if (statusEl) {
        statusEl.textContent = err.userMessage
          || 'Nie udało się złożyć zamówienia. Spróbuj ponownie albo zadzwoń: 123 456 789.';
        statusEl.className = 'checkout__status checkout__status--error';
      }
      if (submitBtn) submitBtn.disabled = false;
    }
  });
})();

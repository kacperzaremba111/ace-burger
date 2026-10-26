/* =========================================================
   ACE BURGER — cart.js
   Koszyk klienta w localStorage. Suma po stronie klienta jest
   WYŁĄCZNIE poglądowa — serwer (Faza 5) zawsze przelicza ceny
   samodzielnie z bazy przy tworzeniu zamówienia, więc podmiana
   danych w localStorage przez klienta nie ma żadnego znaczenia
   dla faktycznej kwoty do zapłaty.
   ========================================================= */

const CART_STORAGE_KEY = 'aceBurgerCart.v1';

/* ---------- Czyste funkcje (bez DOM) — łatwe do przetestowania ---------- */

function money(cents) {
  const zloty = Math.floor(cents / 100);
  const grosze = cents % 100;
  return grosze === 0 ? `${zloty} zł` : `${zloty},${String(grosze).padStart(2, '0')} zł`;
}

function lineTotal(item) {
  const addonsSum = item.addons.reduce((sum, a) => sum + a.priceCents * a.qty, 0);
  return (item.priceCents + addonsSum) * item.qty;
}

function cartTotal(items) {
  return items.reduce((sum, item) => sum + lineTotal(item), 0);
}

function cartCount(items) {
  return items.reduce((sum, item) => sum + item.qty, 0);
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/* ---------- Trwałość koszyka ---------- */

function readCart() {
  try {
    const raw = localStorage.getItem(CART_STORAGE_KEY);
    const parsed = raw ? JSON.parse(raw) : [];
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    // localStorage niedostępny (tryb prywatny, zablokowane ciasteczka
    // witryny...) — koszyk po prostu zaczyna się od pustego stanu.
    return [];
  }
}

function writeCart(items) {
  try {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items));
  } catch {
    // Jw. — zapis po cichu się nie udaje, strona wciąż działa.
  }
}

/* Eksport dla Node/jsdom (testy) — w przeglądarce `module` nie istnieje,
   więc ten blok jest tam pomijany bez błędu. */
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { money, lineTotal, cartTotal, cartCount, escapeHtml };
}

/* ---------- Reszta działa tylko w przeglądarce (potrzebuje DOM) ---------- */
if (typeof document !== 'undefined') {
  (function initCart() {
    const addonsDataEl = document.getElementById('addons-data');
    const AVAILABLE_ADDONS = addonsDataEl ? JSON.parse(addonsDataEl.textContent) : [];

    const cartToggle   = document.getElementById('cartToggle');
    const cartCountEl  = document.getElementById('cartCount');
    const cartDialog   = document.getElementById('cartDialog');
    const cartItemsEl  = document.getElementById('cartItems');
    const cartEmptyEl  = document.getElementById('cartEmpty');
    const cartTotalEl  = document.getElementById('cartTotal');
    const cartCloseBtn = document.getElementById('cartClose');

    const addonDialog      = document.getElementById('addonDialog');
    const addonForm        = document.getElementById('addonForm');
    const addonDialogTitle = document.getElementById('addonDialogTitle');
    const addonQtyInput    = document.getElementById('addonQty');
    const addonListEl      = document.getElementById('addonList');
    const addonNotesInput  = document.getElementById('addonNotes');
    const addonCancelBtn   = document.getElementById('addonCancel');

    let pendingItem = null; // burger aktualnie dodawany (przed potwierdzeniem dodatków)

    function openDialog(dialogEl) {
      if (dialogEl && typeof dialogEl.showModal === 'function') {
        dialogEl.showModal();
      } else if (dialogEl) {
        // Bardzo stare przeglądarki bez <dialog> — pokaż jako zwykły blok.
        dialogEl.setAttribute('open', '');
      }
    }

    function closeDialog(dialogEl) {
      if (dialogEl && typeof dialogEl.close === 'function') {
        dialogEl.close();
      } else if (dialogEl) {
        dialogEl.removeAttribute('open');
      }
    }

    function refreshCartBadge() {
      const count = cartCount(readCart());
      if (cartCountEl) {
        cartCountEl.textContent = String(count);
        cartCountEl.hidden = count === 0;
      }
    }

    function openAddonDialog(button) {
      if (!addonDialog) return;

      pendingItem = {
        menuItemId: Number(button.dataset.menuItemId),
        name: button.dataset.name,
        priceCents: Number(button.dataset.priceCents),
      };

      addonDialogTitle.textContent = pendingItem.name;
      addonQtyInput.value = '1';
      addonNotesInput.value = '';

      addonListEl.innerHTML = AVAILABLE_ADDONS.map((addon) => `
        <label class="addon-option">
          <span class="addon-option__main">
            <input type="checkbox" class="addon-option__check" data-addon-id="${addon.id}">
            ${escapeHtml(addon.name)} <span class="addon-option__price">(+${money(addon.priceCents)})</span>
          </span>
          <input type="number" class="addon-option__qty" min="1" value="1" data-addon-id="${addon.id}" disabled>
        </label>
      `).join('');

      addonListEl.querySelectorAll('.addon-option__check').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
          const qtyInput = addonListEl.querySelector(
            `.addon-option__qty[data-addon-id="${checkbox.dataset.addonId}"]`
          );
          if (qtyInput) qtyInput.disabled = !checkbox.checked;
        });
      });

      openDialog(addonDialog);
    }

    document.querySelectorAll('.ticket__add').forEach((button) => {
      button.addEventListener('click', () => openAddonDialog(button));
    });

    if (addonCancelBtn) {
      addonCancelBtn.addEventListener('click', () => {
        closeDialog(addonDialog);
        pendingItem = null;
      });
    }

    if (addonForm) {
      addonForm.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!pendingItem) return;

        const selectedAddons = [];
        addonListEl.querySelectorAll('.addon-option__check:checked').forEach((checkbox) => {
          const addonId = Number(checkbox.dataset.addonId);
          const addon = AVAILABLE_ADDONS.find((a) => a.id === addonId);
          if (!addon) return;
          const qtyInput = addonListEl.querySelector(
            `.addon-option__qty[data-addon-id="${addonId}"]`
          );
          const qty = Math.max(1, parseInt(qtyInput && qtyInput.value, 10) || 1);
          selectedAddons.push({ addonId: addon.id, name: addon.name, priceCents: addon.priceCents, qty });
        });

        const qty = Math.max(1, parseInt(addonQtyInput.value, 10) || 1);
        const notes = addonNotesInput.value.trim();

        const items = readCart();
        items.push({
          menuItemId: pendingItem.menuItemId,
          name: pendingItem.name,
          priceCents: pendingItem.priceCents,
          qty,
          notes,
          addons: selectedAddons,
        });
        writeCart(items);
        refreshCartBadge();

        closeDialog(addonDialog);
        pendingItem = null;

        // Od razu pokaż koszyk — klient widzi, co dodał, i może przejść do zamówienia.
        if (cartDialog) {
          renderCartDialog();
          openDialog(cartDialog);
        }
      });
    }

    function renderCartDialog() {
      const items = readCart();

      if (items.length === 0) {
        cartItemsEl.innerHTML = '';
        cartEmptyEl.hidden = false;
      } else {
        cartEmptyEl.hidden = true;
        cartItemsEl.innerHTML = items.map((item, index) => {
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
              <button type="button" class="cart-item__remove" data-index="${index}"
                aria-label="Usuń ${escapeHtml(item.name)} z koszyka">Usuń</button>
            </div>
          `;
        }).join('');
      }

      cartTotalEl.textContent = money(cartTotal(items));

      cartItemsEl.querySelectorAll('.cart-item__remove').forEach((btn) => {
        btn.addEventListener('click', () => {
          const idx = Number(btn.dataset.index);
          const current = readCart();
          current.splice(idx, 1);
          writeCart(current);
          refreshCartBadge();
          renderCartDialog();
        });
      });
    }

    if (cartToggle && cartDialog) {
      cartToggle.addEventListener('click', () => {
        renderCartDialog();
        openDialog(cartDialog);
      });
    }

    if (cartCloseBtn) {
      cartCloseBtn.addEventListener('click', () => closeDialog(cartDialog));
    }

    refreshCartBadge();
  })();
}

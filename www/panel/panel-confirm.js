/* =========================================================
   ACE BURGER — panel-confirm.js
   Potwierdzenie przed usunięciem (kategorie.php, menu.php, dodatki.php).

   CELOWO nie używamy atrybutu onsubmit="confirm('...nazwa z bazy...')"
   — nazwa pozycji (kontrolowana przez admina, ale i tak) osadzona jako
   JS-string WEWNĄTRZ atrybutu HTML to podwójny kontekst: h() poprawnie
   escapuje HTML, ale przeglądarka HTML-dekoduje atrybut PRZED
   uruchomieniem go jako JS, więc pojedynczy apostrof w nazwie ("Chef's
   Burger") wystarczy, żeby wyjść z JS-stringa i wstrzyknąć dowolny kod
   (zweryfikowane w praktyce przy pisaniu tej poprawki — payload w stylu
   `x'+(window.foo=true)+'` faktycznie się wykonywał).

   Tutaj nazwa trafia do atrybutu data-confirm-message — WYŁĄCZNIE
   kontekst HTML (h() w pełni wystarcza), a JS odczytuje ją przez
   .dataset jako already-decoded STRING, nigdy nie parsując jej jako kod. */

document.querySelectorAll('form.confirm-delete').forEach((form) => {
  form.addEventListener('submit', (event) => {
    const message = form.dataset.confirmMessage || 'Na pewno usunąć?';
    if (!window.confirm(message)) {
      event.preventDefault();
    }
  });
});

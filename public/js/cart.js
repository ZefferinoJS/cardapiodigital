// Cart drawer behavior: update badge, render items, toggle drawer
(function () {
    function getCart() {
        try { const raw = localStorage.getItem('cart'); return raw ? JSON.parse(raw) : []; } catch (e) { return []; }
    }
    function saveCart(cart) { try { localStorage.setItem('cart', JSON.stringify(cart)); } catch (e) { } }

    const toggleBtn = document.getElementById('cart-toggle');
    const drawer = document.getElementById('cart-drawer');
    const overlay = document.getElementById('cart-overlay');
    const closeBtn = document.getElementById('cart-close');
    const itemsEl = document.getElementById('cart-items');
    const badge = document.querySelector('.cart-badge');
    const subtotalEl = document.getElementById('cart-subtotal');

    function formatPriceList(cart) {
        // returns a display string for subtotal (now computes numeric subtotal when possible)
        const count = cart.reduce((s, i) => s + (i.qty || 1), 0);
        return `(${count} itens)`;
    }

    function renderCart() {
        const cart = getCart();
        const count = cart.reduce((s, i) => s + (i.qty || 1), 0);
        if (badge) { badge.textContent = count; badge.style.display = count ? 'inline-block' : 'none'; }

        if (!itemsEl) return;
        itemsEl.innerHTML = '';
        if (cart.length === 0) { itemsEl.innerHTML = '<p>Seu carrinho está vazio.</p>'; subtotalEl.textContent = 'AO 0,00'; return; }

        // compute numeric subtotal (try using priceValue, otherwise parse price string)
        function parsePrice(str) {
            if (str == null) return 0;
            const s = String(str).replace(/[^0-9,\.]/g, '').trim();
            // remove thousands separator (.) when used, convert comma to dot
            const noThousand = s.replace(/\.(?=\d{3})/g, '');
            const normalized = noThousand.replace(/,/g, '.');
            const v = parseFloat(normalized);
            return Number.isFinite(v) ? v : 0;
        }

        function formatCurrency(v) {
            try {
                // format as 'AO 25,00' using simple replacement
                const str = Number(v).toFixed(2).replace('.', ',');
                return `AO ${str}`;
            } catch (e) { return 'AO 0,00'; }
        }

        let subtotal = 0;
        cart.forEach(item => {
            const qty = item.qty || 1;
            const priceVal = ('priceValue' in item) ? Number(item.priceValue) : parsePrice(item.price);
            subtotal += (priceVal || 0) * qty;

            const div = document.createElement('div'); div.className = 'cart-item';
            div.innerHTML = `
        <img src="${item.img || 'images/crispy-baked-meat-potatoes.webp'}" alt="${item.title}">
        <div class="meta">
          <h4>${item.title}</h4>
          <small>${item.price}</small>
        </div>
        <div class="actions">
          <div class="qty-controls">
            <button class="qty-decrease" data-id="${item.id}" aria-label="Diminuir quantidade">-</button>
            <span class="qty-value">${qty}</span>
            <button class="qty-increase" data-id="${item.id}" aria-label="Aumentar quantidade">+</button>
          </div>
          <button class="cart-remove" data-id="${item.id}" aria-label="Remover ${item.title}">&times;</button>
        </div>
      `;
            itemsEl.appendChild(div);
        });

        subtotalEl.textContent = formatCurrency(subtotal);
    }

    function openDrawer() {
        if (drawer) drawer.classList.add('open');
        if (overlay) { overlay.hidden = false; overlay.classList.add('show'); }
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
    }
    function closeDrawer() {
        if (drawer) drawer.classList.remove('open');
        if (overlay) { overlay.classList.remove('show'); setTimeout(() => overlay.hidden = true, 200); }
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
    }

    // Event listeners
    if (toggleBtn) { toggleBtn.addEventListener('click', function (e) { e.preventDefault(); const isOpen = drawer.classList.contains('open'); if (isOpen) closeDrawer(); else { renderCart(); openDrawer(); } }); }
    if (closeBtn) { closeBtn.addEventListener('click', closeDrawer); }
    if (overlay) { overlay.addEventListener('click', closeDrawer); }

    // Remove item delegation
    itemsEl && itemsEl.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.cart-remove');
        if (removeBtn) {
            const id = removeBtn.dataset.id;
            let cart = getCart(); cart = cart.filter(i => i.id !== id); saveCart(cart); renderCart(); window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart }));
            return;
        }
        const inc = e.target.closest('.qty-increase');
        if (inc) { const id = inc.dataset.id; let cart = getCart(); const it = cart.find(i => i.id === id); if (it) { it.qty = (it.qty || 1) + 1; saveCart(cart); renderCart(); window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart })); } return; }
        const dec = e.target.closest('.qty-decrease');
        if (dec) { const id = dec.dataset.id; let cart = getCart(); const it = cart.find(i => i.id === id); if (it) { it.qty = (it.qty || 1) - 1; if (it.qty <= 0) { cart = cart.filter(i => i.id !== id); } saveCart(cart); renderCart(); window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart })); } return; }
    });

    // Checkout handler
    const checkoutBtn = document.getElementById('cart-checkout');
    function showMessage(msg, timeout = 3000) {
        let el = drawer.querySelector('.cart-message');
        if (!el) { el = document.createElement('div'); el.className = 'cart-message'; drawer.insertBefore(el, drawer.querySelector('.cart-drawer-footer')); }
        el.textContent = msg;
        if (timeout > 0) setTimeout(() => { if (el && el.parentNode) el.parentNode.removeChild(el); }, timeout);
    }
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function () {
            const cart = getCart();
            if (!cart || cart.length === 0) { showMessage('Carrinho vazio'); return; }
            checkoutBtn.disabled = true; checkoutBtn.classList.add('cart-checkout-disabled');
            // Try to POST to /checkout, fallback to simulated success
            fetch('/checkout', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ cart }) })
                .then(res => {
                    if (res.ok) return res.json().catch(() => ({}));
                    throw new Error('Erro no servidor');
                })
                .then(data => {
                    showMessage('Compra finalizada com sucesso!', 2500);
                    saveCart([]); renderCart(); window.dispatchEvent(new CustomEvent('cartUpdated', { detail: [] }));
                })
                .catch(err => {
                    // fallback: simulate success after short delay
                    console.warn('Checkout falhou, simulando sucesso', err);
                    setTimeout(() => {
                        showMessage('Compra simulada (sem backend). Carrinho limpo.', 2500);
                        saveCart([]); renderCart(); window.dispatchEvent(new CustomEvent('cartUpdated', { detail: [] }));
                    }, 800);
                })
                .finally(() => { checkoutBtn.disabled = false; checkoutBtn.classList.remove('cart-checkout-disabled'); });
        }
        );
    }


    // Listen to cartUpdated to re-render
    window.addEventListener('cartUpdated', function (e) { renderCart(); });


    // Initial render
    document.addEventListener('DOMContentLoaded', function () { renderCart(); });

})();



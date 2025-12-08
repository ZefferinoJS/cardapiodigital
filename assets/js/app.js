// Front-end app: handle QR/table flow and load menu dynamically
(async function(){
  const API_BASE = '/assets/api';
  const restaurantSlug = document.body.dataset.restaurant || null;

  function qs(sel){ return document.querySelector(sel); }
  function qsa(sel){ return Array.from(document.querySelectorAll(sel)); }

  function getQueryParam(name){
    const url = new URL(window.location.href);
    return url.searchParams.get(name);
  }

  function escapeHtml(str){
    if(str === null || str === undefined) return '';
    return String(str).replace(/[&<>\"']/g, function(s){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[s];
    });
  }

  function truncate(str, max = 16){
    if(str === null || str === undefined) return '';
    const s = String(str);
    return s.length <= max ? s : s.slice(0, max) + '...';
  }

  function formatCurrency(v){
    try{
      return new Intl.NumberFormat('pt-AO',{ style:'currency', currency:'AOA', maximumFractionDigits:2 }).format(Number(v));
    }catch(e){
      return `AO ${Number(v).toFixed(2)}`;
    }
  }

  // Try primary path (/api/<path>) then fallback to (/api/index.php/<path>)
  async function tryFetchJson(url, opts){
    const res = await fetch(url, opts);
    const txt = await res.text().catch(()=>null);
    let data = null;
    try{ data = txt ? JSON.parse(txt) : null; }catch(e){ data = txt; }
    return { res, data };
  }

  async function apiPost(path, body){
    const primary = API_BASE + '/' + path;
    const fallback = API_BASE + '/index.php/' + path;
    let attempt = await tryFetchJson(primary, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) });
    if(attempt.res.status === 404){
      attempt = await tryFetchJson(fallback, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) });
    }
    if(!attempt.res.ok) throw { status: attempt.res.status, data: attempt.data };
    return attempt.data;
  }

  async function apiGet(path){
    const primary = API_BASE + '/' + path;
    const fallback = API_BASE + '/index.php/' + path;
    let attempt = await tryFetchJson(primary, { method: 'GET' });
    if(attempt.res.status === 404){
      attempt = await tryFetchJson(fallback, { method: 'GET' });
    }
    if(!attempt.res.ok) throw { status: attempt.res.status, data: attempt.data };
    return attempt.data;
  }

  function saveSession(token){
    localStorage.setItem('session_token', token);
  }
  function getSession(){
    return localStorage.getItem('session_token');
  }

  // simple overlay prompt for table number
  function showTablePrompt(){
    return new Promise((resolve)=>{
      const overlay = document.createElement('div');
      overlay.className = 'app-overlay';
      overlay.innerHTML = `
        <div class="app-prompt" role="dialog" aria-modal="true">
          <h3>Informe o número da mesa</h3>
          <p>Se você não chegou via QR, por favor indique sua mesa.</p>
          <input type="text" inputmode="numeric" placeholder="Número da mesa" aria-label="Número da mesa" />
          <div class="app-prompt-actions">
            <button class="btn-secondary cancel">Cancelar</button>
            <button class="btn-primary submit">Continuar</button>
          </div>
          <p class="app-prompt-msg" aria-live="polite"></p>
        </div>`;
      document.body.appendChild(overlay);
      const input = overlay.querySelector('input');
      const btnSubmit = overlay.querySelector('.submit');
      const btnCancel = overlay.querySelector('.cancel');
      const msg = overlay.querySelector('.app-prompt-msg');
      input.focus();

      btnCancel.addEventListener('click', ()=>{
        overlay.remove();
        resolve(null);
      });
      btnSubmit.addEventListener('click', async ()=>{
        const val = input.value.trim();
        if(!val){ msg.textContent = 'Por favor insira o número da mesa.'; input.focus(); return; }
        msg.textContent = 'Verificando...';
        try{
          const payload = { table_number: val };
          if(restaurantSlug) payload.restaurant_slug = restaurantSlug;
          const res = await apiPost('visits', payload);
          // res: { session_token, restaurant_id, table_id, in_use }
          if(res.in_use){
            // warn, ask to continue
            const cont = confirm('A mesa parece estar em uso. Deseja continuar mesmo assim?');
            if(!cont){ msg.textContent = 'Operação cancelada.'; return; }
          }
          saveSession(res.session_token);
          overlay.remove();
          resolve(res);
        }catch(err){
          console.error(err);
          msg.textContent = err.data && err.data.error ? err.data.error : 'Erro ao criar visita';
        }
      });

      input.addEventListener('keydown', (e)=>{ if(e.key === 'Enter') btnSubmit.click(); });
    });
  }

  // Render menu into .prato-lista with dynamic category filtering
  function renderMenu(menu){
    // menu: {categories: [ { id, name, slug, items: [...] } ] }
    const categories = menu.categories || [];

    // (moved globally) formatCurrency available above

    // Main container: use data-category="populares" if present; else the first .prato-lista
    const mainContainer = qs('.prato-lista[data-category="populares"]') || qs('.prato-lista:not([data-category])') || qs('.prato-lista');

    // Render "populares" (aqui usamos todos os itens de todas as categorias)
    if(mainContainer){
      let html = '';
      categories.forEach(cat=>{
        (cat.items || []).forEach(item=>{
          const rating = item.rating || {avg:0,total:0,counts:{}};
          const ingredients = (item.ingredients || []).join(',');
          const priceDisplay = (item.price !== undefined && item.price !== null) ? formatCurrency(item.price) : (item.price_display || 'AO 0,00');
          const cookTime = item.cook_time || item.prep_time || '';
          html += `
              <div class="prato-card populares" data-id="${escapeHtml(item.id)}" data-desc="${escapeHtml(item.description||'')}" data-price="${escapeHtml(priceDisplay)}" data-ingredients="${escapeHtml(ingredients)}" data-category="${escapeHtml(cat.name || cat.slug || 'Categoria')}" data-rating='${JSON.stringify(rating)}'>
              <div class="reacion"><i class="fa-regular fa-eye"></i> <i class="fa-regular fa-heart"></i></div>
              <h3>${escapeHtml(truncate(item.name,16))}</h3>
              <div class="time-rank">
                <span><i class="fas fa-clock"></i> ${escapeHtml(cookTime)} ${cookTime ? 'min' : ''}</span>
                <span><i class="fas fa-star"></i> ${rating.avg ? rating.avg.toFixed(1) : '—'}</span>
              </div>
              <div class="preco-add">
                <span class="preco">${escapeHtml(priceDisplay)}</span>
                <a href="#" class="btn-secondary" aria-label="Adicionar ${escapeHtml(item.name)} ao carrinho"><i class="fa-solid fa-circle-plus"></i></a>
              </div>
              <div class="prato-card-fndo-circulo"></div>
              <div class="prato-card-img">  
                <img src="${escapeHtml(item.image || '/assets/images/crispy-baked-meat-potatoes.webp')}" alt="${escapeHtml(item.name)}">
              </div>
            </div>`;
        });
      });
      mainContainer.innerHTML = html || '<p>Nenhum prato encontrado.</p>';
    }

    // Fill category-specific sections
    categories.forEach(cat=>{
      const catSlug = cat.slug || cat.name.toLowerCase().replace(/\s+/g,'-');
      const catContainer = qs(`.prato-lista[data-category="${catSlug}"]`);
      if(!catContainer) return;
      let catHtml = '';
      (cat.items || []).forEach(item=>{
        const rating = item.rating || {avg:0,total:0,counts:{}};
        const ingredients = (item.ingredients || []).join(',');
        const priceDisplay = (item.price !== undefined && item.price !== null) ? formatCurrency(item.price) : (item.price_display || 'AO 0,00');
        const cookTime = item.cook_time || item.prep_time || '';
        catHtml += `
          <div class="prato-card especifico" data-id="${escapeHtml(item.id)}" data-desc="${escapeHtml(item.description||'')}" data-price="${escapeHtml(priceDisplay)}" data-ingredients="${escapeHtml(ingredients)}" data-category="${escapeHtml(cat.name || cat.slug || 'Categoria')}" data-rating='${JSON.stringify(rating)}'>
            <div class="reacion"><i class="fa-regular fa-eye"></i> <i class="fa-regular fa-heart"></i></div>
            <img src="${escapeHtml(item.image || 'images/crispy-baked-meat-potatoes.webp')}" alt="${escapeHtml(item.name)}">
            <h3>${escapeHtml(truncate(item.name,16))}</h3>
            <div class="time-rank">
              <span><i class="fas fa-clock"></i> ${escapeHtml(cookTime)} ${cookTime ? 'min' : ''}</span>
              <span><i class="fas fa-star"></i> ${rating.avg ? rating.avg.toFixed(1) : '—'}</span>
            </div>
            <div class="preco-add">
              <span class="preco">${escapeHtml(priceDisplay)}</span>
              <a href="#" class="btn-secondary" aria-label="Adicionar ${escapeHtml(item.name)} ao carrinho"><span>Adicionar</span> <i class="fa-solid fa-circle-plus"></i></a>
            </div>
          </div>`;
      });
      catContainer.innerHTML = catHtml || '<p>Nenhum prato nesta categoria.</p>';
    });

    // Trigger a custom event so other modules can re-bind listeners if necessary
    document.dispatchEvent(new CustomEvent('menu:rendered'));
  }

  // Render Top sections: first shows 4 most voted; others show categories
  function renderTopSections(menu){
    const categories = menu.categories || [];
    const mainEl = qs('main') || document.body;
    const topSection = qs('.top-five:not(.categoria)');

    // Build flattened list to compute Top 4 by total votes
    const flattened = [];
    categories.forEach(cat => {
      const catNome = cat.name || cat.slug || 'Categoria';
      (cat.items || []).forEach(item => flattened.push({ ...item, __categoria: catNome }));
    });
    const byVotes = (it) => (it.rating && typeof it.rating.total === 'number') ? it.rating.total : 0;
    flattened.sort((a,b)=> byVotes(b) - byVotes(a));
    const top4 = flattened.slice(0,4);

    function setHeader(el, title){
      const h = el.querySelector('.header-pratos h2');
      if(h) h.textContent = escapeHtml(title);
    }
    function renderCards(el, items, limit, categoria = ''){
      const wrap = el.querySelector('.top-five-cards');
      if(!wrap) return;
      const html = items.map((item, idx)=>{
        const rating = item.rating || {avg:0,total:0,counts:{}};
        const priceDisplay = (item.price !== undefined && item.price !== null) ? formatCurrency(item.price) : (item.price_display || 'AO 0,00');
        const image = item.image || 'images/crispy-baked-meat-potatoes.webp';
        const hiddenStyle = (typeof limit === 'number' && idx >= limit) ? 'style="display:none"' : '';
        const categoriaCard = item.category || item.categoria || item.__categoria || categoria || 'Categoria';
        return `
          <div class="card categoria-card" ${hiddenStyle} data-id="${escapeHtml(item.id)}" data-desc="${escapeHtml(item.description||'')}" data-price="${escapeHtml(priceDisplay)}" data-img="${escapeHtml(image)}" data-category="${escapeHtml(categoriaCard)}" data-rating='${JSON.stringify(rating)}'>
            <div class="space-image">
              <div class="descricao">
                <h3>${escapeHtml(truncate(item.name,16))}</h3>
                <p class="descricao-breve">${escapeHtml(truncate(item.description || '', 36))}</p>
                <div class="rank">
                  <div class="preco">${escapeHtml(priceDisplay)}</div>
                  <div class="details"><i class="fas fa-star"></i><span>${rating.avg ? rating.avg.toFixed(1) : '—'}</span></div>
                </div>
                <div class="accoes">
                  <div class="ver"><span>Ver detalhes</span><i class="fas fa-eye"></i></div>
                  <div class="add-cart"><span>Adicionar</span><i class="fas fa-plus-circle"></i></div>
                </div>
              </div>
              <img src="${escapeHtml(image)}" alt="${escapeHtml(item.name)}">
            </div>
          </div>`;
      }).join('');
      wrap.innerHTML = html || '<p>Nenhum prato encontrado.</p>';
    }

    // Render Top 4 section if present
    if(topSection){
      setHeader(topSection, 'Top 4 Mais Votados');
      renderCards(topSection, top4);
    }

    // Remove any manually inserted category sections
    qsa('.top-five.categoria').forEach(el => el.remove());

    // Dynamically create a section per category with items
    categories.forEach(cat => {
      if(!cat.items || cat.items.length === 0) return;
      const section = document.createElement('section');
      section.className = 'top-five categoria';
      section.innerHTML = `
        <div class="header-pratos">
          <h2>${escapeHtml(cat.name || cat.slug || 'Categoria')}</h2> <i class="fas fa-chevron-down"></i>
        </div>
        <div class="top-five-cards"></div>
      `;
      mainEl.appendChild(section);
      // Render all items, hiding beyond the first 4 initially
      renderCards(section, cat.items, 4, cat.name || cat.slug || 'Categoria');

      // Toggle remaining items on chevron click
      const chevron = section.querySelector('.header-pratos .fa-chevron-down');
      let expanded = false;
      if(chevron){
        chevron.style.cursor = 'pointer';
        chevron.addEventListener('click', () => {
          const cards = Array.from(section.querySelectorAll('.categoria-card'));
          if(!expanded){
            cards.forEach((card, idx) => { if(idx >= 4) card.style.display = ''; });
            section.classList.add('expanded');
            expanded = true;
            chevron.classList.remove('fa-chevron-down');
            chevron.classList.add('fa-chevron-up');
          } else {
            cards.forEach((card, idx) => { if(idx >= 4) card.style.display = 'none'; });
            section.classList.remove('expanded');
            expanded = false;
            chevron.classList.remove('fa-chevron-up');
            chevron.classList.add('fa-chevron-down');
          }
        });
      }
    });

    document.dispatchEvent(new CustomEvent('top:rendered'));
  }

  // Bind add-to-cart on Top sections (.categoria-card)
  function bindTopActions(){
    if(window.__topActionsBound) return; window.__topActionsBound = true;
    document.addEventListener('click', function(e){
      const add = e.target.closest('.top-five .add-cart i, .top-five .add-cart');
      if(!add) return;
      const card = add.closest('.categoria-card');
      if(!card) return;

      const id = card.dataset.id || '';
      const title = (card.querySelector('h3')?.textContent || '').trim();
      const price = card.dataset.price || (card.querySelector('.preco')?.textContent || '').trim();
      const img = card.dataset.img || (card.querySelector('img')?.getAttribute('src') || '');

      try{
        let cart = JSON.parse(localStorage.getItem('cart') || '[]');
        const existing = cart.find(i => i.id === id);
        if(existing){ existing.qty = (existing.qty || 1) + 1; }
        else {
          const cleaned = String(price).replace(/[A-Za-z\s]/g,'').replace(/\./g,'').replace(',','.');
          const priceValue = parseFloat(cleaned) || 0;
          cart.push({ id, title, price, priceValue, img, qty: 1 });
        }
        localStorage.setItem('cart', JSON.stringify(cart));
        window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart }));
      }catch(e){ console.error('Erro ao atualizar carrinho', e); }
    });
  }

  async function loadMenu(){
    try{
      const path = restaurantSlug ? `menu?slug=${encodeURIComponent(restaurantSlug)}` : 'menu';
      const menu = await apiGet(path);
      // API returns array of categories; normalize to { categories: [...] }
      const normalized = Array.isArray(menu) ? { categories: menu } : (menu || { categories: [] });
      renderMenu(normalized);
      renderTopSections(normalized);
      bindTopActions();
    }catch(e){
      console.error('Erro carregando menu', e);
    }
  }

  // boot sequence
  async function init(){
    try{
      const existing = getSession();
      const qr = getQueryParam('qr') || getQueryParam('token');
      if(existing){
        // already have session
        await loadMenu();
        return;
      }
      if(qr){
        try{
          const res = await apiPost('visits', { qr_token: qr });
          saveSession(res.session_token);
          await loadMenu();
          return;
        }catch(err){
          console.error('QR visit failed', err);
        }
      }
      // no session and no qr -> ask for table
      const visit = await showTablePrompt();
      if(visit){
        await loadMenu();
      } else {
        // user cancelled — still attempt to load menu read-only
        await loadMenu();
      }
    }catch(e){ console.error(e); }
  }

  // run
  document.addEventListener('DOMContentLoaded', init);

})();

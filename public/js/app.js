// Front-end app: handle QR/table flow and load menu dynamically
(async function(){
  const API_BASE = '/api';
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

    function formatCurrency(v){
      try{ return new Intl.NumberFormat('pt-AO',{ style:'currency', currency:'AOA', maximumFractionDigits:2 }).format(Number(v)); }catch(e){ return `AO ${Number(v).toFixed(2)}`; }
    }

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
            <div class="prato-card" data-id="${escapeHtml(item.id)}" data-desc="${escapeHtml(item.description||'')}" data-price="${escapeHtml(priceDisplay)}" data-ingredients="${escapeHtml(ingredients)}" data-rating='${JSON.stringify(rating)}'>
              <div class="reacion"><i class="fa-regular fa-eye"></i> <i class="fa-regular fa-heart"></i></div>
              <img src="${escapeHtml(item.image || 'images/crispy-baked-meat-potatoes.webp')}" alt="${escapeHtml(item.name)}">
              <h3>${escapeHtml(item.name)}</h3>
              <div class="time-rank">
                <span><i class="fas fa-clock"></i> ${escapeHtml(cookTime)} ${cookTime ? 'min' : ''}</span>
                <span><i class="fas fa-star"></i> ${rating.avg ? rating.avg.toFixed(1) : '—'}</span>
              </div>
              <div class="preco-add">
                <span class="preco">${escapeHtml(priceDisplay)}</span>
                <a href="#" class="btn-secondary" aria-label="Adicionar ${escapeHtml(item.name)} ao carrinho"><i class="fas fa-cart-plus"></i></a>
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
          <div class="prato-card" data-id="${escapeHtml(item.id)}" data-desc="${escapeHtml(item.description||'')}" data-price="${escapeHtml(priceDisplay)}" data-ingredients="${escapeHtml(ingredients)}" data-rating='${JSON.stringify(rating)}'>
            <div class="reacion"><i class="fa-regular fa-eye"></i> <i class="fa-regular fa-heart"></i></div>
            <img src="${escapeHtml(item.image || 'images/crispy-baked-meat-potatoes.webp')}" alt="${escapeHtml(item.name)}">
            <h3>${escapeHtml(item.name)}</h3>
            <div class="time-rank">
              <span><i class="fas fa-clock"></i> ${escapeHtml(cookTime)} ${cookTime ? 'min' : ''}</span>
              <span><i class="fas fa-star"></i> ${rating.avg ? rating.avg.toFixed(1) : '—'}</span>
            </div>
            <div class="preco-add">
              <span class="preco">${escapeHtml(priceDisplay)}</span>
              <a href="#" class="btn-secondary" aria-label="Adicionar ${escapeHtml(item.name)} ao carrinho"><i class="fas fa-cart-plus"></i></a>
            </div>
          </div>`;
      });
      catContainer.innerHTML = catHtml || '<p>Nenhum prato nesta categoria.</p>';
    });

    // Trigger a custom event so other modules can re-bind listeners if necessary
    document.dispatchEvent(new CustomEvent('menu:rendered'));
  }

  async function loadMenu(){
    try{
      const path = restaurantSlug ? `menu?slug=${encodeURIComponent(restaurantSlug)}` : 'menu';
      const menu = await apiGet(path);
      // API returns array of categories; normalize to { categories: [...] }
      if(Array.isArray(menu)) renderMenu({ categories: menu });
      else renderMenu(menu);
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

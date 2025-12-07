// Modal behavior for prato cards
(function(){
  function createModal(){
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `
      <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-header">
          <strong class="modal-title"></strong>
          <button class="modal-close" aria-label="Fechar">&times;</button>
        </div>
        <div class="modal-body">
          <img class="modal-image" src="" alt="imagem do prato">
          <div class="modal-content">
            <h3></h3>
            <p class="modal-desc"></p>
            <div class="modal-meta"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-modal-primary">Adicionar ao carrinho</button>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);
    return overlay;
  }

  const modalOverlay = createModal();
  const modal = modalOverlay.querySelector('.modal');
  const modalTitle = modalOverlay.querySelector('.modal-title');
  const modalImg = modalOverlay.querySelector('.modal-image');
  const modalH3 = modalOverlay.querySelector('.modal-content h3');
  const modalDesc = modalOverlay.querySelector('.modal-desc');
  const modalMeta = modalOverlay.querySelector('.modal-meta');
  const btnClose = modalOverlay.querySelector('.modal-close');

  let _previousActive = null;
  function getFocusable(container){
    return Array.from(container.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'))
      .filter(el => !el.hasAttribute('disabled'));
  }

  function openModalFromCard(card){
    // Prefer data attributes if available
    const dataset = card.dataset || {};
    const title = dataset.title || card.querySelector('h3')?.textContent?.trim() || dataset.id || '';
    const img = dataset.img || card.querySelector('img')?.getAttribute('src') || '';
    const timeSpan = card.querySelector('.time-rank span:nth-child(1)');
    const ratingSpan = card.querySelector('.time-rank span:nth-child(2)');
    const price = dataset.price || card.querySelector('.preco')?.textContent?.trim() || '';
    const desc = dataset.desc || card.querySelector('p')?.textContent?.trim() || 'Descrição do prato disponível aqui.';

    modalTitle.textContent = title;
    modalH3.textContent = title;
    modalImg.src = img;
    modalImg.alt = title;
    modalDesc.textContent = desc;
    modalMeta.innerHTML = '';
    if(timeSpan) modalMeta.innerHTML += `<div style="margin-bottom:6px">${timeSpan.innerHTML}</div>`;
    if(ratingSpan) modalMeta.innerHTML += `<div style="margin-bottom:6px">${ratingSpan.innerHTML}</div>`;
    if(price) modalMeta.innerHTML += `<div><strong>Preço:</strong> ${price}</div>`;

    // Ingredients: try dataset.ingredients (comma-separated) or fallback to data-desc parsing
    const ingredientsRaw = dataset.ingredients || '';
    let ingredients = [];
    if(ingredientsRaw){
      ingredients = String(ingredientsRaw).split(',').map(s => s.trim()).filter(Boolean);
    }
    // render ingredients list inside modal (create container if needed)
    let ingContainer = modalOverlay.querySelector('.modal-ingredients');
    if(!ingContainer){
      const wrapper = document.createElement('div');
      wrapper.className = 'modal-ingredients';
      wrapper.innerHTML = '<h4>Ingredientes</h4><ul></ul>';
      modal.querySelector('.modal-content').appendChild(wrapper);
      ingContainer = modalOverlay.querySelector('.modal-ingredients');
    }
    const ul = ingContainer.querySelector('ul');
    ul.innerHTML = '';
    if(ingredients.length === 0){
      ul.innerHTML = '<li>Ingredientes não especificados.</li>';
    } else {
      ingredients.forEach(it => { const li = document.createElement('li'); li.textContent = it; ul.appendChild(li); });
    }

    // Ratings: parse dataset.rating JSON if present
    let ratingData = null;
    if(dataset.rating){
      try{ ratingData = JSON.parse(dataset.rating); }catch(e){ ratingData = null; }
    }
    // fallback: try to read average from displayed star span
    if(!ratingData){
      const avgSpan = card ? card.querySelector('.time-rank span:nth-child(2)') : null;
      const avgText = avgSpan ? avgSpan.textContent.replace(/[^0-9\.,]/g,'').trim() : '';
      const avg = avgText ? parseFloat(avgText.replace(',', '.')) : null;
      ratingData = avg ? { avg: avg, total: 0, counts: {} } : null;
    }

    // Render ratings block
    let ratingsBlock = modalOverlay.querySelector('.modal-ratings');
    if(!ratingsBlock){
      const div = document.createElement('div'); div.className = 'modal-ratings';
      div.innerHTML = `
        <div class="rating-summary">
          <div class="avg">-</div>
          <div class="total">- avaliações</div>
        </div>
        <div class="rating-breakdown">
        </div>
      `;
      modal.querySelector('.modal-content').appendChild(div);
      ratingsBlock = modalOverlay.querySelector('.modal-ratings');
    }

    const summaryAvg = ratingsBlock.querySelector('.rating-summary .avg');
    const summaryTotal = ratingsBlock.querySelector('.rating-summary .total');
    const breakdownEl = ratingsBlock.querySelector('.rating-breakdown');
    breakdownEl.innerHTML = '';

    if(ratingData){
      const total = ratingData.total || Object.values(ratingData.counts || {}).reduce((s,v)=>s+ (Number(v)||0),0);
      summaryAvg.textContent = ratingData.avg ? Number(ratingData.avg).toFixed(1) : '-';
      summaryTotal.textContent = total ? `${total} avaliações` : 'Sem avaliações';

      // ensure counts exist for 5..1
      const counts = ratingData.counts || {};
      const sumCounts = Object.keys(counts).reduce((s,k)=>s + (Number(counts[k])||0),0) || total || 0;
      for(let s=5;s>=1;s--){
        const cnt = Number(counts[String(s)]) || 0;
        const percent = sumCounts ? Math.round((cnt / sumCounts) * 100) : 0;
        const row = document.createElement('div'); row.className = 'rating-row';
        row.innerHTML = `
          <div class="star-label">${s} <i class="fas fa-star" style="color:var(--amarelo-suave);"></i></div>
          <div class="bar"><div class="fill" style="width:${percent}%;"></div></div>
          <div class="percent">${percent}%</div>
        `;
        breakdownEl.appendChild(row);
      }
    } else {
      summaryAvg.textContent = '-'; summaryTotal.textContent = 'Sem avaliações';
      breakdownEl.innerHTML = '<p style="color:var(--cinza-escuro)">Sem dados de avaliações.</p>';
    }

    // set add-to-cart action data
    const addBtn = modalOverlay.querySelector('.btn-modal-primary');
    addBtn.dataset.pratoId = dataset.id || '';
    addBtn.dataset.pratoTitle = title;
    addBtn.dataset.pratoPrice = price;
    addBtn.dataset.pratoImg = modalImg.src || '';

    // accessibility: save previous focus and move focus into modal
    _previousActive = document.activeElement;
    modalOverlay.classList.add('show');
    document.addEventListener('keydown', onKey);
    // focus first actionable element (close button)
    setTimeout(()=>{
      const focusables = getFocusable(modal);
      if(focusables.length) focusables[0].focus();
    },50);
  }

  function closeModal(){
    modalOverlay.classList.remove('show');
    document.removeEventListener('keydown', onKey);
    // restore focus
    try{ if(_previousActive && typeof _previousActive.focus === 'function') _previousActive.focus(); }catch(e){}
  }

  function onKey(e){
    if(e.key === 'Escape') return closeModal();
    if(e.key === 'Tab'){
      const focusables = getFocusable(modal);
      if(focusables.length === 0) { e.preventDefault(); return; }
      const first = focusables[0];
      const last = focusables[focusables.length - 1];
      if(e.shiftKey){
        if(document.activeElement === first){ last.focus(); e.preventDefault(); }
      } else {
        if(document.activeElement === last){ first.focus(); e.preventDefault(); }
      }
    }
  }

  modalOverlay.addEventListener('click', function(e){
    if(e.target === modalOverlay || e.target.classList.contains('modal-close')) closeModal();
  });

  // Delegate click on eye icons
  document.addEventListener('click', function(e){
    const eye = e.target.closest('i.fa-eye');
    if(!eye) return;
    const card = eye.closest('.prato-card');
    if(!card) return;
    openModalFromCard(card);
  });

  // Add to cart button behavior — persist cart in localStorage
  modalOverlay.querySelector('.btn-modal-primary').addEventListener('click', function(){
    const id = this.dataset.pratoId || '(sem id)';
    const title = this.dataset.pratoTitle || '(sem titulo)';
    const price = this.dataset.pratoPrice || '(sem preco)';
    const img = this.dataset.pratoImg || '';

    function getCart(){
      try{
        const raw = localStorage.getItem('cart');
        return raw ? JSON.parse(raw) : [];
      }catch(e){ return []; }
    }
    function saveCart(cart){
      try{ localStorage.setItem('cart', JSON.stringify(cart)); }catch(e){ console.error('Erro ao salvar carrinho', e); }
    }

    const cart = getCart();
    const existing = cart.find(i => i.id === id);
    if(existing){
      existing.qty = (existing.qty || 1) + 1;
    } else {
      // compute numeric price if possible
      function parsePrice(str){
        if(!str) return 0;
        const cleaned = String(str).replace(/[A-Za-z\s]/g,'').replace(/\./g,'').replace(',','.');
        const v = parseFloat(cleaned);
        return Number.isFinite(v) ? v : 0;
      }
      const priceValue = parsePrice(price);
      cart.push({ id, title, price, priceValue, img, qty: 1 });
    }
    saveCart(cart);

    // Dispatch event so other UI can react
    try{ window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart })); }catch(e){}

    // visual feedback
    const btn = this;
    btn.disabled = true;
    const prevText = btn.textContent;
    btn.textContent = 'Adicionado ✓';
    setTimeout(() => {
      btn.textContent = prevText;
      btn.disabled = false;
      closeModal();
    }, 700);
  });

})();

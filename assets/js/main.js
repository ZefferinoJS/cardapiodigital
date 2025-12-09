
/**
 * admin.js — Painel de Administração (ficheiro unificado)
 * Contém: Dashboard · Pratos · Categorias · Pedidos · Mesas · Avaliações · Menu lateral
 *
 * DIAGNÓSTICO DO ERRO 404 (histórico — já corrigido):
 * ─────────────────────────────────────────────────────────────────────────────
 * O ficheiro da API vive em /public/api/index.php (ver o cabeçalho desse
 * ficheiro). Versões anteriores deste script apontavam para '/assets/api'
 * ou '/api', nenhum dos quais existe no servidor — o Apache devolvia uma
 * página HTML de erro 404, daí o erro:
 *   SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
 *
 * SOLUÇÃO: API_BASE aponta agora para '/public/api', com fallback explícito
 * para '/public/api/index.php/<rota>' (funciona sempre, mesmo sem .htaccess).
 * ─────────────────────────────────────────────────────────────────────────────
 */

 
(function () {
  'use strict';
 
  /* ═══════════════════════════════════════════════════════════════
     CONFIGURAÇÃO GLOBAL
  ═══════════════════════════════════════════════════════════════ */
 
  /**
   * public/api/index.php é o único ficheiro PHP da API.
   * Sem .htaccess o servidor não sabe que /public/api/admin/orders
   * deve ser tratado por index.php — devolve 404 HTML.
   * Por isso tentamos primeiro o URL limpo e, se vier 404,
   * chamamos explicitamente /public/api/index.php/<rota>.
   */
  const API_BASE     = '/public/api';           // requer .htaccess / PATH_INFO
  const API_FALLBACK = '/public/api/index.php'; // funciona sempre
  const RESTAURANT_ID = 1;
 
  /* ═══════════════════════════════════════════════════════════════
     UTILITÁRIOS PARTILHADOS
  ═══════════════════════════════════════════════════════════════ */
 
  /**
   * Escapa HTML para evitar XSS.
   * @param {string|null} str
   * @returns {string}
   */
  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }
 
  /**
   * Formata um valor numérico como moeda angolana (AOA).
   * @param {number} value
   * @returns {string}
   */
  function formatPrice(value) {
    try {
      return new Intl.NumberFormat('pt-AO', {
        style: 'currency',
        currency: 'AOA',
        maximumFractionDigits: 2
      }).format(Number(value) || 0);
    } catch (e) {
      return (Number(value) || 0).toFixed(2) + ' AOA';
    }
  }
 
  /**
   * Fetch com fallback automático para /index.php/<rota>.
   *
   * Sem .htaccess, /public/api/admin/orders não existe como ficheiro/pasta
   * → o servidor devolve uma página HTML de 404
   * → JSON.parse falha com "Unexpected token '<'"
   *
   * Solução: se a primeira tentativa devolver 404, repetimos com
   * /public/api/index.php/<rota> que chama o PHP directamente.
   *
   * @param {string} path  — ex: "admin/orders?restaurant_id=1"
   * @param {RequestInit} opts
   */
  async function apiFetch(path, opts = {}) {
    const primaryUrl  = `${API_BASE}/${path}`;
    const fallbackUrl = `${API_FALLBACK}/${path}`;
 
    async function doFetch(url) {
      let res;
      try {
        res = await fetch(url, opts);
      } catch (netErr) {
        throw new Error(
          `[Rede] Sem resposta de "${url}". ` +
          `Servidor em baixo ou CORS bloqueado. Detalhe: ${netErr.message}`
        );
      }
      return res;
    }
 
    async function toJson(res, url) {
      const ct = res.headers.get('content-type') || '';
      if (!ct.includes('application/json')) {
        // O servidor devolveu HTML (página de erro) em vez de JSON
        const snippet = await res.text().then(t => t.slice(0, 200)).catch(() => '');
        throw new Error(
          `[HTTP ${res.status}] "${url}" devolveu "${ct || 'sem content-type'}" em vez de JSON.\n` +
          `Início da resposta: ${snippet}\n` +
          `→ Verifique se existe .htaccess com RewriteRule ou use o caminho /index.php/.`
        );
      }
      try {
        return await res.json();
      } catch (e) {
        throw new Error(`[JSON] Falha ao interpretar resposta de "${url}": ${e.message}`);
      }
    }
 
    // 1.ª tentativa — URL limpo (funciona com .htaccess ou PATH_INFO configurado)
    let res = await doFetch(primaryUrl);
 
    // Fallback automático quando não há rewrite configurado
    if (res.status === 404) {
      console.warn(
        `[apiFetch] 404 em "${primaryUrl}" — provavelmente sem .htaccess.\n` +
        `A tentar fallback: "${fallbackUrl}"`
      );
      res = await doFetch(fallbackUrl);
    }
 
    const data = await toJson(res, res.url || primaryUrl);
 
    if (!res.ok) {
      const msg = data?.details || data?.error || `Erro HTTP ${res.status}`;
      throw new Error(`[API ${res.status}] ${msg}`);
    }
 
    return data;
  }
 
  /* ═══════════════════════════════════════════════════════════════
     MÓDULO: MENU LATERAL (adminMenu.js)
  ═══════════════════════════════════════════════════════════════ */
 
  function initAdminMenu() {
    const sideLeft    = document.querySelector('.side-left');
    const hamburguer  = document.querySelector('.hamburguer');
    const adminSidebar = document.querySelector('.admin-sidebar');
 
    if (!hamburguer || !adminSidebar) return;
 
    hamburguer.addEventListener('click', () => {
      adminSidebar.classList.toggle('menu-collapsed');
      if (sideLeft) sideLeft.classList.toggle('display');
    });
  }
 
  /* ═══════════════════════════════════════════════════════════════
     MÓDULO: DASHBOARD (admin-dashboard.js)
  ═══════════════════════════════════════════════════════════════ */
 
  function initDashboard() {
    const totalEl    = document.getElementById('total-pratos');
    const mediaEl    = document.getElementById('media-avaliacao');
    const pedidosEl  = document.getElementById('pedidos-hoje');
    const receitaEl  = document.getElementById('receita');
 
    if (!totalEl && !mediaEl && !pedidosEl && !receitaEl) return;
 
    async function loadMetrics() {
      [totalEl, mediaEl, pedidosEl, receitaEl].forEach(el => {
        if (el) el.textContent = '…';
      });
 
      const today = new Date().toISOString().slice(0, 10);
      const url = `admin/metrics?restaurant_id=${RESTAURANT_ID}&date=${today}`;
 
      try {
        const data = await apiFetch(url);
 
        if (totalEl)   totalEl.textContent   = typeof data.total_items === 'number' ? data.total_items : '—';
        if (mediaEl)   mediaEl.textContent   = (data.average_rating != null) ? Number(data.average_rating).toFixed(2) : '—';
        if (pedidosEl) pedidosEl.textContent = typeof data.orders_today === 'number' ? data.orders_today : '—';
        if (receitaEl) receitaEl.textContent = typeof data.revenue_today === 'number' ? formatPrice(data.revenue_today) : '—';
      } catch (err) {
        console.error('[Dashboard] Erro ao carregar métricas:', err.message);
        [totalEl, mediaEl, pedidosEl, receitaEl].forEach(el => {
          if (el) el.textContent = '—';
        });
      }
    }
 
    loadMetrics();
  }
 
  /* ═══════════════════════════════════════════════════════════════
     MÓDULO: PRATOS (admin-pratos.js)
  ═══════════════════════════════════════════════════════════════ */
 
  function initPratos() {
    const tbody           = document.getElementById('pratos-tbody');
    const modalOverlay    = document.getElementById('modal-prato');
    const modalTitle      = document.getElementById('modal-title');
    const form            = document.getElementById('form-prato');
    const btnNovo         = document.getElementById('btn-novo-prato');
    const btnClose        = document.getElementById('close-modal-prato');
    const btnCancel       = document.getElementById('cancel-prato');
    const filterCategoria = document.getElementById('filter-categoria');
    const filterDisponivel = document.getElementById('filter-disponivel');
    const searchInput     = document.getElementById('search-prato');
    const btnSearch       = document.getElementById('btn-search');
    const selectCategoria = document.getElementById('prato-categoria');
 
    if (!tbody) return; // não estamos na página de pratos
 
    let currentItemId = null;
 
    async function init() {
      await loadCategories();
      await loadItems();
      setupEventListeners();
    }
 
    function setupEventListeners() {
      btnNovo  && btnNovo.addEventListener('click', () => openModal());
      btnClose && btnClose.addEventListener('click', closeModal);
      btnCancel && btnCancel.addEventListener('click', closeModal);
      modalOverlay && modalOverlay.addEventListener('click', e => {
        if (e.target === modalOverlay) closeModal();
      });
      form       && form.addEventListener('submit', handleSubmit);
      btnSearch  && btnSearch.addEventListener('click', loadItems);
      searchInput && searchInput.addEventListener('keypress', e => { if (e.key === 'Enter') loadItems(); });
      filterCategoria  && filterCategoria.addEventListener('change', loadItems);
      filterDisponivel && filterDisponivel.addEventListener('change', loadItems);
    }
 
    async function loadCategories() {
      try {
        const categories = await apiFetch(
          `admin/categories?restaurant_id=${RESTAURANT_ID}`
        );
 
        if (filterCategoria) {
          filterCategoria.innerHTML = '<option value="">Todas</option>';
          categories.forEach(cat => {
            filterCategoria.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
          });
        }
        if (selectCategoria) {
          selectCategoria.innerHTML = '<option value="">Selecione...</option>';
          categories.forEach(cat => {
            selectCategoria.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
          });
        }
      } catch (err) {
        console.error('[Pratos] Erro ao carregar categorias:', err.message);
      }
    }
 
    async function loadItems() {
      try {
        tbody.innerHTML = '<tr><td colspan="7" class="loading"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';
 
        const categoryId = filterCategoria ? filterCategoria.value : '';
        const available  = filterDisponivel ? filterDisponivel.value : '';
        const search     = searchInput ? searchInput.value.trim() : '';
 
        let url = `admin/items?restaurant_id=${RESTAURANT_ID}`;
        if (categoryId) url += `&category_id=${categoryId}`;
        if (available !== '') url += `&available=${available}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
 
        const items = await apiFetch(url);
 
        if (items.length === 0) {
          tbody.innerHTML = '<tr><td colspan="7" class="empty">Nenhum prato encontrado</td></tr>';
          return;
        }
 
        tbody.innerHTML = items.map(item => `
          <tr data-id="${item.id}">
            <td class="td-imagem">
              <img src="${escapeHtml(item.image || '/images/placeholder.png')}"
                   alt="${escapeHtml(item.name)}"
                   class="item-thumb"
                   onerror="this.src='/images/placeholder.png'">
            </td>
            <td class="td-nome"><strong>${escapeHtml(item.name)}</strong></td>
            <td class="td-categoria">${escapeHtml(item.category_name || '-')}</td>
            <td class="td-preco">${formatPrice(item.price)}</td>
            <td class="td-disponivel">
              <span class="badge ${item.available ? 'badge-success' : 'badge-danger'}">
                ${item.available ? 'Sim' : 'Não'}
              </span>
            </td>
            <td class="td-avaliacao">
              ${item.avg_rating
                ? `<i class="fas fa-star" style="color:var(--amarelo-suave);"></i>
                   ${item.avg_rating.toFixed(1)} (${item.total_ratings})`
                : '-'}
            </td>
            <td class="td-acoes actions">
              <button class="btn-icon btn-edit"   data-id="${item.id}" title="Editar">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn-icon btn-delete" data-id="${item.id}" title="Deletar">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        `).join('');
 
        tbody.querySelectorAll('.btn-edit').forEach(btn =>
          btn.addEventListener('click', () => editItem(btn.dataset.id))
        );
        tbody.querySelectorAll('.btn-delete').forEach(btn =>
          btn.addEventListener('click', () => deleteItem(btn.dataset.id))
        );
      } catch (err) {
        console.error('[Pratos] Erro ao carregar pratos:', err.message);
        tbody.innerHTML = `<tr><td colspan="7" class="error">Erro ao carregar pratos: ${escapeHtml(err.message)}</td></tr>`;
      }
    }
 
    function openModal(item = null) {
      currentItemId = item ? item.id : null;
      modalTitle.textContent = item ? 'Editar Prato' : 'Novo Prato';
 
      if (item) {
        const prep = item.prep_time_minutes ?? item.cook_time_minutes ?? '';
        document.getElementById('prato-id').value          = item.id ?? '';
        document.getElementById('prato-nome').value        = item.name ?? '';
        document.getElementById('prato-categoria').value   = item.category_id ?? '';
        document.getElementById('prato-preco').value       = item.price ?? '';
        document.getElementById('prato-tempo').value       = prep;
        document.getElementById('prato-descricao').value   = item.description ?? '';
        document.getElementById('prato-imagem').value      = item.image ?? '';
        document.getElementById('prato-ingredientes').value = item.ingredients ?? '';
        document.getElementById('prato-disponivel').checked = Boolean(item.available);
      } else {
        form.reset();
        document.getElementById('prato-disponivel').checked = true;
      }
 
      modalOverlay.classList.add('show');
      document.getElementById('prato-nome').focus();
    }
 
    function closeModal() {
      modalOverlay.classList.remove('show');
      form.reset();
      currentItemId = null;
    }
 
    async function editItem(id) {
      try {
        const item = await apiFetch(`admin/items/${id}`);
        if (item.error) throw new Error(item.error);
        openModal(item);
      } catch (err) {
        console.error('[Pratos] Erro ao editar prato:', err.message);
        alert('Erro ao carregar dados do prato: ' + err.message);
      }
    }
 
    async function deleteItem(id) {
      if (!confirm('Tem certeza que deseja deletar este prato?')) return;
      try {
        await apiFetch(`admin/items/${id}`, { method: 'DELETE' });
        alert('Prato deletado com sucesso!');
        await loadItems();
      } catch (err) {
        console.error('[Pratos] Erro ao deletar prato:', err.message);
        alert('Erro ao deletar prato: ' + err.message);
      }
    }
 
    async function handleSubmit(e) {
      e.preventDefault();
      const formData = {
        restaurant_id:   RESTAURANT_ID,
        name:            document.getElementById('prato-nome').value.trim(),
        slug:            '',
        description:     document.getElementById('prato-descricao').value.trim(),
        price:           parseFloat(document.getElementById('prato-preco').value),
        image_url:       document.getElementById('prato-imagem').value.trim(),
        category_id:     parseInt(document.getElementById('prato-categoria').value) || null,
        prep_time_minutes: parseInt(document.getElementById('prato-tempo').value) || null,
        ingredients:     document.getElementById('prato-ingredientes').value.trim(),
        is_available:    document.getElementById('prato-disponivel').checked
      };
 
      const url    = currentItemId ? `admin/items/${currentItemId}` : `admin/items`;
      const method = currentItemId ? 'PUT' : 'POST';
 
      try {
        await apiFetch(url, {
          method,
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(formData)
        });
        alert(currentItemId ? 'Prato atualizado com sucesso!' : 'Prato criado com sucesso!');
        closeModal();
        await loadItems();
      } catch (err) {
        console.error('[Pratos] Erro ao salvar prato:', err.message);
        alert('Erro ao salvar prato: ' + err.message);
      }
    }
 
    init();
  }
 
  /* ═══════════════════════════════════════════════════════════════
     MÓDULO: CATEGORIAS (admin-categorias.js)
  ═══════════════════════════════════════════════════════════════ */
 
  function initCategorias() {
    const tbody       = document.getElementById('categorias-tbody');
    const modalOverlay = document.getElementById('modal-categoria');
    const modalTitle  = document.getElementById('modal-title');
    const form        = document.getElementById('form-categoria');
    const btnNova     = document.getElementById('btn-nova-categoria');
    const btnClose    = document.getElementById('close-modal-categoria');
    const btnCancel   = document.getElementById('cancel-categoria');
 
    if (!tbody) return;
 
    let currentCategoryId = null;
 
    function setupEventListeners() {
      btnNova    && btnNova.addEventListener('click', () => openModal());
      btnClose   && btnClose.addEventListener('click', closeModal);
      btnCancel  && btnCancel.addEventListener('click', closeModal);
      modalOverlay && modalOverlay.addEventListener('click', e => {
        if (e.target === modalOverlay) closeModal();
      });
      form && form.addEventListener('submit', handleSubmit);
    }
 
    async function loadCategories() {
      try {
        tbody.innerHTML = '<tr><td colspan="6" class="loading"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';
        const categories = await apiFetch(
          `admin/categories?restaurant_id=${RESTAURANT_ID}`
        );
 
        if (categories.length === 0) {
          tbody.innerHTML = '<tr><td colspan="6" class="empty">Nenhuma categoria encontrada</td></tr>';
          return;
        }
 
        tbody.innerHTML = categories.map(cat => `
          <tr data-id="${cat.id}">
            <td class="td-nome"><strong>${escapeHtml(cat.name)}</strong></td>
            <td class="td-slug">${escapeHtml(cat.slug)}</td>
            <td class="td-pratos"><span class="badge badge-info">--</span></td>
            <td class="td-posicao">${cat.position}</td>
            <td class="td-ativa">
              <span class="badge ${cat.active ? 'badge-success' : 'badge-danger'}">
                ${cat.active ? 'Sim' : 'Não'}
              </span>
            </td>
            <td class="actions td-acoes">
              <button class="btn-icon btn-edit"   data-id="${cat.id}" title="Editar">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn-icon btn-delete" data-id="${cat.id}" title="Deletar">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        `).join('');
 
        tbody.querySelectorAll('.btn-edit').forEach(btn =>
          btn.addEventListener('click', () => {
            const cat = categories.find(c => c.id == btn.dataset.id);
            if (cat) openModal(cat);
          })
        );
        tbody.querySelectorAll('.btn-delete').forEach(btn =>
          btn.addEventListener('click', () => deleteCategory(btn.dataset.id))
        );
      } catch (err) {
        console.error('[Categorias] Erro ao carregar categorias:', err.message);
        tbody.innerHTML = `<tr><td colspan="6" class="error">Erro: ${escapeHtml(err.message)}</td></tr>`;
      }
    }
 
    function openModal(category = null) {
      currentCategoryId = category ? category.id : null;
      modalTitle.textContent = category ? 'Editar Categoria' : 'Nova Categoria';
 
      if (category) {
        document.getElementById('categoria-id').value       = category.id;
        document.getElementById('categoria-nome').value     = category.name;
        document.getElementById('categoria-slug').value     = category.slug;
        document.getElementById('categoria-posicao').value  = category.position;
        document.getElementById('categoria-ativa').checked  = category.active;
      } else {
        form.reset();
        document.getElementById('categoria-ativa').checked = true;
      }
 
      modalOverlay.classList.add('show');
      document.getElementById('categoria-nome').focus();
    }
 
    function closeModal() {
      modalOverlay.classList.remove('show');
      form.reset();
      currentCategoryId = null;
    }
 
    async function deleteCategory(id) {
      if (!confirm('Tem certeza que deseja deletar esta categoria?')) return;
      try {
        await apiFetch(`admin/categories/${id}`, { method: 'DELETE' });
        alert('Categoria deletada com sucesso!');
        await loadCategories();
      } catch (err) {
        console.error('[Categorias] Erro ao deletar categoria:', err.message);
        alert('Erro ao deletar categoria: ' + err.message);
      }
    }
 
    async function handleSubmit(e) {
      e.preventDefault();
      const formData = {
        restaurant_id: RESTAURANT_ID,
        name:     document.getElementById('categoria-nome').value.trim(),
        slug:     document.getElementById('categoria-slug').value.trim(),
        position: parseInt(document.getElementById('categoria-posicao').value),
        active:   document.getElementById('categoria-ativa').checked
      };
 
      const url    = currentCategoryId
        ? `admin/categories/${currentCategoryId}`
        : `admin/categories`;
      const method = currentCategoryId ? 'PUT' : 'POST';
 
      try {
        await apiFetch(url, {
          method,
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(formData)
        });
        alert(currentCategoryId ? 'Categoria atualizada com sucesso!' : 'Categoria criada com sucesso!');
        closeModal();
        await loadCategories();
      } catch (err) {
        console.error('[Categorias] Erro ao salvar categoria:', err.message);
        alert('Erro ao salvar categoria: ' + err.message);
      }
    }
 
    loadCategories();
    setupEventListeners();
  }
 
  /* ═══════════════════════════════════════════════════════════════
     MÓDULO: PEDIDOS (admin-pedidos.js) — BUG PRINCIPAL CORRIGIDO
  ═══════════════════════════════════════════════════════════════ */
 
  function initPedidos() {
    const tbody          = document.getElementById('pedidos-tbody');
    const modalOverlay   = document.getElementById('modal-pedido');
    const modalBody      = document.getElementById('pedido-modal-body');
    const btnClose       = document.getElementById('close-modal-pedido');
    const btnCloseFoot   = document.getElementById('close-modal-pedido-footer');
    const filterStatus   = document.getElementById('filter-status');
    const filterData     = document.getElementById('filter-data');
    const searchInput    = document.getElementById('search-pedido');
    const btnSearch      = document.getElementById('btn-search');
    const btnUpdateStatus = document.getElementById('btn-update-status');
    const statusSelect   = document.getElementById('pedido-status-select');
    const modalIdSpan    = document.getElementById('pedido-id-modal');
 
    // Mesas
    const btnNovaMesa    = document.getElementById('btn-nova-cadeira');
    const btnVerMesas    = document.getElementById('btn-ver-lista-cadeiras');
    const modalNovaMesa  = document.getElementById('modal-nova-mesa');
    const modalListaMesas = document.getElementById('modal-lista-mesas');
    const formMesa       = document.getElementById('form-mesa');
    const mesasTbody     = document.getElementById('mesas-tbody');
    const closeMesa      = document.getElementById('close-modal-mesa');
    const cancelMesa     = document.getElementById('cancel-mesa');
    const closeLista     = document.getElementById('close-modal-lista');
    const closeListaFooter = document.getElementById('close-modal-lista-footer');
 
    if (!tbody) return;
 
    let currentOrderId = null;
    let currentMesaId  = null;
 
    function init() {
      loadOrders();
      setupEventListeners();
    }
 
    function setupEventListeners() {
      btnClose      && btnClose.addEventListener('click', closeModal);
      btnCloseFoot  && btnCloseFoot.addEventListener('click', closeModal);
      modalOverlay  && modalOverlay.addEventListener('click', e => {
        if (e.target === modalOverlay) closeModal();
      });
      btnSearch     && btnSearch.addEventListener('click', loadOrders);
      searchInput   && searchInput.addEventListener('keypress', e => {
        if (e.key === 'Enter') loadOrders();
      });
      filterStatus  && filterStatus.addEventListener('change', loadOrders);
      filterData    && filterData.addEventListener('change', loadOrders);
      btnUpdateStatus && btnUpdateStatus.addEventListener('click', updateOrderStatus);
 
      btnNovaMesa  && btnNovaMesa.addEventListener('click', openNovaMesaModal);
      btnVerMesas  && btnVerMesas.addEventListener('click', openListaMesasModal);
      closeMesa    && closeMesa.addEventListener('click', closeMesaModal);
      cancelMesa   && cancelMesa.addEventListener('click', closeMesaModal);
      closeLista   && closeLista.addEventListener('click', closeListaMesasModal);
      closeListaFooter && closeListaFooter.addEventListener('click', closeListaMesasModal);
      formMesa     && formMesa.addEventListener('submit', saveMesa);
    }
 
    async function loadOrders() {
      try {
        tbody.innerHTML = '<tr><td colspan="7" class="loading"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';
 
        const status = filterStatus ? filterStatus.value : '';
        const date   = filterData   ? filterData.value   : '';
        const search = searchInput  ? searchInput.value.trim() : '';
 
        // ✅ Usa apiFetch(), que já resolve para /public/api/admin/orders (com fallback)
        let url = `admin/orders?restaurant_id=${RESTAURANT_ID}`;
        if (status) url += `&status=${status}`;
        if (date)   url += `&date=${date}`;
        if (search) url += `&table=${encodeURIComponent(search)}`;
 
        const orders = await apiFetch(url);
 
        if (orders.length === 0) {
          tbody.innerHTML = '<tr><td colspan="7" class="empty">Nenhum pedido encontrado</td></tr>';
          return;
        }
 
        tbody.innerHTML = orders.map(order => `
          <tr data-id="${order.id}">
            <td><strong>#${order.id}</strong></td>
            <td>${escapeHtml(order.table_number || '-')}</td>
            <td>
              <span class="badge ${getStatusBadge(order.status)}">
                ${translateStatus(order.status)}
              </span>
            </td>
            <td>${formatPrice(order.total)}</td>
            <td>${formatDate(order.created_at)}</td>
            <td>--</td>
            <td class="actions">
              <button class="btn-icon btn-view" data-id="${order.id}" title="Ver Detalhes">
                <i class="fas fa-eye"></i>
              </button>
            </td>
          </tr>
        `).join('');
 
        tbody.querySelectorAll('.btn-view').forEach(btn =>
          btn.addEventListener('click', () => viewOrder(btn.dataset.id))
        );
      } catch (err) {
        console.error('[Pedidos] Erro ao carregar pedidos:', err.message);
        tbody.innerHTML = `<tr><td colspan="7" class="error">Erro ao carregar pedidos: ${escapeHtml(err.message)}</td></tr>`;
      }
    }
 
    async function viewOrder(id) {
      try {
        const order = await apiFetch(`admin/orders/${id}`);
 
        modalIdSpan.textContent = order.id || '-';
        statusSelect.value      = order.status || 'submitted';
        currentOrderId          = order.id;
 
        const tableName    = order.table_number || '-';
        const dataFormatada = formatDate(order.created_at);
        const totalValue   = parseFloat(order.total) || 0;
        const items        = Array.isArray(order.items) ? order.items : [];
 
        const itemsHtml = items.length > 0
          ? items.map(item => {
              const qty   = parseInt(item.qty) || 1;
              const price = parseFloat(item.total_price) || 0;
              const name  = escapeHtml(item.name || 'Item');
              return `
                <div class="order-item">
                  <span>${qty}x ${name}</span>
                  <span>${formatPrice(price)}</span>
                </div>`;
            }).join('')
          : '<p>Nenhum item encontrado</p>';
 
        if (modalBody) {
          modalBody.innerHTML = `
            <div class="pedido-info">
              <p><strong>Mesa:</strong> ${escapeHtml(tableName)}</p>
              <p><strong>Data:</strong> ${escapeHtml(dataFormatada)}</p>
              <p><strong>Status:</strong> ${translateStatus(order.status || 'submitted')}</p>
              <p><strong>Total:</strong> ${formatPrice(totalValue)}</p>
            </div>
            <div class="pedido-items">
              <h4>Itens do Pedido</h4>
              ${itemsHtml}
            </div>
          `;
        }
 
        if (order.notes) {
          const notesEl = document.createElement('div');
          notesEl.className = 'pedido-notes';
          notesEl.innerHTML = `<h4>Observações</h4><p>${escapeHtml(order.notes)}</p>`;
          modalBody && modalBody.appendChild(notesEl);
        }
 
        modalOverlay && modalOverlay.classList.add('show');
      } catch (err) {
        console.error('[Pedidos] Erro ao carregar detalhe do pedido:', err.message);
        alert('Erro ao carregar pedido: ' + err.message);
      }
    }
 
    async function updateOrderStatus() {
      if (!currentOrderId) {
        alert('Nenhum pedido selecionado');
        return;
      }
      const status = statusSelect.value;
      try {
        await apiFetch(`admin/orders/${currentOrderId}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ status })
        });
        alert('Status atualizado com sucesso!');
        closeModal();
        await loadOrders();
      } catch (err) {
        console.error('[Pedidos] Erro ao atualizar status:', err.message);
        alert('Erro ao atualizar status: ' + err.message);
      }
    }
 
    function closeModal() {
      modalOverlay && modalOverlay.classList.remove('show');
      currentOrderId = null;
    }
 
    // ── Mesas ────────────────────────────────────────────────────
 
    function openNovaMesaModal() {
      currentMesaId = null;
      document.getElementById('modal-mesa-title').textContent = 'Nova Mesa';
      document.getElementById('mesa-id').value          = '';
      document.getElementById('mesa-numero').value      = '';
      document.getElementById('mesa-descricao').value   = '';
      document.getElementById('mesa-ativa').checked     = true;
      modalNovaMesa && modalNovaMesa.classList.add('show');
    }
 
    function closeMesaModal() {
      modalNovaMesa && modalNovaMesa.classList.remove('show');
      currentMesaId = null;
    }
 
    async function openListaMesasModal() {
      modalListaMesas && modalListaMesas.classList.add('show');
      await loadMesas();
    }
 
    function closeListaMesasModal() {
      modalListaMesas && modalListaMesas.classList.remove('show');
    }
 
    async function loadMesas() {
      if (!mesasTbody) return;
      try {
        mesasTbody.innerHTML = '<tr><td colspan="5" class="loading"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';
        const mesas = await apiFetch(`admin/tables?restaurant_id=${RESTAURANT_ID}`);
 
        if (mesas.length === 0) {
          mesasTbody.innerHTML = '<tr><td colspan="5" class="empty">Nenhuma mesa cadastrada</td></tr>';
          return;
        }
 
        mesasTbody.innerHTML = mesas.map(mesa => `
          <tr>
            <td><strong>${escapeHtml(mesa.number)}</strong></td>
            <td>${escapeHtml(mesa.description || '-')}</td>
            <td><small>${escapeHtml(mesa.qr_code || '-')}</small></td>
            <td>
              <span class="badge ${mesa.active ? 'badge-success' : 'badge-danger'}">
                ${mesa.active ? 'Ativa' : 'Inativa'}
              </span>
            </td>
            <td class="actions">
              <button class="btn-icon btn-edit-mesa"
                data-id="${mesa.id}"
                data-number="${escapeHtml(mesa.number)}"
                data-description="${escapeHtml(mesa.description || '')}"
                data-active="${mesa.active}" title="Editar">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn-icon btn-delete-mesa"
                data-id="${mesa.id}"
                data-number="${escapeHtml(mesa.number)}" title="Excluir">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        `).join('');
 
        mesasTbody.querySelectorAll('.btn-edit-mesa').forEach(btn =>
          btn.addEventListener('click', () => editMesa(btn))
        );
        mesasTbody.querySelectorAll('.btn-delete-mesa').forEach(btn =>
          btn.addEventListener('click', () => deleteMesa(btn))
        );
      } catch (err) {
        console.error('[Pedidos/Mesas] Erro ao carregar mesas:', err.message);
        mesasTbody.innerHTML = `<tr><td colspan="5" class="error">Erro: ${escapeHtml(err.message)}</td></tr>`;
      }
    }
 
    async function saveMesa(e) {
      e.preventDefault();
      const data = {
        restaurant_id: RESTAURANT_ID,
        number:      document.getElementById('mesa-numero').value.trim(),
        description: document.getElementById('mesa-descricao').value.trim() || null,
        active:      document.getElementById('mesa-ativa').checked ? 1 : 0
      };
 
      const url    = currentMesaId ? `admin/tables/${currentMesaId}` : `admin/tables`;
      const method = currentMesaId ? 'PUT' : 'POST';
 
      try {
        await apiFetch(url, {
          method,
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        alert(currentMesaId ? 'Mesa atualizada com sucesso!' : 'Mesa criada com sucesso!');
        closeMesaModal();
        if (modalListaMesas && modalListaMesas.classList.contains('show')) await loadMesas();
      } catch (err) {
        console.error('[Pedidos/Mesas] Erro ao salvar mesa:', err.message);
        alert('Erro ao salvar mesa: ' + err.message);
      }
    }
 
    function editMesa(btn) {
      currentMesaId = btn.dataset.id;
      document.getElementById('modal-mesa-title').textContent  = 'Editar Mesa';
      document.getElementById('mesa-id').value                 = currentMesaId;
      document.getElementById('mesa-numero').value             = btn.dataset.number;
      document.getElementById('mesa-descricao').value          = btn.dataset.description;
      document.getElementById('mesa-ativa').checked            = btn.dataset.active === '1';
      modalNovaMesa && modalNovaMesa.classList.add('show');
    }
 
    async function deleteMesa(btn) {
      const mesaId     = btn.dataset.id;
      const mesaNumber = btn.dataset.number;
      if (!confirm(`Tem certeza que deseja excluir a mesa "${mesaNumber}"?`)) return;
      try {
        await apiFetch(`admin/tables/${mesaId}`, { method: 'DELETE' });
        alert('Mesa excluída com sucesso!');
        await loadMesas();
      } catch (err) {
        console.error('[Pedidos/Mesas] Erro ao excluir mesa:', err.message);
        alert('Erro ao excluir mesa: ' + err.message);
      }
    }
 
    // ── Auxiliares de pedidos ────────────────────────────────────
 
    function translateStatus(status) {
      return ({
        submitted:  'Submetido',
        preparing:  'Preparando',
        ready:      'Pronto',
        served:     'Servido',
        completed:  'Completo',
        paid:       'Pago',
        cancelled:  'Cancelado'
      })[status] || status;
    }
 
    function getStatusBadge(status) {
      return ({
        submitted:  'badge-info',
        preparing:  'badge-warning',
        ready:      'badge-info',
        served:     'badge-success',
        completed:  'badge-success',
        paid:       'badge-success',
        cancelled:  'badge-danger'
      })[status] || 'badge-secondary';
    }
 
    function formatDate(raw) {
      if (!raw) return '-';
      try {
        const d = new Date(raw);
        return isNaN(d.getTime()) ? raw : d.toLocaleString('pt-AO');
      } catch (e) {
        return raw;
      }
    }
 
    init();
  }
 
  /* ═══════════════════════════════════════════════════════════════
     MÓDULO: AVALIAÇÕES (admin-avaliacoes.js)
  ═══════════════════════════════════════════════════════════════ */
 
  function initAvaliacoes() {
    const tbody       = document.getElementById('avaliacoes-tbody');
    const filterRating = document.getElementById('filter-rating');
    const searchInput  = document.getElementById('search-prato');
    const btnSearch    = document.getElementById('btn-search');
 
    if (!tbody) return;
 
    function setupEventListeners() {
      btnSearch   && btnSearch.addEventListener('click', loadRatings);
      searchInput && searchInput.addEventListener('keypress', e => {
        if (e.key === 'Enter') loadRatings();
      });
      filterRating && filterRating.addEventListener('change', loadRatings);
    }
 
    async function loadRatings() {
      try {
        tbody.innerHTML = '<tr><td colspan="5" class="loading"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';
 
        const rating = filterRating ? filterRating.value : '';
        const search = searchInput  ? searchInput.value.trim() : '';
 
        let url = `admin/ratings?restaurant_id=${RESTAURANT_ID}`;
        if (rating) url += `&rating=${rating}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
 
        const ratings = await apiFetch(url);
 
        if (ratings.length === 0) {
          tbody.innerHTML = '<tr><td colspan="5" class="empty">Nenhuma avaliação encontrada</td></tr>';
          return;
        }
 
        tbody.innerHTML = ratings.map(rat => `
          <tr data-id="${rat.id}">
            <td><strong>${escapeHtml(rat.item_name)}</strong></td>
            <td>
              <div class="stars">
                ${generateStars(rat.rating)}
                <span class="rating-value">${rat.rating}/5</span>
              </div>
            </td>
            <td>
              ${rat.comment
                ? `<em>"${escapeHtml(rat.comment)}"</em>`
                : '<span class="muted">Sem comentário</span>'}
            </td>
            <td>${new Date(rat.created_at).toLocaleDateString('pt-AO')}</td>
            <td class="actions">
              <button class="btn-icon btn-delete" data-id="${rat.id}" title="Deletar">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        `).join('');
 
        tbody.querySelectorAll('.btn-delete').forEach(btn =>
          btn.addEventListener('click', () => deleteRating(btn.dataset.id))
        );
      } catch (err) {
        console.error('[Avaliações] Erro ao carregar avaliações:', err.message);
        tbody.innerHTML = `<tr><td colspan="5" class="error">Erro: ${escapeHtml(err.message)}</td></tr>`;
      }
    }
 
    async function loadStats() {
      try {
        const stats = await apiFetch(
          `admin/ratings/stats?restaurant_id=${RESTAURANT_ID}`
        );
        const ids = ['stat-media','stat-total','stat-5','stat-4','stat-3','stat-2','stat-1'];
        const vals = [
          stats.average.toFixed(1),
          stats.total,
          stats.by_rating['5'],
          stats.by_rating['4'],
          stats.by_rating['3'],
          stats.by_rating['2'],
          stats.by_rating['1']
        ];
        ids.forEach((id, i) => {
          const el = document.getElementById(id);
          if (el) el.textContent = vals[i];
        });
      } catch (err) {
        console.error('[Avaliações] Erro ao carregar estatísticas:', err.message);
      }
    }
 
    async function deleteRating(id) {
      if (!confirm('Tem certeza que deseja deletar esta avaliação?')) return;
      try {
        await apiFetch(`admin/ratings/${id}`, { method: 'DELETE' });
        alert('Avaliação deletada com sucesso!');
        await loadRatings();
        await loadStats();
      } catch (err) {
        console.error('[Avaliações] Erro ao deletar avaliação:', err.message);
        alert('Erro ao deletar avaliação: ' + err.message);
      }
    }
 
    function generateStars(rating) {
      return Array.from({ length: 5 }, (_, i) =>
        `<i class="fas fa-star" style="color:${i < rating ? 'var(--amarelo-suave)' : '#ddd'};"></i>`
      ).join('');
    }
 
    loadRatings();
    loadStats();
    setupEventListeners();
  }
 
  /* ═══════════════════════════════════════════════════════════════
     INICIALIZAÇÃO — detecta a página e activa o módulo correto
  ═══════════════════════════════════════════════════════════════ */
 
  document.addEventListener('DOMContentLoaded', () => {
    // Menu lateral está presente em todas as páginas admin
    initAdminMenu();
 
    // Cada função guarda-se a si própria com um guard (verifica se os
    // elementos necessários existem) — é seguro chamar todas aqui.
    initDashboard();
    initPratos();
    initCategorias();
    initPedidos();
    initAvaliacoes();
  });
 
})();
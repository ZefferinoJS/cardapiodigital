// Admin - Gestão de Pratos
(function() {
    'use strict';

    const API_BASE = '/api';
    const RESTAURANT_ID = 1; // Default restaurant

    // Elements
    const tbody = document.getElementById('pratos-tbody');
    const modalOverlay = document.getElementById('modal-prato');
    const modalTitle = document.getElementById('modal-title');
    const form = document.getElementById('form-prato');
    const btnNovo = document.getElementById('btn-novo-prato');
    const btnClose = document.getElementById('close-modal-prato');
    const btnCancel = document.getElementById('cancel-prato');
    const filterCategoria = document.getElementById('filter-categoria');
    const filterDisponivel = document.getElementById('filter-disponivel');
    const searchInput = document.getElementById('search-prato');
    const btnSearch = document.getElementById('btn-search');
    const selectCategoria = document.getElementById('prato-categoria');

    let currentItemId = null;

    // Initialize
    async function init() {
        await loadCategories();
        await loadItems();
        setupEventListeners();
    }

    // Setup Event Listeners
    function setupEventListeners() {
        btnNovo.addEventListener('click', () => openModal());
        btnClose.addEventListener('click', closeModal);
        btnCancel.addEventListener('click', closeModal);
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeModal();
        });
        form.addEventListener('submit', handleSubmit);
        btnSearch.addEventListener('click', loadItems);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') loadItems();
        });
        filterCategoria.addEventListener('change', loadItems);
        filterDisponivel.addEventListener('change', loadItems);
    }

    // Load Categories
    async function loadCategories() {
        try {
            const response = await fetch(`${API_BASE}/admin/categories?restaurant_id=${RESTAURANT_ID}`);
            const categories = await response.json();
            
            // Populate filter select
            filterCategoria.innerHTML = '<option value="">Todas</option>';
            categories.forEach(cat => {
                filterCategoria.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
            });

            // Populate form select
            selectCategoria.innerHTML = '<option value="">Selecione...</option>';
            categories.forEach(cat => {
                selectCategoria.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
            });
        } catch (error) {
            console.error('Erro ao carregar categorias:', error);
        }
    }

    // Load Items
    async function loadItems() {
        try {
            tbody.innerHTML = '<tr><td colspan="7" class="loading"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';

            const categoryId = filterCategoria.value;
            const available = filterDisponivel.value;
            const search = searchInput.value.trim();

            let url = `${API_BASE}/admin/items?restaurant_id=${RESTAURANT_ID}`;
            if (categoryId) url += `&category_id=${categoryId}`;
            if (available !== '') url += `&available=${available}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;

            const response = await fetch(url);
            const items = await response.json();

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty">Nenhum prato encontrado</td></tr>';
                return;
            }

            tbody.innerHTML = items.map(item => `
                <tr data-id="${item.id}">
                    <td class="td-imagem">
                        <img src="/${item.image || '/images/placeholder.png'}" 
                             alt="${item.name}" 
                             class="item-thumb"
                             onerror="this.src='/images/placeholder.png'">
                    </td>
                    <td class="td-nome"><strong>${escapeHtml(item.name)}</strong></td>
                    <td class="td-categoria">${item.category_name || '-'}</td>
                    <td class="td-preco">${formatPrice(item.price)}</td>
                    <td class="td-disponivel">
                        <span class="badge ${item.available ? 'badge-success' : 'badge-danger'}">
                            ${item.available ? 'Sim' : 'Não'}
                        </span>
                    </td>
                    <td class="td-avaliacao">
                        ${item.avg_rating ? `
                            <i class="fas fa-star" style="color: var(--amarelo-suave);"></i>
                            ${item.avg_rating.toFixed(1)} (${item.total_ratings})
                        ` : '-'}
                    </td>
                    <td class="td-acoes actions">
                        <button class="btn-icon btn-edit" data-id="${item.id}" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-delete" data-id="${item.id}" title="Deletar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            // Add event listeners to action buttons
            document.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', () => editItem(btn.dataset.id));
            });
            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', () => deleteItem(btn.dataset.id));
            });
        } catch (error) {
            console.error('Erro ao carregar pratos:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="error">Erro ao carregar pratos</td></tr>';
        }
    }

    // Open Modal (Create or Edit)
    function openModal(item = null) {
        currentItemId = item ? item.id : null;
        modalTitle.textContent = item ? 'Editar Prato' : 'Novo Prato';
        
        if (item) {
            const prep = item.prep_time_minutes ?? item.cook_time_minutes ?? '';
            document.getElementById('prato-id').value = item.id ?? '';
            document.getElementById('prato-nome').value = item.name ?? '';
            document.getElementById('prato-categoria').value = item.category_id ?? '';
            document.getElementById('prato-preco').value = item.price ?? '';
            document.getElementById('prato-tempo').value = prep;
            document.getElementById('prato-descricao').value = item.description ?? '';
            document.getElementById('prato-imagem').value = item.image ?? '';
            document.getElementById('prato-ingredientes').value = item.ingredients ?? '';
            document.getElementById('prato-disponivel').checked = Boolean(item.available);
        } else {
            form.reset();
            document.getElementById('prato-disponivel').checked = true;
        }

        modalOverlay.classList.add('show');
        document.getElementById('prato-nome').focus();
    }

    // Close Modal
    function closeModal() {
        modalOverlay.classList.remove('show');
        form.reset();
        currentItemId = null;
    }

    // Edit Item
    async function editItem(id) {
        try {
            const response = await fetch(`${API_BASE}/admin/items/${id}`);
            const item = await response.json();
            console.log('Item carregado', item);
            if (item.error) {
                alert('Erro ao carregar prato: ' + item.error);
                return;
            }
            openModal(item);
        } catch (error) {
            console.error('Erro ao editar:', error);
            alert('Erro ao carregar dados do prato');
        }
    }

    // Delete Item
    async function deleteItem(id) {
        if (!confirm('Tem certeza que deseja deletar este prato?')) return;

        try {
            const response = await fetch(`${API_BASE}/admin/items/${id}`, {
                method: 'DELETE'
            });
            const result = await response.json();
            
            if (response.ok) {
                alert('Prato deletado com sucesso!');
                await loadItems();
            } else {
                alert('Erro ao deletar: ' + (result.error || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('Erro ao deletar:', error);
            alert('Erro ao deletar prato');
        }
    }

    // Handle Form Submit
    async function handleSubmit(e) {
        e.preventDefault();

        const formData = {
            restaurant_id: RESTAURANT_ID,
            name: document.getElementById('prato-nome').value.trim(),
            slug: '', // auto-generated by API
            description: document.getElementById('prato-descricao').value.trim(),
            price: parseFloat(document.getElementById('prato-preco').value),
            image_url: document.getElementById('prato-imagem').value.trim(),
            category_id: parseInt(document.getElementById('prato-categoria').value) || null,
            prep_time_minutes: parseInt(document.getElementById('prato-tempo').value) || null,
            ingredients: document.getElementById('prato-ingredientes').value.trim(),
            is_available: document.getElementById('prato-disponivel').checked
        };

        try {
            const url = currentItemId 
                ? `${API_BASE}/admin/items/${currentItemId}` 
                : `${API_BASE}/admin/items`;
            
            const method = currentItemId ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (response.ok) {
                alert(currentItemId ? 'Prato atualizado com sucesso!' : 'Prato criado com sucesso!');
                closeModal();
                await loadItems();
            } else {
                const errorMsg = result.details || result.error || 'Erro desconhecido';
                console.error('Erro do servidor:', result);
                alert('Erro: ' + errorMsg);
            }
        } catch (error) {
            console.error('Erro ao salvar:', error);
            alert('Erro ao salvar prato');
        }
    }

    // Utility Functions
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatPrice(price) {
        return new Intl.NumberFormat('pt-AO', {
            style: 'currency',
            currency: 'AOA'
        }).format(price);
    }

    // Start
    init();
})();

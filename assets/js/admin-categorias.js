// Admin - Gestão de Categorias
(function() {
    'use strict';

    const API_BASE = '/api';
    const RESTAURANT_ID = 1;

    // Elements
    const tbody = document.getElementById('categorias-tbody');
    const modalOverlay = document.getElementById('modal-categoria');
    const modalTitle = document.getElementById('modal-title');
    const form = document.getElementById('form-categoria');
    const btnNova = document.getElementById('btn-nova-categoria');
    const btnClose = document.getElementById('close-modal-categoria');
    const btnCancel = document.getElementById('cancel-categoria');

    let currentCategoryId = null;

    function init() {
        loadCategories();
        setupEventListeners();
    }

    function setupEventListeners() {
        btnNova.addEventListener('click', () => openModal());
        btnClose.addEventListener('click', closeModal);
        btnCancel.addEventListener('click', closeModal);
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeModal();
        });
        form.addEventListener('submit', handleSubmit);
    }

    async function loadCategories() {
        try {
            tbody.innerHTML = '<tr><td colspan="6" class="loading"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';

            const response = await fetch(`${API_BASE}/admin/categories?restaurant_id=${RESTAURANT_ID}`);
            const categories = await response.json();

            if (categories.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty">Nenhuma categoria encontrada</td></tr>';
                return;
            }

            tbody.innerHTML = categories.map(cat => `
                <tr data-id="${cat.id}">
                    <td class="td-nome"><strong>${escapeHtml(cat.name)}</strong></td>
                    <td class="td-slug">${cat.slug}</td>
                    <td class="td-pratos"><span class="badge badge-info">--</span></td>
                    <td class="td-posicao">${cat.position}</td>
                    <td class="td-ativa">
                        <span class="badge ${cat.active ? 'badge-success' : 'badge-danger'}">
                            ${cat.active ? 'Sim' : 'Não'}
                        </span>
                    </td>
                    <td class="actions td-acoes">
                        <button class="btn-icon btn-edit" data-id="${cat.id}" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-delete" data-id="${cat.id}" title="Deletar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            document.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', () => editCategory(btn.dataset.id, categories));
            });
            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', () => deleteCategory(btn.dataset.id));
            });
        } catch (error) {
            console.error('Erro ao carregar categorias:', error);
            tbody.innerHTML = '<tr><td colspan="6" class="error">Erro ao carregar categorias</td></tr>';
        }
    }

    function openModal(category = null) {
        currentCategoryId = category ? category.id : null;
        modalTitle.textContent = category ? 'Editar Categoria' : 'Nova Categoria';
        
        if (category) {
            document.getElementById('categoria-id').value = category.id;
            document.getElementById('categoria-nome').value = category.name;
            document.getElementById('categoria-slug').value = category.slug;
            document.getElementById('categoria-posicao').value = category.position;
            document.getElementById('categoria-ativa').checked = category.active;
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

    function editCategory(id, categories) {
        const category = categories.find(c => c.id == id);
        if (category) openModal(category);
    }

    async function deleteCategory(id) {
        if (!confirm('Tem certeza que deseja deletar esta categoria?')) return;

        try {
            const response = await fetch(`${API_BASE}/admin/categories/${id}`, {
                method: 'DELETE'
            });
            const result = await response.json();
            
            if (response.ok) {
                alert('Categoria deletada com sucesso!');
                await loadCategories();
            } else {
                alert('Erro ao deletar: ' + (result.error || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('Erro ao deletar:', error);
            alert('Erro ao deletar categoria');
        }
    }

    async function handleSubmit(e) {
        e.preventDefault();

        const formData = {
            restaurant_id: RESTAURANT_ID,
            name: document.getElementById('categoria-nome').value.trim(),
            slug: document.getElementById('categoria-slug').value.trim(),
            position: parseInt(document.getElementById('categoria-posicao').value),
            active: document.getElementById('categoria-ativa').checked
        };

        try {
            const url = currentCategoryId 
                ? `${API_BASE}/admin/categories/${currentCategoryId}` 
                : `${API_BASE}/admin/categories`;
            
            const method = currentCategoryId ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (response.ok) {
                alert(currentCategoryId ? 'Categoria atualizada com sucesso!' : 'Categoria criada com sucesso!');
                closeModal();
                await loadCategories();
            } else {
                alert('Erro: ' + (result.error || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('Erro ao salvar:', error);
            alert('Erro ao salvar categoria');
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    init();
})();

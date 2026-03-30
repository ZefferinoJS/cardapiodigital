// Admin - Gestão de Pedidos
(function() {
    'use strict';

    const API_BASE = '/api';
    const RESTAURANT_ID = 1;

    // Elements
    const tbody = document.getElementById('pedidos-tbody');
    const modalOverlay = document.getElementById('modal-pedido');
    const modalBody = document.getElementById('pedido-modal-body');
    const btnClose = document.getElementById('close-modal-pedido');
    const btnCloseFoot = document.getElementById('close-modal-pedido-footer');
    const filterStatus = document.getElementById('filter-status');
    const filterData = document.getElementById('filter-data');
    const searchInput = document.getElementById('search-pedido');
    const btnSearch = document.getElementById('btn-search');
    const btnUpdateStatus = document.getElementById('btn-update-status');
    const statusSelect = document.getElementById('pedido-status-select');
    const modalIdSpan = document.getElementById('pedido-id-modal');

    // Mesas elements
    const btnNovaMesa = document.getElementById('btn-nova-cadeira');
    const btnVerMesas = document.getElementById('btn-ver-lista-cadeiras');
    const modalNovaMesa = document.getElementById('modal-nova-mesa');
    const modalListaMesas = document.getElementById('modal-lista-mesas');
    const formMesa = document.getElementById('form-mesa');
    const mesasTbody = document.getElementById('mesas-tbody');
    const closeMesa = document.getElementById('close-modal-mesa');
    const cancelMesa = document.getElementById('cancel-mesa');
    const closeLista = document.getElementById('close-modal-lista');
    const closeListaFooter = document.getElementById('close-modal-lista-footer');

    let currentOrderId = null;
    let currentMesaId = null;

    function init() {
        loadOrders();
        setupEventListeners();
    }

    function setupEventListeners() {
        btnClose.addEventListener('click', closeModal);
        btnCloseFoot.addEventListener('click', closeModal);
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeModal();
        });
        btnSearch.addEventListener('click', loadOrders);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') loadOrders();
        });
        filterStatus.addEventListener('change', loadOrders);
        filterData.addEventListener('change', loadOrders);
        btnUpdateStatus.addEventListener('click', updateOrderStatus);

        // Mesas event listeners
        if (btnNovaMesa) btnNovaMesa.addEventListener('click', openNovaMesaModal);
        if (btnVerMesas) btnVerMesas.addEventListener('click', openListaMesasModal);
        if (closeMesa) closeMesa.addEventListener('click', closeMesaModal);
        if (cancelMesa) cancelMesa.addEventListener('click', closeMesaModal);
        if (closeLista) closeLista.addEventListener('click', closeListaMesasModal);
        if (closeListaFooter) closeListaFooter.addEventListener('click', closeListaMesasModal);
        if (formMesa) formMesa.addEventListener('submit', saveMesa);
    }

    async function loadOrders() {
        try {
            tbody.innerHTML = '<tr><td colspan="7" class="loading"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';

            const status = filterStatus.value;
            const data = filterData.value;
            const search = searchInput.value.trim();

            let url = `${API_BASE}/admin/orders?restaurant_id=${RESTAURANT_ID}`;
            if (status) url += `&status=${status}`;
            if (data) url += `&date=${data}`;
            if (search) url += `&table=${search}`;

            const response = await fetch(url);
            const orders = await response.json();

            if (orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty">Nenhum pedido encontrado</td></tr>';
                return;
            }

            tbody.innerHTML = orders.map(order => `
                <tr data-id="${order.id}">
                    <td><strong>#${order.id}</strong></td>
                    <td>${order.table_number || '-'}</td>
                    <td>
                        <span class="badge ${getStatusBadge(order.status)}">
                            ${translateStatus(order.status)}
                        </span>
                    </td>
                    <td>${formatPrice(order.total)}</td>
                    <td>${new Date(order.created_at).toLocaleString('pt-AO')}</td>
                    <td>--</td>
                    <td class="actions">
                        <button class="btn-icon btn-view" data-id="${order.id}" title="Ver Detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            document.querySelectorAll('.btn-view').forEach(btn => {
                btn.addEventListener('click', () => viewOrder(btn.dataset.id));
            });
        } catch (error) {
            console.error('Erro ao carregar pedidos:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="error">Erro ao carregar pedidos</td></tr>';
        }
    }

    async function viewOrder(id) {
        try {
            const response = await fetch(`${API_BASE}/admin/orders/${id}`);
            
            console.log('=== INICIANDO VIEW ORDER ===');
            console.log('Order ID:', id);

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Erro ao carregar pedido');
            }

            const order = await response.json();
            console.log('📋 Resposta completa da API:', order);

            // Validar e definir dados
            modalIdSpan.textContent = order.id || '-';
            statusSelect.value = order.status || 'submitted';
            currentOrderId = order.id;

            console.log('✅ ID do pedido:', order.id);
            console.log('✅ Status:', order.status);

            // Dados da mesa
            const tableName = order.table_number && order.table_number !== null ? order.table_number : '-';
            console.log('✅ Table Number (raw):', order.table_number);
            console.log('✅ Table Number (final):', tableName);

            // Formatar data
            let dataFormatada = '-';
            if (order.created_at) {
                console.log('✅ Created At (raw):', order.created_at);
                try {
                    const date = new Date(order.created_at);
                    console.log('📅 Date object:', date);
                    console.log('⏰ Date timestamp:', date.getTime());
                    if (!isNaN(date.getTime())) {
                        dataFormatada = date.toLocaleString('pt-AO');
                        console.log('✅ Data formatada:', dataFormatada);
                    } else {
                        console.warn('⚠️ Data inválida, mantendo raw:', order.created_at);
                        dataFormatada = order.created_at;
                    }
                } catch (e) {
                    console.error('❌ Erro ao formatar data:', e);
                    dataFormatada = order.created_at;
                }
            } else {
                console.warn('⚠️ Sem data no pedido');
            }

            // Total
            const totalValue = parseFloat(order.total) || 0;
            console.log('💰 Total (raw):', order.total);
            console.log('💰 Total (parseFloat):', totalValue);

            // Handle items safely
            const items = Array.isArray(order.items) ? order.items : [];
            console.log('📦 Items count:', items.length);
            console.log('📦 Items array:', items);

            const itemsHtml = items.length > 0 ? items.map((item, index) => {
                const qty = parseInt(item.qty) || 1;
                const price = parseFloat(item.total_price) || 0;
                const name = item.name || 'Item';
                console.log(`  Item ${index + 1}:`, {
                    qty_raw: item.qty,
                    qty_parsed: qty,
                    price_raw: item.total_price,
                    price_parsed: price,
                    name: name
                });
                return `
                    <div class="order-item">
                        <span>${qty}x ${escapeHtml(name)}</span>
                        <span>${formatPrice(price)}</span>
                    </div>
                `;
            }).join('') : '<p>Nenhum item encontrado</p>';

            console.log('=== DADOS FINAIS PARA EXIBIÇÃO ===');
            console.log('Mesa:', tableName);
            console.log('Data:', dataFormatada);
            console.log('Status:', translateStatus(order.status || 'submitted'));
            console.log('Total:', formatPrice(totalValue));
            console.log('Items HTML:', itemsHtml);
            console.log('Notas:', order.notes || 'Sem notas');

            modalBody.innerHTML = `
                <div class="order-details">
                    <div class="order-info">
                        <p><strong>Mesa:</strong> ${escapeHtml(tableName)}</p>
                        <p><strong>Data:</strong> ${dataFormatada}</p>
                        <p><strong>Status:</strong> ${translateStatus(order.status || 'submitted')}</p>
                    </div>
                    <div class="order-items">
                        <h4>Itens</h4>
                        ${itemsHtml}
                    </div>
                    <div class="order-total">
                        <strong>Total: ${formatPrice(totalValue)}</strong>
                    </div>
                    ${order.notes ? `<div class="order-notes"><p><strong>Notas:</strong> ${escapeHtml(order.notes)}</p></div>` : ''}
                </div>
            `;

            console.log('✨ Modal atualizado com sucesso!');
            console.log('=== FIM VIEW ORDER ===\n');

            modalOverlay.classList.add('show');
        } catch (error) {
            console.error('❌ Erro ao carregar pedido:', error);
            console.error('Stack trace:', error.stack);
            alert('Erro ao carregar detalhes do pedido: ' + error.message);
        }
    }

    async function updateOrderStatus() {
        if (!currentOrderId) return;

        const newStatus = statusSelect.value;
        try {
            const response = await fetch(`${API_BASE}/admin/orders/${currentOrderId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: newStatus })
            });

            if (response.ok) {
                alert('Status atualizado com sucesso!');
                closeModal();
                await loadOrders();
            } else {
                alert('Erro ao atualizar status');
            }
        } catch (error) {
            console.error('Erro ao atualizar:', error);
            alert('Erro ao atualizar status');
        }
    }

    function closeModal() {
        modalOverlay.classList.remove('show');
        currentOrderId = null;
    }

    function translateStatus(status) {
        const statuses = {
            'submitted': 'Submetido',
            'preparing': 'Preparando',
            'ready': 'Pronto',
            'completed': 'Completo',
            'cancelled': 'Cancelado'
        };
        return statuses[status] || status;
    }

    function getStatusBadge(status) {
        const badges = {
            'submitted': 'badge-info',
            'preparing': 'badge-warning',
            'ready': 'badge-info',
            'completed': 'badge-success',
            'cancelled': 'badge-danger'
        };
        return badges[status] || 'badge-secondary';
    }

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

    // ========== MESAS MANAGEMENT ==========

    function openNovaMesaModal() {
        currentMesaId = null;
        document.getElementById('modal-mesa-title').textContent = 'Nova Mesa';
        document.getElementById('mesa-id').value = '';
        document.getElementById('mesa-numero').value = '';
        document.getElementById('mesa-descricao').value = '';
        document.getElementById('mesa-ativa').checked = true;
        modalNovaMesa.classList.add('show');
    }

    function closeMesaModal() {
        modalNovaMesa.classList.remove('show');
        currentMesaId = null;
    }

    async function saveMesa(e) {
        e.preventDefault();
        
        const numero = document.getElementById('mesa-numero').value.trim();
        const descricao = document.getElementById('mesa-descricao').value.trim();
        const ativa = document.getElementById('mesa-ativa').checked ? 1 : 0;

        const data = {
            restaurant_id: RESTAURANT_ID,
            number: numero,
            description: descricao || null,
            active: ativa
        };

        try {
            const url = currentMesaId 
                ? `${API_BASE}/admin/tables/${currentMesaId}` 
                : `${API_BASE}/admin/tables`;
            const method = currentMesaId ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok) {
                alert(currentMesaId ? 'Mesa atualizada com sucesso!' : 'Mesa criada com sucesso!');
                closeMesaModal();
                if (modalListaMesas.classList.contains('show')) {
                    loadMesas();
                }
            } else {
                alert('Erro: ' + (result.error || 'Falha ao salvar mesa'));
            }
        } catch (error) {
            console.error('Erro ao salvar mesa:', error);
            alert('Erro ao salvar mesa');
        }
    }

    async function openListaMesasModal() {
        modalListaMesas.classList.add('show');
        await loadMesas();
    }

    function closeListaMesasModal() {
        modalListaMesas.classList.remove('show');
    }

    async function loadMesas() {
        try {
            mesasTbody.innerHTML = '<tr><td colspan="5" class="loading"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';

            const response = await fetch(`${API_BASE}/admin/tables?restaurant_id=${RESTAURANT_ID}`);
            const mesas = await response.json();

            if (mesas.length === 0) {
                mesasTbody.innerHTML = '<tr><td colspan="5" class="empty">Nenhuma mesa cadastrada</td></tr>';
                return;
            }

            mesasTbody.innerHTML = mesas.map(mesa => `
                <tr>
                    <td><strong>${escapeHtml(mesa.number)}</strong></td>
                    <td>${escapeHtml(mesa.description || '-')}</td>
                    <td><small>${mesa.qr_code || '-'}</small></td>
                    <td>
                        <span class="badge ${mesa.active ? 'badge-success' : 'badge-danger'}">
                            ${mesa.active ? 'Ativa' : 'Inativa'}
                        </span>
                    </td>
                    <td class="actions">
                        <button class="btn-icon btn-edit-mesa" data-id="${mesa.id}" data-number="${escapeHtml(mesa.number)}" data-description="${escapeHtml(mesa.description || '')}" data-active="${mesa.active}" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon btn-delete-mesa" data-id="${mesa.id}" data-number="${escapeHtml(mesa.number)}" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            // Attach event listeners
            document.querySelectorAll('.btn-edit-mesa').forEach(btn => {
                btn.addEventListener('click', () => editMesa(btn));
            });
            document.querySelectorAll('.btn-delete-mesa').forEach(btn => {
                btn.addEventListener('click', () => deleteMesa(btn));
            });
        } catch (error) {
            console.error('Erro ao carregar mesas:', error);
            mesasTbody.innerHTML = '<tr><td colspan="5" class="error">Erro ao carregar mesas</td></tr>';
        }
    }

    function editMesa(btn) {
        currentMesaId = btn.dataset.id;
        document.getElementById('modal-mesa-title').textContent = 'Editar Mesa';
        document.getElementById('mesa-id').value = currentMesaId;
        document.getElementById('mesa-numero').value = btn.dataset.number;
        document.getElementById('mesa-descricao').value = btn.dataset.description;
        document.getElementById('mesa-ativa').checked = btn.dataset.active === '1';
        
        modalNovaMesa.classList.add('show');
    }

    async function deleteMesa(btn) {
        const mesaId = btn.dataset.id;
        const mesaNumber = btn.dataset.number;

        if (!confirm(`Tem certeza que deseja excluir a mesa "${mesaNumber}"?`)) return;

        try {
            const response = await fetch(`${API_BASE}/admin/tables/${mesaId}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                alert('Mesa excluída com sucesso!');
                await loadMesas();
            } else {
                const result = await response.json();
                alert('Erro: ' + (result.error || 'Falha ao excluir mesa'));
            }
        } catch (error) {
            console.error('Erro ao excluir mesa:', error);
            alert('Erro ao excluir mesa');
        }
    }

    init();
})();

// Admin - Gestão de Avaliações
(function() {
    'use strict';

    const API_BASE = '/api';
    const RESTAURANT_ID = 1;

    // Elements
    const tbody = document.getElementById('avaliacoes-tbody');
    const filterRating = document.getElementById('filter-rating');
    const searchInput = document.getElementById('search-prato');
    const btnSearch = document.getElementById('btn-search');

    function init() {
        loadRatings();
        loadStats();
        setupEventListeners();
    }

    function setupEventListeners() {
        btnSearch.addEventListener('click', loadRatings);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') loadRatings();
        });
        filterRating.addEventListener('change', loadRatings);
    }

    async function loadRatings() {
        try {
            tbody.innerHTML = '<tr><td colspan="5" class="loading"><i class="fas fa-spinner fa-spin"></i> Carregando...</td></tr>';

            const rating = filterRating.value;
            const search = searchInput.value.trim();

            let url = `${API_BASE}/admin/ratings?restaurant_id=${RESTAURANT_ID}`;
            if (rating) url += `&rating=${rating}`;
            if (search) url += `&search=${encodeURIComponent(search)}`;

            const response = await fetch(url);
            const ratings = await response.json();

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
                        ${rat.comment ? `<em>"${escapeHtml(rat.comment)}"</em>` : '<span class="muted">Sem comentário</span>'}
                    </td>
                    <td>${new Date(rat.created_at).toLocaleDateString('pt-AO')}</td>
                    <td class="actions">
                        <button class="btn-icon btn-delete" data-id="${rat.id}" title="Deletar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', () => deleteRating(btn.dataset.id));
            });
        } catch (error) {
            console.error('Erro ao carregar avaliações:', error);
            tbody.innerHTML = '<tr><td colspan="5" class="error">Erro ao carregar avaliações</td></tr>';
        }
    }

    async function loadStats() {
        try {
            const response = await fetch(`${API_BASE}/admin/ratings/stats?restaurant_id=${RESTAURANT_ID}`);
            const stats = await response.json();

            document.getElementById('stat-media').textContent = stats.average.toFixed(1);
            document.getElementById('stat-total').textContent = stats.total;
            document.getElementById('stat-5').textContent = stats.by_rating['5'];
            document.getElementById('stat-4').textContent = stats.by_rating['4'];
            document.getElementById('stat-3').textContent = stats.by_rating['3'];
            document.getElementById('stat-2').textContent = stats.by_rating['2'];
            document.getElementById('stat-1').textContent = stats.by_rating['1'];
        } catch (error) {
            console.error('Erro ao carregar estatísticas:', error);
        }
    }

    async function deleteRating(id) {
        if (!confirm('Tem certeza que deseja deletar esta avaliação?')) return;

        try {
            const response = await fetch(`${API_BASE}/admin/ratings/${id}`, {
                method: 'DELETE'
            });
            const result = await response.json();
            
            if (response.ok) {
                alert('Avaliação deletada com sucesso!');
                await loadRatings();
                await loadStats();
            } else {
                alert('Erro ao deletar: ' + (result.error || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('Erro ao deletar:', error);
            alert('Erro ao deletar avaliação');
        }
    }

    function generateStars(rating) {
        let html = '';
        for (let i = 1; i <= 5; i++) {
            html += i <= rating 
                ? '<i class="fas fa-star" style="color: var(--amarelo-suave);"></i>' 
                : '<i class="fas fa-star" style="color: #ddd;"></i>';
        }
        return html;
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    init();
})();

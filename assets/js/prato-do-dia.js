/**
 * Gerenciamento do Prato do Dia
 * Permite selecionar um prato para aparecer como destaque na página inicial
 */
(function() {
    'use strict';

    const API_BASE = '/public/api';
    const PRATO_DIA_KEY = 'prato_do_dia'; // localStorage key

    // Elementos DOM
    const btnPratoDia = document.getElementById('btn-prato-do-dia');
    const modalPratoDia = document.getElementById('modal-prato-dia');
    const closePratoDia = document.getElementById('close-modal-prato-dia');
    const cancelPratoDia = document.getElementById('cancel-prato-dia');
    const confirmPratoDia = document.getElementById('confirm-prato-dia');
    const pratosDiaLista = document.getElementById('pratos-dia-lista');
    const searchPratoDia = document.getElementById('search-prato-dia');

    // State para rastrear prato selecionado
    let pratoSelecionadoAtual = null;

    if (!btnPratoDia || !modalPratoDia) return;

    // Funções utilitárias
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, function(s) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[s];
        });
    }

    function formatCurrency(v) {
        try {
            return new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA', maximumFractionDigits: 2 }).format(Number(v));
        } catch(e) {
            return `AO ${Number(v).toFixed(2)}`;
        }
    }

    // Normaliza URLs de imagem vindas da API para um formato utilizável
    function resolveImageUrl(u) {
        if (!u) return '';
        const s = String(u).trim();
        if (/^https?:\/\//i.test(s)) return s; // URL absoluta externa
        if (s.startsWith('/')) return s;        // Caminho absoluto do site
        // Remove prefixos relativos
        const clean = s.replace(/^(\.\/|(?:\.\.\/)+)/, '');
        // Quando estiver na área admin (ex.: /public/admin/...), precisamos subir um nível
        const isAdmin = typeof window !== 'undefined' && /\/public\/admin\//.test(window.location.pathname || '');
        return (isAdmin ? '../' : '/') + clean;
    }

    async function tryFetchJson(url, opts) {
        const res = await fetch(url, opts);
        const txt = await res.text().catch(() => null);
        let data = null;
        try { data = txt ? JSON.parse(txt) : null; } catch(e) { data = txt; }
        return { res, data };
    }

    async function apiGet(path) {
        const primary = API_BASE + '/' + path;
        const fallback = API_BASE + '/index.php/' + path;
        let attempt = await tryFetchJson(primary, { method: 'GET' });
        if (attempt.res.status === 404) {
            attempt = await tryFetchJson(fallback, { method: 'GET' });
        }
        if (!attempt.res.ok) throw { status: attempt.res.status, data: attempt.data };
        return attempt.data;
    }

    // Salvar/Carregar prato do dia no localStorage
    function salvarPratoDoDia(prato) {
        try {
            localStorage.setItem(PRATO_DIA_KEY, JSON.stringify(prato));
            return true;
        } catch(e) {
            console.error('Erro ao salvar prato do dia', e);
            return false;
        }
    }

    function obterPratoDoDia() {
        try {
            const data = localStorage.getItem(PRATO_DIA_KEY);
            return data ? JSON.parse(data) : null;
        } catch(e) {
            console.error('Erro ao obter prato do dia', e);
            return null;
        }
    }

    // Carregar todos os pratos disponíveis
    async function carregarPratos() {
        try {
            const params = new URLSearchParams({
                restaurant_id: 1,
                available: 1
            });
            const items = await apiGet('admin/items?' + params.toString());
            return items || [];
        } catch(e) {
            console.error('Erro ao carregar pratos', e);
            return [];
        }
    }

    // Renderizar lista de pratos
    function renderizarListaPratos(pratos, filtro = '') {
        if (!pratosDiaLista) return;

        const filtroLower = filtro.toLowerCase().trim();
        const pratosFiltrados = filtro 
            ? pratos.filter(p => (p.name || '').toLowerCase().includes(filtroLower))
            : pratos;

        if (pratosFiltrados.length === 0) {
            pratosDiaLista.innerHTML = '<p style="text-align: center; color: var(--cinza-escuro); padding: 40px;">Nenhum prato encontrado.</p>';
            return;
        }

        const html = pratosFiltrados.map(prato => {
            const rating = prato.avg_rating || 0;
            const totalRatings = prato.total_count || 0;
            const priceDisplay = formatCurrency(prato.price || 0);
            const rawImg = prato.image_url || prato.image || prato.img || prato.photo || prato.thumbnail;
            const imgSrc = resolveImageUrl(rawImg) || '../images/crispy-baked-meat-potatoes.webp';

            return `
                <div class="prato-dia-card" data-prato='${escapeHtml(JSON.stringify(prato))}' style="display: flex; gap: 12px; padding: 12px; border: 1px solid var(--cinza-bem-claro); border-radius: 8px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s ease;">
                    <img src="${escapeHtml(imgSrc)}" 
                         alt="${escapeHtml(prato.name)}" 
                         style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px; flex-shrink: 0;">
                    <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h4 style="margin: 0 0 4px 0; font-size: 16px; color: var(--preto-suave);">${escapeHtml(prato.name)}</h4>
                            <p style="margin: 0; font-size: 13px; color: var(--cinza-escuro); line-height: 1.3;">${escapeHtml((prato.description || '').substring(0, 80))}${prato.description && prato.description.length > 80 ? '...' : ''}</p>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 6px;">
                            <span style="font-size: 14px; font-weight: 600; color: var(--laranja-primario);">${escapeHtml(priceDisplay)}</span>
                            <span style="font-size: 12px; color: var(--cinza-medio);">
                                <i class="fas fa-star" style="color: var(--amarelo-suave);"></i> ${rating.toFixed(1)} (${totalRatings})
                            </span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        pratosDiaLista.innerHTML = html;

        // Adicionar evento de hover
        const cards = pratosDiaLista.querySelectorAll('.prato-dia-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                if (!card.classList.contains('selected')) {
                    card.style.backgroundColor = 'var(--laranja-claro)';
                    card.style.borderColor = 'var(--laranja-primario)';
                }
            });
            card.addEventListener('mouseleave', () => {
                if (!card.classList.contains('selected')) {
                    card.style.backgroundColor = '';
                    card.style.borderColor = 'var(--cinza-bem-claro)';
                    card.style.borderWidth = '1px';
                    const t = card.querySelector('h4');
                    const d = card.querySelector('p');
                    if (t) t.style.color = 'var(--preto-suave)';
                    if (d) d.style.color = 'var(--cinza-escuro)';
                } else {
                    // Reforça estilos de seleção
                    card.style.backgroundColor = 'var(--laranja-primario)';
                    card.style.borderColor = 'var(--preto-suave)';
                    card.style.borderWidth = '2px';
                    const t = card.querySelector('h4');
                    const d = card.querySelector('p');
                    const span = card.querySelector('span');
                    if (t) t.style.color = 'var(--branco-puro)';
                    if (d) d.style.color = 'var(--branco-puro)';
                    if (span) span.style.color = 'var(--preto-suave)';
                }
            });
            card.addEventListener('click', () => selecionarPratoUI(card));
        });

        // Reaplica seleção ativa após renderização (busca estado atual ou salvo)
        if (pratoSelecionadoAtual && pratoSelecionadoAtual.id) {
            cards.forEach(card => {
                try {
                    const data = JSON.parse(card.dataset.prato);
                    if (data.id === pratoSelecionadoAtual.id) {
                        selecionarPratoUI(card);
                    }
                } catch(e) {}
            });
        }
    }

    // Selecionar prato na UI (marca visualmente e ativa botão)
    function selecionarPratoUI(card) {
        try {
            // Remove destaque anterior
            const cardsAntes = pratosDiaLista.querySelectorAll('.prato-dia-card');
            cardsAntes.forEach(c => {
                c.style.backgroundColor = '';
                c.style.borderColor = 'var(--cinza-bem-claro)';
                c.style.borderWidth = '1px';
                c.classList.remove('selected');
                const ct = c.querySelector('h4');
                const cd = c.querySelector('p');
                if (ct) ct.style.color = 'var(--preto-suave)';
                if (cd) cd.style.color = 'var(--cinza-escuro)';
            });

            // Marca selecionado
            pratoSelecionadoAtual = JSON.parse(card.dataset.prato);
            card.style.backgroundColor = 'var(--laranja-primario)';
            card.style.borderColor = 'var(--preto-suave)';
            card.style.borderWidth = '2px';
            card.style.color = 'var(--branco-puro)';
            card.classList.add('selected');

            // Atualiza cor do texto dentro do card
            const titulo = card.querySelector('h4');
            const desc = card.querySelector('p');
            if (titulo) titulo.style.color = 'var(--branco-puro)';
            if (desc) desc.style.color = 'var(--branco-puro)';

            // Ativa botão de confirmação
            if (confirmPratoDia) confirmPratoDia.style.display = 'block';
        } catch(e) {
            console.error('Erro ao selecionar prato na UI', e);
        }
    }

    // Confirmar e salvar prato como prato do dia
    function confirmarSalvarPratoDia() {
        if (!pratoSelecionadoAtual) {
            mostrarMensagem('Selecione um prato primeiro', 'error');
            return;
        }

        try {
            const pratoSalvar = {
                id: pratoSelecionadoAtual.id,
                name: pratoSelecionadoAtual.name,
                description: pratoSelecionadoAtual.description,
                price: pratoSelecionadoAtual.price,
                image_url: pratoSelecionadoAtual.image_url || pratoSelecionadoAtual.image || pratoSelecionadoAtual.img || pratoSelecionadoAtual.photo || pratoSelecionadoAtual.thumbnail,
                category_name: pratoSelecionadoAtual.category_name,
                prep_time_minutes: pratoSelecionadoAtual.prep_time_minutes,
                ingredients: pratoSelecionadoAtual.ingredients || []
            };

            if (salvarPratoDoDia(pratoSalvar)) {
                fecharModal();
                mostrarMensagem('Prato do Dia definido com sucesso!', 'success');
                
                // Notificar que foi atualizado
                window.dispatchEvent(new CustomEvent('pratoDoDiaAtualizado', { detail: pratoSalvar }));
            } else {
                mostrarMensagem('Erro ao salvar prato do dia', 'error');
            }
        } catch(e) {
            console.error('Erro ao confirmar prato do dia', e);
            mostrarMensagem('Erro ao processar confirmação', 'error');
        }
    }

    // Mostrar mensagem de feedback
    function mostrarMensagem(texto, tipo = 'info') {
        const msg = document.createElement('div');
        msg.className = 'admin-notification';
        msg.textContent = texto;
        msg.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${tipo === 'success' ? 'var(--laranja-primario)' : 'var(--vermelho-pastel)'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 10000;
            font-size: 14px;
            animation: slideIn 0.3s ease;
        `;
        document.body.appendChild(msg);
        setTimeout(() => {
            msg.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => msg.remove(), 300);
        }, 3000);
    }

    // Restaurar seleção anterior do localStorage
    function restaurarSelecaoPrato(pratoDiaSalvo) {
        if (!pratoDiaSalvo || !pratoDiaSalvo.id) return;
        
        const cards = pratosDiaLista.querySelectorAll('.prato-dia-card');
        cards.forEach(card => {
            try {
                const cardData = JSON.parse(card.dataset.prato);
                if (cardData.id === pratoDiaSalvo.id) {
                    // Marcar como selecionado
                    selecionarPratoUI(card);
                }
            } catch(e) {
                // Ignorar erros de parsing
            }
        });
    }

    // Abrir modal
    async function abrirModal() {
        modalPratoDia.classList.add('show');
        document.body.classList.add('modal-aberto');
        
        const pratos = await carregarPratos();
        renderizarListaPratos(pratos);
        
        // Restaurar seleção anterior
        const pratoDiaSalvo = obterPratoDoDia();
        if (pratoDiaSalvo) {
            restaurarSelecaoPrato(pratoDiaSalvo);
        }
    }

    // Fechar modal
    function fecharModal() {
        modalPratoDia.classList.remove('show');
        document.body.classList.remove('modal-aberto');
        if (searchPratoDia) searchPratoDia.value = '';
        // Nota: NÃO limpamos pratoSelecionadoAtual aqui para permitir restauração ao reabrir
        // Apenas ocultamos o botão de confirmação
        if (confirmPratoDia) confirmPratoDia.style.display = 'none';
    }

    // Event Listeners
    btnPratoDia.addEventListener('click', abrirModal);
    
    if (closePratoDia) {
        closePratoDia.addEventListener('click', fecharModal);
    }
    
    if (cancelPratoDia) {
        cancelPratoDia.addEventListener('click', fecharModal);
    }

    if (confirmPratoDia) {
        confirmPratoDia.addEventListener('click', confirmarSalvarPratoDia);
    }
    
    // Fechar ao clicar no overlay
    modalPratoDia.addEventListener('click', (e) => {
        if (e.target === modalPratoDia) fecharModal();
    });

    // Busca em tempo real
    if (searchPratoDia) {
        let pratos = [];
        searchPratoDia.addEventListener('input', () => {
            renderizarListaPratos(pratos, searchPratoDia.value);
        });
        
        // Carregar pratos ao focar no input
        searchPratoDia.addEventListener('focus', async () => {
            if (pratos.length === 0) {
                pratos = await carregarPratos();
                renderizarListaPratos(pratos, searchPratoDia.value);
            }
        });
    }

    // Tecla ESC para fechar
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalPratoDia.classList.contains('show')) {
            fecharModal();
        }
    });

    // Adicionar animações CSS
    if (!document.getElementById('prato-dia-animations')) {
        const style = document.createElement('style');
        style.id = 'prato-dia-animations';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }

})();

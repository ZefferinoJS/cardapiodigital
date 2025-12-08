/**
 * Script para exibir o Prato do Dia na página inicial
 * Carrega dados do localStorage e renderiza na seção welcome
 */
(function() {
    'use strict';

    const PRATO_DIA_KEY = 'prato_do_dia';

    // Elementos DOM
    const pratoImg = document.getElementById('prato-dia-img');
    const pratoNome = document.getElementById('prato-dia-nome');
    const pratoDesc = document.getElementById('prato-dia-desc');
    const pratoPreco = document.getElementById('prato-dia-preco');
    const pratoAdd = document.getElementById('prato-dia-add');
    const pratoDetails = document.getElementById('prato-dia-details');

    function formatCurrency(v) {
        try {
            return new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA', maximumFractionDigits: 2 }).format(Number(v));
        } catch(e) {
            return `AO ${Number(v).toFixed(2)}`;
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

    // Normaliza URLs de imagem para um formato absoluto no site
    function resolveImageUrl(u) {
        if (!u) return '';
        const s = String(u).trim();
        if (/^https?:\/\//i.test(s)) return s; // URL absoluta externa
        if (s.startsWith('/')) return s;        // Caminho absoluto do site
        // Remove prefixos relativos e força caminho absoluto a partir da raiz pública
        const clean = s.replace(/^(\.\/|(?:\.\.\/)+)/, '');
        return '/' + clean;
    }

    function renderizarPratoDoDia() {
        const prato = obterPratoDoDia();
        
        if (!prato) {
            // Mantém o conteúdo padrão
            console.log('Nenhum prato do dia definido, mantendo padrão');
            return;
        }

        // Atualizar imagem
        if (pratoImg && prato.image_url) {
            pratoImg.src = resolveImageUrl(prato.image_url) || '/assets/images/crispy-baked-meat-potatoes.webp';
            pratoImg.alt = prato.name || 'Prato do Dia';
        }

        // Atualizar nome
        if (pratoNome && prato.name) {
            pratoNome.textContent = prato.name;
        }

        // Atualizar descrição
        if (pratoDesc && prato.description) {
            pratoDesc.textContent = prato.description;
        }

        // Atualizar preço
        if (pratoPreco && prato.price !== undefined) {
            pratoPreco.textContent = formatCurrency(prato.price);
        }

        // Atualizar detalhes (tempo de preparo, ingredientes, etc)
        if (pratoDetails && prato.prep_time_minutes) {
            const detailsHTML = `
                <div class="detail-item">
                    <i class="fas fa-clock"></i>
                    <span>${prato.prep_time_minutes} min</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-star"></i>
                    <span>Prato do Dia</span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-fire"></i>
                    <span>Popular</span>
                </div>
            `;
            pratoDetails.innerHTML = detailsHTML;
        }

        // Configurar botão de adicionar ao carrinho
        if (pratoAdd) {
            pratoAdd.onclick = function(e) {
                e.preventDefault();
                adicionarAoCarrinho(prato);
            };
        }
    }

    function adicionarAoCarrinho(prato) {
        try {
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const existing = cart.find(i => i.id === String(prato.id));
            
            const priceDisplay = formatCurrency(prato.price);
            
            if (existing) {
                existing.qty = (existing.qty || 1) + 1;
            } else {
                cart.push({
                    id: String(prato.id),
                    title: prato.name,
                    price: priceDisplay,
                    priceValue: prato.price,
                    img: resolveImageUrl(prato.image_url) || '/assets/images/crispy-baked-meat-potatoes.webp',
                    qty: 1
                });
            }
            
            localStorage.setItem('cart', JSON.stringify(cart));
            window.dispatchEvent(new CustomEvent('cartUpdated', { detail: cart }));
            
            // Feedback visual
            mostrarFeedback('Adicionado ao carrinho!');
        } catch(e) {
            console.error('Erro ao adicionar ao carrinho', e);
            mostrarFeedback('Erro ao adicionar', 'error');
        }
    }

    function mostrarFeedback(texto, tipo = 'success') {
        const msg = document.createElement('div');
        msg.textContent = texto;
        msg.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 12px 20px;
            background: ${tipo === 'success' ? 'var(--laranja-primario)' : 'var(--vermelho-pastel)'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 9999;
            font-size: 14px;
            font-weight: 600;
            animation: slideInRight 0.3s ease;
        `;
        document.body.appendChild(msg);
        setTimeout(() => {
            msg.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => msg.remove(), 300);
        }, 2000);
    }

    // Adicionar animações
    if (!document.getElementById('prato-dia-animations-front')) {
        const style = document.createElement('style');
        style.id = 'prato-dia-animations-front';
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }

    // Renderizar ao carregar a página
    document.addEventListener('DOMContentLoaded', renderizarPratoDoDia);

    // Atualizar se o prato do dia for alterado em outra aba/janela
    window.addEventListener('storage', function(e) {
        if (e.key === PRATO_DIA_KEY) {
            renderizarPratoDoDia();
        }
    });

})();

/**
 * Modal de Detalhes do Prato
 * Gerencia a exibição de informações detalhadas dos pratos em um modal
 * Inclui: imagem, descrição, ingredientes, avaliações e adição ao carrinho
 */
(function() {
  'use strict';

  // ==================== CONSTANTES ====================
  const CART_STORAGE_KEY = 'cart';
  const FOCUS_DELAY_MS = 50;
  const FEEDBACK_DELAY_MS = 700;
  const DEFAULT_QUANTITY = 1;

  const SELECTORS = {
    CARD: '.prato-card, .categoria-card',
    EYE_ICON: 'i.fa-eye',
    ADD_CART: '.add-cart',
    BTN_SECONDARY: '.btn-secondary'
  };

  // ==================== CRIAÇÃO DO MODAL ====================
  
  /**
   * Cria a estrutura HTML do modal e adiciona ao DOM
   * @returns {HTMLElement} Elemento overlay do modal
   */
  function criarModal() {
    const overlay = document.createElement('div');
    overlay.className = 'modal-card-overlay';
    overlay.innerHTML = `
      <div class="modal-card" role="dialog" aria-modal="true">
        <div class="modal-image-section">
          <img class="modal-image" src="" alt="imagem do prato">
          <button class="modal-fav" title="Favoritar" aria-label="Favoritar"><i class="fa-regular fa-heart"></i></button>
        </div>
        <div class="modal-content-section">
          <div class="modal-header-row">
            <span class="modal-badges"></span>
            <button class="modal-close" aria-label="Fechar">&times;</button>
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="modal-title"></span>
          </div>
          
          <div class="modal-ratings"></div>
          <div class="modal-ingredients"><h4>Ingredientes</h4><ul></ul></div>
          <div class="modal-desc"></div>
          <div class="modal-footer-row">
            <div class="modal-qty">
              <button class="modal-qty-minus" aria-label="Diminuir quantidade">-</button>
              <span class="modal-qty-value">1</span>
              <button class="modal-qty-plus" aria-label="Aumentar quantidade">+</button>
              <span class="modal-price">$00.0</span>
            </div>
            <button class="modal-order-btn">Pedir <i class="fa-solid fa-bell-concierge"></i></button>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);
    return overlay;
  }

  // ==================== INICIALIZAÇÃO ====================
  
  const modalOverlay = criarModal();
  const modal = modalOverlay.querySelector('.modal-card');
  
  // Cache dos elementos do modal
  const elementos = {
    titulo: modalOverlay.querySelector('.modal-title'),
    imagem: modalOverlay.querySelector('.modal-image'),
    descricao: modalOverlay.querySelector('.modal-desc'),
    categoria: modalOverlay.querySelector('.modal-badges'),
    preco: modalOverlay.querySelector('.modal-price'),
    combo: modalOverlay.querySelector('.modal-combo'),
    quantidade: modalOverlay.querySelector('.modal-qty-value'),
    botaoDiminuirQtd: modalOverlay.querySelector('.modal-qty-minus'),
    botaoAumentarQtd: modalOverlay.querySelector('.modal-qty-plus'),
    botaoPedido: modalOverlay.querySelector('.modal-order-btn'),
    botaoFavorito: modalOverlay.querySelector('.modal-fav')
  };

  let precoBaseAtual = 0;
  let precoOriginalAtual = '';

  let elementoAnteriorFoco = null;

  // ==================== FUNÇÕES UTILITÁRIAS ====================

  /**
   * Obtém todos os elementos focáveis dentro de um container
   * @param {HTMLElement} container - Container para buscar elementos focáveis
   * @returns {Array<HTMLElement>} Lista de elementos focáveis
   */
  function obterElementosFocaveis(container) {
    const seletor = 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';
    return Array.from(container.querySelectorAll(seletor))
      .filter(el => !el.hasAttribute('disabled'));
  }

  /**
   * Extrai dados do card para popular o modal
   * @param {HTMLElement} card - Elemento card do prato
   * @returns {Object} Objeto com dados do prato
   */
  function extrairDadosCard(card) {
    const dataset = card.dataset || {};
    
    return {
      id: dataset.id || '',
      titulo: card.querySelector('h3')?.textContent?.trim() || dataset.id || '',
      imagem: dataset.img || card.querySelector('img')?.getAttribute('src') || '',
      preco: dataset.price || card.querySelector('.preco')?.textContent?.trim() || '0.0',
      descricao: dataset.desc || card.querySelector('p')?.textContent?.trim() || 'Descrição do prato disponível aqui.',
      ingredientes: dataset.ingredients || '',
      avaliacoes: dataset.rating || null,
      categoria: dataset.category || dataset.categoria || card.querySelector('.categoria')?.textContent?.trim() || 'Categoria'
    };
  }

  /**
   * Formata valor monetário no padrão brasileiro
   * @param {number} valor - Valor numérico
   * @param {string} fallback - Texto a exibir caso valor não seja numérico
   * @returns {string} Valor formatado
   */
  function formatarPreco(valor, fallback) {
    if(Number.isFinite(valor)) {
      return `Kz ${valor.toFixed(2).replace('.', ',')}`;
    }
    return fallback;
  }

  /**
   * Atualiza quantidade e preço exibidos no modal
   * @param {number} precoBase - Preço unitário numérico
   * @param {number} quantidade - Quantidade atual
   * @param {string} precoOriginal - Texto original do preço (fallback)
   */
  function atualizarQuantidadeEPreco(precoBase, quantidade, precoOriginal) {
    if(elementos.quantidade) {
      elementos.quantidade.textContent = String(quantidade);
    }
    if(elementos.preco) {
      const total = Number.isFinite(precoBase) ? precoBase * quantidade : NaN;
      elementos.preco.textContent = formatarPreco(total, precoOriginal);
    }
  }

  /**
   * Altera quantidade em delta, respeitando mínimo 1
   * @param {number} delta - Incremento/decremento
   */
  function alterarQuantidade(delta) {
    const atual = parseInt(elementos.quantidade?.textContent || DEFAULT_QUANTITY, 10) || DEFAULT_QUANTITY;
    const novaQuantidade = Math.max(1, atual + delta);
    atualizarQuantidadeEPreco(precoBaseAtual, novaQuantidade, precoOriginalAtual);
  }

  /**
   * Abre o modal com dados do card selecionado
   * @param {HTMLElement} card - Card do prato clicado
   */
  function abrirModal(card) {
    const dados = extrairDadosCard(card);

    // Popula elementos básicos
    if(elementos.titulo) elementos.titulo.textContent = dados.titulo;
    if(elementos.imagem) { 
      elementos.imagem.src = dados.imagem; 
      elementos.imagem.alt = dados.titulo; 
    }

    const dadosAvaliacao = processarDadosAvaliacao(dados.avaliacoes, card);
    renderizarAvaliacoes(dadosAvaliacao);

    if(elementos.categoria) elementos.categoria.textContent = dados.categoria;
    if(elementos.combo) elementos.combo.textContent = dados.combo;

    // Renderiza seções dinâmicas
    renderizarIngredientes(dados.ingredientes);
    renderizarDescricao(dados.descricao);

    // Preço e quantidade
    precoBaseAtual = converterPreco(dados.preco);
    precoOriginalAtual = dados.preco;
    atualizarQuantidadeEPreco(precoBaseAtual, DEFAULT_QUANTITY, precoOriginalAtual);

    // Configura dados para adicionar ao carrinho
    if(elementos.botaoPedido) {
      elementos.botaoPedido.dataset.pratoId = dados.id;
      elementos.botaoPedido.dataset.pratoTitle = dados.titulo;
      elementos.botaoPedido.dataset.pratoPrice = dados.preco;
      elementos.botaoPedido.dataset.pratoImg = dados.imagem;
    }

    // Gerencia foco para acessibilidade
    elementoAnteriorFoco = document.activeElement;
    modalOverlay.classList.add('show');
    document.body.classList.add('modal-aberto');
    document.addEventListener('keydown', tratarTecla);

    // Move foco para primeiro elemento focável
    setTimeout(() => {
      const focaveis = obterElementosFocaveis(modal);
      if(focaveis.length) focaveis[0].focus();
    }, FOCUS_DELAY_MS);
  }

  /**
   * Renderiza a descrição do prato no modal
   * @param {string} descricaoTexto - Texto da descrição
   */
  function renderizarDescricao(descricaoTexto) {
    let container = modalOverlay.querySelector('.modal-desc');
    
    // Cria container se não existir
    if(!container) {
      const wrapper = document.createElement('div');
      wrapper.className = 'modal-desc';
      
      const contentSection = modal.querySelector('.modal-content-section');
      const footerRow = contentSection?.querySelector('.modal-footer-row');
      if(footerRow) {
        contentSection.insertBefore(wrapper, footerRow);
      } else {
        contentSection.appendChild(wrapper);
      }
      
      container = modalOverlay.querySelector('.modal-desc');
    }

    container.textContent = descricaoTexto;
  }

  // ==================== GERENCIAMENTO DE INGREDIENTES ====================

  /**
   * Renderiza a lista de ingredientes no modal
   * @param {string} ingredientesRaw - String com ingredientes separados por vírgula
   */
  function renderizarIngredientes(ingredientesRaw) {
    const ingredientes = ingredientesRaw
      ? String(ingredientesRaw).split(',').map(s => s.trim()).filter(Boolean)
      : [];

    let container = modalOverlay.querySelector('.modal-ingredients');
    
    // Cria container se não existir
    if(!container) {
      const wrapper = document.createElement('div');
      wrapper.className = 'modal-ingredients';
      wrapper.innerHTML = '<h4>Ingredientes</h4><ul></ul>';
      
      const contentSection = modal.querySelector('.modal-content-section');
      const footerRow = contentSection?.querySelector('.modal-footer-row');
      if(footerRow) {
        contentSection.insertBefore(wrapper, footerRow);
      } else {
        contentSection.appendChild(wrapper);
      }
      
      container = modalOverlay.querySelector('.modal-ingredients');
    }

    const lista = container.querySelector('ul');
    lista.innerHTML = '';

    if(ingredientes.length === 0) {
      lista.innerHTML = '<li>Ingredientes não especificados.</li>';
    } else {
      ingredientes.forEach(ingrediente => {
        const item = document.createElement('li');
        item.textContent = ingrediente;
        lista.appendChild(item);
      });
    }
  }

  // ==================== GERENCIAMENTO DE AVALIAÇÕES ====================

  /**
   * Processa dados de avaliação do card
   * @param {string|null} avaliacoesRaw - JSON string ou null
   * @param {HTMLElement} card - Card elemento para fallback
   * @returns {Object|null} Dados processados de avaliação
   */
  function processarDadosAvaliacao(avaliacoesRaw, card) {
    let dados = null;

    // Tenta parsear JSON do dataset
    if(avaliacoesRaw) {
      try {
        dados = JSON.parse(avaliacoesRaw);
      } catch(e) {
        dados = null;
      }
    }

    // Fallback: extrai média das estrelas exibidas
    if(!dados && card) {
      const spanAvaliacao = card.querySelector('.time-rank span:nth-child(2)');
      const textoMedia = spanAvaliacao?.textContent.replace(/[^0-9\.,]/g, '').trim() || '';
      const media = textoMedia ? parseFloat(textoMedia.replace(',', '.')) : null;
      dados = media ? { avg: media, total: 0, counts: {} } : null;
    }

    return dados;
  }

  /**
   * Renderiza o bloco de avaliações no modal
   * @param {Object|null} dadosAvaliacao - Dados de avaliação processados
   */
  function renderizarAvaliacoes(dadosAvaliacao) {
    let blocoAvaliacoes = modalOverlay.querySelector('.modal-ratings');

    // Popula bloco existente no HTML
    if(!blocoAvaliacoes) {
      blocoAvaliacoes = document.createElement('div');
      blocoAvaliacoes.className = 'modal-ratings';
      const contentSection = modal.querySelector('.modal-content-section');
      contentSection.appendChild(blocoAvaliacoes);
    }

    blocoAvaliacoes.innerHTML = `
      <div class="rating-summary">
        <div class="avg"><i class="fa fa-star"></i> -</div>
        <div class="total">- avaliações</div>
      </div>
    `;

    const elementoMedia = blocoAvaliacoes.querySelector('.rating-summary .avg');
    const elementoTotal = blocoAvaliacoes.querySelector('.rating-summary .total');

    if(dadosAvaliacao) {
      const total = dadosAvaliacao.total || 
        Object.values(dadosAvaliacao.counts || {})
          .reduce((soma, valor) => soma + (Number(valor) || 0), 0);
      
      elementoMedia.textContent = dadosAvaliacao.avg 
        ? Number(dadosAvaliacao.avg).toFixed(1) 
        : '-';
      
      elementoTotal.textContent = total 
        ? `${total} avaliações` 
        : 'Sem avaliações';
    } else {
      elementoMedia.textContent = '-';
      elementoTotal.textContent = 'Sem avaliações';
    }
  }

  // ==================== GERENCIAMENTO DO MODAL ====================

  /**
   * Fecha o modal e restaura foco anterior
   */
  function fecharModal() {
    modalOverlay.classList.remove('show');
    document.body.classList.remove('modal-aberto');
    document.removeEventListener('keydown', tratarTecla);
    
    if(elementoAnteriorFoco && typeof elementoAnteriorFoco.focus === 'function') {
      try { 
        elementoAnteriorFoco.focus(); 
      } catch(e) { 
        // Ignora erros de foco em elementos removidos
      }
      elementoAnteriorFoco = null;
    }
  }

  /**
   * Gerencia teclas de atalho no modal (ESC e TAB)
   * @param {KeyboardEvent} evento - Evento de teclado
   */
  function tratarTecla(evento) {
    if(evento.key === 'Escape') {
      fecharModal();
      return;
    }

    // Gerencia navegação por TAB (trap focus)
    if(evento.key === 'Tab') {
      const focaveis = obterElementosFocaveis(modal);
      if(focaveis.length === 0) { 
        evento.preventDefault(); 
        return; 
      }

      const primeiro = focaveis[0];
      const ultimo = focaveis[focaveis.length - 1];

      if(evento.shiftKey) {
        if(document.activeElement === primeiro) { 
          ultimo.focus(); 
          evento.preventDefault(); 
        }
      } else {
        if(document.activeElement === ultimo) { 
          primeiro.focus(); 
          evento.preventDefault(); 
        }
      }
    }
  }

  // ==================== GERENCIAMENTO DO CARRINHO ====================

  /**
   * Obtém carrinho do localStorage
   * @returns {Array} Array de itens do carrinho
   */
  function obterCarrinho() {
    try {
      const carrinhoJSON = localStorage.getItem(CART_STORAGE_KEY);
      return carrinhoJSON ? JSON.parse(carrinhoJSON) : [];
    } catch(e) {
      console.error('Erro ao ler carrinho:', e);
      return [];
    }
  }

  /**
   * Salva carrinho no localStorage
   * @param {Array} carrinho - Array de itens
   */
  function salvarCarrinho(carrinho) {
    try {
      localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(carrinho));
    } catch(e) {
      console.error('Erro ao salvar carrinho:', e);
    }
  }

  /**
   * Converte string de preço para número
   * @param {string} precoStr - String com preço (ex: "Kz 25,90")
   * @returns {number} Valor numérico
   */
  function converterPreco(precoStr) {
    if(!precoStr) return 0;
    
    const limpo = String(precoStr)
      .replace(/[A-Za-z\s]/g, '')
      .replace(/\./g, '')
      .replace(',', '.');
    
    const valor = parseFloat(limpo);
    return Number.isFinite(valor) ? valor : 0;
  }

  /**
   * Adiciona ou atualiza item no carrinho
   * @param {Object} novoItem - Item a ser adicionado
   */
  function adicionarAoCarrinho(novoItem, quantidade = 1) {
    const quantidadeFinal = Math.max(1, Number(quantidade) || 1);
    const carrinho = obterCarrinho();
    const existente = carrinho.find(item => item.id === novoItem.id);

    if(existente) {
      existente.qty = (existente.qty || 1) + quantidadeFinal;
      existente.priceValue = converterPreco(novoItem.price) * existente.qty;
    } else {
      const priceValue = converterPreco(novoItem.price) * quantidadeFinal;
      carrinho.push({
        id: novoItem.id,
        title: novoItem.title,
        price: novoItem.price,
        priceValue: priceValue,
        img: novoItem.img,
        qty: quantidadeFinal
      });
    }

    salvarCarrinho(carrinho);

    // Dispara evento para outros componentes
    try {
      window.dispatchEvent(new CustomEvent('cartUpdated', { detail: carrinho }));
    } catch(e) {
      console.error('Erro ao disparar evento cartUpdated:', e);
    }
  }

  /**
   * Exibe feedback visual após adicionar ao carrinho
   * @param {HTMLElement} botao - Botão que foi clicado
   * @param {Function} callback - Função a executar após feedback
   */
  function exibirFeedbackCarrinho(botao, callback) {
    botao.disabled = true;
    const textoOriginal = botao.textContent;
    botao.textContent = 'Adicionado ✓';

    setTimeout(() => {
      botao.textContent = textoOriginal;
      botao.disabled = false;
      if(callback) callback();
    }, FEEDBACK_DELAY_MS);
  }

  // ==================== EVENT LISTENERS ====================

  // Fecha modal ao clicar no overlay ou botão fechar
  modalOverlay.addEventListener('click', (evento) => {
    if(evento.target === modalOverlay || evento.target.classList.contains('modal-close')) {
      fecharModal();
    }
  });

  // Delegação: abre modal ao clicar no ícone de olho
  document.addEventListener('click', (evento) => {
    const iconeOlho = evento.target.closest('i.fa-eye');
    if(!iconeOlho) return;
    
    const card = iconeOlho.closest(SELECTORS.CARD);
    if(card) abrirModal(card);
  });

  // Delegação: abre modal ao clicar no card (exceto controles de adicionar ao carrinho)
  document.addEventListener('click', (evento) => {
    const card = evento.target.closest(SELECTORS.CARD);
    if(!card) return;

    // Ignora cliques em controles de carrinho e ícone de olho
    if(evento.target.closest('.add-cart') || 
       evento.target.closest('.btn-secondary') || 
       evento.target.closest('i.fa-eye')) {
      return;
    }

    abrirModal(card);
  });

  // Controles de quantidade
  if(elementos.botaoDiminuirQtd) {
    elementos.botaoDiminuirQtd.addEventListener('click', () => alterarQuantidade(-1));
  }
  if(elementos.botaoAumentarQtd) {
    elementos.botaoAumentarQtd.addEventListener('click', () => alterarQuantidade(1));
  }

  // Adiciona ao carrinho ao clicar em "Fazer Pedido"
  elementos.botaoPedido.addEventListener('click', function() {
    const item = {
      id: this.dataset.pratoId || '(sem id)',
      title: this.dataset.pratoTitle || '(sem titulo)',
      price: this.dataset.pratoPrice || '(sem preco)',
      img: this.dataset.pratoImg || ''
    };

    const quantidadeSelecionada = parseInt(elementos.quantidade?.textContent || DEFAULT_QUANTITY, 10) || DEFAULT_QUANTITY;
    adicionarAoCarrinho(item, quantidadeSelecionada);
    exibirFeedbackCarrinho(this, fecharModal);
  });

})();

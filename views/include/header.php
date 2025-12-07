<header>
    <div class="icon">
        <i class="fas fa-home"></i>
        <span>Meu cardápio</span>
    </div>

    <nav>
        <ul>
            <li><a href="#">Início</a></li>
            <li><a href="#">Contato</a></li>
            <li class="cart">
                <button id="cart-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="cart-drawer" aria-label="Abrir carrinho">
                    <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                    <span class="cart-badge" aria-hidden="false">0</span>
                </button>
            </li>
        </ul>
    </nav>
</header>

<!-- Carrinho drawer e overlay (inseridos no header para facilitar include) -->
<aside id="cart-drawer" class="cart-drawer" aria-hidden="true">
  <div class="cart-drawer-header">
    <h3>Seu Carrinho</h3>
    <button id="cart-close" class="cart-close" aria-label="Fechar carrinho">&times;</button>
  </div>
  <div class="cart-items" id="cart-items">
    <!-- items rendered here -->
  </div>
  <div class="cart-drawer-footer">
    <div class="cart-subtotal">Total: <span id="cart-subtotal">AO 0,00</span></div>
    <button id="cart-checkout" class="btn-modal-primary">Finalizar compra</button>
  </div>
</aside>
<div id="cart-overlay" class="cart-overlay" hidden></div>
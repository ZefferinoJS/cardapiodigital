<!DOCTYPE html>
<html lang="pt-AO">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="shortcut icon" href="/assets/images/logo/5noback.png" type="image/x-icon">
    <title>O cardapio</title>
</head>

<body data-restaurant="minha-lanchonete">
    <header>
        <div class="icon">
            <img src="/assets/images/logo/2noback.png" alt="" srcset="">
        </div>

        <nav>
            <ul>

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
    <main>
        <section class="welcome" id="prato-dia-section">
            <div class="big-dish">
                <img id="prato-dia-img" src="/assets/images/autumn-salad.webp" alt="Prato do Dia">
            </div>
            <div class="container">
                <div>
                    <div class="destaque-titulo">
                        <h4>Prato do Dia</h4>
                    </div>
                    <h1 id="prato-dia-nome">Salada saudável</h1>
                    <p id="prato-dia-desc">Salada é uma comida nutricionalmente completa que contém todas as 27 vitaminas essenciais e minerais, proteina, gorduras ácidas essenciais, carboidratos, fibras e fitonutrientes.</p>
                    <div class="preco-action">
                        <a href="#" class="btn-primary" id="prato-dia-add"><i class="fas fa-circle-plus"></i><span>Adicionar</span></a>
                        <span class="preco" id="prato-dia-preco">AO 1,500.00</span>
                    </div>
                </div>
                <div class="details-dayDish" id="prato-dia-details">
                    <div class="detail-item">
                        <i class="fas fa-bolt"></i>
                        <span>Rápido</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-leaf"></i>
                        <span>Vegano</span>
                    </div>
                    <div class="detail-item">
                        <i class="fa-solid fa-wheat-awn"></i>
                        <span>Sem Glúten</span>
                    </div>
                </div>
            </div>


        </section>

        <section class="top-five">
            <div class="header-pratos">
                <h2>Top 4 Mais Votados</h2> <i class="fas fa-chevron-down"></i>
            </div>
            <div class="top-five-cards"><!-- preenchido dinamicamente via app.js --></div>

        </section>
        <section class="top-five categoria">
            <div class="header-pratos">
                <h2>Categoria</h2> <i class="fas fa-chevron-down"></i>
            </div>
            <div class="top-five-cards"><!-- preenchido dinamicamente via app.js --></div>

        </section>


    </main>

    <footer>
        
    </footer>

    <script src="/assets/js/exibir-prato-dia.js" defer></script>
    <script src="/assets/js/modal.js" defer></script>
    <script src="/assets/js/cart.js" defer></script>
    <script src="/assets/js/carousel.js" defer></script>
    <script src="/assets/js/app.js" defer></script>

</body>

</html>
<?php

?>

<!DOCTYPE html>
<html lang="pt-AO">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <title>O teu cardapio</title>
</head>

<body data-restaurant="minha-lanchonete">
    <?php include __DIR__ . '/../views/include/header.php'; ?>
    <main>
        <section class="welcome">
            <div class="container">
                <h1>Bem-vindo ao Meu Cardápio</h1>
                <p>Explore nossos deliciosos pratos e faça seu pedido online!</p>
                <a href="#" class="btn-primary">Ver Produtos</a>
            </div>
        
        </section>
        <section class="filters">
            <h2>Filtrar por Categoria</h2>
            <div class="categorias-search">
                <div class="categories">
                    <button class="category-btn">Todos</button>
                    <button class="category-btn">Saladas</button>
                    <button class="category-btn">Hambúrgueres</button>
                    <button class="category-btn">Bebidas</button>
                </div>
                <div class="search-bar">
                    <input type="text" placeholder="Pesquisar pratos...">
                    <button><i class="fas fa-search"></i></button>
                </div>
            </div>
        </section>
        <section class="pratos populares">
            <div class="header-pratos">
                <h2>Nossos Pratos Populares</h2>
                <div class="header-pratos-action">
                    <div class="scroll-buttons">
                        <button class="scroll-left" aria-label="Scroll Left"><i class="fas fa-chevron-left"></i></button>
                        <button class="scroll-right" aria-label="Scroll Right"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <a href="#" class="btn-primary">Ver Todos</a>

                </div>
            </div>
            <div class="prato-carousel">
                <div class="prato-lista" data-category="populares">
                    <!-- Carregado dinamicamente da API -->
                </div>
            </div>
        </section>

        <section class="pratos saladas" id="secao-saladas">
            <div class="header-pratos">
                <h2>Saladas</h2>
                <div class="header-pratos-action">
                    <div class="scroll-buttons">
                        <button class="scroll-left" aria-label="Scroll Left"><i class="fas fa-chevron-left"></i></button>
                        <button class="scroll-right" aria-label="Scroll Right"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <a href="#" class="btn-primary">Ver Todos</a>

                </div>
            </div>
            <div class="prato-carousel">
                <div class="prato-lista" data-category="saladas">
                    <!-- Carregado dinamicamente da API -->
                </div>
            </div>
        </section>
        <section class="pratos hamburgueres" id="secao-hamburgueres">
            <div class="header-pratos">
                <h2>Hambúrgueres</h2>
                <div class="header-pratos-action">
                    <div class="scroll-buttons">
                        <button class="scroll-left" aria-label="Scroll Left"><i class="fas fa-chevron-left"></i></button>
                        <button class="scroll-right" aria-label="Scroll Right"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <a href="#" class="btn-primary">Ver Todos</a>

                </div>
            </div>
            <div class="prato-carousel">
                <div class="prato-lista" data-category="hamburgueres">
                    <!-- Carregado dinamicamente da API -->
                </div>
            </div>
        </section>

        <section class="pratos bebidas" id="secao-bebidas">
            <div class="header-pratos">
                <h2>Bebidas</h2>
                <div class="header-pratos-action">
                    <div class="scroll-buttons">
                        <button class="scroll-left" aria-label="Scroll Left"><i class="fas fa-chevron-left"></i></button>
                        <button class="scroll-right" aria-label="Scroll Right"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <a href="#" class="btn-primary">Ver Todos</a>

                </div>
            </div>
            <div class="prato-carousel">
                <div class="prato-lista" data-category="bebidas">
                    <!-- Carregado dinamicamente da API -->
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../views/include/footer.php'; ?>

    <script src="js/modal.js" defer></script>
    <script src="js/cart.js" defer></script>
    <script src="js/carousel.js" defer></script>
    <script src="js/app.js" defer></script>

</body>

</html>
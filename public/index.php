<!DOCTYPE html>
<html lang="pt-AO">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/pratos_populares.css">
    <link rel="stylesheet" href="css/cards-menu.css">
    <title>O teu cardapio</title>
</head>

<body data-restaurant="minha-lanchonete">
    <?php include __DIR__ . '/../views/include/header.php'; ?>
    <main>
        <section class="welcome" id="prato-dia-section">
            <div class="big-dish">
                <img id="prato-dia-img" src="images/autumn-salad.webp" alt="Prato do Dia">
            </div>
            <div class="container">
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

    <?php include __DIR__ . '/../views/include/footer.php'; ?>

    <script src="js/exibir-prato-dia.js" defer></script>
    <script src="js/modal.js" defer></script>
    <script src="js/cart.js" defer></script>
    <script src="js/carousel.js" defer></script>
    <script src="js/app.js" defer></script>

</body>

</html>
<!DOCTYPE html>
<html lang="pt-AO">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css?v=1">
    <link rel="stylesheet" href="/assets/css/medianecessarios.css">
    <link rel="shortcut icon" href="/assets/images/logo/5noback.png" type="image/x-icon">
    <title><?= $escape($pageTitle) . " · " .  $nomesistema; ?></title>
</head>

<body data-restaurant="minha-lanchonete">
    <div class="admin-body">
        <div class="admin-container">

            <!-- Sidebar -->
            <div class="side-left">
                <aside class="admin-sidebar">
                    <nav class="admin-nav">
                        <?php foreach ($menuItems as $item) :
                            if (($item['routa'] != 'configuracoes') && ($item['routa'] != 'perfil') && ($item['routa'] != 'notificacoes')): ?>
                                <a class="<?= $routa === $item['routa'] ? ' active' : '' ?>" href="/admin.php?routa=<?= $escape($item['routa']) ?>">
                                    <i class="<?= $escape($item['icon']) ?>"></i>
                                    <span><?= $escape($item['label']) ?></span>
                                </a>
                        <?php
                            endif;
                        endforeach;
                        ?>

                    </nav>
                    <form method="post" action="/admin.php" class="logout">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="menu-logout">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i></i> <span>Sair</span>
                        </button>
                    </form>
                </aside>
            </div>

            <!-- Main Content -->
            <main class="admin-main">

                <!-- ── Header + Summary cards ── -->
                <header class="dashboard-header top">
                    <div class="icon">
                        <img src="/assets/images/logo/7noback.png" alt="" srcset="">

                        <div class="header-top">
                            <a href="admin.php?routa=configuracoes">
                                <div class="configuracoes">
                                    <i class="fa-solid fa-gear"></i>
                                </div>
                            </a>
                            <a href="admin.php?routa=notificacoes">
                                <div class="notificacoes">
                                    <i class="fa-solid fa-bell"></i>
                                </div>
                            </a> 
                            <div class="hamburguer">
                                <i class="fa-solid fa-bars"></i>
                            </div>
                        </div>
                    </div>
                </header>

                <?php require $contentTemplate; ?>



            </main>
        </div>
    </div>

    <script src="/assets/js/main.js" defer></script>
    <script src="/assets/js/modal.js" defer></script>
    <script src="/assets/js/prato-do-dia.js" defer></script>
    <!--
      admin-categorias.js foi desativado de propósito: main.js já tem
      initCategorias() cobrindo os mesmos elementos (categorias-tbody,
      modal-categoria, btn-nova-categoria, etc.). Carregar os dois ao mesmo
      tempo duplicava os event listeners — cada clique disparava a ação
      duas vezes (dois POSTs, duas confirmações de exclusão, etc.).
      O ficheiro continua em /assets/js/admin-categorias.js para referência,
      caso queira separar essa lógica de main.js no futuro.
    -->
</body>

</html>
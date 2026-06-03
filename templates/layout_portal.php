<!DOCTYPE html>
<html lang="pt-AO">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
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
                        <div class="hamburguer">
                            <i class="fa-solid fa-bars"></i>
                        </div>
                    </div>
                </header>

                <?php require $contentTemplate; ?>



            </main>
        </div>
    </div>

    <script src="/assets/js/main.js" defer></script>
</body>

</html>
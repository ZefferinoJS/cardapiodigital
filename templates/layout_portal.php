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
                                <a class="<?= $routa === $item['routa'] ? ' active' : '' ?>" href="index.php?routa=<?= $escape($item['routa']) ?>">
                                    <i class="<?= $escape($item['icon']) ?>"></i>
                                    <span><?= $escape($item['label']) ?></span>
                                </a>
                        <?php
                            endif;
                        endforeach;
                        ?>

                    </nav>
                    <div class="logout">
                        <a href="../logout.php">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i></i> <span>Sair</span>
                        </a>
                    </div>
                </aside>
            </div>

            <!-- Main Content -->
            <main class="admin-main">

                <!-- ── Header + Summary cards ── -->
                <header class="dashboard-header top">
                    <?php include 'header-admin.php'; ?>
                </header>
                <header class="dashboard-header bottom">
                    <?php include 'header-admin.php'; ?>
                </header>




            </main>
        </div>
    </div>

    <script src="/assets/js/adminMEnu.js" defer></script>
</body>

</html>
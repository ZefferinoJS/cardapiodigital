<div class="side-left">
    <aside class="admin-sidebar">
        <!--div class="admin-logo">
        <h2><i class="fas fa-utensils"></i> <span>Admin</span></h2>
    </div-->
        <nav class="admin-nav">
            <a href="dashboard.php" class="<?php echo ($pagina_atual === 'dashboard') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge"></i> <span>Painel</span>
            </a>
            <a href="pratos.php" class="<?php echo ($pagina_atual === 'pratos') ? 'active' : ''; ?>">
                <i class="fa-solid fa-bell-concierge"></i> <span>Pratos</span>
            </a>
            <a href="categorias.php" class="<?php echo ($pagina_atual === 'categorias') ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> <span>Categorias</span>
            </a>
            <a href="pedidos.php" class="<?php echo ($pagina_atual === 'pedidos') ? 'active' : ''; ?>">
                <i class="fas fa-receipt"></i> <span>Pedidos</span>
            </a>
            <a href="avaliacoes.php" class="<?php echo ($pagina_atual === 'avaliacoes') ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> <span>Avaliações</span>
            </a>
            <a href="relatorio.php" class="<?php echo ($pagina_atual === 'relatorio') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-pie"></i></i> <span>Relatórios</span>
            </a>

        </nav>
        <div class="logout">
            <a href="../logout.php">
                <i class="fa-solid fa-arrow-right-from-bracket"></i></i> <span>Sair</span>
            </a>
        </div>
    </aside>
</div>
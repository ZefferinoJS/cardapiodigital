<aside class="admin-sidebar">
    <!--div class="admin-logo">
        <h2><i class="fas fa-utensils"></i> <span>Admin</span></h2>
    </div-->
    <nav class="admin-nav">
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
        <a href="../index.php">
            <i class="fas fa-arrow-left"></i> <span>Voltar ao Site</span>
        </a>
    </nav>
</aside>
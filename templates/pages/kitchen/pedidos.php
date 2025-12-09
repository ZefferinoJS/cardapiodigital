<?php
// TODO: versão mínima focada no que a cozinha precisa (mesa, itens, status).
// Idealmente esta view não mostraria o total em Kz nem outras colunas
// financeiras. Ajustar o JS (fetch da API) para devolver só os campos
// necessários e desenhar um layout tipo "kanban" por status seria o
// próximo passo natural aqui.
?>
<main class="admin-main">
    <header class="dashboard-header dashboard-header-ad">
        <div class="dashboard-header-inner">
            <div class="dh-left">
                <h1><?= $escape($pageTitle) ?></h1>
                <p class="dh-sub"><?= $escape($pageSubtitle) ?></p>
            </div>
        </div>
    </header>

    <section class="admin-filters">
        <div class="filter-group">
            <label for="filter-status">Status:</label>
            <select id="filter-status">
                <option value="">Todos</option>
                <option value="submitted">Submetido</option>
                <option value="preparing">Preparando</option>
                <option value="ready">Pronto</option>
            </select>
        </div>
    </section>

    <section class="admin-table-section">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID Pedido</th>
                    <th>Mesa</th>
                    <th>Itens</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="pedidos-tbody">
                <tr>
                    <td colspan="5" class="loading">
                        <i class="fas fa-spinner fa-spin"></i> Carregando pedidos...
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</main>

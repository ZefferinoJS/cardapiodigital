<?php
// TODO: esta é uma versão mínima, herdada da página de pedidos do admin,
// mas sem os botões de gestão de mesas (criar/editar/apagar), que devem
// continuar a ser uma ação exclusiva do perfil admin. Revever o desenho
// para as necessidades reais do dia-a-dia do staff.
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

    <!-- Filtros -->
    <section class="admin-filters">
        <div class="filter-group">
            <label for="filter-status">Status:</label>
            <select id="filter-status">
                <option value="">Todos</option>
                <option value="submitted">Submetido</option>
                <option value="preparing">Preparando</option>
                <option value="ready">Pronto</option>
                <option value="completed">Completo</option>
                <option value="cancelled">Cancelado</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="filter-data">Data:</label>
            <input type="date" id="filter-data">
        </div>
        <div class="search-group">
            <input type="text" id="search-pedido" placeholder="Buscar por número de mesa...">
            <button id="btn-search"><i class="fas fa-search"></i></button>
        </div>
    </section>

    <!-- Tabela de Pedidos -->
    <section class="admin-table-section">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID Pedido</th>
                    <th>Mesa</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Criado em</th>
                    <th>Itens</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="pedidos-tbody">
                <tr>
                    <td colspan="7" class="loading">
                        <i class="fas fa-spinner fa-spin"></i> Carregando pedidos...
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</main>

<!-- Modal de Detalhes do Pedido (permite actualizar status) -->
<div class="modal-overlay" id="modal-pedido">
    <div class="modal modal-large">
        <div class="modal-header">
            <h3>Detalhes do Pedido #<span id="pedido-id-modal"></span></h3>
            <button class="modal-close" id="close-modal-pedido">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="pedido-modal-body"></div>
        <div class="modal-footer">
            <select id="pedido-status-select" class="form-group-inline">
                <option value="submitted">Submetido</option>
                <option value="preparing">Preparando</option>
                <option value="ready">Pronto</option>
                <option value="completed">Completo</option>
                <option value="cancelled">Cancelado</option>
            </select>
            <button class="btn-primary" id="btn-update-status">
                <i class="fas fa-check"></i> Atualizar Status
            </button>
            <button class="btn-secondary" id="close-modal-pedido-footer">Fechar</button>
        </div>
    </div>
</div>

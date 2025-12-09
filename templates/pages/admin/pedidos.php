
            <main class="admin-main">
                <!-- Dashboard Top Banner -->
                <header class="dashboard-header dashboard-header-ad ">
                    <div class="dashboard-header-inner">
                        <div class="dh-left">
                            <h1><?=  $escape( $pageTitle) ?></h1>
                            <p class="dh-sub"><?= $escape( $pageSubtitle) ?></p>
                        </div>
                        <div class="dh-actions">
                            <button class="btn-primary" id="btn-nova-cadeira">
                                <i class="fas fa-plus"></i> Nova Mesa
                            </button>
                            <button class="btn-primary btn-primary-outline" id="btn-ver-lista-cadeiras">
                                <i class="fas fa-list"></i> Ver todas as mesas
                            </button>
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
                            <!-- Carregado dinamicamente -->
                            <tr>
                                <td colspan="7" class="loading">
                                    <i class="fas fa-spinner fa-spin"></i> Carregando pedidos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </main>


        <!-- Modal para Detalhes do Pedido -->
        <div class="modal-overlay" id="modal-pedido">
            <div class="modal modal-large">
                <div class="modal-header">
                    <h3>Detalhes do Pedido #<span id="pedido-id-modal"></span></h3>
                    <button class="modal-close" id="close-modal-pedido">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" id="pedido-modal-body">
                    <!-- Carregado dinamicamente -->
                </div>
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

    <!-- Modal para Nova Mesa -->
    <div class="modal-overlay" id="modal-nova-mesa">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modal-mesa-title">Nova Mesa</h3>
                <button class="modal-close" id="close-modal-mesa">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="form-mesa" class="modal-body-form">
                <input type="hidden" id="mesa-id" name="id">

                <div class="form-group">
                    <label for="mesa-numero">Número da Mesa *</label>
                    <input type="text" id="mesa-numero" name="number" required placeholder="Ex: 1, A1, Mesa VIP">
                </div>

                <div class="form-group">
                    <label for="mesa-descricao">Descrição</label>
                    <input type="text" id="mesa-descricao" name="description" placeholder="Ex: Próxima à janela">
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="mesa-ativa" name="active" checked>
                        Ativa
                    </label>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" id="cancel-mesa">Cancelar</button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Listar Mesas -->
    <div class="modal-overlay" id="modal-lista-mesas">
        <div class="modal modal-large">
            <div class="modal-header">
                <h3>Gerenciar Mesas</h3>
                <button class="modal-close" id="close-modal-lista">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Descrição</th>
                            <th>QR Code</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="mesas-tbody">
                        <tr>
                            <td colspan="5" class="loading">
                                <i class="fas fa-spinner fa-spin"></i> Carregando mesas...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" id="close-modal-lista-footer">Fechar</button>
            </div>
        </div>
    </div>


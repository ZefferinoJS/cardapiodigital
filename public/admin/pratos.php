<?php
// Página de gerenciamento de pratos
$titulo = "Gerenciar Pratos";
$subtitulo = "Gerencie os pratos do restaurante, suas categorias e disponibilidade.";
$pagina_atual = "pratos";
?>
<!DOCTYPE html>
<html lang="pt-AO">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/admin.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css">
    <link rel="shortcut icon" href="/images/logo/5noback.png" type="image/x-icon">
    <title><?php echo $titulo . " - Admin"; ?></title>

</head>

<body data-restaurant="minha-lanchonete">
    <div class="admin-body">
        <div class="admin-container">
            <!-- Sidebar -->
            <?php include 'aside-admin.php'; ?>
            <!-- Main Content -->
            <main class="admin-main">
                <!-- Dashboard Top Banner -->
                <header class="dashboard-header">
                    <div class="icon">
                        <img src="/images/logo/7noback.png" alt="" srcset="">
                        <div class="hamburguer">
                            <i class="fas fa-hamburguer"></i>
                        </div>
                    </div>
                    <div class="dashboard-cards">
                        <div class="card summary-card variant-1">
                            <div class="left">
                                <div class="card-title">Total Pratos</div>
                                <div class="card-value" id="total-pratos">—</div>
                            </div>
                            <div class="card-icon-wrap">
                                <i class="fas fa-utensils card-icon" aria-hidden="true"></i>
                            </div>
                        </div>

                        <div class="card summary-card variant-2">
                            <div class="left">
                                <div class="card-title">Média Avaliação</div>
                                <div class="card-value" id="media-avaliacao">—</div>
                            </div>
                            <div class="card-icon-wrap">
                                <i class="fas fa-star card-icon" aria-hidden="true"></i>
                            </div>
                        </div>

                        <div class="card summary-card variant-3">
                            <div class="left">
                                <div class="card-title">Pedidos Hoje</div>
                                <div class="card-value" id="pedidos-hoje">—</div>
                            </div>
                            <div class="card-icon-wrap">
                                <i class="fas fa-receipt card-icon" aria-hidden="true"></i>
                            </div>
                        </div>

                        <div class="card summary-card variant-4">
                            <div class="left">
                                <div class="card-title">Receita</div>
                                <div class="card-value" id="receita">—</div>
                            </div>
                            <div class="card-icon-wrap">
                                <i class="fas fa-coins card-icon" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-header-inner">
                        <div class="dh-left">
                            <h1><?php echo $titulo ?></h1>
                            <p class="dh-sub"><?php echo $subtitulo ?></p>
                        </div>
                        <div class="dh-actions">
                            <button class="btn-primary" id="btn-novo-prato">
                                <i class="fas fa-plus"><span>Novo Prato</span></i>
                            </button>
                            <button class="btn-primary btn-primary-outline" id="btn-prato-do-dia">
                                <i class="fas fa-plus"></i> Prato do Dia
                            </button>
                        </div>
                    </div>

                </header>

                <!-- Filtros e Pesquisa -->
                <section class="admin-filters">
                    <div class="filter-group">
                        <label for="filter-categoria">Categoria:</label>
                        <select id="filter-categoria">
                            <option value="">Todas</option>
                            <!-- Carregado dinamicamente -->
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter-disponivel">Status:</label>
                        <select id="filter-disponivel">
                            <option value="">Todos</option>
                            <option value="1">Disponível</option>
                            <option value="0">Indisponível</option>
                        </select>
                    </div>
                    <div class="search-group">
                        <input type="text" id="search-prato" placeholder="Pesquisar prato...">
                        <button id="btn-search"><i class="fas fa-search"></i></button>
                    </div>
                </section>
                <!-- Tabela de Pratos -->
                <section class="admin-table-section">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="td-imagem">Imagem</th>
                                <th class="td-nome">Nome</th>
                                <th class="td-categoria">Categoria</th>
                                <th class="td-preco">Preço</th>
                                <th class="td-disponivel">Disponível</th>
                                <th class="td-avaliacao">Avaliação</th>
                                <th class="td-acoes">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="pratos-tbody">
                            <!-- Carregado dinamicamente -->
                            <tr>
                                <td colspan="7" class="loading">
                                    <i class="fas fa-spinner fa-spin"></i> Carregando pratos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <!-- Paginação -->
                <div class="admin-pagination" id="pagination">
                    <!-- Carregado dinamicamente -->
                </div>
            </main>
        </div>

        <!-- Modal para Selecionar Prato do Dia -->
        <div class="modal-overlay" id="modal-prato-dia">
            <div class="modal modal-large">
                <div class="modal-header">
                    <h3>Selecionar Prato do Dia</h3>
                    <button class="modal-close" id="close-modal-prato-dia">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body-form">
                    <div class="search-group" style="margin-bottom: 16px;">
                        <input type="text" id="search-prato-dia" placeholder="Pesquisar prato...">
                    </div>
                    <div id="pratos-dia-lista" style="max-height: 400px; overflow-y: auto;">
                        <p style="text-align: center; color: var(--cinza-escuro);">
                            <i class="fas fa-spinner fa-spin"></i> Carregando pratos...
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" id="cancel-prato-dia">Cancelar</button>
                        <button type="button" class="btn-primary" id="confirm-prato-dia" style="display: none;">Confirmar Seleção</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Criar/Editar Prato -->
        <div class="modal-overlay" id="modal-prato">
            <div class="modal modal-large">
                <div class="modal-header">
                    <h3 id="modal-title">Novo Prato</h3>
                    <button class="modal-close" id="close-modal-prato">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="form-prato" class="modal-body-form">
                    <input type="hidden" id="prato-id" name="id">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="prato-nome">Nome do Prato *</label>
                            <input type="text" id="prato-nome" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="prato-categoria">Categoria *</label>
                            <select id="prato-categoria" name="category_id" required>
                                <option value="">Selecione...</option>
                                <!-- Carregado dinamicamente -->
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="prato-preco">Preço (AOA) *</label>
                            <input type="number" id="prato-preco" name="price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="prato-tempo">Tempo Preparo (min)</label>
                            <input type="number" id="prato-tempo" name="prep_time_minutes">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="prato-descricao">Descrição</label>
                        <textarea id="prato-descricao" name="description" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="prato-imagem">URL da Imagem</label>
                        <input type="text" id="prato-imagem" name="image_url" placeholder="...">
                    </div>

                    <div class="form-group">
                        <label for="prato-ingredientes">Ingredientes (separados por vírgula)</label>
                        <input type="text" id="prato-ingredientes" name="ingredients" placeholder="Alface, Tomate, Queijo...">
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" id="prato-disponivel" name="is_available" checked>
                            Disponível para pedidos
                        </label>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" id="cancel-prato">Cancelar</button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Salvar Prato
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="../js/prato-do-dia.js" defer></script>
    <script src="../js/admin-pratos.js" defer></script>
    <script src="../js/admin-dashboard.js" defer></script>
</body>

</html>
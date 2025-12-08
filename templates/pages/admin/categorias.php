<!DOCTYPE html>
<html lang="pt-AO">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/css/main.css">
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
                <header class="dashboard-header dashboard-header-ad">
                    <?php include 'header-admin.php'; ?>
                    <div class="dashboard-header-inner">
                        <div class="dh-left">
                            <h1><?php echo $titulo ?></h1>
                            <p class="dh-sub"><?php echo $subtitulo ?></p>
                        </div>
                        <div class="dh-actions">
                            <button class="btn-primary" id="btn-nova-categoria">
                                <i class="fas fa-plus"></i> Nova Categoria
                            </button>
                        </div>
                    </div>

                </header>

                <!-- Tabela de Categorias -->
                <section class="admin-table-section">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="td-nome">Nome</th>
                                <th class="td-slug">Slug</th>
                                <th class="td-pratos">Pratos</th>
                                <th class="td-posicao">Posição</th>
                                <th class="td-ativa">Ativa</th>
                                <th class="td-acoes">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="categorias-tbody">
                            <!-- Carregado dinamicamente -->
                            <tr>
                                <td colspan="6" class="loading">
                                    <i class="fas fa-spinner fa-spin"></i> Carregando categorias...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </main>
        </div>

        <!-- Modal para Criar/Editar Categoria -->
        <div class="modal-overlay" id="modal-categoria">
            <div class="modal">
                <div class="modal-header">
                    <h3 id="modal-title">Nova Categoria</h3>
                    <button class="modal-close" id="close-modal-categoria">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="form-categoria" class="modal-body-form">
                    <input type="hidden" id="categoria-id" name="id">

                    <div class="form-group">
                        <label for="categoria-nome">Nome da Categoria *</label>
                        <input type="text" id="categoria-nome" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="categoria-slug">Slug</label>
                        <input type="text" id="categoria-slug" name="slug" placeholder="auto-gerado">
                    </div>

                    <div class="form-group">
                        <label for="categoria-posicao">Posição (ordem)</label>
                        <input type="number" id="categoria-posicao" name="position" value="0">
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" id="categoria-ativa" name="active" checked>
                            Ativa
                        </label>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" id="cancel-categoria">Cancelar</button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/admin-categorias.js" defer></script>
    <script src="../js/adminMEnu.js" defer></script>
</body>

</html>
<header class="dashboard-header dashboard-header-ad">
    <div class="dashboard-header-inner">
        <div class="dh-left">
            <h1><?= $escape( $pageTitle) ?></h1>
            <p class="dh-sub"><?= $escape($pageSubtitle) ?></p>
        </div>
    </div>
</header>

<!-- Filtros -->
<section class="admin-filters">
    <div class="filter-group">
        <label for="filter-rating">Avaliação:</label>
        <select id="filter-rating">
            <option value="">Todas</option>
            <option value="5">⭐⭐⭐⭐⭐ 5 estrelas</option>
            <option value="4">⭐⭐⭐⭐ 4 estrelas</option>
            <option value="3">⭐⭐⭐ 3 estrelas</option>
            <option value="2">⭐⭐ 2 estrelas</option>
            <option value="1">⭐ 1 estrela</option>
        </select>
    </div>
    <div class="search-group">
        <input type="text" id="search-prato" placeholder="Buscar por prato...">
        <button id="btn-search"><i class="fas fa-search"></i></button>
    </div>
</section>

<!-- Tabela de Avaliações -->
<section class="admin-table-section">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Prato</th>
                <th>Avaliação</th>
                <th>Comentário</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="avaliacoes-tbody">
            <!-- Carregado dinamicamente -->
            <tr>
                <td colspan="5" class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Carregando avaliações...
                </td>
            </tr>
        </tbody>
    </table>
</section>

<!-- Stats de Avaliações -->
<section class="rating-stats">
    <div class="stat-card">
        <h4>Média Geral</h4>
        <p class="stat-value" id="stat-media">0.0</p>
    </div>
    <div class="stat-card">
        <h4>Total de Avaliações</h4>
        <p class="stat-value" id="stat-total">0</p>
    </div>
    <div class="stat-card">
        <h4>5 Estrelas</h4>
        <p class="stat-value" id="stat-5">0</p>
    </div>
    <div class="stat-card">
        <h4>4 Estrelas</h4>
        <p class="stat-value" id="stat-4">0</p>
    </div>
    <div class="stat-card">
        <h4>3 Estrelas</h4>
        <p class="stat-value" id="stat-3">0</p>
    </div>
    <div class="stat-card">
        <h4>2 Estrelas</h4>
        <p class="stat-value" id="stat-2">0</p>
    </div>
    <div class="stat-card">
        <h4>1 Estrela</h4>
        <p class="stat-value" id="stat-1">0</p>
    </div>
</section>
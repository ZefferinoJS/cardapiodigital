<?php

?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/css/main.css">
    <link rel="shortcut icon" href="/images/logo/5noback.png" type="image/x-icon">
    <title><?php echo htmlspecialchars($titulo) . " - Admin"; ?></title>
</head>
 
<body data-restaurant="minha-lanchonete">
<div class="admin-body">
    <div class="admin-container">
 
        <!-- Sidebar -->
        <?php include 'aside-admin.php'; ?>
 
        <!-- Main Content -->
        <main class="admin-main">
 
            <!-- Header -->
            <header class="dashboard-header dashboard-header-ad">
                <?php include 'header-admin.php'; ?>
                <div class="dashboard-header-inner">
                    <div class="dh-left">
                        <h1><?php echo htmlspecialchars($titulo); ?></h1>
                        <p class="dh-sub"><?php echo htmlspecialchars($subtitulo); ?></p>
                    </div>
                </div>
            </header>
 
            <!-- ── Filtros ── -->
            <section class="admin-filters">
                <form method="GET" style="display:contents;">
                    <div class="filter-group">
                        <div class="field">
                            <label for="periodo">Período</label>
                            <select name="periodo" id="periodo">
                                <option value="diario"  <?php echo $periodo === 'diario'  ? 'selected' : ''; ?>>Diário</option>
                                <option value="semanal" <?php echo $periodo === 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                                <option value="mensal"  <?php echo $periodo === 'mensal'  ? 'selected' : ''; ?>>Mensal</option>
                                <option value="anual"   <?php echo $periodo === 'anual'   ? 'selected' : ''; ?>>Anual</option>
                            </select>
                        </div>
 
                        <div class="field">
                            <label for="data">Data de referência</label>
                            <input type="date" name="data" id="data"
                                   value="<?php echo htmlspecialchars($data); ?>">
                        </div>
 
                        <button type="submit" id="btn-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
 
                    <!-- Botões de exportação -->
                    <div class="rel-actions">
                        <button type="button" class="btn-export" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <button type="button" class="btn-export" id="btn-csv">
                            <i class="fas fa-file-csv"></i> Exportar CSV
                        </button>
                    </div>
                </form>
            </section>
 
            <!-- ── KPI cards ── -->
            <div class="relatorio-kpis">
                <div class="kpi-card">
                    <div class="kpi-icon green"><i class="fas fa-coins"></i></div>
                    <div class="kpi-info">
                        <div class="valor"><?php echo number_format($kpi['receita_total'], 2, ',', '.'); ?> Kz</div>
                        <div class="rotulo">Receita — <?php echo htmlspecialchars($label_periodo); ?></div>
                    </div>
                </div>
 
                <div class="kpi-card">
                    <div class="kpi-icon orange"><i class="fas fa-receipt"></i></div>
                    <div class="kpi-info">
                        <div class="valor"><?php echo (int)$kpi['total_pedidos']; ?></div>
                        <div class="rotulo">Pedidos concluídos</div>
                    </div>
                </div>
 
                <div class="kpi-card">
                    <div class="kpi-icon blue"><i class="fas fa-tag"></i></div>
                    <div class="kpi-info">
                        <div class="valor"><?php echo number_format($ticket_medio, 2, ',', '.'); ?> Kz</div>
                        <div class="rotulo">Ticket médio</div>
                    </div>
                </div>
 
                <div class="kpi-card">
                    <div class="kpi-icon yellow"><i class="fas fa-utensils"></i></div>
                    <div class="kpi-info">
                        <div class="valor"><?php echo (int)$kpi['itens_vendidos']; ?></div>
                        <div class="rotulo">Itens vendidos</div>
                    </div>
                </div>
 
                <div class="kpi-card">
                    <div class="kpi-icon red"><i class="fas fa-ban"></i></div>
                    <div class="kpi-info">
                        <div class="valor"><?php echo (int)$kpi['cancelados']; ?></div>
                        <div class="rotulo">Pedidos cancelados</div>
                    </div>
                </div>
            </div>
 
            <!-- ── Tabs ── -->
            <div class="admin-table-section" style="margin-top:20px;">
 
                <div class="rel-tabs">
                    <button class="rel-tab active" data-tab="vendas">
                        <i class="fas fa-chart-bar"></i> Vendas por Prato
                    </button>
                    <button class="rel-tab" data-tab="pedidos">
                        <i class="fas fa-list"></i> Detalhe de Pedidos
                    </button>
                </div>
 
                <!-- Painel: Vendas por prato -->
                <div class="section-panel active" id="panel-vendas">
                    <?php
                    $receita_max = max(array_column($vendas_pratos, 'receita_prato') ?: [1]);
                    ?>
                    <?php if (empty($vendas_pratos)): ?>
                        <div class="empty-state">
                            <i class="fas fa-chart-bar"></i>
                            <p>Nenhuma venda encontrada para o período selecionado.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table class="rel-table" id="tabela-vendas">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Prato</th>
                                        <th>Categoria</th>
                                        <th class="num">Preço unit.</th>
                                        <th class="num">Qtd. vendida</th>
                                        <th>Receita</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vendas_pratos as $i => $row): ?>
                                        <tr>
                                            <td style="color:#9CA3AF;"><?php echo $i + 1; ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                            <td>
                                                <span style="background:#F3F4F6;padding:2px 8px;border-radius:20px;font-size:12px;">
                                                    <?php echo htmlspecialchars($row['categoria'] ?? '—'); ?>
                                                </span>
                                            </td>
                                            <td class="num"><?php echo number_format($row['preco_unitario'], 2, ',', '.'); ?> Kz</td>
                                            <td class="num"><strong><?php echo (int)$row['qtd_vendida']; ?></strong></td>
                                            <td>
                                                <div class="bar-cell">
                                                    <div class="mini-bar">
                                                        <div class="mini-bar-fill"
                                                             style="width:<?php echo round($row['receita_prato'] / $receita_max * 100); ?>%">
                                                        </div>
                                                    </div>
                                                    <span style="font-weight:700;color:#111;white-space:nowrap;">
                                                        <?php echo number_format($row['receita_prato'], 2, ',', '.'); ?> Kz
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:var(--laranja-claro);">
                                        <td colspan="4" style="font-weight:700;padding:12px 14px;">Total</td>
                                        <td class="num" style="font-weight:800;">
                                            <?php echo array_sum(array_column($vendas_pratos, 'qtd_vendida')); ?>
                                        </td>
                                        <td style="font-weight:800;padding:12px 14px;">
                                            <?php echo number_format(array_sum(array_column($vendas_pratos, 'receita_prato')), 2, ',', '.'); ?> Kz
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
 
                <!-- Painel: Detalhe de pedidos -->
                <div class="section-panel" id="panel-pedidos">
                    <?php if (empty($pedidos_detalhados)): ?>
                        <div class="empty-state">
                            <i class="fas fa-receipt"></i>
                            <p>Nenhum pedido encontrado para o período selecionado.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table class="rel-table" id="tabela-pedidos">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Mesa</th>
                                        <th>Itens</th>
                                        <th>Estado</th>
                                        <th class="num">Total</th>
                                        <th>Data / Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos_detalhados as $p): ?>
                                        <tr>
                                            <td><strong><?php echo (int)$p['id']; ?></strong></td>
                                            <td>
                                                <i class="fas fa-chair" style="color:#6B7280;margin-right:4px;font-size:11px;"></i>
                                                Mesa <?php echo htmlspecialchars($p['mesa'] ?? '—'); ?>
                                            </td>
                                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                                title="<?php echo htmlspecialchars($p['itens'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($p['itens'] ?? '—'); ?>
                                            </td>
                                            <td><?php echo status_badge($p['status'], $status_labels, $status_colors); ?></td>
                                            <td class="num"><strong><?php echo number_format($p['total'], 2, ',', '.'); ?> Kz</strong></td>
                                            <td style="color:#9CA3AF;font-size:12px;">
                                                <?php echo date('d/m/Y H:i', strtotime($p['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
 
            </div><!-- /.admin-table-section -->
 
        </main>
    </div>
</div>
 
<script>
    // ── Tabs ──────────────────────────────────────────────────────────────────
    document.querySelectorAll('.rel-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.rel-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
        });
    });
 
    // ── Exportar CSV ──────────────────────────────────────────────────────────
    document.getElementById('btn-csv')?.addEventListener('click', () => {
        const tabAtiva = document.querySelector('.section-panel.active');
        const table    = tabAtiva?.querySelector('table');
        if (!table) return;
 
        const rows = [...table.querySelectorAll('tr')].map(tr =>
            [...tr.querySelectorAll('th, td')]
                .map(td => '"' + td.innerText.replace(/"/g, '""').trim() + '"')
                .join(',')
        );
 
        const blob = new Blob(['\uFEFF' + rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = 'relatorio_<?php echo $periodo; ?>_<?php echo $data; ?>.csv';
        a.click();
        URL.revokeObjectURL(url);
    });
 
    // ── Mudança de período: ajusta visibilidade do campo data ─────────────────
    document.getElementById('periodo')?.addEventListener('change', function () {
        const dataField = document.getElementById('data');
        // Para período anual esconde o mês/dia; para diário mostra tudo — apenas UI hint
        dataField.type = this.value === 'anual' ? 'month' : 'date';
    });
</script>
 
<script src="../js/adminMEnu.js" defer></script>
</body>
</html>
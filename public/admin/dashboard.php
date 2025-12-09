
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
 
            <!-- ── Header + Summary cards ── -->
            <header class="dashboard-header">
                <?php include 'header-admin.php'; ?>
 
                <div class="dashboard-cards">
                    <!-- Total Pratos -->
                    <div class="card summary-card variant-1">
                        <div class="left">
                            <div class="card-title">Total Pratos</div>
                            <div class="card-value"><?php echo (int)$total_pratos; ?></div>
                        </div>
                        <div class="card-icon-wrap">
                            <i class="fas fa-utensils card-icon" aria-hidden="true"></i>
                        </div>
                    </div>
 
                    <!-- Média de Avaliação -->
                    <div class="card summary-card variant-2">
                        <div class="left">
                            <div class="card-title">Média Avaliação</div>
                            <div class="card-value"><?php echo $media_avaliacao; ?> <small style="font-size:13px;font-weight:500;">★</small></div>
                        </div>
                        <div class="card-icon-wrap">
                            <i class="fas fa-star card-icon" aria-hidden="true"></i>
                        </div>
                    </div>
 
                    <!-- Pedidos Hoje -->
                    <div class="card summary-card variant-3">
                        <div class="left">
                            <div class="card-title">Pedidos Hoje</div>
                            <div class="card-value"><?php echo (int)$pedidos_hoje; ?></div>
                        </div>
                        <div class="card-icon-wrap">
                            <i class="fas fa-receipt card-icon" aria-hidden="true"></i>
                        </div>
                    </div>
 
                    <!-- Receita Hoje -->
                    <div class="card summary-card variant-4">
                        <div class="left">
                            <div class="card-title">Receita Hoje</div>
                            <div class="card-value" style="font-size:16px;"><?php echo $receita_hoje; ?> Kz</div>
                        </div>
                        <div class="card-icon-wrap">
                            <i class="fas fa-coins card-icon" aria-hidden="true"></i>
                        </div>
                    </div>
 
                    <!-- Visitas Hoje -->
                    <div class="card summary-card variant-5">
                        <div class="left">
                            <div class="card-title">Visitas Hoje</div>
                            <div class="card-value"><?php echo (int)$visitas_hoje; ?></div>
                        </div>
                        <div class="card-icon-wrap">
                            <i class="fas fa-users card-icon" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
 
                <div class="dashboard-header-inner">
                    <div class="dh-left">
                        <h1><?php echo htmlspecialchars($titulo); ?></h1>
                        <p class="dh-sub"><?php echo htmlspecialchars($subtitulo); ?></p>
                    </div>
                </div>
            </header>
 
            <!-- ── Grid principal ── -->
            <section class="dash-grid">
 
                <!-- Coluna esquerda: Últimos pedidos -->
                <div class="dash-widget">
                    <h2><i class="fas fa-list-check"></i> Últimos Pedidos</h2>
                    <?php if (empty($ultimos_pedidos)): ?>
                        <p style="color:#6B7280;font-size:14px;text-align:center;padding:30px 0;">Nenhum pedido registado.</p>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Mesa</th>
                                        <th>Itens</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimos_pedidos as $pedido): ?>
                                        <tr>
                                            <td><strong><?php echo (int)$pedido['id']; ?></strong></td>
                                            <td>
                                                <i class="fas fa-chair" style="color:#6B7280;margin-right:4px;font-size:11px;"></i>
                                                Mesa <?php echo htmlspecialchars($pedido['mesa'] ?? '—'); ?>
                                            </td>
                                            <td><?php echo (int)$pedido['num_itens']; ?> item<?php echo $pedido['num_itens'] != 1 ? 's' : ''; ?></td>
                                            <td><strong><?php echo number_format($pedido['total'], 2, ',', '.'); ?> Kz</strong></td>
                                            <td><?php echo status_badge($pedido['status'], $status_labels, $status_colors); ?></td>
                                            <td style="color:#9CA3AF;font-size:12px;">
                                                <?php echo date('d/m H:i', strtotime($pedido['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top:14px;text-align:right;">
                            <a href="pedidos.php" style="font-size:13px;color:var(--laranja-primario);font-weight:600;">
                                Ver todos os pedidos <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
 
                <!-- Coluna direita -->
                <div style="display:flex;flex-direction:column;gap:16px;">
 
                    <!-- Top 5 Pratos -->
                    <div class="dash-widget">
                        <h2><i class="fas fa-trophy"></i> Top 5 Pratos</h2>
                        <?php if (empty($top_pratos)): ?>
                            <p style="color:#6B7280;font-size:14px;text-align:center;padding:20px 0;">Sem vendas registadas.</p>
                        <?php else: ?>
                            <?php foreach ($top_pratos as $i => $prato): ?>
                                <div class="top-prato-item">
                                    <div class="top-rank"><?php echo $i + 1; ?></div>
                                    <div class="top-prato-info">
                                        <div class="nome"><?php echo htmlspecialchars($prato['name']); ?></div>
                                        <div class="preco"><?php echo number_format($prato['price'], 2, ',', '.'); ?> Kz</div>
                                    </div>
                                    <div class="top-prato-right">
                                        <div class="vendidos"><?php echo (int)$prato['total_vendido']; ?>×</div>
                                        <div class="stars">
                                            <?php
                                            $r = round((float)$prato['avg_rating']);
                                            for ($s = 1; $s <= 5; $s++) echo $s <= $r ? '★' : '☆';
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
 
                    <!-- Resumo por Status -->
                    <div class="dash-widget">
                        <h2><i class="fas fa-chart-pie"></i> Pedidos por Estado</h2>
                        <?php
                        $total_geral = array_sum($por_status) ?: 1;
                        foreach ($status_labels as $key => $label):
                            $count = $por_status[$key] ?? 0;
                            $pct   = round($count / $total_geral * 100);
                            $color = $status_colors[$key];
                        ?>
                            <div class="status-row">
                                <span class="label"><?php echo $label; ?></span>
                                <div class="status-bar-wrap">
                                    <div class="status-bar"
                                         style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>;"></div>
                                </div>
                                <span class="count"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
 
                </div>
            </section>
 
        </main>
    </div>
</div>
 
<script src="../js/adminMEnu.js" defer></script>
</body>
</html>
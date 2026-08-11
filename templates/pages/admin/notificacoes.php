<!-- Header -->
<header class="dashboard-header dashboard-header-ad">
    <div class="dashboard-header-inner">
        <div class="dh-left">
            <h1><?= $escape($pageTitle); ?></h1>
            <p class="dh-sub"><?= $escape($pageSubtitle); ?></p>
        </div>
        <?php if (!empty($notificacoes_nao_lidas)): ?>
        <div class="dh-right">
            <form method="POST" action="/admin.php">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <input type="hidden" name="routa"  value="notificacoes">
                <input type="hidden" name="action" value="marcar_todas_lidas">
                <button type="submit" class="btn-secondary">
                    <i class="fas fa-check-double"></i> Marcar todas como lidas
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</header>

<!-- ── Filtros ── -->
<section class="admin-filters">
    <form method="GET" style="display:contents;">
        <input type="hidden" name="routa" value="notificacoes">
        <div class="filter-group">
            <div class="field">
                <label for="filtro_tipo">Tipo</label>
                <select name="filtro_tipo" id="filtro_tipo">
                    <option value="">Todos</option>
                    <option value="pedido"     <?= ($filtro_tipo === 'pedido')     ? 'selected' : ''; ?>>Pedido</option>
                    <option value="avaliacao"  <?= ($filtro_tipo === 'avaliacao')  ? 'selected' : ''; ?>>Avaliação</option>
                    <option value="sistema"    <?= ($filtro_tipo === 'sistema')    ? 'selected' : ''; ?>>Sistema</option>
                </select>
            </div>
            <div class="field">
                <label for="filtro_lida">Estado</label>
                <select name="filtro_lida" id="filtro_lida">
                    <option value="">Todas</option>
                    <option value="0" <?= ($filtro_lida === '0') ? 'selected' : ''; ?>>Não lidas</option>
                    <option value="1" <?= ($filtro_lida === '1') ? 'selected' : ''; ?>>Lidas</option>
                </select>
            </div>
            <button type="submit" id="btn-search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </div>
    </form>
</section>

<!-- ── KPI cards ── -->
<div class="relatorio-kpis">
    <div class="kpi-card">
        <div class="kpi-icon blue"><i class="fas fa-bell"></i></div>
        <div class="kpi-info">
            <div class="valor"><?= (int)$kpi_notif['total']; ?></div>
            <div class="rotulo">Total de notificações</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon red"><i class="fas fa-bell-slash"></i></div>
        <div class="kpi-info">
            <div class="valor"><?= (int)$kpi_notif['nao_lidas']; ?></div>
            <div class="rotulo">Não lidas</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon orange"><i class="fas fa-receipt"></i></div>
        <div class="kpi-info">
            <div class="valor"><?= (int)$kpi_notif['pedidos']; ?></div>
            <div class="rotulo">De pedidos</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon yellow"><i class="fas fa-star"></i></div>
        <div class="kpi-info">
            <div class="valor"><?= (int)$kpi_notif['avaliacoes']; ?></div>
            <div class="rotulo">De avaliações</div>
        </div>
    </div>
</div>

<!-- ── Lista de notificações ── -->
<div class="admin-table-section" style="margin-top:20px;">

    <?php if (empty($lista_notificacoes)): ?>
        <div class="empty-state">
            <i class="fas fa-bell-slash"></i>
            <p>Nenhuma notificação encontrada.</p>
        </div>
    <?php else: ?>

        <?php
        // Configuração visual por tipo
        $tipo_cfg = [
            'pedido'    => ['icon' => 'fas fa-receipt',       'color' => '#3B82F6'],
            'avaliacao' => ['icon' => 'fas fa-star',           'color' => '#F59E0B'],
            'sistema'   => ['icon' => 'fas fa-circle-info',   'color' => '#6B7280'],
        ];
        ?>

        <div class="notif-lista">
            <?php foreach ($lista_notificacoes as $n):
                $cfg   = $tipo_cfg[$n['tipo']] ?? $tipo_cfg['sistema'];
                $lida  = (bool)$n['lida'];
                $tempo = notif_tempo_relativo($n['created_at']);
            ?>
            <div class="notif-item <?= $lida ? 'lida' : 'nao-lida'; ?>"
                 style="display:flex;align-items:flex-start;gap:14px;
                        padding:16px 18px;border-bottom:1px solid #F3F4F6;
                        background:<?= $lida ? 'transparent' : '#F0F7FF'; ?>;
                        transition:background .2s;">

                <!-- Ícone -->
                <div style="width:40px;height:40px;border-radius:50%;flex-shrink:0;
                            background:<?= $cfg['color']; ?>20;
                            display:flex;align-items:center;justify-content:center;
                            color:<?= $cfg['color']; ?>;font-size:16px;">
                    <i class="<?= $cfg['icon']; ?>"></i>
                </div>

                <!-- Conteúdo -->
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span style="font-weight:<?= $lida ? '400' : '700'; ?>;color:#111;">
                            <?= $escape($n['titulo']); ?>
                        </span>
                        <?php if (!$lida): ?>
                            <span style="width:8px;height:8px;border-radius:50%;
                                         background:#3B82F6;flex-shrink:0;"></span>
                        <?php endif; ?>
                        <span style="background:<?= $cfg['color']; ?>20;color:<?= $cfg['color']; ?>;
                                     border:1px solid <?= $cfg['color']; ?>40;
                                     padding:1px 8px;border-radius:20px;font-size:11px;margin-left:auto;">
                            <?= $escape(ucfirst($n['tipo'])); ?>
                        </span>
                    </div>
                    <p style="margin:4px 0 0;font-size:13px;color:#6B7280;
                               white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= $escape($n['mensagem']); ?>
                    </p>
                    <span style="font-size:11px;color:#9CA3AF;margin-top:4px;display:block;">
                        <i class="fas fa-clock" style="margin-right:4px;"></i><?= $escape($tempo); ?>
                    </span>
                </div>

                <!-- Acções -->
                <div style="display:flex;flex-direction:column;gap:6px;flex-shrink:0;">
                    <?php if (!$lida): ?>
                    <form method="POST" action="/admin.php">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="routa"         value="notificacoes">
                        <input type="hidden" name="action"        value="marcar_lida">
                        <input type="hidden" name="notificacao_id" value="<?= (int)$n['id']; ?>">
                        <button type="submit" class="btn-icon" title="Marcar como lida"
                                style="color:#3B82F6;">
                            <i class="fas fa-check"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" action="/admin.php">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="routa"         value="notificacoes">
                        <input type="hidden" name="action"        value="apagar_notificacao">
                        <input type="hidden" name="notificacao_id" value="<?= (int)$n['id']; ?>">
                        <button type="submit" class="btn-icon" title="Apagar"
                                style="color:#E53935;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginação simples -->
        <?php if ($notif_paginas > 1): ?>
        <div style="display:flex;gap:6px;justify-content:center;padding:20px 0;">
            <?php for ($p = 1; $p <= $notif_paginas; $p++): ?>
                <a href="/admin.php?routa=notificacoes&pagina=<?= $p; ?>&filtro_tipo=<?= $escape($filtro_tipo); ?>&filtro_lida=<?= $escape($filtro_lida); ?>"
                   style="padding:6px 12px;border-radius:6px;font-size:13px;font-weight:600;
                          background:<?= $p === $notif_pagina_atual ? 'var(--laranja)' : '#F3F4F6'; ?>;
                          color:<?= $p === $notif_pagina_atual ? '#fff' : '#374151'; ?>;
                          text-decoration:none;">
                    <?= $p; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
// Auto-refresh discreto a cada 60 s para novas notificações
setTimeout(() => window.location.reload(), 60_000);
</script>

<!-- Header -->
<header class="dashboard-header dashboard-header-ad">
    <div class="dashboard-header-inner">
        <div class="dh-left">
            <h1><?= $escape($pageTitle); ?></h1>
            <p class="dh-sub"><?= $escape($pageSubtitle); ?></p>
        </div>
        <?php if ($auth['profile'] === 'admin'): ?>
        <div class="dh-right">
            <button class="btn-primary" id="btn-novo-usuario">
                <i class="fa-solid fa-user-plus"></i> Novo Utilizador
            </button>
        </div>
        <?php endif; ?>
    </div>
</header>

<!-- ── Filtros ── -->
<section class="admin-filters">
    <form method="GET" style="display:contents;">
        <input type="hidden" name="routa" value="usuarios">
        <div class="filter-group">
            <div class="field">
                <label for="filtro_role">Função</label>
                <select name="filtro_role" id="filtro_role">
                    <option value="">Todas</option>
                    <option value="manager"  <?= ($filtro_role === 'manager')  ? 'selected' : ''; ?>>Manager</option>
                    <option value="staff"    <?= ($filtro_role === 'staff')    ? 'selected' : ''; ?>>Staff</option>
                </select>
            </div>
            <div class="field">
                <label for="filtro_q">Pesquisar</label>
                <input type="text" name="filtro_q" id="filtro_q"
                       placeholder="Nome ou e-mail…"
                       value="<?= $escape($filtro_q); ?>">
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
        <div class="kpi-icon blue"><i class="fas fa-users"></i></div>
        <div class="kpi-info">
            <div class="valor"><?= (int)$kpi_usuarios['total']; ?></div>
            <div class="rotulo">Total de utilizadores</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon green"><i class="fas fa-user-tie"></i></div>
        <div class="kpi-info">
            <div class="valor"><?= (int)$kpi_usuarios['managers']; ?></div>
            <div class="rotulo">Managers</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon orange"><i class="fas fa-user-clock"></i></div>
        <div class="kpi-info">
            <div class="valor"><?= (int)$kpi_usuarios['staff']; ?></div>
            <div class="rotulo">Staff</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon yellow"><i class="fas fa-calendar-plus"></i></div>
        <div class="kpi-info">
            <div class="valor"><?= (int)$kpi_usuarios['novos_mes']; ?></div>
            <div class="rotulo">Novos este mês</div>
        </div>
    </div>
</div>

<!-- ── Tabela de utilizadores ── -->
<div class="admin-table-section" style="margin-top:20px;">

    <?php if (empty($lista_usuarios)): ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <p>Nenhum utilizador encontrado.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="rel-table" id="tabela-usuarios">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Função</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_usuarios as $u): ?>
                        <?php
                            $role_label  = ['manager' => 'Manager', 'staff' => 'Staff'][$u['role']] ?? $u['role'];
                            $role_color  = ['manager' => '#3B82F6', 'staff' => '#F59E0B'][$u['role']] ?? '#888';
                            $is_proprio  = (int)$u['id'] === (int)$auth['id'];
                        ?>
                        <tr>
                            <td style="color:#9CA3AF;"><?= (int)$u['id']; ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:34px;height:34px;border-radius:50%;background:<?= $role_color; ?>22;
                                                display:flex;align-items:center;justify-content:center;
                                                font-weight:700;color:<?= $role_color; ?>;font-size:14px;flex-shrink:0;">
                                        <?= mb_strtoupper(mb_substr($u['name'], 0, 1, 'UTF-8'), 'UTF-8'); ?>
                                    </div>
                                    <strong><?= $escape($u['name']); ?></strong>
                                    <?php if ($is_proprio): ?>
                                        <span style="font-size:11px;background:#10B98120;color:#10B981;
                                                     padding:2px 7px;border-radius:20px;">Você</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="color:#6B7280;"><?= $escape($u['email']); ?></td>
                            <td>
                                <span style="background:<?= $role_color; ?>20;color:<?= $role_color; ?>;
                                             border:1px solid <?= $role_color; ?>40;
                                             padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600;">
                                    <?= $escape($role_label); ?>
                                </span>
                            </td>
                            <td style="color:#9CA3AF;font-size:12px;">
                                <?= date('d/m/Y', strtotime($u['created_at'])); ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <button class="btn-icon btn-editar-usuario"
                                            data-id="<?= (int)$u['id']; ?>"
                                            data-name="<?= $escape($u['name']); ?>"
                                            data-email="<?= $escape($u['email']); ?>"
                                            data-role="<?= $escape($u['role']); ?>"
                                            title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <?php if (!$is_proprio): ?>
                                    <button class="btn-icon btn-apagar-usuario"
                                            data-id="<?= (int)$u['id']; ?>"
                                            data-name="<?= $escape($u['name']); ?>"
                                            title="Apagar"
                                            style="color:#E53935;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:var(--laranja-claro);">
                        <td colspan="6" style="font-weight:700;padding:12px 14px;">
                            Total: <?= count($lista_usuarios); ?> utilizador(es)
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ── Modal: Novo / Editar utilizador ── -->
<div class="modal-overlay" id="modal-usuario" style="display:none;">
    <div class="modal" style="max-width:460px;width:100%;">
        <div class="modal-header">
            <h2 id="modal-usuario-titulo">Novo Utilizador</h2>
            <button class="modal-close" id="modal-usuario-fechar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" action="/admin.php" id="form-usuario">
                <input type="hidden" name="routa"  value="usuarios">
                <input type="hidden" name="action" value="salvar_usuario" id="usuario-action">
                <input type="hidden" name="usuario_id" id="usuario-id" value="">

                <div class="field" style="margin-bottom:16px;">
                    <label for="u-name">Nome completo</label>
                    <input type="text" name="name" id="u-name" required
                           placeholder="Ex: João Silva">
                </div>
                <div class="field" style="margin-bottom:16px;">
                    <label for="u-email">E-mail</label>
                    <input type="email" name="email" id="u-email" required
                           placeholder="email@exemplo.com">
                </div>
                <div class="field" style="margin-bottom:16px;">
                    <label for="u-role">Função</label>
                    <select name="role" id="u-role" required>
                        <option value="staff">Staff</option>
                        <option value="manager">Manager</option>
                    </select>
                </div>
                <div class="field" style="margin-bottom:16px;" id="campo-senha">
                    <label for="u-password">
                        Senha <span id="senha-hint" style="color:#9CA3AF;font-size:12px;">(obrigatória)</span>
                    </label>
                    <input type="password" name="password" id="u-password"
                           placeholder="Mínimo 8 caracteres">
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" class="btn-secondary" id="modal-usuario-cancelar">Cancelar</button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: Confirmar apagar ── -->
<div class="modal-overlay" id="modal-apagar-usuario" style="display:none;">
    <div class="modal" style="max-width:400px;width:100%;">
        <div class="modal-header">
            <h2>Confirmar remoção</h2>
            <button class="modal-close" id="modal-apagar-fechar"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p>Tem a certeza que deseja remover o utilizador <strong id="apagar-nome"></strong>?
               Esta acção não pode ser desfeita.</p>
            <form method="POST" action="/admin.php" style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <input type="hidden" name="routa"      value="usuarios">
                <input type="hidden" name="action"     value="apagar_usuario">
                <input type="hidden" name="usuario_id" id="apagar-id" value="">
                <button type="button" class="btn-secondary" id="modal-apagar-cancelar">Cancelar</button>
                <button type="submit" class="btn-danger">
                    <i class="fas fa-trash"></i> Remover
                </button>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    // ── Abrir modal novo ──────────────────────────────────────────────────────
    document.getElementById('btn-novo-usuario')?.addEventListener('click', () => {
        document.getElementById('modal-usuario-titulo').textContent = 'Novo Utilizador';
        document.getElementById('usuario-action').value  = 'criar_usuario';
        document.getElementById('usuario-id').value      = '';
        document.getElementById('u-name').value          = '';
        document.getElementById('u-email').value         = '';
        document.getElementById('u-role').value          = 'staff';
        document.getElementById('u-password').required   = true;
        document.getElementById('senha-hint').textContent = '(obrigatória)';
        document.getElementById('modal-usuario').style.display = 'flex';
    });

    // ── Abrir modal editar ────────────────────────────────────────────────────
    document.querySelectorAll('.btn-editar-usuario').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('modal-usuario-titulo').textContent = 'Editar Utilizador';
            document.getElementById('usuario-action').value  = 'editar_usuario';
            document.getElementById('usuario-id').value      = btn.dataset.id;
            document.getElementById('u-name').value          = btn.dataset.name;
            document.getElementById('u-email').value         = btn.dataset.email;
            document.getElementById('u-role').value          = btn.dataset.role;
            document.getElementById('u-password').required   = false;
            document.getElementById('senha-hint').textContent = '(deixe em branco para manter)';
            document.getElementById('modal-usuario').style.display = 'flex';
        });
    });

    // ── Fechar modal utilizador ───────────────────────────────────────────────
    ['modal-usuario-fechar','modal-usuario-cancelar'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', () => {
            document.getElementById('modal-usuario').style.display = 'none';
        });
    });

    // ── Abrir modal apagar ────────────────────────────────────────────────────
    document.querySelectorAll('.btn-apagar-usuario').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('apagar-nome').textContent = btn.dataset.name;
            document.getElementById('apagar-id').value         = btn.dataset.id;
            document.getElementById('modal-apagar-usuario').style.display = 'flex';
        });
    });

    // ── Fechar modal apagar ───────────────────────────────────────────────────
    ['modal-apagar-fechar','modal-apagar-cancelar'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', () => {
            document.getElementById('modal-apagar-usuario').style.display = 'none';
        });
    });

    // ── Fechar ao clicar no overlay ───────────────────────────────────────────
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.style.display = 'none';
        });
    });
})();
</script>

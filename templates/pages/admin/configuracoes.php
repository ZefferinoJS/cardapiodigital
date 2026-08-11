<!-- Header -->
<header class="dashboard-header dashboard-header-ad">
    <div class="dashboard-header-inner">
        <div class="dh-left">
            <h1><?= $escape($pageTitle); ?></h1>
            <p class="dh-sub"><?= $escape($pageSubtitle); ?></p>
        </div>
    </div>
</header>

<?php if (!empty($config_sucesso)): ?>
<div class="alert alert-success" style="margin:0 0 20px;padding:12px 18px;background:#D1FAE5;
     color:#065F46;border-radius:8px;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-check-circle"></i>
    <?= $escape($config_sucesso); ?>
</div>
<?php endif; ?>

<?php if (!empty($config_erro)): ?>
<div class="alert alert-error" style="margin:0 0 20px;padding:12px 18px;background:#FEE2E2;
     color:#991B1B;border-radius:8px;display:flex;align-items:center;gap:10px;">
    <i class="fas fa-exclamation-triangle"></i>
    <?= $escape($config_erro); ?>
</div>
<?php endif; ?>

<!-- ── Tabs de configuração ── -->
<div class="admin-table-section" style="margin-top:0;">

    <div class="rel-tabs">
        <button class="rel-tab active" data-tab="restaurante">
            <i class="fas fa-store"></i> Restaurante
        </button>
        <button class="rel-tab" data-tab="mesas">
            <i class="fas fa-chair"></i> Mesas
        </button>
        <button class="rel-tab" data-tab="sistema">
            <i class="fas fa-sliders"></i> Sistema
        </button>
    </div>

    <!-- ── Painel: Restaurante ── -->
    <div class="section-panel active" id="panel-restaurante">
        <form method="POST" action="/admin.php" style="max-width:600px;">
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="routa"  value="configuracoes">
            <input type="hidden" name="action" value="salvar_restaurante">

            <div style="display:grid;gap:18px;padding:24px 0;">

                <div class="field">
                    <label for="cfg-nome">Nome do restaurante</label>
                    <input type="text" name="cfg_nome" id="cfg-nome"
                           value="<?= $escape($config['nome'] ?? ''); ?>"
                           placeholder="Ex: Minha Lanchonete" required>
                </div>

                <div class="field">
                    <label for="cfg-slug">Slug (URL pública)</label>
                    <input type="text" name="cfg_slug" id="cfg-slug"
                           value="<?= $escape($config['slug'] ?? ''); ?>"
                           placeholder="minha-lanchonete" required
                           pattern="[a-z0-9\-]+"
                           title="Apenas letras minúsculas, números e hífens">
                    <span style="font-size:12px;color:#9CA3AF;">
                        Usado em: <code>/<?= $escape($config['slug'] ?? 'minha-lanchonete'); ?></code>
                    </span>
                </div>

                <div class="field">
                    <label for="cfg-timezone">Fuso horário</label>
                    <select name="cfg_timezone" id="cfg-timezone">
                        <?php
                        $timezones = [
                            'UTC'                     => 'UTC',
                            'Africa/Luanda'           => 'África / Luanda (WAT +01:00)',
                            'Africa/Johannesburg'     => 'África / Joanesburgo (SAST +02:00)',
                            'Atlantic/Cape_Verde'     => 'Atlântico / Cabo Verde (CVT -01:00)',
                            'Europe/Lisbon'           => 'Europa / Lisboa (WET/WEST)',
                            'America/Sao_Paulo'       => 'América / São Paulo (BRT -03:00)',
                        ];
                        foreach ($timezones as $tz_val => $tz_label):
                        ?>
                        <option value="<?= $escape($tz_val); ?>"
                                <?= ($config['timezone'] ?? 'UTC') === $tz_val ? 'selected' : ''; ?>>
                            <?= $escape($tz_label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex;gap:10px;padding-top:8px;">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar alterações
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ── Painel: Mesas ── -->
    <div class="section-panel" id="panel-mesas">

        <div style="display:flex;justify-content:flex-end;padding:16px 0 8px;">
            <button class="btn-primary" id="btn-nova-mesa">
                <i class="fas fa-plus"></i> Nova Mesa
            </button>
        </div>

        <?php if (empty($lista_mesas)): ?>
            <div class="empty-state">
                <i class="fas fa-chair"></i>
                <p>Nenhuma mesa registada.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="rel-table" id="tabela-mesas">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Número</th>
                            <th>Descrição</th>
                            <th>QR Code</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_mesas as $mesa): ?>
                        <tr>
                            <td style="color:#9CA3AF;"><?= (int)$mesa['id']; ?></td>
                            <td><strong>Mesa <?= $escape($mesa['number']); ?></strong></td>
                            <td style="color:#6B7280;"><?= $escape($mesa['description'] ?? '—'); ?></td>
                            <td>
                                <code style="font-size:11px;background:#F3F4F6;
                                             padding:2px 6px;border-radius:4px;">
                                    <?= $escape($mesa['qr_code'] ?? '—'); ?>
                                </code>
                            </td>
                            <td>
                                <?php if ($mesa['active']): ?>
                                    <span style="background:#D1FAE5;color:#065F46;border:1px solid #6EE7B7;
                                                 padding:2px 10px;border-radius:20px;font-size:12px;">Activa</span>
                                <?php else: ?>
                                    <span style="background:#F3F4F6;color:#6B7280;border:1px solid #D1D5DB;
                                                 padding:2px 10px;border-radius:20px;font-size:12px;">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <button class="btn-icon btn-editar-mesa"
                                            data-id="<?= (int)$mesa['id']; ?>"
                                            data-number="<?= $escape($mesa['number']); ?>"
                                            data-description="<?= $escape($mesa['description'] ?? ''); ?>"
                                            data-active="<?= (int)$mesa['active']; ?>"
                                            title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="btn-icon btn-apagar-mesa"
                                            data-id="<?= (int)$mesa['id']; ?>"
                                            data-number="<?= $escape($mesa['number']); ?>"
                                            title="Apagar" style="color:#E53935;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--laranja-claro);">
                            <td colspan="6" style="font-weight:700;padding:12px 14px;">
                                Total: <?= count($lista_mesas); ?> mesa(s)
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Painel: Sistema ── -->
    <div class="section-panel" id="panel-sistema">
        <form method="POST" action="/admin.php" style="max-width:600px;">
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="routa"  value="configuracoes">
            <input type="hidden" name="action" value="salvar_sistema">

            <div style="display:grid;gap:18px;padding:24px 0;">

                <!-- Alterar senha -->
                <fieldset style="border:1px solid #E5E7EB;border-radius:10px;padding:20px;">
                    <legend style="font-weight:700;padding:0 8px;color:#374151;">
                        <i class="fas fa-lock" style="margin-right:6px;"></i>Alterar senha
                    </legend>
                    <div style="display:grid;gap:14px;margin-top:8px;">
                        <div class="field">
                            <label for="cfg-senha-atual">Senha actual</label>
                            <input type="password" name="senha_atual" id="cfg-senha-atual"
                                   placeholder="A sua senha actual">
                        </div>
                        <div class="field">
                            <label for="cfg-senha-nova">Nova senha</label>
                            <input type="password" name="senha_nova" id="cfg-senha-nova"
                                   placeholder="Mínimo 8 caracteres" minlength="8">
                        </div>
                        <div class="field">
                            <label for="cfg-senha-confirmar">Confirmar nova senha</label>
                            <input type="password" name="senha_confirmar" id="cfg-senha-confirmar"
                                   placeholder="Repetir nova senha">
                        </div>
                    </div>
                </fieldset>

                <!-- Preferências do sistema -->
                <fieldset style="border:1px solid #E5E7EB;border-radius:10px;padding:20px;">
                    <legend style="font-weight:700;padding:0 8px;color:#374151;">
                        <i class="fas fa-sliders" style="margin-right:6px;"></i>Preferências
                    </legend>
                    <div style="display:grid;gap:14px;margin-top:8px;">
                        <label style="display:flex;align-items:center;gap:12px;cursor:pointer;">
                            <input type="checkbox" name="notif_novos_pedidos"
                                   style="width:16px;height:16px;"
                                   <?= !empty($config['notif_novos_pedidos']) ? 'checked' : ''; ?>>
                            <span>Notificar novos pedidos</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:12px;cursor:pointer;">
                            <input type="checkbox" name="notif_avaliacoes"
                                   style="width:16px;height:16px;"
                                   <?= !empty($config['notif_avaliacoes']) ? 'checked' : ''; ?>>
                            <span>Notificar novas avaliações</span>
                        </label>
                    </div>
                </fieldset>

                <!-- Zona de perigo -->
                <fieldset style="border:1px solid #FCA5A5;border-radius:10px;padding:20px;">
                    <legend style="font-weight:700;padding:0 8px;color:#DC2626;">
                        <i class="fas fa-triangle-exclamation" style="margin-right:6px;"></i>Zona de perigo
                    </legend>
                    <div style="margin-top:12px;">
                        <p style="font-size:13px;color:#6B7280;margin:0 0 14px;">
                            Estas acções são irreversíveis. Proceda com cuidado.
                        </p>
                        <button type="button" class="btn-danger" id="btn-limpar-pedidos"
                                style="background:#FEF2F2;color:#DC2626;border:1px solid #FCA5A5;">
                            <i class="fas fa-trash-can"></i> Limpar histórico de pedidos
                        </button>
                    </div>
                </fieldset>

                <div style="display:flex;gap:10px;padding-top:4px;">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar alterações
                    </button>
                </div>
            </div>
        </form>
    </div>

</div><!-- /.admin-table-section -->

<!-- ── Modal: Nova / Editar Mesa ── -->
<div class="modal-overlay" id="modal-mesa" style="display:none;">
    <div class="modal" style="max-width:420px;width:100%;">
        <div class="modal-header">
            <h2 id="modal-mesa-titulo">Nova Mesa</h2>
            <button class="modal-close" id="modal-mesa-fechar"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form method="POST" action="/admin.php">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <input type="hidden" name="routa"   value="configuracoes">
                <input type="hidden" name="action"  value="salvar_mesa" id="mesa-action">
                <input type="hidden" name="mesa_id" id="mesa-id" value="">
                <div style="display:grid;gap:14px;">
                    <div class="field">
                        <label for="mesa-number">Número / Nome</label>
                        <input type="text" name="number" id="mesa-number" required
                               placeholder="Ex: 1, 2, VIP-A">
                    </div>
                    <div class="field">
                        <label for="mesa-description">Descrição</label>
                        <input type="text" name="description" id="mesa-description"
                               placeholder="Ex: Próximo à janela">
                    </div>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="active" id="mesa-active"
                               style="width:16px;height:16px;" checked>
                        <span>Mesa activa</span>
                    </label>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" class="btn-secondary" id="modal-mesa-cancelar">Cancelar</button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: Confirmar apagar mesa ── -->
<div class="modal-overlay" id="modal-apagar-mesa" style="display:none;">
    <div class="modal" style="max-width:380px;width:100%;">
        <div class="modal-header">
            <h2>Remover mesa</h2>
            <button class="modal-close" id="modal-apagar-mesa-fechar"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p>Tem a certeza que deseja remover a <strong id="apagar-mesa-nome"></strong>?</p>
            <form method="POST" action="/admin.php"
                  style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                  <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <input type="hidden" name="routa"   value="configuracoes">
                <input type="hidden" name="action"  value="apagar_mesa">
                <input type="hidden" name="mesa_id" id="apagar-mesa-id" value="">
                <button type="button" class="btn-secondary" id="modal-apagar-mesa-cancelar">Cancelar</button>
                <button type="submit" class="btn-danger">
                    <i class="fas fa-trash"></i> Remover
                </button>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    // ── Tabs ──────────────────────────────────────────────────────────────────
    document.querySelectorAll('.rel-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.rel-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
        });
    });

    // ── Nova mesa ─────────────────────────────────────────────────────────────
    document.getElementById('btn-nova-mesa')?.addEventListener('click', () => {
        document.getElementById('modal-mesa-titulo').textContent = 'Nova Mesa';
        document.getElementById('mesa-action').value  = 'criar_mesa';
        document.getElementById('mesa-id').value      = '';
        document.getElementById('mesa-number').value  = '';
        document.getElementById('mesa-description').value = '';
        document.getElementById('mesa-active').checked = true;
        document.getElementById('modal-mesa').style.display = 'flex';
    });

    // ── Editar mesa ───────────────────────────────────────────────────────────
    document.querySelectorAll('.btn-editar-mesa').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('modal-mesa-titulo').textContent = 'Editar Mesa';
            document.getElementById('mesa-action').value      = 'editar_mesa';
            document.getElementById('mesa-id').value          = btn.dataset.id;
            document.getElementById('mesa-number').value      = btn.dataset.number;
            document.getElementById('mesa-description').value = btn.dataset.description;
            document.getElementById('mesa-active').checked    = btn.dataset.active === '1';
            document.getElementById('modal-mesa').style.display = 'flex';
        });
    });

    // ── Apagar mesa ───────────────────────────────────────────────────────────
    document.querySelectorAll('.btn-apagar-mesa').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('apagar-mesa-nome').textContent = 'Mesa ' + btn.dataset.number;
            document.getElementById('apagar-mesa-id').value = btn.dataset.id;
            document.getElementById('modal-apagar-mesa').style.display = 'flex';
        });
    });

    // ── Fechar modais ─────────────────────────────────────────────────────────
    const fechos = {
        'modal-mesa-fechar':        'modal-mesa',
        'modal-mesa-cancelar':      'modal-mesa',
        'modal-apagar-mesa-fechar': 'modal-apagar-mesa',
        'modal-apagar-mesa-cancelar': 'modal-apagar-mesa',
    };
    Object.entries(fechos).forEach(([btnId, modalId]) => {
        document.getElementById(btnId)?.addEventListener('click', () => {
            document.getElementById(modalId).style.display = 'none';
        });
    });

    // ── Fechar ao clicar no overlay ───────────────────────────────────────────
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.style.display = 'none';
        });
    });

    // ── Validação de senha ────────────────────────────────────────────────────
    const formSistema = document.querySelector('form [name="senha_nova"]')?.closest('form');
    formSistema?.addEventListener('submit', e => {
        const nova      = document.getElementById('cfg-senha-nova').value;
        const confirmar = document.getElementById('cfg-senha-confirmar').value;
        if (nova && nova !== confirmar) {
            e.preventDefault();
            alert('As senhas não coincidem.');
        }
    });

    // ── Botão zona de perigo ──────────────────────────────────────────────────
    document.getElementById('btn-limpar-pedidos')?.addEventListener('click', () => {
        if (confirm('Tem a certeza? Esta acção apagará TODO o histórico de pedidos permanentemente.')) {
            const f = document.createElement('form');
            f.method = 'POST'; f.action = '/admin.php';
            f.innerHTML = '<input type="hidden" name="routa" value="configuracoes">'
                        + '<input type="hidden" name="action" value="limpar_pedidos">';
            document.body.appendChild(f);
            f.submit();
        }
    });

    // ── Auto-gerar slug a partir do nome ─────────────────────────────────────
    document.getElementById('cfg-nome')?.addEventListener('input', function () {
        const slug = this.value.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        const slugField = document.getElementById('cfg-slug');
        if (slugField && !slugField.dataset.manual) slugField.value = slug;
    });
    document.getElementById('cfg-slug')?.addEventListener('input', function () {
        this.dataset.manual = '1';
    });
})();
</script>

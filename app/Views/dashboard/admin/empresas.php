<?php
$statusLabels = [
    'pending' => 'Pendente',
    'changes_requested' => 'Correção solicitada',
    'active' => 'Ativa',
    'suspended' => 'Suspensa',
    'rejected' => 'Rejeitada',
    'inactive' => 'Inativa',
];
$statusClasses = [
    'pending' => 'pending',
    'changes_requested' => 'review',
    'active' => 'active',
    'suspended' => 'suspended',
    'rejected' => 'rejected',
    'inactive' => 'inactive',
];
$counts = $companyStatusCounts ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>RE.SOURCE — Empresas</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/admin-dashboard.css'), ENT_QUOTES, 'UTF-8') ?>" />
<link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/empresas.css'), ENT_QUOTES, 'UTF-8') ?>" />
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/flash.css'), ENT_QUOTES, 'UTF-8') ?>" />
</head>
<body>

<?php require __DIR__ . '/../../components/flash.php'; ?>

<header class="site-header">
    <?php require_once __DIR__ . '/../../components/topbar.php'; ?>
    <?php require_once __DIR__ . '/../../components/navbar.php'; ?>
</header>

<main class="admin-main">
  <div class="admin-content">
    <section class="dash-hero">
      <div class="hero-left">
        <span class="hero-breadcrumb"><i data-lucide="building-2"></i> Gestão • Empresas</span>
        <h1 class="hero-title">Empresas cadastradas</h1>
        <p class="hero-subtitle">Analise cadastros, solicite correções e controle o acesso de cada empresa.</p>
        <div class="hero-meta">
          <span class="hero-meta-item"><i data-lucide="check-circle-2"></i> <?= (int) ($counts['active'] ?? 0) ?> ativas</span>
          <span class="hero-meta-item"><i data-lucide="clock"></i> <?= (int) ($counts['pending'] ?? 0) ?> pendentes</span>
          <span class="hero-meta-item"><i data-lucide="file-warning"></i> <?= (int) ($counts['changes_requested'] ?? 0) ?> em correção</span>
          <span class="hero-meta-item"><i data-lucide="ban"></i> <?= (int) ($counts['suspended'] ?? 0) ?> suspensas</span>
        </div>
      </div>
      <div class="hero-right">
        <div class="hero-badge-num"><?= array_sum($counts) ?></div>
        <div class="hero-badge-label">Total</div>
        <div class="hero-badge-sub">Empresas no sistema</div>
      </div>
    </section>

    <section class="company-filters" aria-label="Filtros de empresas">
      <form method="GET" action="<?= htmlspecialchars(app_url('/admin/empresas'), ENT_QUOTES, 'UTF-8') ?>">
        <label>
          <span>Buscar</span>
          <input type="search" name="q" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome, CNPJ ou e-mail" />
        </label>
        <label>
          <span>Status</span>
          <select name="status">
            <option value="">Todos os status</option>
            <?php foreach ($statusLabels as $value => $label): ?>
              <option value="<?= $value ?>" <?= ($statusFilter ?? '') === $value ? 'selected' : '' ?>>
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?> (<?= (int) ($counts[$value] ?? 0) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <button class="btn-primary" type="submit"><i data-lucide="search"></i> Filtrar</button>
        <?php if (($statusFilter ?? '') !== '' || ($search ?? '') !== ''): ?>
          <a class="filter-clear" href="<?= htmlspecialchars(app_url('/admin/empresas'), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
        <?php endif; ?>
      </form>
    </section>

    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Lista de empresas</div>
          <div class="card-sub"><?= count($empresas ?? []) ?> resultado(s) nesta consulta</div>
        </div>
      </div>
      <div class="table-wrapper">
        <table class="admin-table" id="empresasTable">
          <thead>
            <tr>
              <th>Empresa</th>
              <th>CNPJ / contato</th>
              <th>Localização</th>
              <th>Responsável</th>
              <th>Status</th>
              <th style="text-align:right">Ações</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($empresas)): ?>
            <tr><td colspan="6" class="empty-table">Nenhuma empresa encontrada com esses filtros.</td></tr>
          <?php else: ?>
            <?php foreach ($empresas as $company): ?>
              <?php
              $companyName = $company['nome_fantasia'] ?: $company['razao_social'];
              $words = preg_split('/\s+/', trim($companyName)) ?: [];
              $initials = '';
              foreach (array_slice($words, 0, 2) as $word) {
                  $initials .= mb_strtoupper(mb_substr($word, 0, 1));
              }
              $status = (string) $company['status'];
              ?>
              <tr>
                <td>
                  <div class="company-cell">
                    <div class="company-initials"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                    <div>
                      <div class="company-name"><?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="company-city"><?= htmlspecialchars($company['razao_social'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <strong><?= htmlspecialchars($company['cnpj'], ENT_QUOTES, 'UTF-8') ?></strong>
                  <small class="table-secondary"><?= htmlspecialchars($company['email'], ENT_QUOTES, 'UTF-8') ?></small>
                </td>
                <td><?= htmlspecialchars(trim(($company['city'] ?? '') . ' / ' . ($company['state'] ?? ''), ' /'), ENT_QUOTES, 'UTF-8') ?: '-' ?></td>
                <td><?= htmlspecialchars($company['responsible_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <span class="status-badge <?= $statusClasses[$status] ?? 'inactive' ?>">
                    <?= htmlspecialchars($statusLabels[$status] ?? ucfirst($status), ENT_QUOTES, 'UTF-8') ?>
                  </span>
                  <?php if (!empty($company['review_notes'])): ?>
                    <button
                      class="review-note js-review-note"
                      type="button"
                      title="Ver motivo"
                      data-company-name="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>"
                      data-review-note="<?= htmlspecialchars($company['review_notes'], ENT_QUOTES, 'UTF-8') ?>"
                    >
                      <i data-lucide="message-square-text"></i> Ver motivo
                    </button>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="action-buttons" style="justify-content:flex-end">
                    <?php if ($status === 'pending' && AdminAuth::can('company_approve')): ?>
                      <form method="POST" action="<?= htmlspecialchars(app_url('/admin/empresas/aprovar'), ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Aprovar esta empresa e liberar o acesso completo?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="company_id" value="<?= (int) $company['id'] ?>" />
                        <button class="btn-icon success" type="submit" title="Aprovar empresa"><i data-lucide="check"></i></button>
                      </form>
                      <button class="btn-icon warning js-reason-action" type="button"
                        data-company-id="<?= (int) $company['id'] ?>"
                        data-company-name="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>"
                        data-action="<?= htmlspecialchars(app_url('/admin/empresas/solicitar-correcao'), ENT_QUOTES, 'UTF-8') ?>"
                        data-title="Solicitar correção" data-label="Enviar solicitação"
                        title="Solicitar correção dos dados"><i data-lucide="file-pen-line"></i></button>
                      <button class="btn-icon danger js-reason-action" type="button"
                        data-company-id="<?= (int) $company['id'] ?>"
                        data-company-name="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>"
                        data-action="<?= htmlspecialchars(app_url('/admin/empresas/rejeitar'), ENT_QUOTES, 'UTF-8') ?>"
                        data-title="Rejeitar cadastro" data-label="Rejeitar"
                        title="Rejeitar empresa"><i data-lucide="x"></i></button>
                    <?php elseif ($status === 'changes_requested' && AdminAuth::can('company_approve')): ?>
                      <button class="btn-icon danger js-reason-action" type="button"
                        data-company-id="<?= (int) $company['id'] ?>"
                        data-company-name="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>"
                        data-action="<?= htmlspecialchars(app_url('/admin/empresas/rejeitar'), ENT_QUOTES, 'UTF-8') ?>"
                        data-title="Rejeitar cadastro" data-label="Rejeitar"
                        title="Rejeitar empresa"><i data-lucide="x"></i></button>
                    <?php elseif ($status === 'active' && AdminAuth::can('company_suspend')): ?>
                      <button class="btn-icon danger js-reason-action" type="button"
                        data-company-id="<?= (int) $company['id'] ?>"
                        data-company-name="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>"
                        data-action="<?= htmlspecialchars(app_url('/admin/empresas/suspender'), ENT_QUOTES, 'UTF-8') ?>"
                        data-title="Suspender empresa" data-label="Suspender"
                        title="Suspender empresa"><i data-lucide="ban"></i></button>
                    <?php elseif ($status === 'suspended' && AdminAuth::can('company_suspend')): ?>
                      <form method="POST" action="<?= htmlspecialchars(app_url('/admin/empresas/reativar'), ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Reativar esta empresa e restaurar o acesso?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="company_id" value="<?= (int) $company['id'] ?>" />
                        <button class="btn-icon success" type="submit" title="Reativar empresa"><i data-lucide="rotate-ccw"></i></button>
                      </form>
                    <?php else: ?>
                      <span class="no-actions" title="Nenhuma ação disponível"><i data-lucide="minus"></i></span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<dialog class="company-action-dialog" id="companyActionDialog">
  <form method="POST" id="companyActionForm">
    <?= csrf_field() ?>
    <input type="hidden" name="company_id" id="dialogCompanyId" />
    <div class="dialog-icon"><i data-lucide="shield-alert"></i></div>
    <h2 id="dialogTitle">Confirmar ação</h2>
    <p>Empresa: <strong id="dialogCompanyName"></strong></p>
    <label for="dialogReason">Motivo da decisão</label>
    <textarea id="dialogReason" name="reason" minlength="10" maxlength="1000" required placeholder="Explique claramente o que precisa ser corrigido ou o motivo da decisão."></textarea>
    <small>Mínimo de 10 e máximo de 1.000 caracteres.</small>
    <div class="dialog-actions">
      <button class="dialog-cancel" type="button" id="dialogCancel">Cancelar</button>
      <button class="btn-primary" type="submit" id="dialogSubmit">Confirmar</button>
    </div>
  </form>
</dialog>

<dialog class="company-action-dialog" id="reviewNoteDialog">
  <div class="review-note-dialog-content">
    <div class="dialog-icon"><i data-lucide="message-square-text"></i></div>
    <h2>Motivo registrado</h2>
    <p>Empresa: <strong id="reviewNoteCompany"></strong></p>
    <div class="review-note-text" id="reviewNoteText"></div>
    <div class="dialog-actions">
      <button class="btn-primary" type="button" id="reviewNoteClose">Fechar</button>
    </div>
  </div>
</dialog>

<script>
document.getElementById('menuToggle')?.addEventListener('click', () => {
  document.getElementById('navbar')?.classList.toggle('open');
});

const dialog = document.getElementById('companyActionDialog');
const actionForm = document.getElementById('companyActionForm');
document.querySelectorAll('.js-reason-action').forEach((button) => {
  button.addEventListener('click', () => {
    actionForm.action = button.dataset.action;
    document.getElementById('dialogCompanyId').value = button.dataset.companyId;
    document.getElementById('dialogCompanyName').textContent = button.dataset.companyName;
    document.getElementById('dialogTitle').textContent = button.dataset.title;
    document.getElementById('dialogSubmit').textContent = button.dataset.label;
    document.getElementById('dialogReason').value = '';
    dialog.showModal();
    document.getElementById('dialogReason').focus();
  });
});
document.getElementById('dialogCancel')?.addEventListener('click', () => dialog.close());
dialog?.addEventListener('click', (event) => {
  if (event.target === dialog) dialog.close();
});

const reviewDialog = document.getElementById('reviewNoteDialog');
document.querySelectorAll('.js-review-note').forEach((button) => {
  button.addEventListener('click', () => {
    document.getElementById('reviewNoteCompany').textContent = button.dataset.companyName || '';
    document.getElementById('reviewNoteText').textContent = button.dataset.reviewNote || 'Nenhum motivo informado.';
    reviewDialog.showModal();
  });
});
document.getElementById('reviewNoteClose')?.addEventListener('click', () => reviewDialog.close());
reviewDialog?.addEventListener('click', (event) => {
  if (event.target === reviewDialog) reviewDialog.close();
});

lucide.createIcons();
</script>
</body>
</html>

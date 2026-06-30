<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>RE.SOURCE — Empresas</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<link rel="stylesheet" href="/re.source/public/css/admin-dashboard.css" />
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<link rel="stylesheet" href="/empresas.css" />
</head>
<body>

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
        <p class="hero-subtitle">Gerencie o ciclo de vida das empresas da plataforma — <strong>aprove, recuse, analise ou desative</strong> cadastros com poucos cliques.</p>
        <div class="hero-meta">
          <span class="hero-meta-item"><i data-lucide="check-circle-2"></i> 128 ativas</span>
          <span class="hero-meta-item"><i data-lucide="clock"></i> 12 pendentes</span>
          <span class="hero-meta-item"><i data-lucide="alert-triangle"></i> 3 em análise</span>
        </div>
      </div>
      <div class="hero-right">
        <div class="hero-badge-num">143</div>
        <div class="hero-badge-label">Total</div>
        <div class="hero-badge-sub">Empresas no sistema</div>
      </div>
    </section>
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Lista de empresas</div>
          <div class="card-sub">Visualize, aprove e gerencie cadastros</div>
        </div>
        <a href="#" class="card-link">Exportar CSV →</a>
      </div>
      <div class="table-wrapper">
        <table class="admin-table" id="empresasTable">
          <thead>
            <tr>
              <th>Empresa</th>
              <th>CNPJ</th>
              <th>Cidade</th>
              <th>Estado</th>
              <th>Responsável</th>
              <th>Status</th>
              <th style="text-align:right">Ações</th>
            </tr>
          </thead>
          <tbody>

                <?php if (empty($empresas)): ?>

                <tr>
                    <td colspan="7" style="text-align:center;padding:2rem;">
                        Nenhuma empresa encontrada.
                    </td>
                </tr>

                <?php else: ?>

                <?php foreach ($empresas as $e): ?>

                <?php

                $statusClass = match($e['status']) {
                    'active'    => 'active',
                    'pending'   => 'pending',
                    'review'    => 'review',
                    'suspended' => 'suspended',
                    default     => 'pending'
                };

                $statusLabel = match($e['status']) {
                    'active'    => 'Ativa',
                    'pending'   => 'Pendente',
                    'review'    => 'Em análise',
                    'suspended' => 'Suspensa',
                    default     => ucfirst($e['status'])
                };

                $initials = implode(
                    '',
                    array_map(
                        fn($w) => strtoupper($w[0]),
                        array_slice(explode(' ', $e['razao_social']), 0, 2)
                    )
                );

                ?>

                <tr>

                    <td>
                        <div class="company-cell">

                            <div class="company-initials">
                                <?= $initials ?>
                            </div>

                            <div>
                                <div class="company-name">
                                    <?= htmlspecialchars($e['razao_social']) ?>
                                </div>

                                <div class="company-city">
                                    <?= htmlspecialchars($e['city'] ?? '') ?>
                                    <?php if (!empty($e['state'])): ?>
                                        • <?= htmlspecialchars($e['state']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </td>

                    <td>
                        <?= htmlspecialchars($e['cnpj']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($e['city'] ?? '-') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($e['state'] ?? '-') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($e['responsible_name']) ?>
                    </td>

                    <td>
                        <span class="status-badge <?= $statusClass ?>">
                            <?= $statusLabel ?>
                        </span>
                    </td>

                    <td>
                        <div class="action-buttons" style="justify-content:flex-end">

                            <button
                                class="btn-icon success"
                                title="Aprovar"
                                data-id="<?= $e['id'] ?>"
                            >
                                <i data-lucide="check"></i>
                            </button>

                            <button
                                class="btn-icon danger"
                                title="Recusar"
                                data-id="<?= $e['id'] ?>"
                            >
                                <i data-lucide="x"></i>
                            </button>

                            <button
                                class="btn-icon warning"
                                title="Desativar"
                                data-id="<?= $e['id'] ?>"
                            >
                                <i data-lucide="ban"></i>
                            </button>

                            <button
                                class="btn-icon"
                                title="Analisar"
                                data-id="<?= $e['id'] ?>"
                            >
                                <i data-lucide="search"></i>
                            </button>

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
<script>
    document.getElementById('menuToggle')?.addEventListener('click', () => {
        document.getElementById('navbar').classList.toggle('open');
    });

    lucide.createIcons();
</script>
</body>
</html>
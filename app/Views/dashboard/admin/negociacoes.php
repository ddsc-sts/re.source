<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>RE.SOURCE — Anúncios</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<link rel="stylesheet" href="/re.source/public/css/admin-dashboard.css" />
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<link rel="stylesheet" href="/re.source/public/css/empresas.css" />
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
        <span class="hero-breadcrumb"><i data-lucide="handshake"></i> Gestão • Negociações</span>
        <h1 class="hero-title">Negociações cadastradas</h1>
        <p class="hero-subtitle">Gerencie as negociações da plataforma — <strong>aprove, recuse, analise ou desative</strong> negociações com poucos cliques.</p>
        <div class="hero-meta">
          <span class="hero-meta-item"><i data-lucide="check-circle-2"></i> 128 ativas</span>
          <span class="hero-meta-item"><i data-lucide="clock"></i> 12 pendentes</span>
          <span class="hero-meta-item"><i data-lucide="alert-triangle"></i> 3 em análise</span>
        </div>
      </div>
      <div class="hero-right">
        <div class="hero-badge-num">143</div>
        <div class="hero-badge-label">Total</div>
        <div class="hero-badge-sub">Negociações no sistema</div>
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
            <th>Comprador</th>
            <th>Vendedor</th>
            <th>Quantidade</th>
            <th>Preço</th>
            <th>Total</th>
            <th>Status</th>
            <th style="text-align:right">Ações</th>
        </tr>
        </thead>
      <tbody>

            <?php if (empty($negociacoes)): ?>

            <tr>
                <td colspan="7" style="text-align:center;padding:2rem;">
                    Nenhuma negociação encontrada.
                </td>
            </tr>

            <?php else: ?>

            <?php foreach ($negociacoes as $n): ?>

            <?php

            $statusClass = match($n['status']) {
                'open'          => 'pending',
                'proposal_sent' => 'review',
                'accepted'      => 'active',
                'concluded'     => 'active',
                'cancelled'     => 'suspended',
                default         => 'pending'
            };

            $statusLabel = match($n['status']) {
                'open'          => 'Aberta',
                'proposal_sent' => 'Proposta enviada',
                'accepted'      => 'Aceita',
                'concluded'     => 'Concluída',
                'cancelled'     => 'Cancelada',
                default         => ucfirst($n['status'])
            };

            ?>

            <tr>

                <td>
                    <?= htmlspecialchars($n['comprador'] ?? '-') ?>
                </td>

                <td>
                    <?= htmlspecialchars($n['vendedor'] ?? '-') ?>
                </td>

                <td>
                    <?= number_format($n['proposed_quantity'] ?? 0, 3, ',', '.') ?>
                </td>

                <td>
                    R$ <?= number_format($n['proposed_price'] ?? 0, 2, ',', '.') ?>
                </td>

                <td>
                    <strong>
                        R$ <?= number_format($n['proposed_total'] ?? 0, 2, ',', '.') ?>
                    </strong>
                </td>

                <td>
                    <span class="status-badge <?= $statusClass ?>">
                        <?= $statusLabel ?>
                    </span>
                </td>

                <td>
                    <div class="action-buttons" style="justify-content:flex-end">

                        <button
                            class="btn-icon"
                            title="Visualizar"
                        >
                            <i data-lucide="eye"></i>
                        </button>

                        <button
                            class="btn-icon success"
                            title="Aprovar"
                        >
                            <i data-lucide="check"></i>
                        </button>

                        <button
                            class="btn-icon danger"
                            title="Cancelar"
                        >
                            <i data-lucide="x"></i>
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
document
    .getElementById('menuToggle')
    ?.addEventListener('click', () => {
        document
            .getElementById('navbar')
            .classList.toggle('open');
    });

lucide.createIcons();
</script>
</body>
</html>

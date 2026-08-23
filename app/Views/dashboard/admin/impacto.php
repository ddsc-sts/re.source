<?php

$userName = htmlspecialchars($user['name'] ?? 'Administrador');

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impacto ESG — RE.SOURCE</title>
    <link rel="icon" href="<?= htmlspecialchars(asset_url('/img/logos/favicon.svg'), ENT_QUOTES, 'UTF-8') ?>" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="/re.source/public/css/admin-dashboard.css">
    <link rel="stylesheet" href="/re.source/public/css/admin-v2.css">
</head>

<body>

<header class="site-header">
    <?php require_once __DIR__ . '/../../components/topbar.php'; ?>
    <?php require_once __DIR__ . '/../../components/navbar.php'; ?>
</header>

<main class="admin-main">

    <div class="admin-content">

        <!-- HERO -->

        <section class="dash-hero">

            <div class="hero-left">

                <span class="hero-breadcrumb">
                    <i data-lucide="leaf"></i>
                    ESG • Sustentabilidade
                </span>

                <h1 class="hero-title">
                    Impacto ESG da Plataforma
                </h1>

                <p class="hero-subtitle">
                    Indicadores ambientais gerados pelas negociações concluídas na RE.SOURCE.
                </p>

            </div>

        </section>

        <!-- MÉTRICAS -->

        <div class="metrics-grid">

            <div class="metric-card">

                <div class="metric-label">
                    Resíduos reaproveitados
                </div>

                <div class="metric-value">
                    <?= number_format($totalKg, 0, ',', '.') ?> kg
                </div>

                <div class="metric-footer">
                    Materiais reinseridos na cadeia produtiva
                </div>

            </div>

            <div class="metric-card">

                <div class="metric-label">
                    Economia Circular
                </div>

                <div class="metric-value">
                    R$ <?= number_format($valorMovimentado, 2, ',', '.') ?>
                </div>

                <div class="metric-footer">
                    Valor movimentado em negociações concluídas
                </div>

            </div>

            <div class="metric-card">

                <div class="metric-label">
                    Negociações Concluídas
                </div>

                <div class="metric-value">
                    <?= $negociacoesConcluidas ?>
                </div>

                <div class="metric-footer">
                    Transações sustentáveis realizadas
                </div>

            </div>

            <div class="metric-card">

                <div class="metric-label">
                    Empresas Participantes
                </div>

                <div class="metric-value">
                    <?= $empresasAtivas ?>
                </div>

                <div class="metric-footer">
                    Empresas ativas na plataforma
                </div>

            </div>

        </div>

        <!-- GRÁFICOS -->

        <div class="bottom-grid">

            <div class="card">

                <div class="card-header">

                    <div>
                        <div class="card-title">
                            Materiais Mais Reaproveitados
                        </div>

                        <div class="card-sub">
                            Distribuição por categoria
                        </div>
                    </div>

                </div>

                <div class="chart-box">
                    <canvas id="categoriasChart"></canvas>
                </div>

            </div>

            <div class="card">

                <div class="card-header">

                    <div>
                        <div class="card-title">
                            Indicadores ESG
                        </div>

                        <div class="card-sub">
                            Resumo ambiental da plataforma
                        </div>
                    </div>

                </div>

                <div class="esg-list">

                    <div>

                        <div class="esg-item-header">

                            <span class="esg-item-label">
                                Resíduos reaproveitados
                            </span>

                            <span class="esg-item-value green">
                                <?= number_format($totalKg,0,',','.') ?> kg
                            </span>

                        </div>

                        <div class="esg-bar">
                            <div class="esg-bar-fill green" style="width:100%"></div>
                        </div>

                    </div>

                    <div>

                        <div class="esg-item-header">

                            <span class="esg-item-label">
                                Empresas engajadas
                            </span>

                            <span class="esg-item-value green">
                                <?= $empresasAtivas ?>
                            </span>

                        </div>

                    </div>

                    <div>

                        <div class="esg-item-header">

                            <span class="esg-item-label">
                                Negociações sustentáveis
                            </span>

                            <span class="esg-item-value green">
                                <?= $negociacoesConcluidas ?>
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- TABELA -->

        <div class="card">

            <div class="card-header">

                <div>

                    <div class="card-title">
                        Ranking de Categorias
                    </div>

                    <div class="card-sub">
                        Categorias com maior reaproveitamento
                    </div>

                </div>

            </div>

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>

                        <tr>
                            <th>Categoria</th>
                            <th>Quantidade (kg)</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($categoriasESG as $categoria): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($categoria['name']) ?>
                                </td>

                                <td>
                                    <?= number_format($categoria['total'],0,',','.') ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</main>

<script>

lucide.createIcons();

const categorias = <?= json_encode(
    array_column($categoriasESG, 'name')
) ?>;

const totais = <?= json_encode(
    array_map(
        'floatval',
        array_column($categoriasESG, 'total')
    )
) ?>;

new Chart(
    document.getElementById('categoriasChart'),
    {
        type: 'doughnut',

        data: {
            labels: categorias,

            datasets: [{
                data: totais
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    }
);

</script>

</body>
</html>

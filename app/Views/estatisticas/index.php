<?php
$nome_exibicao = $nome_empresa;
$titulo_pagina = $titulo_pagina ?? 'Estatísticas do Painel — Re.Source';
require_once __DIR__ . '/../components/header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/dashboard-sidebar.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/estatisticas.css'), ENT_QUOTES, 'UTF-8') ?>">

<main class="dashboard-shell">
    <?php $sidebarActive = 'statistics'; require __DIR__ . '/../components/dashboard_sidebar.php'; ?>

    <div class="dashboard-content">
        
        <div class="dash-header">
            <h1 class="dash-title">Visão Geral</h1>
            <div class="dash-date">
                <i data-lucide="calendar"></i> 
                Atualizado Hoje
            </div>
        </div>

        <?php if(isset($_SESSION['saque_msg'])): ?>
            <div class="alert alert-<?= $_SESSION['saque_tipo']; ?>">
                <?= htmlspecialchars($_SESSION['saque_msg'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php unset($_SESSION['saque_msg']); unset($_SESSION['saque_tipo']); ?>
        <?php endif; ?>

        <div class="dash-cards">
            <div class="dash-card">
                <div class="dash-card-header">
                    <span class="dash-card-title">Disponível para Saque</span>
                    <div class="dash-card-icon icon-green"><i data-lucide="wallet"></i></div>
                </div>
                <div class="dash-card-value">R$ <?= number_format($saldo_disponivel, 2, ',', '.'); ?></div>
                <?php if($saldo_disponivel > 0): ?>
                    <a class="btn-sacar" href="/re.source/estatisticas/saque">Solicitar Saque</a>
                <?php else: ?>
                    <button class="btn-sacar" disabled>Saldo Insuficiente</button>
                <?php endif; ?>
            </div>

            <div class="dash-card">
                <div class="dash-card-header"><span class="dash-card-title">Saldo Futuro</span><div class="dash-card-icon icon-blue"><i data-lucide="clock-3"></i></div></div>
                <div class="dash-card-value">R$ <?= number_format($saldo_futuro, 2, ',', '.'); ?></div>
                <div class="dash-card-note">Vendas acordadas ainda não entregues</div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header"><span class="dash-card-title">Reservado</span><div class="dash-card-icon icon-blue"><i data-lucide="lock-keyhole"></i></div></div>
                <div class="dash-card-value">R$ <?= number_format($saldo_reservado, 2, ',', '.'); ?></div>
                <div class="dash-card-note">Saques aguardando análise</div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header"><span class="dash-card-title">Total Sacado</span><div class="dash-card-icon icon-green"><i data-lucide="circle-check"></i></div></div>
                <div class="dash-card-value">R$ <?= number_format($saldo_sacado, 2, ',', '.'); ?></div>
                <div class="dash-card-note">Solicitações aprovadas</div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header">
                    <span class="dash-card-title">Anúncios Ativos</span>
                    <div class="dash-card-icon icon-blue"><i data-lucide="package"></i></div>
                </div>
                <div class="dash-card-value"><?= $total_anuncios; ?></div>
                <div class="dash-card-note">Visíveis no marketplace</div>
            </div>

            <div class="dash-card is-highlight">
                <div class="dash-card-header">
                    <span class="dash-card-title is-green">Visualizações</span>
                    <div class="dash-card-icon icon-green"><i data-lucide="eye"></i></div>
                </div>
                <div class="dash-card-value is-green"><?= $total_views; ?></div>
                <div class="dash-card-note">Visualizações totais na base</div>
            </div>
        </div>

        <div class="dash-grid-2 dash-grid-wide">
            <div class="dash-panel">
                <h2 class="panel-title">Visualizações (Últimos 30 Dias)</h2>
                <div class="chart-box">
                    <canvas id="viewsChart"></canvas>
                </div>
            </div>

            <div class="dash-panel">
                <h2 class="panel-title">Distribuição de Materiais</h2>
                <div class="chart-box chart-box-centered">
                    <canvas id="materialsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="dash-grid-2">
            <div class="dash-panel">
                <h2 class="panel-title">Negociações Recentes</h2>
                <div class="table-scroll">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Material</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($negociacoes_recentes)): ?>
                                <tr><td colspan="5" class="table-empty">Nenhuma negociação.</td></tr>
                            <?php else: ?>
                                <?php foreach ($negociacoes_recentes as $negociacao): 
                                    $badgeClass = ($negociacao['status'] === 'concluded' || $negociacao['status'] === 'accepted') ? 'status-concluido' : 'status-pendente';
                                    $tipoClass = ($negociacao['tipo'] == 'Venda') ? 'is-sale' : 'is-purchase';
                                ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($negociacao['data'])); ?></td>
                                    <td><span class="table-type <?= $tipoClass; ?>"><?= $negociacao['tipo']; ?></span></td>
                                    <td><?= htmlspecialchars($negociacao['material']); ?></td>
                                    <td><?= $negociacao['valor'] ? 'R$ ' . number_format($negociacao['valor'], 2, ',', '.') : '--'; ?></td>
                                    <td><span class="status-badge <?= $badgeClass; ?>"><?= ucfirst($negociacao['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dash-panel">
                <h2 class="panel-title">Histórico de Saques</h2>
                <div class="table-scroll">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Destino</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historico_saques)): ?>
                                <tr><td colspan="4" class="table-empty">Nenhum saque solicitado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($historico_saques as $saque): 
                                    if ($saque['status'] === 'completed') {
                                        $badgeSaque = 'status-concluido';
                                        $labelSaque = 'Concluído';
                                    } elseif ($saque['status'] === 'rejected') {
                                        $badgeSaque = 'status-rejeitado';
                                        $labelSaque = 'Rejeitado';
                                    } else {
                                        $badgeSaque = 'status-pendente';
                                        $labelSaque = 'Pendente';
                                    }
                                ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($saque['created_at'])); ?></td>
                                    <td class="table-destination" title="<?= htmlspecialchars($saque['destination']); ?>">
                                        <?= strtoupper(htmlspecialchars($saque['method'])) ?> · <?= htmlspecialchars($saque['destination']); ?>
                                    </td>
                                    <td class="table-money">R$ <?= number_format($saque['amount'], 2, ',', '.'); ?></td>
                                    <td><span class="status-badge <?= $badgeSaque; ?>"><?= $labelSaque; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
        
        const labelsViews = <?= json_encode($labelsViews, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const dataViews = <?= json_encode($dataViews, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        
        const ctxViews = document.getElementById('viewsChart').getContext('2d');
        let gradientGreen = ctxViews.createLinearGradient(0, 0, 0, 400);
        gradientGreen.addColorStop(0, 'rgba(21, 115, 71, 0.4)');
        gradientGreen.addColorStop(1, 'rgba(21, 115, 71, 0.0)');

        new Chart(ctxViews, {
            type: 'line',
            data: {
                labels: labelsViews,
                datasets: [{
                    label: 'Visualizações Diárias',
                    data: dataViews, 
                    borderColor: '#157347',
                    backgroundColor: gradientGreen,
                    borderWidth: 2,
                    pointRadius: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#157347',
                    fill: true, tension: 0.3
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f3f4f6' }, border: { display: false }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false }, border: { display: false }, ticks: { maxTicksLimit: 10 } }
                }
            }
        });

        const labelsCategorias = <?= json_encode($catLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const dataCategorias = <?= json_encode($catData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const cores = ['#157347', '#0D6EFD', '#FD7E14', '#6C757D', '#6f42c1', '#d63384', '#0dcaf0'];

        const ctxMaterials = document.getElementById('materialsChart').getContext('2d');
        new Chart(ctxMaterials, {
            type: 'doughnut',
            data: {
                labels: labelsCategorias,
                datasets: [{
                    data: dataCategorias,
                    backgroundColor: cores.slice(0, dataCategorias.length),
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20, usePointStyle: true, font: { family: "'Inter', sans-serif" } }
                    }
                }
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

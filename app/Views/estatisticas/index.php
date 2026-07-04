<?php
$nome_exibicao = $nome_empresa;
$titulo_pagina = $titulo_pagina ?? 'Estatísticas do Painel — Re.Source';
require_once __DIR__ . '/../components/header.php';
?>
<link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/dashboard-sidebar.css'), ENT_QUOTES, 'UTF-8') ?>">

<style>
/* Layout Base Unificado */
.dashboard-layout {
    max-width: 1280px;
    margin: 2rem auto;
    padding: 0 1.5rem;
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 2rem;
    align-items: start;
}
.dashboard-content { display: flex; flex-direction: column; gap: 1.5rem; position: sticky; top: 100px; height: calc(100vh - 120px); overflow-y: auto; padding-right: 10px; }
.dash-header { display: flex; justify-content: space-between; align-items: center; }
.dash-title { font-family: var(--font-main); font-size: 1.75rem; font-weight: 700; color: var(--dark); }
.dash-date { font-size: 0.875rem; color: var(--muted); background: var(--white); padding: 0.5rem 1rem; border-radius: 9999px; border: 1px solid var(--border-color); }
.dash-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; }
.dash-card { background: var(--white); border-radius: var(--radius); border: 1px solid var(--border-color); padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; transition: box-shadow 0.3s; }
.dash-card:hover { box-shadow: var(--shadow-card); }
.dash-card-header { display: flex; justify-content: space-between; align-items: center; }
.dash-card-title { font-size: 0.85rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; }
.dash-card-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.icon-green { background: rgba(21, 115, 71, 0.1); color: var(--green); }
.icon-blue { background: rgba(13, 110, 253, 0.1); color: #0D6EFD; }
.dash-card-value { font-family: var(--font-main); font-size: 2rem; font-weight: 700; color: var(--dark); }

.btn-sacar { display: block; box-sizing: border-box; background-color: var(--green); color: white; border: none; border-radius: 0.5rem; padding: 0.5rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-align: center; text-decoration: none; width: 100%; }
.btn-sacar:hover { background-color: #0f5132; }
.btn-sacar:disabled { background-color: #6c757d; cursor: not-allowed; opacity: 0.7; }

.modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
.modal-content { background: white; padding: 2.5rem; border-radius: var(--radius); width: 100%; max-width: 420px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
.modal-content h3 { margin-bottom: 0.5rem; color: var(--dark); }
.modal-content input { width: 100%; padding: 0.85rem; margin-bottom: 1.2rem; border: 1px solid var(--border-color); border-radius: 0.5rem; font-size: 1rem; }
.modal-content input:focus { outline: none; border-color: var(--green); }

.dash-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.dash-panel { background: var(--white); border-radius: var(--radius); border: 1px solid var(--border-color); padding: 1.5rem; }
.panel-title { font-family: var(--font-main); font-size: 1.1rem; font-weight: 700; color: var(--dark); margin-bottom: 1.5rem; }
.dash-table { width: 100%; border-collapse: collapse; }
.dash-table th, .dash-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 0.875rem; }
.dash-table th { font-weight: 600; color: var(--muted); background: var(--bg); }
.status-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
.status-concluido { background: rgba(21, 115, 71, 0.1); color: var(--green); }
.status-pendente { background: rgba(253, 126, 20, 0.1); color: #FD7E14; }
.status-rejeitado { background: rgba(220, 53, 69, 0.1); color: #DC3545; }

.alert { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-weight: 500; font-size: 0.9rem; }
.alert-success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
.alert-error { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

@media (max-width: 992px) {
    .dashboard-layout { grid-template-columns: 1fr; }
    .dashboard-sidebar, .dashboard-content { position: static; height: auto; overflow-y: visible; }
    .dash-grid-2 { grid-template-columns: 1fr; }
}
</style>

<main class="dashboard-layout">
    <?php $sidebarActive = 'statistics'; require __DIR__ . '/../components/dashboard_sidebar.php'; ?>

    <div class="dashboard-content">
        
        <div class="dash-header">
            <h1 class="dash-title">Visão Geral</h1>
            <div class="dash-date">
                <i data-lucide="calendar" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 4px;"></i> 
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
                <div style="font-size:.75rem;color:var(--muted)">Vendas acordadas ainda não entregues</div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header"><span class="dash-card-title">Reservado</span><div class="dash-card-icon icon-blue"><i data-lucide="lock-keyhole"></i></div></div>
                <div class="dash-card-value">R$ <?= number_format($saldo_reservado, 2, ',', '.'); ?></div>
                <div style="font-size:.75rem;color:var(--muted)">Saques aguardando análise</div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header"><span class="dash-card-title">Total Sacado</span><div class="dash-card-icon icon-green"><i data-lucide="circle-check"></i></div></div>
                <div class="dash-card-value">R$ <?= number_format($saldo_sacado, 2, ',', '.'); ?></div>
                <div style="font-size:.75rem;color:var(--muted)">Solicitações aprovadas</div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header">
                    <span class="dash-card-title">Anúncios Ativos</span>
                    <div class="dash-card-icon icon-blue"><i data-lucide="package"></i></div>
                </div>
                <div class="dash-card-value"><?= $total_anuncios; ?></div>
                <div style="font-size: 0.75rem; color: var(--muted);">Visíveis no marketplace</div>
            </div>

            <div class="dash-card" style="border: 1px solid var(--green); background: rgba(21, 115, 71, 0.02);">
                <div class="dash-card-header">
                    <span class="dash-card-title" style="color: var(--green);">Visualizações</span>
                    <div class="dash-card-icon icon-green"><i data-lucide="eye"></i></div>
                </div>
                <div class="dash-card-value" style="color: var(--green);"><?= $total_views; ?></div>
                <div style="font-size: 0.75rem; color: var(--muted);">Visualizações totais na base</div>
            </div>
        </div>

        <div class="dash-grid-2" style="grid-template-columns: 2fr 1fr;">
            <div class="dash-panel">
                <h2 class="panel-title">Visualizações (Últimos 30 Dias)</h2>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="viewsChart"></canvas>
                </div>
            </div>

            <div class="dash-panel">
                <h2 class="panel-title">Distribuição de Materiais</h2>
                <div style="position: relative; height: 300px; width: 100%; display: flex; justify-content: center;">
                    <canvas id="materialsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="dash-grid-2">
            <div class="dash-panel">
                <h2 class="panel-title">Negociações Recentes</h2>
                <div style="overflow-x: auto;">
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
                                <tr><td colspan="5" style="text-align: center;">Nenhuma negociação.</td></tr>
                            <?php else: ?>
                                <?php foreach ($negociacoes_recentes as $negociacao): 
                                    $badgeClass = ($negociacao['status'] === 'concluded' || $negociacao['status'] === 'accepted') ? 'status-concluido' : 'status-pendente';
                                    $corTipo = ($negociacao['tipo'] == 'Venda') ? 'var(--green)' : '#FD7E14';
                                ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($negociacao['data'])); ?></td>
                                    <td><span style="color: <?= $corTipo; ?>; font-weight: 600;"><?= $negociacao['tipo']; ?></span></td>
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
                <div style="overflow-x: auto;">
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
                                <tr><td colspan="4" style="text-align: center;">Nenhum saque solicitado.</td></tr>
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
                                    <td style="max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($saque['destination']); ?>">
                                        <?= strtoupper(htmlspecialchars($saque['method'])) ?> · <?= htmlspecialchars($saque['destination']); ?>
                                    </td>
                                    <td style="font-weight: 600;">R$ <?= number_format($saque['amount'], 2, ',', '.'); ?></td>
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

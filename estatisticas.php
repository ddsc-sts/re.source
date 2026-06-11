<?php
// estatisticas.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/BackEnd/config/conexao.php"; 

$company_id = $_SESSION['company_id'] ?? 1; 

try {
    // --- DADOS DA EMPRESA ---
    $stmtEmpresa = $pdo->prepare("SELECT razao_social, cnpj FROM companies WHERE id = ?");
    $stmtEmpresa->execute([$company_id]);
    $empresa = $stmtEmpresa->fetch() ?: ['razao_social' => 'Empresa Não Encontrada', 'cnpj' => '00.000.000/0000-00'];

    // --- SALDO DISPONÍVEL ---
    $stmtSaldo = $pdo->prepare("SELECT COALESCE(SUM(proposed_total), 0) FROM negotiations WHERE seller_company_id = ? AND status = 'concluded'");
    $stmtSaldo->execute([$company_id]);
    $saldo_disponivel = (float) $stmtSaldo->fetchColumn();

    // --- ANÚNCIOS E VIEWS (TOTAIS DA TABELA LISTINGS) ---
    $stmtAnuncios = $pdo->prepare("SELECT COUNT(id) FROM listings WHERE company_id = ? AND status = 'active' AND deleted_at IS NULL");
    $stmtAnuncios->execute([$company_id]);
    $total_anuncios = $stmtAnuncios->fetchColumn();

    $stmtViews = $pdo->prepare("SELECT COALESCE(SUM(views_count), 0) FROM listings WHERE company_id = ? AND deleted_at IS NULL");
    $stmtViews->execute([$company_id]);
    $total_views = (int) $stmtViews->fetchColumn();

    // =====================================================================
    // NOVO: GRÁFICO DE VIEWS COM HISTÓRICO REAL DOS ÚLTIMOS 30 DIAS
    // =====================================================================
    $stmtViewsHistory = $pdo->prepare("
        SELECT DATE(vh.created_at) as data_view, COUNT(vh.id) as views_dia
        FROM views_history vh
        JOIN listings l ON vh.listing_id = l.id
        WHERE l.company_id = ? AND vh.created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
        GROUP BY DATE(vh.created_at)
    ");
    $stmtViewsHistory->execute([$company_id]);
    // Retorna array no formato ['2023-10-01' => 5, '2023-10-02' => 12]
    $views_db = $stmtViewsHistory->fetchAll(PDO::FETCH_KEY_PAIR);

    // Preenche os 30 dias (mesmo os que tiveram 0 views) para o gráfico não quebrar
    $labelsViews = [];
    $dataViews = [];
    for ($i = 29; $i >= 0; $i--) {
        $dataLabel = date('Y-m-d', strtotime("-$i days"));
        $labelsViews[] = date('d/m', strtotime("-$i days")); // Formato para o eixo X
        $dataViews[] = $views_db[$dataLabel] ?? 0;
    }

    // --- DADOS DINÂMICOS: DISTRIBUIÇÃO DE MATERIAIS ---
    $stmtCats = $pdo->prepare("
        SELECT c.name, COUNT(l.id) as total_anuncios 
        FROM listings l 
        JOIN categories c ON l.category_id = c.id 
        WHERE l.company_id = ? AND l.deleted_at IS NULL
        GROUP BY c.id
    ");
    $stmtCats->execute([$company_id]);
    $categorias_db = $stmtCats->fetchAll();

    $catLabels = [];
    $catData = [];
    if (empty($categorias_db)) {
        $catLabels = ['Sem anúncios'];
        $catData = [1]; 
    } else {
        foreach ($categorias_db as $cat) {
            $catLabels[] = $cat['name'];
            $catData[] = $cat['total_anuncios'];
        }
    }

    // --- TRANSAÇÕES RECENTES ---
    $stmtRecentes = $pdo->prepare("
        SELECT 
            n.updated_at as data, 
            IF(n.buyer_company_id = ?, 'Compra', 'Venda') as tipo,
            l.title as material,
            n.proposed_total as valor,
            n.status
        FROM negotiations n
        JOIN listings l ON n.listing_id = l.id
        WHERE (n.buyer_company_id = ? OR n.seller_company_id = ?)
        ORDER BY n.updated_at DESC 
        LIMIT 5
    ");
    $stmtRecentes->execute([$company_id, $company_id, $company_id]);
    $negociacoes_recentes = $stmtRecentes->fetchAll();

} catch (PDOException $e) {
    die("Erro ao carregar o painel: " . $e->getMessage());
}

function formatCnpj($cnpj) {
    return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "$1.$2.$3/$4-$5", $cnpj);
}

$titulo_pagina = 'Estatísticas do Painel — Re.Source';
include 'header.php';
?>

<style>
/* Os estilos continuam os mesmos da versão anterior, com dashboard-content já tendo position: sticky */
.dashboard-layout { max-width: 1280px; margin: 2rem auto; padding: 0 1.5rem; display: grid; grid-template-columns: 260px 1fr; gap: 2rem; align-items: start; }
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.dashboard-sidebar { background: var(--white); border-radius: var(--radius); border: 1px solid var(--border-color); padding: 1.5rem 1rem; position: sticky; top: 100px; height: calc(100vh - 120px); overflow-y: auto; }
.sidebar-user { text-align: center; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); margin-bottom: 1.5rem; }
.sidebar-avatar { width: 64px; height: 64px; background: var(--bg); border-radius: 50%; margin: 0 auto 0.75rem; display: flex; align-items: center; justify-content: center; color: var(--green); }
.sidebar-user h3 { font-family: var(--font-main); font-size: 1rem; color: var(--dark); }
.sidebar-user p { font-size: 0.75rem; color: var(--muted); }
.sidebar-nav { display: flex; flex-direction: column; gap: 0.5rem; }
.sidebar-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.9rem; font-weight: 500; color: var(--muted); transition: all 0.2s; }
.sidebar-link:hover { background: var(--bg); color: var(--dark); }
.sidebar-link.active { background: rgba(21, 115, 71, 0.1); color: var(--green); font-weight: 600; }
.sidebar-link i { width: 18px; height: 18px; }

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

.btn-sacar { background-color: var(--green); color: white; border: none; border-radius: 0.5rem; padding: 0.5rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-align: center; width: 100%; }
.btn-sacar:hover { background-color: #0f5132; }
.btn-sacar:disabled { background-color: #6c757d; cursor: not-allowed; }

.modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
.modal-content { background: white; padding: 2rem; border-radius: 0.5rem; width: 100%; max-width: 400px; }
.modal-content h3 { margin-bottom: 1rem; }
.modal-content input { width: 100%; padding: 0.75rem; margin-bottom: 1rem; border: 1px solid var(--border-color); border-radius: 0.5rem; }

.dash-grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
.dash-panel { background: var(--white); border-radius: var(--radius); border: 1px solid var(--border-color); padding: 1.5rem; }
.panel-title { font-family: var(--font-main); font-size: 1.1rem; font-weight: 700; color: var(--dark); margin-bottom: 1.5rem; }
.dash-table { width: 100%; border-collapse: collapse; }
.dash-table th, .dash-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 0.875rem; }
.dash-table th { font-weight: 600; color: var(--muted); background: var(--bg); }
.status-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
.status-concluido { background: rgba(21, 115, 71, 0.1); color: var(--green); }
.status-pendente { background: rgba(253, 126, 20, 0.1); color: #FD7E14; }

@media (max-width: 992px) {
    .dashboard-layout { grid-template-columns: 1fr; }
    .dashboard-sidebar, .dashboard-content { position: static; height: auto; overflow-y: visible; }
    .dash-grid-2 { grid-template-columns: 1fr; }
}
</style>

<main class="dashboard-layout">
    
    <aside class="dashboard-sidebar">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><i data-lucide="building-2" style="width: 32px; height: 32px;"></i></div>
            <h3><?= htmlspecialchars($empresa['razao_social']); ?></h3>
            <p>CNPJ: <?= formatCnpj($empresa['cnpj']); ?></p>
        </div>
        <nav class="sidebar-nav">
            <a href="estatisticas.php" class="sidebar-link active"><i data-lucide="bar-chart-2"></i> Painel e Estatísticas</a>
            <a href="meusAnuncios.php" class="sidebar-link"><i data-lucide="package"></i> Meus Anúncios</a>
            <a href="conta.php" class="sidebar-link"><i data-lucide="user"></i> Detalhes da Conta</a>
            <a href="configuracoes.php" class="sidebar-link"><i data-lucide="settings"></i> Configurações</a>
        </nav>
    </aside>

    <div class="dashboard-content">
        
        <div class="dash-header">
            <h1 class="dash-title">Visão Geral</h1>
            <div class="dash-date">
                <i data-lucide="calendar" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 4px;"></i> 
                Atualizado Hoje
            </div>
        </div>

        <div class="dash-cards">
            <div class="dash-card">
                <div class="dash-card-header">
                    <span class="dash-card-title">Disponível para Saque</span>
                    <div class="dash-card-icon icon-green"><i data-lucide="wallet"></i></div>
                </div>
                <div class="dash-card-value">R$ <?= number_format($saldo_disponivel, 2, ',', '.'); ?></div>
                <?php if($saldo_disponivel > 0): ?>
                    <button class="btn-sacar" onclick="abrirModal()">Solicitar Saque</button>
                <?php else: ?>
                    <button class="btn-sacar" disabled>Saldo Insuficiente</button>
                <?php endif; ?>
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

        <div class="dash-grid-2">
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
                            <tr><td colspan="5" style="text-align: center;">Nenhuma negociação encontrada.</td></tr>
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

    </div>
</main>

<div class="modal-overlay" id="modalSaque">
    <div class="modal-content">
        <h3>Solicitar Saque</h3>
        <p style="font-size: 0.85rem; color: var(--muted); margin-bottom: 1rem;">Saldo disponível: R$ <?= number_format($saldo_disponivel, 2, ',', '.'); ?></p>
        <form action="processar_saque.php" method="POST">
            <label style="font-size: 0.85rem; font-weight: 600;">Valor a sacar (R$)</label>
            <input type="number" name="valor_saque" max="<?= $saldo_disponivel; ?>" step="0.01" required placeholder="Ex: 1000.00">
            <label style="font-size: 0.85rem; font-weight: 600;">Chave PIX</label>
            <input type="text" name="chave_pix" required placeholder="Digite sua chave PIX">
            
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="button" class="btn-sacar" style="background: #6c757d;" onclick="fecharModal()">Cancelar</button>
                <button type="submit" class="btn-sacar">Confirmar Saque</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function abrirModal() { document.getElementById('modalSaque').style.display = 'flex'; }
    function fecharModal() { document.getElementById('modalSaque').style.display = 'none'; }

    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. Gráfico de Visualizações (AGORA PUXANDO DO BANCO REAL - 30 DIAS)
        const labelsViews = <?= json_encode($labelsViews); ?>;
        const dataViews = <?= json_encode($dataViews); ?>;
        
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
                    pointRadius: 2, // Pontos menores pois são muitos dias
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
                    x: { grid: { display: false }, border: { display: false }, ticks: { maxTicksLimit: 10 } } // Exibe no máx 10 labels no eixo X para não embolar
                }
            }
        });

        // 2. Gráfico de Materiais
        const labelsCategorias = <?= json_encode($catLabels); ?>;
        const dataCategorias = <?= json_encode($catData); ?>;
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

<?php include 'footer.php'; ?>
<?php
// Variáveis injetadas pelo AdminController:
// $user, $metrics, $recentCompanies, $recentActivity, $esgIndicators, $volumeChart, $chartStats, $heroStats


$hour         = (int)(new DateTime())->format('H');
$greeting     = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');

function companyColor(string $name): string {
    $colors = ['#157347','#1d4ed8','#7c3aed','#0d9488','#ea580c','#be185d','#0369a1'];
    return $colors[abs(crc32($name)) % count($colors)];
}

// Helper para renderizar o badge de delta
function renderDelta(?array $delta): string {
    if (!$delta) return '';
    $dir   = $delta['direcao'];
    $val   = htmlspecialchars($delta['valor']);
    $icon  = $dir === 'up' ? 'trending-up' : ($dir === 'down' ? 'trending-down' : 'minus');
    $class = $dir === 'up' ? 'up' : ($dir === 'down' ? 'down' : 'flat');
    return "<div class=\"metric-delta {$class}\"><i data-lucide=\"{$icon}\"></i>{$val}</div>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Painel Administrativo — Re.Source</title>
  <link rel="icon" href="<?= htmlspecialchars(asset_url('/img/logos/favicon.svg'), ENT_QUOTES, 'UTF-8') ?>" type="image/svg+xml" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="stylesheet" href="/re.source/public/css/admin-dashboard.css" />
  <link rel="stylesheet" href="/re.source/public/css/admin-v2.css" />
</head>
<body>

<!-- ═══════════════════════
     HEADER
═══════════════════════ -->
<header class="site-header">

<?php require_once __DIR__ . '/../../components/topbar.php'; ?>

<?php require_once __DIR__ . '/../../components/navbar.php'; ?>

</header>

<!-- ═══════════════════════
     MAIN
═══════════════════════ -->
<div class="admin-main">
  <main class="admin-content">

    <!-- ── Hero ── -->
    <div class="dash-hero">
      <div class="hero-left">
        <div class="hero-breadcrumb">
          <i data-lucide="layout-dashboard"></i>
          Painel administrativo · B2B
        </div>
        <h1 class="hero-title"><?= $greeting ?>, <?= explode(' ', $userName)[0] ?></h1>
        <p class="hero-subtitle">
          <?php if (($heroStats['novos_hoje'] ?? 0) > 0): ?>
            Hoje há <strong><?= $heroStats['novos_hoje'] ?> novo<?= $heroStats['novos_hoje'] != 1 ? 's' : '' ?> cadastro<?= $heroStats['novos_hoje'] != 1 ? 's' : '' ?> de empresa<?= $heroStats['novos_hoje'] != 1 ? 's' : '' ?></strong><?php if (($metrics['anuncios_pendentes'] ?? 0) > 0): ?>,<?php endif; ?>
          <?php endif; ?>
          <?php if (($metrics['anuncios_pendentes'] ?? 0) > 0): ?>
            <strong><?= $metrics['anuncios_pendentes'] ?> anúncio<?= $metrics['anuncios_pendentes'] != 1 ? 's' : '' ?></strong> aguardando moderação
          <?php endif; ?>
          <?php if ($heroStats['delta_semana'] ?? null): ?>
            <?= (($heroStats['novos_hoje'] ?? 0) > 0 || ($metrics['anuncios_pendentes'] ?? 0) > 0) ? 'e ' : '' ?>
            <strong><?= $heroStats['delta_semana']['direcao'] === 'up' ? '+' : '' ?><?= $heroStats['delta_semana']['valor'] ?></strong> de volume negociado vs. semana anterior.
          <?php elseif (($heroStats['novos_hoje'] ?? 0) == 0 && ($metrics['anuncios_pendentes'] ?? 0) == 0): ?>
            Bem-vindo ao painel administrativo.
          <?php else: ?>
            .
          <?php endif; ?>
        </p>
        <div class="hero-meta">
          <span class="hero-meta-item">
            <i data-lucide="calendar"></i>
            <?php
              $days_pt   = ['Sunday'=>'Domingo','Monday'=>'Segunda-feira','Tuesday'=>'Terça-feira',
                            'Wednesday'=>'Quarta-feira','Thursday'=>'Quinta-feira','Friday'=>'Sexta-feira','Saturday'=>'Sábado'];
              $months_pt = ['January'=>'Janeiro','February'=>'Fevereiro','March'=>'Março','April'=>'Abril',
                            'May'=>'Maio','June'=>'Junho','July'=>'Julho','August'=>'Agosto',
                            'September'=>'Setembro','October'=>'Outubro','November'=>'Novembro','December'=>'Dezembro'];
              echo ($days_pt[date('l')] ?? date('l')) . ', ' . date('j') . ' de ' . ($months_pt[date('F')] ?? date('F')) . ' de ' . date('Y');
            ?>
          </span>
          <span class="hero-meta-item">
            <i data-lucide="clock"></i>
            <span id="liveTime"></span>
          </span>
        </div>
      </div>
      <?php if (($metrics['anuncios_pendentes'] ?? 0) > 0): ?>
      <div class="hero-right">
        <div class="hero-badge-num"><?= $metrics['anuncios_pendentes'] ?></div>
        <div class="hero-badge-label">Moderação</div>
        <div class="hero-badge-sub">Pendentes<br>Anúncios para revisar</div>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── Metrics ── -->
    <div class="metrics-grid">

      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-icon green"><i data-lucide="building-2"></i></div>
          <?= renderDelta($metrics['delta_empresas'] ?? null) ?>
        </div>
        <div class="metric-label">Empresas Ativas</div>
        <div class="metric-value"><?= $metrics['empresas_ativas'] ?></div>
        <div class="metric-footer">vs mês anterior</div>
      </div>

      <a class="metric-card" href="<?= htmlspecialchars(app_url('/admin/saques'), ENT_QUOTES, 'UTF-8') ?>" style="text-decoration:none;color:inherit">
        <div class="metric-top"><div class="metric-icon orange"><i data-lucide="wallet-cards"></i></div></div>
        <div class="metric-label">Saques Pendentes</div>
        <div class="metric-value"><?= (int) ($metrics['saques_pendentes'] ?? 0) ?></div>
        <div class="metric-footer">R$ <?= number_format((float)($metrics['saques_valor_pendente'] ?? 0),2,',','.') ?> reservados</div>
      </a>

      <a class="metric-card" href="<?= htmlspecialchars(app_url('/admin/logistica'), ENT_QUOTES, 'UTF-8') ?>" style="text-decoration:none;color:inherit">
        <div class="metric-top"><div class="metric-icon teal"><i data-lucide="truck"></i></div></div>
        <div class="metric-label">Entregas Ativas</div>
        <div class="metric-value"><?= (int) ($metrics['entregas_ativas'] ?? 0) ?></div>
        <div class="metric-footer">Fretes em andamento</div>
      </a>

      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-icon blue"><i data-lucide="tag"></i></div>
          <?= renderDelta($metrics['delta_anuncios'] ?? null) ?>
        </div>
        <div class="metric-label">Anúncios Publicados</div>
        <div class="metric-value"><?= $metrics['anuncios_publicados'] ?></div>
        <div class="metric-footer">vs mês anterior</div>
      </div>

      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-icon purple"><i data-lucide="handshake"></i></div>
          <?= renderDelta($metrics['delta_negs'] ?? null) ?>
        </div>
        <div class="metric-label">Negociações Fechadas</div>
        <div class="metric-value"><?= $metrics['negociacoes_fechadas'] ?></div>
        <div class="metric-footer">vs mês anterior</div>
      </div>

      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-icon teal"><i data-lucide="wallet"></i></div>
          <?= renderDelta($metrics['delta_gmv'] ?? null) ?>
        </div>
        <div class="metric-label">GMV do Mês</div>
        <div class="metric-value"><?= $metrics['gmv_mes'] ?></div>
        <div class="metric-footer">vs mês anterior</div>
      </div>

      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-icon green"><i data-lucide="leaf"></i></div>
          <?= renderDelta($metrics['delta_co2'] ?? null) ?>
        </div>
        <div class="metric-label">CO₂ Evitado (estimado)</div>
        <div class="metric-value"><?= $metrics['co2_evitado'] ?></div>
        <div class="metric-footer">vs mês anterior</div>
      </div>

      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-icon orange"><i data-lucide="life-buoy"></i></div>
          <?= renderDelta($metrics['delta_chamados'] ?? null) ?>
        </div>
        <div class="metric-label">Negociações Abertas</div>
        <div class="metric-value"><?= $metrics['chamados_abertos'] ?></div>
        <div class="metric-footer">vs mês anterior</div>
      </div>

    </div>

    <!-- ── Main Grid ── -->
    <div class="main-grid">

      <!-- Empresas recentes -->
      <div class="card">
        <div class="card-header">
          <div class="card-title-wrap">
            <div class="card-title">Empresas recentes</div>
            <div class="card-sub">Últimos cadastros B2B na plataforma</div>
          </div>
          <a href="<?= htmlspecialchars(app_url('/admin/empresas'), ENT_QUOTES, 'UTF-8') ?>" class="card-link">Ver todas</a>
        </div>
        <table class="companies-table">
          <thead>
            <tr>
              <th>Empresa</th><th>Segmento</th><th>Anúncios</th><th>Status</th><th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentCompanies)): ?>
            <tr>
              <td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted);">
                <i data-lucide="building-2" style="display:block;margin:0 auto 0.5rem;opacity:.3;width:32px;height:32px;"></i>
                Nenhuma empresa cadastrada ainda.
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($recentCompanies as $c):
              $initials  = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $c['name']), 0, 2)));
              $color     = companyColor($c['name']);
              $statusKey = $c['status'] ?? 'active';
              $statusLbl = match($statusKey) {
                  'active'    => 'Ativa',
                  'suspended' => 'Suspensa',
                  'inactive'  => 'Inativa',
                  default     => ucfirst($statusKey),
              };
              $city = htmlspecialchars(($c['city'] ?? '') . (isset($c['state']) ? ', ' . $c['state'] : ''));
            ?>
            <tr>
              <td>
                <div class="company-cell">
                  <div class="company-initials" style="background:<?= $color ?>"><?= $initials ?></div>
                  <div>
                    <div class="company-name"><?= htmlspecialchars($c['name']) ?></div>
                    <div class="company-city"><?= $city ?></div>
                  </div>
                </div>
              </td>
              <td><?= htmlspecialchars($c['segment'] ?? '—') ?></td>
              <td><?= htmlspecialchars($c['volume']  ?? '—') ?></td>
              <td><span class="status-badge <?= $statusKey ?>"><?= $statusLbl ?></span></td>
              <td>
                <div class="table-actions"><a class="table-action-btn" href="<?= htmlspecialchars(app_url('/admin/empresas'), ENT_QUOTES, 'UTF-8') ?>" title="Abrir gestão de empresas"><i data-lucide="eye"></i></a></div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Atividades recentes -->
      <div class="card">
        <div class="card-header">
          <div class="card-title-wrap">
            <div class="card-title">Atividades recentes</div>
            <div class="card-sub">Eventos da plataforma</div>
          </div>
          <a href="<?= htmlspecialchars(app_url('/admin/saques'), ENT_QUOTES, 'UTF-8') ?>" class="card-link">Ver financeiro</a>
        </div>
        <div class="activity-list">
          <?php if (empty($recentActivity)): ?>
          <div style="text-align:center;padding:2rem;color:var(--text-muted);">
            <i data-lucide="activity" style="display:block;margin:0 auto 0.5rem;opacity:.3;width:32px;height:32px;"></i>
            Nenhuma atividade registrada ainda.
          </div>
          <?php else: ?>
          <?php foreach ($recentActivity as $act): ?>
          <div class="activity-item">
            <div class="activity-icon <?= $act['color'] ?>">
              <i data-lucide="<?= $act['icon'] ?>"></i>
            </div>
            <div class="activity-body">
              <div class="activity-title"><?= htmlspecialchars($act['title']) ?></div>
              <div class="activity-desc"><?= htmlspecialchars($act['desc']) ?></div>
            </div>
            <div class="activity-time"><?= $act['time'] ?></div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ── Bottom Grid ── -->
    <div class="bottom-grid">

      <!-- Volume Chart -->
      <div class="card">
        <div class="card-header">
          <div class="card-title-wrap">
            <div class="card-title">Volume transacionado</div>
            <div class="card-sub">GMV negociado — últimos 12 meses</div>
          </div>
        </div>
        <div class="chart-wrap">
          <div class="chart-header">
            <div>
              <span class="chart-total"><?= htmlspecialchars($chartStats['total_fmt']) ?></span>
              <?php if ($chartStats['delta'] ?? null): ?>
              <span class="chart-delta">
                <i data-lucide="<?= $chartStats['delta']['direcao'] === 'up' ? 'trending-up' : 'trending-down' ?>"></i>
                <?= $chartStats['delta']['direcao'] === 'up' ? '+' : '-' ?><?= $chartStats['delta']['valor'] ?>
              </span>
              <?php endif; ?>
              <div class="chart-period">vs ano anterior</div>
            </div>
          </div>
          <?php
            $pts      = $volumeChart;
            $isEmpty  = array_sum($pts) === 0;
            $minV     = $isEmpty ? 0 : min($pts);
            $maxV     = $isEmpty ? 1 : max($pts);
            $range    = max($maxV - $minV, 1);
            $count    = count($pts);
            $W = 600; $H = 140; $pad = 8;
            $step   = ($W - $pad * 2) / ($count - 1);
            $coords = [];
            foreach ($pts as $i => $v) {
                $x = $pad + $i * $step;
                $y = $isEmpty ? ($H / 2) : ($H - $pad - (($v - $minV) / $range) * ($H - $pad * 2));
                $coords[] = [$x, $y];
            }
            $polyline = implode(' ', array_map(fn($c) => $c[0].','.$c[1], $coords));
            $area     = $polyline . " {$coords[$count-1][0]},{$H} {$coords[0][0]},{$H}";
          ?>
          <?php if ($isEmpty): ?>
          <div style="text-align:center;padding:2.5rem 1rem;color:var(--text-muted);">
            <i data-lucide="bar-chart-2" style="display:block;margin:0 auto 0.5rem;opacity:.3;width:32px;height:32px;"></i>
            Nenhuma negociação concluída ainda.
          </div>
          <?php else: ?>
          <div class="chart-svg-wrap">
            <svg class="chart-svg" viewBox="0 0 <?= $W ?> <?= $H ?>" preserveAspectRatio="none">
              <defs>
                <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%"   stop-color="#157347" stop-opacity="0.18"/>
                  <stop offset="100%" stop-color="#157347" stop-opacity="0.01"/>
                </linearGradient>
              </defs>
              <polygon points="<?= $area ?>" fill="url(#areaGrad)" />
              <polyline points="<?= $polyline ?>" fill="none" stroke="#157347" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
              <?php foreach ($coords as [$cx, $cy]): ?>
              <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="3.5" fill="#157347" opacity="0"/>
              <?php endforeach; ?>
            </svg>
          </div>
          <?php endif; ?>
          <div class="chart-labels">
            <?php foreach (['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'] as $m): ?>
            <span><?= $m ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Indicadores ESG -->
      <div class="card">
        <div class="card-header">
          <div class="card-title-wrap">
            <div class="card-title">Indicadores ESG</div>
            <div class="card-sub">Performance circular do mês</div>
          </div>
        </div>
        <div class="esg-list">
          <?php foreach ($esgIndicators as $esg): ?>
          <div class="esg-item">
            <div class="esg-item-header">
              <span class="esg-item-label"><?= htmlspecialchars($esg['label']) ?></span>
              <span class="esg-item-value <?= $esg['color'] ?>"><?= $esg['value'] ?>%</span>
            </div>
            <div class="esg-bar">
              <div class="esg-bar-fill <?= $esg['color'] ?>" style="width:<?= $esg['value'] ?>%"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ── Ações Rápidas ── -->
    <div class="card quick-actions-card">
      <div class="card-header">
        <div class="card-title-wrap">
          <div class="card-title">Ações rápidas</div>
          <div class="card-sub">Atalhos operacionais</div>
        </div>
      </div>
      <div class="quick-actions-grid">
        <?php
        $actions = [];
        if (AdminAuth::can('company_approve')) $actions[] = ['icon'=>'shield-check','label'=>'Verificar Empresa','url'=>'/admin/empresas'];
        $actions[] = ['icon'=>'check-square','label'=>'Revisar Anúncios','url'=>'/admin/anuncios'];
        $actions[] = ['icon'=>'truck','label'=>'Acompanhar Entregas','url'=>'/admin/logistica'];
        if (AdminAuth::can('view_financial')) $actions[] = ['icon'=>'wallet-cards','label'=>'Aprovar Saques','url'=>'/admin/saques'];
        $actions[] = ['icon'=>'file-bar-chart','label'=>'Ver Impacto ESG','url'=>'/admin/impacto'];
        if (AdminAuth::can('support_manage')) $actions[] = ['icon'=>'life-buoy','label'=>'Ver Suporte','url'=>'/admin/suporte'];
        foreach ($actions as $a):
        ?>
        <a class="quick-action" href="<?= htmlspecialchars(app_url($a['url']), ENT_QUOTES, 'UTF-8') ?>">
          <div class="quick-action-icon"><i data-lucide="<?= $a['icon'] ?>"></i></div>
          <span class="quick-action-label"><?= $a['label'] ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

  </main>
</div>

<script>
  lucide.createIcons();

  function updateClock() {
    const el = document.getElementById('liveTime');
    if (el) el.textContent = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  }
  updateClock();
  setInterval(updateClock, 1000);

  document.getElementById('navToggle')?.addEventListener('click', () => {
    document.getElementById('navbar').classList.toggle('open');
  });

  document.querySelectorAll('.chart-svg circle').forEach(c => {
    c.closest('svg').addEventListener('mousemove', e => {
      const rect = c.closest('svg').getBoundingClientRect();
      const mx   = (e.clientX - rect.left) / rect.width * 600;
      const cx   = parseFloat(c.getAttribute('cx'));
      c.setAttribute('opacity', Math.abs(cx - mx) < 20 ? '1' : '0');
    });
  });
</script>
</body>
</html>

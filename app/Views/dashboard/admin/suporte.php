<?php $userName = htmlspecialchars($user['name'] ?? 'Administrador', ENT_QUOTES, 'UTF-8'); ?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Central de Suporte — Re.Source</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"><script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script><link rel="stylesheet" href="/re.source/public/css/admin-dashboard.css"></head><body>
<header class="site-header"><?php require __DIR__ . '/../../components/topbar.php'; require __DIR__ . '/../../components/navbar.php'; ?></header>
<main class="admin-content admin-operational-page">
  <header class="admin-page-heading"><div><span>Operação do MVP</span><h1>Central de Suporte</h1><p>Acompanhe pontos que podem exigir intervenção administrativa.</p></div><a href="mailto:contato@resource.com.br" class="admin-primary-action"><i data-lucide="mail"></i> Contato externo</a></header>
  <div class="support-summary-grid">
    <?php foreach ([['building-2','Empresas em análise',$supportSummary['pending_companies']],['handshake','Negociações em curso',$supportSummary['open_negotiations']],['truck','Entregas ativas',$supportSummary['active_deliveries']],['wallet','Saques em análise',$supportSummary['pending_withdrawals']]] as [$icon,$label,$value]): ?>
      <article class="support-summary-card"><i data-lucide="<?= $icon ?>"></i><div><strong><?= (int)$value ?></strong><span><?= $label ?></span></div></article>
    <?php endforeach; ?>
  </div>
  <div class="support-layout">
    <section class="admin-panel"><header><div><h2>Fila operacional</h2><p>Negociações ainda não concluídas.</p></div><a href="/re.source/admin/negociacoes">Ver todas</a></header>
      <div class="admin-table-wrap"><table class="admin-simple-table"><thead><tr><th>Protocolo</th><th>Anúncio</th><th>Empresas</th><th>Status</th></tr></thead><tbody>
      <?php if (!$supportQueue): ?><tr><td colspan="4" class="table-empty">Nenhuma negociação exige acompanhamento.</td></tr><?php endif; ?>
      <?php foreach ($supportQueue as $item): ?><tr><td>#<?= (int)$item['id'] ?></td><td><?= htmlspecialchars($item['title'],ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars($item['buyer_name'].' ↔ '.$item['seller_name'],ENT_QUOTES,'UTF-8') ?></td><td><span class="admin-status-pill"><?= htmlspecialchars($item['status'],ENT_QUOTES,'UTF-8') ?></span></td></tr><?php endforeach; ?>
      </tbody></table></div>
    </section>
    <aside class="admin-panel"><header><div><h2>Alertas recentes</h2><p>Auditoria com severidade elevada.</p></div></header><div class="support-alert-list">
      <?php if (!$supportActivity): ?><p class="table-empty">Nenhum alerta recente.</p><?php endif; ?>
      <?php foreach ($supportActivity as $activity): ?><div class="support-alert"><i data-lucide="alert-triangle"></i><div><strong><?= htmlspecialchars($activity['action'],ENT_QUOTES,'UTF-8') ?></strong><span><?= htmlspecialchars(($activity['entity_type'] ?: 'registro').' #'.($activity['entity_id'] ?: '—'),ENT_QUOTES,'UTF-8') ?></span><time><?= htmlspecialchars($activity['created_at'],ENT_QUOTES,'UTF-8') ?></time></div></div><?php endforeach; ?>
    </div></aside>
  </div>
</main><script>window.lucide?.createIcons();document.getElementById('navToggle')?.addEventListener('click',()=>document.getElementById('navbar')?.classList.toggle('mobile-open'));</script></body></html>

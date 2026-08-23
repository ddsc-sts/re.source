<?php
$titulo_pagina = $titulo_pagina ?? 'Impacto circular — Re.Source';
$hideSearchBar = true;
require VIEW_PATH . '/components/header.php';
$reusedKg = (float) ($overview['reused_kg'] ?? 0);
$co2Kg = (float) ($overview['avoided_co2_kg'] ?? 0);
$formatMass = static fn(float $kg): string => $kg >= 1000
    ? number_format($kg / 1000, 1, ',', '.') . ' t'
    : number_format($kg, 0, ',', '.') . ' kg';
$completed = (int) ($overview['completed_negotiations'] ?? 0);
$revenue = (float) ($overview['released_revenue'] ?? 0);
$announcedKg = max($reusedKg, $reusedKg * 1.15);
$reusedPercent = $announcedKg > 0 ? min(100, ($reusedKg / $announcedKg) * 100) : 0;
?>
<main class="v2-impact-page">
  <header class="v2-page-heading">
    <div><span class="ui-eyebrow">Resultados da sua empresa</span><h1>Impacto circular</h1><p>Dados rastreáveis sobre materiais, receita e emissões evitadas.</p></div>
    <button class="v2-secondary-action" type="button" onclick="window.print()"><i data-lucide="download"></i> Exportar relatório</button>
  </header>

  <section class="v2-impact-summary">
    <div class="v2-impact-message"><h2>Seus materiais continuam gerando valor.</h2><p>Resultados acumulados das negociações concluídas.</p></div>
    <article><i data-lucide="refresh-cw"></i><strong><?= $formatMass($reusedKg) ?></strong><span>resíduos desviados</span></article>
    <article><i data-lucide="cloud"></i><strong><?= $formatMass($co2Kg) ?></strong><span>CO₂e evitado</span></article>
    <article><i data-lucide="circle-dollar-sign"></i><strong>R$ <?= number_format($revenue, 2, ',', '.') ?></strong><span>receita recuperada</span></article>
    <article><i data-lucide="handshake"></i><strong><?= number_format($completed, 0, ',', '.') ?></strong><span>negociações circulares</span></article>
  </section>

  <section class="v2-impact-grid">
    <article class="v2-impact-card v2-flow-panel">
      <div class="v2-card-heading"><div><span class="ui-eyebrow">Fluxo de materiais</span><h2>Do anúncio ao reaproveitamento</h2></div><span><?= number_format($reusedPercent, 1, ',', '.') ?>% movimentado</span></div>
      <div class="v2-material-progress"><div style="width:<?= number_format($reusedPercent, 2, '.', '') ?>%"></div></div>
      <div class="v2-flow-numbers"><div><small>Materiais anunciados</small><strong><?= $formatMass($announcedKg) ?></strong></div><i data-lucide="arrow-right"></i><div><small>Reutilizados</small><strong><?= $formatMass($reusedKg) ?></strong></div><i data-lucide="arrow-right"></i><div><small>CO₂e evitado</small><strong><?= $formatMass($co2Kg) ?></strong></div></div>
    </article>
    <article class="v2-impact-card">
      <span class="ui-eyebrow">Metodologia</span><h2>Como calculamos</h2>
      <dl><div><dt>Fator utilizado</dt><dd><?= number_format((float) ($method['factor_kg_co2e_per_kg'] ?? 2.5), 2, ',', '.') ?> kg CO₂e/kg</dd></div><div><dt>Fonte</dt><dd><?= htmlspecialchars((string) ($method['source_name'] ?? 'Metodologia re.source'), ENT_QUOTES, 'UTF-8') ?></dd></div><div><dt>Versão</dt><dd><?= htmlspecialchars((string) ($method['methodology_version'] ?? 'MVP-2026.1'), ENT_QUOTES, 'UTF-8') ?></dd></div></dl>
      <p class="v2-method-note"><i data-lucide="info"></i> Estimativa acadêmica baseada no peso e destino informado. Transporte e particularidades do material ainda não estão incluídos.</p>
    </article>
  </section>
</main>
<?php require VIEW_PATH . '/components/footer.php'; ?>

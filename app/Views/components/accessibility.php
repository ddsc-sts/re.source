<?php
if (!empty($GLOBALS['resourceAccessibilityRendered'])) { return; }
$GLOBALS['resourceAccessibilityRendered'] = true;
?>
<button class="a11y-launcher" id="a11yLauncher" type="button" aria-haspopup="dialog" aria-expanded="false" aria-controls="a11yPanel">
  <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="4" r="2" fill="currentColor"/><path d="M4 8h16M12 6v15M8 21l4-7 4 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  <span data-i18n="accessibility">Acessibilidade</span>
</button>

<section class="a11y-panel" id="a11yPanel" role="dialog" aria-labelledby="a11yTitle" hidden>
  <div class="a11y-head"><h2 id="a11yTitle" data-i18n="accessibility_options">Opções de acessibilidade</h2><button class="a11y-close" type="button" aria-label="Fechar acessibilidade">×</button></div>
  <div class="a11y-group">
    <label for="a11yLanguage" data-i18n="site_language">Idioma da interface</label>
    <select id="a11yLanguage"><option value="pt-BR">Português (Brasil)</option><option value="en">English</option><option value="es">Español</option></select>
  </div>
  <div class="a11y-group">
    <p class="a11y-group-title" data-i18n="text_size">Tamanho do texto</p>
    <div class="a11y-actions"><button type="button" data-font="normal" aria-label="Texto padrão" aria-pressed="true">A</button><button type="button" data-font="large" aria-label="Texto grande" aria-pressed="false">A+</button><button type="button" data-font="larger" aria-label="Texto maior" aria-pressed="false">A++</button></div>
  </div>
  <div class="a11y-group">
    <label for="a11yColorVision" data-i18n="color_vision">Percepção de cores</label>
    <select id="a11yColorVision"><option value="default">Padrão</option><option value="protanopia">Protanopia</option><option value="deuteranopia">Deuteranopia</option><option value="tritanopia">Tritanopia</option><option value="monochrome">Monocromático</option></select>
    <button class="a11y-toggle" type="button" data-setting="contrast" aria-pressed="false" data-i18n="high_contrast">Alto contraste</button>
    <button class="a11y-toggle" type="button" data-setting="readable" aria-pressed="false" data-i18n="readable_font">Fonte de alta legibilidade</button>
    <button class="a11y-toggle" type="button" data-setting="lineSpacing" aria-pressed="false" data-i18n="line_spacing">Aumentar espaçamento entre linhas</button>
    <button class="a11y-toggle" type="button" data-setting="highlightLinks" aria-pressed="false" data-i18n="highlight_links">Sublinhar e destacar links</button>
    <button class="a11y-toggle" type="button" data-setting="reduceMotion" aria-pressed="false" data-i18n="reduce_motion">Reduzir animações</button>
  </div>
  <div class="a11y-group"><button class="a11y-guide-btn" id="openSiteGuide" type="button" data-i18n="open_guide">Abrir guia do site</button><button class="a11y-reset" id="resetA11y" type="button" data-i18n="reset_preferences">Restaurar preferências</button></div>
</section>

<div class="site-guide" id="siteGuide" role="dialog" aria-modal="true" aria-labelledby="siteGuideTitle" hidden>
  <div class="site-guide-card">
    <h2 id="siteGuideTitle" data-i18n="guide_title">Como usar a Re.Source</h2>
    <p data-i18n="guide_intro">Escolha o que você quer fazer. Você pode retornar a este guia pelo botão de acessibilidade.</p>
    <div class="guide-grid">
      <a class="guide-link" href="<?= htmlspecialchars(app_url('/busca'), ENT_QUOTES, 'UTF-8') ?>"><strong data-i18n="find_materials">Encontrar materiais</strong><span data-i18n="find_materials_desc">Pesquise por categoria, localização e disponibilidade.</span></a>
      <a class="guide-link" href="<?= htmlspecialchars(app_url('/anuncios/novo'), ENT_QUOTES, 'UTF-8') ?>"><strong data-i18n="publish_material">Publicar material</strong><span data-i18n="publish_material_desc">Cadastre um resíduo ou insumo para reaproveitamento.</span></a>
      <a class="guide-link" href="<?= htmlspecialchars(app_url('/conversas'), ENT_QUOTES, 'UTF-8') ?>"><strong data-i18n="negotiate">Negociar com segurança</strong><span data-i18n="negotiate_desc">Acompanhe propostas e mensagens em um só lugar.</span></a>
      <a class="guide-link" href="<?= htmlspecialchars(app_url('/impacto'), ENT_QUOTES, 'UTF-8') ?>"><strong data-i18n="impact">Ver seu impacto</strong><span data-i18n="impact_desc">Consulte indicadores ambientais e relatórios ESG.</span></a>
      <a class="guide-link" href="<?= htmlspecialchars(app_url('/ajuda'), ENT_QUOTES, 'UTF-8') ?>"><strong data-i18n="help_center">Central de ajuda</strong><span data-i18n="help_center_desc">Encontre respostas e canais de atendimento.</span></a>
    </div>
    <button class="guide-close" type="button" data-i18n="close_guide">Fechar guia</button>
  </div>
</div>

<div id="a11yLive" class="sr-only" aria-live="polite"></div>
<div vw class="enabled"><div vw-access-button class="active"></div><div vw-plugin-wrapper><div class="vw-plugin-top-wrapper"></div></div></div>
<script src="https://vlibras.gov.br/app/vlibras-plugin.js" defer></script>
<script src="<?= htmlspecialchars(asset_url('/js/accessibility.js?v=3.2'), ENT_QUOTES, 'UTF-8') ?>" defer></script>

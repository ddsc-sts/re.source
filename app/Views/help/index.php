<?php
$titulo_pagina = $titulo_pagina ?? 'Central de Ajuda — Re.Source';
$css_especifico = asset_url('/css/experience.css');
require VIEW_PATH . '/components/header.php';
$guides = [
  ['package-plus', 'Cadastrar um resíduo', 'Abra “Novo anúncio”, informe categoria, quantidade, localização e fotos, revise e publique.'],
  ['pencil', 'Editar um anúncio', 'Em “Meus anúncios”, escolha o material e use Editar. Salve para atualizar o catálogo.'],
  ['search', 'Pesquisar materiais', 'Use a busca por texto e categoria. Refine por localização, tipo e faixa de preço.'],
  ['messages-square', 'Iniciar uma negociação', 'Abra um anúncio de outra empresa e selecione iniciar conversa.'],
  ['handshake', 'Enviar uma proposta', 'Na conversa, informe quantidade, valor, prazo e quem será responsável pelo frete.'],
  ['truck', 'Acompanhar uma entrega', 'Acesse Entregas para ver cotação, contratação, transporte e confirmação.'],
  ['leaf', 'Entender os indicadores ESG', 'As métricas são estimativas e exibem quantidade, fator e método de cálculo.'],
  ['message-circle', 'Utilizar o chat', 'Troque mensagens, acompanhe itens não lidos e mantenha a negociação no mesmo histórico.'],
];
?>
<main class="help-page">
  <header class="help-hero">
    <span>Suporte re.source</span>
    <h1>Como podemos ajudar?</h1>
    <p>Encontre respostas rápidas para completar todo o ciclo de uma negociação circular.</p>
    <label class="v2-help-search"><i data-lucide="search"></i><span class="sr-only">Buscar ajuda</span><input id="helpSearch" type="search" placeholder="Busque uma dúvida ou assunto"></label>
  </header>
  <section class="help-grid" aria-label="Tutoriais da plataforma">
    <?php foreach ($guides as [$icon, $title, $copy]): ?>
      <article class="help-card" data-help-card data-search="<?= htmlspecialchars(mb_strtolower($title . ' ' . $copy), ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="<?= $icon ?>"></i><h2><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2><p><?= htmlspecialchars($copy, ENT_QUOTES, 'UTF-8') ?></p></article>
    <?php endforeach; ?>
  </section>
  <p class="v2-help-empty" id="helpEmpty" hidden>Nenhum guia encontrado. Tente outro termo ou fale com nossa equipe.</p>
  <section class="help-flow"><h2>Uma negociação, cinco etapas</h2><ol><li>Publique ou encontre um material</li><li>Converse e envie a proposta</li><li>Comprador e vendedor aceitam</li><li>Organize e acompanhe o frete</li><li>Confirme a entrega e consulte o impacto</li></ol></section>
  <section class="v2-help-contact"><div><span class="ui-eyebrow">Ainda precisa de ajuda?</span><h2>Converse com nossa equipe</h2><p>Atendimento de segunda a sexta, das 8h às 18h.</p></div><a class="ui-btn ui-btn--primary" href="<?= htmlspecialchars(app_url('/contato'), ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="message-circle"></i> Falar com o suporte</a></section>
</main>
<?php require VIEW_PATH . '/components/footer.php'; ?>

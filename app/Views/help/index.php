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
    <span>Aprenda no seu ritmo</span>
    <h1>Central de Ajuda</h1>
    <p>Respostas rápidas para completar todo o ciclo de uma negociação circular.</p>
    <a href="<?= htmlspecialchars(app_url('/base?tour=1'), ENT_QUOTES, 'UTF-8') ?>" class="help-tour"><i data-lucide="play-circle"></i> Fazer tour guiado</a>
  </header>
  <section class="help-grid" aria-label="Tutoriais da plataforma">
    <?php foreach ($guides as [$icon, $title, $copy]): ?>
      <article class="help-card"><i data-lucide="<?= $icon ?>"></i><h2><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2><p><?= htmlspecialchars($copy, ENT_QUOTES, 'UTF-8') ?></p></article>
    <?php endforeach; ?>
  </section>
  <section class="help-flow"><h2>Uma negociação, cinco etapas</h2><ol><li>Publique ou encontre um material</li><li>Converse e envie a proposta</li><li>Comprador e vendedor aceitam</li><li>Organize e acompanhe o frete</li><li>Confirme a entrega e consulte o impacto</li></ol></section>
</main>
<?php require VIEW_PATH . '/components/footer.php'; ?>

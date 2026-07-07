<?php
$titulo_pagina = $titulo_pagina ?? "Política de Privacidade — Re.Source";
$css_especifico = "/re.source/public/css/legal.css";
$privacyEmail = app_setting('support_email', 'contato@resource.com.br') ?: 'contato@resource.com.br';
require_once __DIR__ . '/../components/header.php';
?>

<section class="legal-hero">
  <div class="legal-hero-inner">
    <div class="legal-eyebrow">
      <span class="eyebrow-dot"></span>
      Documento legal
    </div>
    <h1>Política de Privacidade</h1>
    <p>Como a Re.Source coleta, usa e protege dados no marketplace B2B.</p>
  </div>
</section>

<div class="legal-wrap">
  <span class="legal-updated"><i data-lucide="calendar" style="width:14px;height:14px;"></i> Última atualização: julho de 2026</span>

  <div class="legal-toc" id="topo">
    <strong>Nesta página</strong>
    <ol>
      <li><a href="#dados">Dados coletados</a></li>
      <li><a href="#uso">Uso das informações</a></li>
      <li><a href="#compartilhamento">Compartilhamento</a></li>
      <li><a href="#seguranca">Segurança</a></li>
      <li><a href="#retencao">Retenção</a></li>
      <li><a href="#direitos">Direitos do titular</a></li>
      <li><a href="#contato">Contato</a></li>
    </ol>
  </div>

  <div class="legal-section" id="dados">
    <h2><span class="num">1</span> Dados coletados</h2>
    <p>Coletamos dados fornecidos pela empresa no cadastro, como razão social, nome fantasia, CNPJ, e-mail, telefone, endereço, segmento de atuação e dados do responsável. Também registramos informações geradas pelo uso da plataforma, como anúncios, propostas, mensagens, solicitações de frete, saldos internos e logs administrativos.</p>
  </div>

  <div class="legal-section" id="uso">
    <h2><span class="num">2</span> Uso das informações</h2>
    <p>Os dados são usados para validar empresas, operar o marketplace, permitir negociações, registrar auditoria, enviar notificações essenciais, apoiar atendimento e demonstrar indicadores acadêmicos do MVP.</p>
    <div class="legal-highlight">A Re.Source é uma plataforma B2B acadêmica. O financeiro, o frete e alguns indicadores são controlados internamente para fins de demonstração.</div>
  </div>

  <div class="legal-section" id="compartilhamento">
    <h2><span class="num">3</span> Compartilhamento</h2>
    <p>Dados comerciais essenciais podem ser exibidos a outras empresas durante anúncios, conversas e negociações. Não vendemos dados pessoais. Informações podem ser compartilhadas quando necessário para cumprir obrigações legais, proteger a plataforma ou executar uma funcionalidade solicitada.</p>
  </div>

  <div class="legal-section" id="seguranca">
    <h2><span class="num">4</span> Segurança</h2>
    <p>Utilizamos autenticação, controle de sessão, CSRF, senhas com hash, logs de auditoria e validações de permissão para reduzir riscos. Mesmo assim, nenhum sistema é absolutamente imune a falhas, por isso recomendamos que usuários mantenham credenciais sob sigilo.</p>
  </div>

  <div class="legal-section" id="retencao">
    <h2><span class="num">5</span> Retenção</h2>
    <p>Dados são mantidos enquanto a conta estiver ativa e pelo período necessário para auditoria, defesa de direitos, integridade do histórico de negociações e apresentação acadêmica. Contas desativadas podem ter acesso bloqueado e dados preservados conforme necessidade operacional.</p>
  </div>

  <div class="legal-section" id="direitos">
    <h2><span class="num">6</span> Direitos do titular</h2>
    <p>A empresa ou responsável pode solicitar acesso, correção ou revisão de informações mantidas na plataforma. Algumas solicitações podem ser limitadas quando houver obrigação de preservar registros transacionais, financeiros ou de auditoria.</p>
  </div>

  <div class="legal-section" id="contato">
    <h2><span class="num">7</span> Contato</h2>
    <p>Dúvidas sobre privacidade podem ser enviadas para o canal abaixo.</p>
    <div class="legal-contact-box">
      <p>Fale com a nossa equipe</p>
      <a href="mailto:<?= htmlspecialchars($privacyEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($privacyEmail, ENT_QUOTES, 'UTF-8') ?></a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

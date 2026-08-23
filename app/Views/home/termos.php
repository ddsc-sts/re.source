<?php
// 1. Configurações dinâmicas para esta página específica
$titulo_pagina = $titulo_pagina ?? "Termos de Uso — Re.Source";
$css_especifico = "/re.source/public/css/legal.css";

// 2. Puxa o cabeçalho global
require_once __DIR__ . '/../components/header.php';
?>

<main id="main-content">

<section class="legal-hero">
  <div class="legal-hero-inner">
    <div class="legal-eyebrow">
      <span class="eyebrow-dot"></span>
      Documento legal
    </div>
    <h1>Termos de Uso</h1>
    <p>Regras de utilização da plataforma Re.Source para empresas cadastradas.</p>
  </div>
</section>

<div class="legal-wrap">

  <span class="legal-updated"><i data-lucide="calendar" style="width:14px;height:14px;"></i> Última atualização: julho de 2026</span>

  <div class="legal-toc" id="topo">
    <strong>Nesta página</strong>
    <ol>
      <li><a href="#aceitacao">Aceitação dos termos</a></li>
      <li><a href="#descricao">Descrição do serviço</a></li>
      <li><a href="#cadastro">Cadastro e elegibilidade</a></li>
      <li><a href="#anuncios">Anúncios e negociações</a></li>
      <li><a href="#financeiro">Regras financeiras</a></li>
      <li><a href="#condutas">Condutas proibidas</a></li>
      <li><a href="#propriedade">Propriedade intelectual</a></li>
      <li><a href="#responsabilidade">Limitação de responsabilidade</a></li>
      <li><a href="#suspensao">Suspensão e encerramento</a></li>
      <li><a href="#alteracoes">Alterações destes termos</a></li>
      <li><a href="#foro">Legislação e foro</a></li>
      <li><a href="#contato">Contato</a></li>
    </ol>
  </div>

  <div class="legal-section" id="aceitacao">
    <h2><span class="num">1</span> Aceitação dos termos</h2>
    <p>Ao criar uma conta ou utilizar qualquer funcionalidade da Re.Source, sua empresa declara que leu, entendeu e concorda integralmente com estes Termos de Uso e com a nossa <a href="/re.source/privacidade">Política de Privacidade</a>. Caso não concorde com alguma disposição, a empresa não deve utilizar a plataforma.</p>
    <div class="legal-highlight">Este projeto é desenvolvido em contexto acadêmico (MVP) e estes termos descrevem o funcionamento pretendido da plataforma para fins de demonstração.</div>
  </div>

  <div class="legal-section" id="descricao">
    <h2><span class="num">2</span> Descrição do serviço</h2>
    <p>A Re.Source é um marketplace B2B que conecta empresas para a negociação de resíduos e materiais reaproveitáveis, promovendo economia circular. A plataforma disponibiliza recursos de anúncio, busca, chat entre empresas, propostas comerciais, simulação de frete, código de confirmação de entrega e controle financeiro interno (saldo, reserva e saque).</p>
    <p>A Re.Source atua apenas como intermediária tecnológica entre as empresas. A responsabilidade pela qualidade, quantidade, descrição e legalidade dos materiais anunciados é exclusiva da empresa anunciante.</p>
  </div>

  <div class="legal-section" id="cadastro">
    <h2><span class="num">3</span> Cadastro e elegibilidade</h2>
    <p>O acesso à plataforma é restrito a pessoas jurídicas (CNPJ ativo). Não é permitido o cadastro de pessoas físicas.</p>
    <ul>
      <li>As informações fornecidas no cadastro (razão social, nome fantasia, CNPJ, endereço, segmento, dados do responsável) devem ser verdadeiras e mantidas atualizadas.</li>
      <li>Toda conta passa por análise e aprovação manual antes da liberação total de acesso.</li>
      <li>A empresa é responsável pela guarda da senha e por qualquer atividade realizada em sua conta.</li>
    </ul>
  </div>

  <div class="legal-section" id="anuncios">
    <h2><span class="num">4</span> Anúncios e negociações</h2>
    <p>Os anúncios devem descrever com precisão o material oferecido (categoria, quantidade, condição e localização). Negociações, propostas e aceites feitos dentro da plataforma geram um protocolo de acordo único entre as partes envolvidas.</p>
    <p>Após o aceite mútuo de uma proposta, a edição das condições comerciais é bloqueada, sendo possível apenas a recusa ou o cancelamento com justificativa, conforme as regras internas de cada etapa do fluxo de negociação.</p>
  </div>

  <div class="legal-section" id="financeiro">
    <h2><span class="num">5</span> Regras financeiras</h2>
    <p>A plataforma opera com um controle financeiro interno (ledger), no qual valores de transações concluídas são creditados ao saldo da empresa vendedora. Saques podem ser solicitados via PIX ou TED e estão sujeitos à aprovação manual da administração.</p>
    <ul>
      <li>Ao solicitar um saque, o valor correspondente é reservado imediatamente do saldo disponível.</li>
      <li>Em caso de recusa do saque, o valor reservado é integralmente devolvido ao saldo disponível.</li>
      <li>O frete apresentado na plataforma é simulado para fins de demonstração acadêmica do fluxo logístico.</li>
    </ul>
  </div>

  <div class="legal-section" id="condutas">
    <h2><span class="num">6</span> Condutas proibidas</h2>
    <p>É expressamente proibido:</p>
    <ul>
      <li>Utilizar a plataforma para fins ilícitos ou para anunciar materiais cuja comercialização seja vedada por lei;</li>
      <li>Fornecer informações falsas sobre a empresa, seus produtos ou representantes;</li>
      <li>Tentar acessar dados de outras empresas sem autorização ou burlar mecanismos de segurança;</li>
      <li>Utilizar o chat da plataforma para fins diversos da negociação de resíduos, incluindo spam ou assédio.</li>
    </ul>
  </div>

  <div class="legal-section" id="propriedade">
    <h2><span class="num">7</span> Propriedade intelectual</h2>
    <p>A marca Re.Source, sua identidade visual, layout e o código-fonte da plataforma são protegidos e não podem ser copiados, redistribuídos ou utilizados comercialmente sem autorização prévia. O conteúdo enviado por cada empresa (fotos, descrições de anúncios, logotipo) permanece de propriedade da própria empresa, que concede à Re.Source uma licença de uso para exibição dentro da plataforma.</p>
  </div>

  <div class="legal-section" id="responsabilidade">
    <h2><span class="num">8</span> Limitação de responsabilidade</h2>
    <p>A Re.Source não garante a concretização de negócios, a qualidade dos materiais anunciados por terceiros ou a solvência das empresas participantes. A plataforma envida esforços razoáveis para manter o serviço disponível e seguro, mas não se responsabiliza por indisponibilidades temporárias, falhas de conectividade ou eventos fora de seu controle.</p>
  </div>

  <div class="legal-section" id="suspensao">
    <h2><span class="num">9</span> Suspensão e encerramento</h2>
    <p>A Re.Source pode suspender ou encerrar contas que violem estes Termos, mediante notificação, sem prejuízo de eventuais medidas cabíveis. A empresa pode solicitar o encerramento voluntário de sua conta a qualquer momento, entrando em contato com nossa equipe.</p>
  </div>

  <div class="legal-section" id="alteracoes">
    <h2><span class="num">10</span> Alterações destes termos</h2>
    <p>Estes Termos podem ser atualizados periodicamente para refletir mudanças no funcionamento da plataforma. Alterações relevantes serão comunicadas dentro do próprio sistema. O uso continuado da plataforma após uma atualização representa a aceitação da nova versão.</p>
  </div>

  <div class="legal-section" id="foro">
    <h2><span class="num">11</span> Legislação e foro</h2>
    <p>Estes Termos são regidos pela legislação brasileira. Fica eleito o foro da comarca de Joinville/SC para dirimir eventuais controvérsias, com renúncia a qualquer outro, por mais privilegiado que seja.</p>
  </div>

  <div class="legal-section" id="contato">
    <h2><span class="num">12</span> Contato</h2>
    <p>Dúvidas sobre estes Termos de Uso podem ser enviadas para o e-mail abaixo.</p>
    <div class="legal-contact-box">
      <p>Fale com a nossa equipe</p>
      <a href="mailto:contato@resource.com.br">contato@resource.com.br</a>
    </div>
  </div>

</div>

</main>

<?php
// 3. Puxa o rodapé global
require_once __DIR__ . '/../components/footer.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Re.Source — Conectando a Indústria Circular</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <link rel="stylesheet" href="/re.source/public/css/index.css" />
</head>
<body>

  <!-- ══ NAVBAR ══ -->
  <header class="site-header" id="siteHeader">
    <div class="header-inner">
      <a href="#" class="logo-mark">
        <svg width="38" height="38" viewBox="0 0 38 38" fill="none">
          <rect x="2" y="2" width="13" height="13" rx="2" stroke="#157347" stroke-width="2" fill="none"/>
          <rect x="2" y="23" width="13" height="13" rx="2" stroke="#157347" stroke-width="2" fill="none"/>
          <path d="M19 8.5 C27 8.5 27 19 27 19 C27 19 27 29.5 19 29.5" stroke="#157347" stroke-width="2" fill="none" stroke-linecap="round"/>
          <circle cx="19" cy="8.5" r="2.5" fill="#157347"/>
          <circle cx="19" cy="29.5" r="2.5" fill="#157347"/>
        </svg>
        <span>Re<span class="dot">.</span>Source</span>
      </a>

      <nav class="main-nav">
        <a href="#como-funciona">Como funciona</a>
        <a href="#categorias">Categorias</a>
        <a href="#impacto">Impacto ESG</a>
        <a href="#seguranca">Segurança</a>
      </nav>

      <div class="header-ctas">
        <a href="/re.source/login" class="link-login">Entrar</a>
        <a href="/re.source/cadastro" class="btn-cta-nav">Cadastrar minha empresa</a>
      </div>

      <button class="nav-toggle" id="navToggle" aria-label="Abrir menu">
        <i data-lucide="menu"></i>
      </button>
    </div>

    <!-- mobile -->
    <div class="mobile-nav" id="mobileNav">
      <a href="#como-funciona">Como funciona</a>
      <a href="#categorias">Categorias</a>
      <a href="#impacto">Impacto ESG</a>
      <a href="#seguranca">Segurança</a>
     <div class="mobile-nav-ctas">
        <a href="/re.source/login" class="btn-cta-nav">Entrar</a>
        <a href="/re.source/cadastro" class="btn-cta-nav">Cadastrar minha empresa</a>
      </div>
    </div>
  </header>

  <!-- ══ HERO ══ -->
  <section class="hero">
    <div class="hero-bg-lines"></div>
    <div class="hero-glow"></div>

    <div class="hero-layout">
      <!-- texto -->
      <div class="hero-left">
        <div class="hero-eyebrow">
          <span class="eyebrow-dot"></span>
          Plataforma B2B &nbsp;·&nbsp; Economia Circular &nbsp;·&nbsp; ESG
        </div>

        <h1 class="hero-heading">
          Conectando<br>
          <em>Indústrias,</em><br>
          Gerando Valor
        </h1>

        <p class="hero-body">
          O marketplace exclusivo para empresas comprarem, venderem e reaproveitarem resíduos industriais. Sem pessoas físicas. Sem ruído. Só negócio sustentável.
        </p>

        <div class="hero-actions">
          <a href="#" class="btn-primary-lg">Cadastrar minha empresa</a>
          <a href="#como-funciona" class="btn-ghost-lg">
            Ver como funciona
            <i data-lucide="arrow-down" style="width:16px;height:16px;"></i>
          </a>
        </div>

        <div class="hero-numbers">
          <div class="hn-item">
            <strong>+1.200</strong>
            <span>empresas ativas</span>
          </div>
          <div class="hn-sep"></div>
          <div class="hn-item">
            <strong>+48 t</strong>
            <span>resíduos/mês</span>
          </div>
          <div class="hn-sep"></div>
          <div class="hn-item">
            <strong>8 estados</strong>
            <span>de cobertura</span>
          </div>
        </div>
      </div>

      <!-- mockup -->
      <div class="hero-right">
        <div class="mockup-shell">
          <div class="mockup-bar">
            <span class="mbar-dot r"></span>
            <span class="mbar-dot y"></span>
            <span class="mbar-dot g"></span>
            <span class="mbar-url">re.source · marketplace</span>
          </div>
          <div class="mockup-search-row">
            <i data-lucide="search" style="width:15px;height:15px;color:#6C757D"></i>
            <span>Ex: aparas de papelão, serragem…</span>
          </div>
          <div class="mockup-listings">
            <div class="ml-item featured">
              <div class="ml-left">
                <span class="ml-cat">Papelão</span>
                <strong>Aparas de Papelão</strong>
                <span class="ml-co">Ind. Têxtil Alfa · SP</span>
              </div>
              <div class="ml-right">
                <span class="ml-qty">500 kg</span>
                <span class="ml-price">R$ 0,45/kg</span>
              </div>
            </div>
            <div class="ml-item">
              <div class="ml-left">
                <span class="ml-cat">Madeira</span>
                <strong>Serragem de Pinus</strong>
                <span class="ml-co">MadeiraMais · PR</span>
              </div>
              <div class="ml-right">
                <span class="ml-qty">2 t</span>
                <span class="ml-price">R$ 0,12/kg</span>
              </div>
            </div>
            <div class="ml-item">
              <div class="ml-left">
                <span class="ml-cat">Metal</span>
                <strong>Sucata de Alumínio</strong>
                <span class="ml-co">AutoPeças Zeta · MG</span>
              </div>
              <div class="ml-right">
                <span class="ml-qty">300 kg</span>
                <span class="ml-price">R$ 4,20/kg</span>
              </div>
            </div>
            <div class="ml-item">
              <div class="ml-left">
                <span class="ml-cat">Têxtil</span>
                <strong>Retalhos de Tecido</strong>
                <span class="ml-co">Confecções Delta · SC</span>
              </div>
              <div class="ml-right">
                <span class="ml-qty">80 kg</span>
                <span class="ml-price">R$ 1,10/kg</span>
              </div>
            </div>
          </div>
        </div>

        <!-- floater badge -->
        <div class="hero-floater">
          <i data-lucide="shield-check" style="width:18px;height:18px;color:#157347"></i>
          <span>Somente empresas verificadas</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ COMO FUNCIONA ══ -->
  <section class="section cf-section" id="como-funciona">
    <div class="wrap">
      <div class="section-header reveal">
        <p class="section-eyebrow">Como funciona</p>
        <h2 class="section-title">Três passos.<br>Sem complicação.</h2>
      </div>

      <div class="cf-steps">
        <div class="cf-step reveal">
          <div class="cf-num">01</div>
          <div class="cf-icon-wrap"><i data-lucide="building-2"></i></div>
          <h3>Cadastre sua empresa</h3>
          <p>Validamos CNPJ, localização e segmento antes de liberar qualquer acesso. Plataforma exclusiva B2B — zero pessoas físicas.</p>
        </div>
        <div class="cf-arrow reveal rd-1"><i data-lucide="arrow-right"></i></div>
        <div class="cf-step reveal rd-1">
          <div class="cf-num">02</div>
          <div class="cf-icon-wrap"><i data-lucide="package-search"></i></div>
          <h3>Anuncie ou pesquise</h3>
          <p>Publique resíduos com fotos, quantidade e preço. Ou busque por categoria, estado e tipo de material com filtros precisos.</p>
        </div>
        <div class="cf-arrow reveal rd-2"><i data-lucide="arrow-right"></i></div>
        <div class="cf-step reveal rd-2">
          <div class="cf-num">03</div>
          <div class="cf-icon-wrap"><i data-lucide="handshake"></i></div>
          <h3>Negocie e feche</h3>
          <p>Chat integrado, histórico auditável e relatório ESG automático a cada transação. Impacto real com cada tonelada movimentada.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ FEATURE — CATEGORIAS ══ -->
  <section class="section feat-section" id="categorias">
    <div class="wrap feat-row">
      <div class="feat-text reveal">
        <p class="section-eyebrow">Categorias</p>
        <h2 class="section-title">Oito materiais.<br><em>Infinitas conexões.</em></h2>
        <p class="feat-body">Madeira, Plástico, Têxtil, Metal, Papelão, Borracha, Eletrônicos e Vidro. Cada resíduo encontra seu comprador — e cada comprador encontra sua matéria-prima.</p>
        <a href="#" class="btn-primary-sm">Explorar categorias <i data-lucide="arrow-right" style="width:15px;height:15px;"></i></a>
      </div>
      <div class="feat-visual reveal rd-1">
        <div class="cat-grid">
          <div class="cat-tile"><span class="ct-emoji">🪵</span><span>Madeira</span></div>
          <div class="cat-tile"><span class="ct-emoji">♻️</span><span>Plástico</span></div>
          <div class="cat-tile"><span class="ct-emoji">🧵</span><span>Têxtil</span></div>
          <div class="cat-tile"><span class="ct-emoji">⚙️</span><span>Metal</span></div>
          <div class="cat-tile"><span class="ct-emoji">📦</span><span>Papelão</span></div>
          <div class="cat-tile"><span class="ct-emoji">🔘</span><span>Borracha</span></div>
          <div class="cat-tile"><span class="ct-emoji">💻</span><span>Eletrônicos</span></div>
          <div class="cat-tile"><span class="ct-emoji">🍶</span><span>Vidro</span></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ FEATURE — ESG ══ -->
  <section class="section feat-section feat-alt" id="impacto">
    <div class="wrap feat-row rev">
      <div class="feat-visual reveal">
        <div class="esg-widget">
          <div class="esg-top">
            <span class="esg-label-pill">Relatório ESG</span>
            <span class="esg-period">Jan – Mai 2025</span>
          </div>
          <div class="esg-bars">
            <div class="esg-bar-row">
              <div class="esg-bar-meta">
                <span>CO₂ evitado</span>
                <strong>4,2 t</strong>
              </div>
              <div class="esg-track"><div class="esg-fill" data-w="82" style="background:#157347"></div></div>
            </div>
            <div class="esg-bar-row">
              <div class="esg-bar-meta">
                <span>Resíduos desviados</span>
                <strong>12,8 t</strong>
              </div>
              <div class="esg-track"><div class="esg-fill" data-w="65" style="background:#2E8B57"></div></div>
            </div>
            <div class="esg-bar-row">
              <div class="esg-bar-meta">
                <span>Negócios fechados</span>
                <strong>37</strong>
              </div>
              <div class="esg-track"><div class="esg-fill" data-w="74" style="background:#3CB371"></div></div>
            </div>
          </div>
          <div class="esg-foot">
            <i data-lucide="check-circle" style="width:14px;height:14px;color:#157347"></i>
            Compatível com GRI, ISE e B3 ESG
          </div>
        </div>
      </div>

      <div class="feat-text reveal rd-1">
        <p class="section-eyebrow">Impacto ESG</p>
        <h2 class="section-title">Mais que negócio.<br><em>Relatório automático.</em></h2>
        <p class="feat-body">Cada transação gera um relatório de impacto socioambiental: CO₂ evitado, toneladas desviadas de aterros e dados prontos para GRI, ISE e seus stakeholders — sem planilha manual.</p>
        <ul class="feat-list">
          <li><i data-lucide="check" style="width:14px;height:14px;color:#157347"></i> Relatórios por transação em PDF</li>
          <li><i data-lucide="check" style="width:14px;height:14px;color:#157347"></i> Histórico auditável completo</li>
          <li><i data-lucide="check" style="width:14px;height:14px;color:#157347"></i> Dashboard de impacto em tempo real</li>
          <li><i data-lucide="check" style="width:14px;height:14px;color:#157347"></i> Dados para certificações ambientais</li>
        </ul>
        <a href="#" class="btn-primary-sm">Saiba mais <i data-lucide="arrow-right" style="width:15px;height:15px;"></i></a>
      </div>
    </div>
  </section>

  <!-- ══ FEATURE — SEGURANÇA ══ -->
  <section class="section feat-section" id="seguranca">
    <div class="wrap feat-row">
      <div class="feat-text reveal">
        <p class="section-eyebrow">Segurança B2B</p>
        <h2 class="section-title">Só empresas.<br><em>Sempre verificadas.</em></h2>
        <p class="feat-body">Nenhum anônimo opera aqui. Validamos CNPJ, endereço e segmento antes de liberar acesso. Você negocia sabendo exatamente com quem está falando — e com histórico de reputação transparente.</p>
        <ul class="feat-list">
          <li><i data-lucide="check" style="width:14px;height:14px;color:#157347"></i> Verificação de CNPJ ativa via Receita Federal</li>
          <li><i data-lucide="check" style="width:14px;height:14px;color:#157347"></i> Avaliações e reputação entre empresas</li>
          <li><i data-lucide="check" style="width:14px;height:14px;color:#157347"></i> Plataforma 100% restrita — sem PF</li>
          <li><i data-lucide="check" style="width:14px;height:14px;color:#157347"></i> Conformidade total com LGPD</li>
        </ul>
        <a href="#" class="btn-primary-sm">Criar conta empresarial <i data-lucide="arrow-right" style="width:15px;height:15px;"></i></a>
      </div>

      <div class="feat-visual reveal rd-1">
        <div class="trust-widget">
          <div class="tw-head">
            <div class="tw-avatar"><i data-lucide="building-2" style="width:22px;height:22px;color:#157347"></i></div>
            <div>
              <strong>Ind. Alfa Ltda.</strong>
              <span class="tw-verified"><i data-lucide="shield-check" style="width:12px;height:12px;"></i> Verificada</span>
            </div>
          </div>
          <div class="tw-rows">
            <div class="tw-row"><span>CNPJ</span><span>12.345.678/0001-90</span></div>
            <div class="tw-row"><span>Segmento</span><span>Têxtil</span></div>
            <div class="tw-row"><span>Localização</span><span>São Paulo, SP</span></div>
            <div class="tw-row"><span>Membro desde</span><span>Jan 2024</span></div>
          </div>
          <div class="tw-rating">
            <span class="tw-stars">★★★★★</span>
            <span class="tw-count">47 avaliações</span>
          </div>
          <div class="tw-tags">
            <span>Entrega no prazo</span>
            <span>Boa embalagem</span>
            <span>Comunicativo</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ STATS BAR ══ -->
  <section class="stats-section">
    <div class="wrap">
      <div class="stats-row">
        <div class="stat-item reveal">
          <strong>+1.200</strong>
          <span>Empresas cadastradas</span>
        </div>
        <div class="stat-item reveal rd-1">
          <strong>+48 t</strong>
          <span>Resíduos conectados/mês</span>
        </div>
        <div class="stat-item reveal rd-2">
          <strong>8</strong>
          <span>Estados de cobertura</span>
        </div>
        <div class="stat-item reveal rd-3">
          <strong>100%</strong>
          <span>Empresas verificadas</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ CTA FINAL ══ -->
  <section class="cta-section">
    <div class="wrap cta-inner">
      <p class="section-eyebrow reveal" style="justify-content:center;">Pronto para começar?</p>
      <h2 class="cta-title reveal">Transforme resíduos em<br><em>oportunidade de negócio.</em></h2>
      <p class="cta-sub reveal">Acesso exclusivo a empresas. Cadastro gratuito. Comece agora.</p>
      <div class="cta-btns reveal">
        <a href="#" class="btn-primary-lg">Cadastrar minha empresa</a>
        <a href="#" class="btn-ghost-lg">Falar com a equipe</a>
      </div>
    </div>
  </section>

  <!-- ══ FOOTER ══ -->
  <footer class="site-footer">
    <div class="wrap footer-inner">
      <div class="footer-brand">
        <a href="#" class="logo-mark footer-logo-mark">
          <svg width="32" height="32" viewBox="0 0 38 38" fill="none">
            <rect x="2" y="2" width="13" height="13" rx="2" stroke="#157347" stroke-width="2" fill="none"/>
            <rect x="2" y="23" width="13" height="13" rx="2" stroke="#157347" stroke-width="2" fill="none"/>
            <path d="M19 8.5 C27 8.5 27 19 27 19 C27 19 27 29.5 19 29.5" stroke="#157347" stroke-width="2" fill="none" stroke-linecap="round"/>
            <circle cx="19" cy="8.5" r="2.5" fill="#157347"/>
            <circle cx="19" cy="29.5" r="2.5" fill="#157347"/>
          </svg>
          <span>Re<span class="dot">.</span>Source</span>
        </a>
        <p>Conectando a Indústria Circular</p>
      </div>

      <div class="footer-cols">
        <div class="f-col">
          <strong>Plataforma</strong>
          <a href="#">Marketplace</a>
          <a href="#">Categorias</a>
          <a href="#">Como funciona</a>
        </div>
        <div class="f-col">
          <strong>Empresa</strong>
          <a href="#">Sobre nós</a>
          <a href="#">Contato</a>
          <a href="#">SENAI — SA</a>
        </div>
        <div class="f-col">
          <strong>Legal</strong>
          <a href="#">Termos de uso</a>
          <a href="#">Privacidade</a>
          <a href="#">LGPD</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2025 Re.Source — Todos os direitos reservados</span>
      <span class="footer-project">Desenvolvido no SENAI · SA</span>
    </div>
  </footer>

<script src="/re.source/public/js/index.js"></script>
</body>
</html>

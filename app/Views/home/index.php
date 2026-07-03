<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Re.Source — Conectando a Indústria Circular</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=JetBrains+Mono:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
  <link rel="stylesheet" href="public/css/index.css" />
</head>
<body>

  <!-- ══ SITE HEADER (navbar fixa no topo) ══ -->
  <header class="site-header" id="siteHeader">
    <div class="header-inner">
      <a href="#" class="logo-mark">
        <svg width="34" height="34" viewBox="0 0 38 38" fill="none">
          <rect x="2" y="2" width="13" height="13" rx="1" stroke="#157347" stroke-width="2" fill="none"/>
          <rect x="2" y="23" width="13" height="13" rx="1" stroke="#157347" stroke-width="2" fill="none"/>
          <path d="M19 8.5 C27 8.5 27 19 27 19 C27 19 27 29.5 19 29.5" stroke="#157347" stroke-width="2" fill="none" stroke-linecap="round"/>
          <circle cx="19" cy="8.5" r="2.5" fill="#157347"/>
          <circle cx="19" cy="29.5" r="2.5" fill="#157347"/>
        </svg>
        <span>Re<span class="dot">.</span>Source</span>
      </a>

      <nav class="main-nav">
        <a href="#como-funciona" data-section="como-funciona">Como funciona</a>
        <a href="#categorias" data-section="categorias">Categorias</a>
        <a href="#impacto" data-section="impacto">Impacto ESG</a>
        <a href="#seguranca" data-section="seguranca">Segurança</a>
      </nav>

      <div class="header-ctas">
        <a href="/re.source/login" class="link-login">Entrar</a>
        <a href="/re.source/cadastro" class="btn-cta-nav">Cadastrar minha empresa</a>
      </div>

      <button class="nav-toggle" id="navToggle" aria-label="Abrir menu">
        <i data-lucide="menu"></i>
      </button>
    </div>

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

  <main class="page-content">

    <!-- ══ HERO ══ -->
    <section class="hero">
      <div class="hero-bg-lines"></div>
      <div class="hero-glow"></div>

      <div class="hero-layout">
        <!-- texto -->
        <div class="hero-left">
          <div class="hero-eyebrow">
            <span class="eyebrow-dot"></span>
            Plataforma B2B · Economia Circular · ESG
          </div>

          <h1 class="hero-heading">
            Conectando<br>
            indústrias,<br>
            gerando valor<span class="accent-sq"></span>
          </h1>

          <p class="hero-body">
            O marketplace exclusivo para empresas comprarem, venderem e reaproveitarem resíduos industriais. Sem pessoas físicas. Sem ruído. Só negócio sustentável.
          </p>

          <div class="hero-actions">
            <a href="#" class="btn-primary-lg">Cadastrar minha empresa</a>
            <a href="#como-funciona" class="btn-ghost-lg">
              Ver como funciona
              <i data-lucide="arrow-down" style="width:15px;height:15px;"></i>
            </a>
          </div>

          <div class="hero-numbers">
            <div class="hn-item">
              <strong>+1.200</strong>
              <span>Empresas ativas</span>
            </div>
            <div class="hn-sep"></div>
            <div class="hn-item">
              <strong>+48t</strong>
              <span>Resíduos / mês</span>
            </div>
            <div class="hn-sep"></div>
            <div class="hn-item">
              <strong>8 UF</strong>
              <span>De cobertura</span>
            </div>
          </div>

          <div class="hero-partners">
            <p class="hp-label">// Empresas que já negociam na plataforma</p>
            <div class="hp-row">
              <span>Nortex Têxtil</span>
              <span>Ferromax</span>
              <span>Papelcon</span>
              <span>Vidralli</span>
              <span>Borraplast</span>
            </div>
          </div>
        </div>

        <!-- visual: foto industrial, direta -->
        <div class="hero-right">
          <div class="hero-photo">
            <img src="https://images.unsplash.com/photo-1615797534094-7fde0a4861f3?w=900&q=80&auto=format&fit=crop" alt="Galpão industrial parceiro da plataforma" loading="lazy" />
          </div>

          <!-- floater badge -->
          <div class="hero-floater">
            <i data-lucide="shield-check" style="width:16px;height:16px;color:#157347"></i>
            <span>Somente empresas verificadas</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ══ COMO FUNCIONA (features strip, logo abaixo do hero) ══ -->
    <section class="features-strip" id="como-funciona">
      <div class="wrap">
        <div class="section-header reveal" style="margin-bottom: 3rem;">
          <p class="section-eyebrow">Como funciona</p>
          <h2 class="section-title">Três passos.<br>Sem complicação.</h2>
        </div>
      </div>

      <div class="cf-steps">
        <div class="cf-step reveal">
          <div class="cf-num">[ 01 ]</div>
          <div class="cf-icon-wrap"><i data-lucide="building-2"></i></div>
          <h3>Cadastre sua empresa</h3>
          <p>Validamos CNPJ, localização e segmento antes de liberar qualquer acesso. Plataforma exclusiva B2B — zero pessoas físicas.</p>
        </div>
        <div class="cf-arrow reveal rd-1"><i data-lucide="arrow-right"></i></div>
        <div class="cf-step reveal rd-1">
          <div class="cf-num">[ 02 ]</div>
          <div class="cf-icon-wrap"><i data-lucide="package-search"></i></div>
          <h3>Anuncie ou pesquise</h3>
          <p>Publique resíduos com fotos, quantidade e preço. Ou busque por categoria, estado e tipo de material com filtros precisos.</p>
        </div>
        <div class="cf-arrow reveal rd-2"><i data-lucide="arrow-right"></i></div>
        <div class="cf-step reveal rd-2">
          <div class="cf-num">[ 03 ]</div>
          <div class="cf-icon-wrap"><i data-lucide="handshake"></i></div>
          <h3>Negocie e feche</h3>
          <p>Chat integrado, histórico auditável e relatório ESG automático a cada transação. Impacto real com cada tonelada movimentada.</p>
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
                <div class="esg-track"><div class="esg-fill" data-w="82" style="background:#3CB371"></div></div>
              </div>
              <div class="esg-bar-row">
                <div class="esg-bar-meta">
                  <span>Resíduos desviados</span>
                  <strong>12,8 t</strong>
                </div>
                <div class="esg-track"><div class="esg-fill" data-w="65" style="background:#3CB371"></div></div>
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
              <i data-lucide="check-circle" style="width:14px;height:14px;color:#3CB371"></i>
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
              <div class="tw-avatar"><i data-lucide="building-2" style="width:20px;height:20px;"></i></div>
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
            <strong>+48t</strong>
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
        <p class="cta-sub reveal">// Acesso exclusivo a empresas · Cadastro gratuito · Comece agora</p>
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
            <svg width="30" height="30" viewBox="0 0 38 38" fill="none">
              <rect x="2" y="2" width="13" height="13" rx="1" stroke="#3CB371" stroke-width="2" fill="none"/>
              <rect x="2" y="23" width="13" height="13" rx="1" stroke="#3CB371" stroke-width="2" fill="none"/>
              <path d="M19 8.5 C27 8.5 27 19 27 19 C27 19 27 29.5 19 29.5" stroke="#3CB371" stroke-width="2" fill="none" stroke-linecap="round"/>
              <circle cx="19" cy="8.5" r="2.5" fill="#3CB371"/>
              <circle cx="19" cy="29.5" r="2.5" fill="#3CB371"/>
            </svg>
            <span>Re<span class="dot">.</span>Source</span>
          </a>
          <p>// Conectando a indústria circular</p>
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
      <div class="wrap footer-bottom">
        <span>© 2025 Re.Source — Todos os direitos reservados</span>
        <span class="footer-project">Desenvolvido no SENAI · SA</span>
      </div>
    </footer>

  </main>

<script src="public/js/index.js"></script>
</body>
</html>
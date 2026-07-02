<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Entrar — Re.Source</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="stylesheet" href="/re.source/public/css/login.css"/>
</head>
<body>

  <header>
    <div class="header-top">
      <div class="header-top-inner">
        <a href="/re.source/" class="logo">
          <span><img src="/re.source/public/img/logos/logo.png" alt="Re.Source" /></span>
        </a>
      </div>
    </div>
  </header>

  <main class="register-main">

    <aside class="register-aside">
      <div class="aside-content">
        <div class="aside-badge">
          <span class="esg-dot"></span>
          <span>Plataforma ESG Certificada</span>
        </div>
        <h2 class="aside-title">Bem-vindo de volta à economia circular.</h2>
        <p class="aside-desc">
          Mais de 12 000 empresas já transformam descarte em oportunidade. Acesse sua conta e continue gerando impacto.
        </p>
        <ul class="aside-benefits">
          <li>
            <span class="benefit-icon"><i data-lucide="leaf"></i></span>
            <div>
              <strong>Acesso instantâneo</strong>
              <span>Seus anúncios e conexões onde você parou</span>
            </div>
          </li>
          <li>
            <span class="benefit-icon"><i data-lucide="shield-check"></i></span>
            <div>
              <strong>Sessão segura</strong>
              <span>Autenticação protegida com token criptografado</span>
            </div>
          </li>
          <li>
            <span class="benefit-icon"><i data-lucide="trending-up"></i></span>
            <div>
              <strong>Dashboard ESG atualizado</strong>
              <span>Métricas de impacto em tempo real</span>
            </div>
          </li>
        </ul>
        <blockquote class="aside-quote">
          <p>"Entramos na plataforma e em 2 dias já fechamos nossa primeira parceria de reciclagem."</p>
          <cite>— Carlos Menezes, CEO · EcoFibras Brasil</cite>
        </blockquote>
        <div class="aside-stats">
          <div class="aside-stat">
            <span class="aside-stat-val">12k+</span>
            <span class="aside-stat-label">Empresas</span>
          </div>
          <div class="aside-stat">
            <span class="aside-stat-val">98t</span>
            <span class="aside-stat-label">Resíduos / mês</span>
          </div>
          <div class="aside-stat">
            <span class="aside-stat-val">R$2M</span>
            <span class="aside-stat-label">Economizados</span>
          </div>
        </div>
      </div>
      <div class="aside-orb aside-orb-1"></div>
      <div class="aside-orb aside-orb-2"></div>
    </aside>

    <section class="register-panel">
      <div class="register-card">

        <!-- TELA 1: LOGIN -->
        <div id="screenLogin" class="auth-screen active">
          <div class="card-header">
            <h1 class="card-title">Acessar minha conta</h1>
            <p class="card-sub">Entre com seu e-mail e senha cadastrados</p>
          </div>

          <!-- Alerta geral -->
          <div class="alert-box alert-danger" id="loginAlert" style="display:none;margin-bottom:1rem;"></div>

          <!-- 🚀 CORREÇÃO: Alterado de <div> para <form> para resolver o aviso do [DOM] -->
          <form class="form-body" id="formLogin">
            <input type="hidden" id="loginCsrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-field">
              <label for="loginEmail">E-mail <span class="req">*</span></label>
              <div class="input-wrap">
                <i data-lucide="mail" class="input-icon"></i>
                <input type="email" id="loginEmail" placeholder="contato@empresa.com.br" autocomplete="email" />
              </div>
              <p class="field-error" id="emailError"></p>
            </div>

            <div class="form-field">
              <label for="loginSenha">Senha <span class="req">*</span></label>
              <div class="input-wrap">
                <i data-lucide="lock" class="input-icon"></i>
                <input type="password" id="loginSenha" placeholder="Sua senha" autocomplete="current-password" />
                <button class="toggle-pw" type="button" id="toggleLoginSenha" aria-label="Mostrar senha">
                  <i data-lucide="eye"></i>
                </button>
              </div>
              <p class="field-error" id="senhaError"></p>
            </div>

            <div class="login-options-row">
              <label class="check-label" id="rememberLabel">
                <input type="checkbox" id="chkLembrar" />
                <span class="check-box"><i data-lucide="check"></i></span>
                <span>Lembrar de mim</span>
              </label>
              <button class="link-btn" id="btnForgot" type="button">Esqueci minha senha</button>
            </div>

            <button class="btn-submit" id="btnLogin" type="button">
              <i data-lucide="log-in"></i>
              <span>Entrar na plataforma</span>
            </button>
            <p class="login-link">Ainda não tem conta? <a href="/re.source/cadastro">Criar conta grátis</a></p>
          </form>
        </div>

        <!-- TELA 2: RECUPERAÇÃO -->
        <div id="screenRecover" class="auth-screen">
          <button class="back-btn" id="btnBackFromRecover" type="button">
            <i data-lucide="arrow-left"></i>
            <span>Voltar ao login</span>
          </button>
          <div class="card-header">
            <div class="screen-icon-wrap">
              <i data-lucide="key-round"></i>
            </div>
            <h1 class="card-title">Recuperar senha</h1>
            <p class="card-sub">Enviaremos um link válido por <strong>1 hora</strong> para o seu e-mail</p>
          </div>
          <div class="form-body">
            <div class="form-field">
              <label for="recoverEmail">E-mail cadastrado <span class="req">*</span></label>
              <div class="input-wrap">
                <i data-lucide="mail" class="input-icon"></i>
                <input type="email" id="recoverEmail" placeholder="contato@empresa.com.br" autocomplete="email" />
              </div>
              <p class="field-error" id="recoverEmailError"></p>
            </div>
            <div class="info-box">
              <i data-lucide="info"></i>
              <p>O link expirará automaticamente em <strong>60 minutos</strong>. Verifique também sua caixa de spam.</p>
            </div>
            <button class="btn-submit" id="btnSendRecover" type="button">
              <i data-lucide="send"></i>
              <span>Enviar link de recuperação</span>
            </button>
            <p class="login-link">Lembrou a senha? <a href="#" id="linkBackLogin">Entrar agora</a></p>
          </div>
        </div>

        <!-- TELA 3: E-MAIL ENVIADO -->
        <div id="screenSent" class="auth-screen">
          <div class="sent-container">
            <div class="sent-icon-wrap"><i data-lucide="mail-check"></i></div>
            <h1 class="card-title">E-mail enviado!</h1>
            <p class="sent-desc">Enviamos um link de recuperação para</p>
            <p class="sent-email" id="sentEmailDisplay"></p>
            <div class="token-timer" id="tokenTimer">
              <i data-lucide="clock"></i>
              <span>Link expira em <strong id="countdown">60:00</strong></span>
            </div>
            <div class="info-box">
              <i data-lucide="info"></i>
              <p>Não recebeu? Verifique o spam ou <button class="link-btn-inline" id="btnResend" type="button">reenvie o e-mail</button>.</p>
            </div>
            <button class="btn-submit btn-outline" id="btnBackToLogin" type="button">
              <i data-lucide="arrow-left"></i>
              <span>Voltar ao login</span>
            </button>
          </div>
        </div>

      </div>
    </section>
  </main>

  <footer style="text-align:center;padding:1.5rem;font-size:0.8rem;color:#6C757D;background:var(--white);border-top:1px solid var(--border-color);">
    © 2026 Re.Source · Todos os direitos reservados ·
    <a href="#" style="color:var(--green);">Política de Privacidade</a>
  </footer>

  <!-- 🚀 CORREÇÃO: Adicionado o '?v=2' para forçar o navegador a carregar o seu JavaScript novo sem usar o cache -->
  <script src="/re.source/public/js/login.js?v=3"></script>
</body>
</html>

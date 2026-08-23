<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Entrar — Re.Source</title>
  <link rel="icon" href="<?= htmlspecialchars(asset_url('/img/logos/favicon.svg'), ENT_QUOTES, 'UTF-8') ?>" type="image/svg+xml" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="stylesheet" href="/re.source/public/css/login.css"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/flash.css'), ENT_QUOTES, 'UTF-8') ?>"/>
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/auth-v2.css?v=2.3'), ENT_QUOTES, 'UTF-8') ?>"/>
</head>
<body>

  <?php require __DIR__ . '/../components/flash.php'; ?>

  <!-- ══ HEADER (overlay transparente sobre a foto e o painel) ══ -->
  <header class="login-header">
    <a href="/re.source/" class="login-logo">
      <img src="/re.source/public/img/logos/logo.png" alt="Re.Source" />
    </a>
    <div class="login-header-ctas">
      <a href="/re.source/login" class="link-login active">Entrar</a>
      <a href="/re.source/cadastro" class="btn-cta-nav">Cadastrar minha empresa</a>
    </div>
  </header>

  <main class="login-main">

    <!-- ══ ASIDE — foto industrial + headline ══ -->
    <aside class="login-aside">
      <img class="login-aside-photo" src="https://images.unsplash.com/photo-1615797534094-7fde0a4861f3?w=1200&q=80&auto=format&fit=crop" alt="" />
      <div class="login-aside-scrim"></div>
      <div class="login-aside-content">
        <h1 class="login-aside-heading">
          Conectando<br>
          indústrias,<br>
          gerando valor<span class="accent-sq"></span>
        </h1>
        <p class="login-aside-sub">
          Plataforma B2B de economia circular para empresas. Sem ruído. Só negócio sustentável.
        </p>
      </div>
    </aside>

    <!-- ══ PANEL — formulário ══ -->
    <section class="login-panel">
      <div class="login-panel-bg-lines"></div>

      <div class="login-card">

        <!-- TELA 1: LOGIN -->
        <div id="screenLogin" class="auth-screen active">
          <div class="card-header">
            <h1 class="card-title">Acesse sua conta</h1>
            <p class="card-sub">Bem-vindo de volta à sua rede industrial.</p>
          </div>

          <!-- Alerta geral -->
          <div class="alert-box alert-danger" id="loginAlert" style="display:none;margin-bottom:1rem;"></div>

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

      <!-- floater badge -->
      <div class="login-floater">
        <i data-lucide="shield-check" style="width:16px;height:16px;color:#157347"></i>
        <span>Somente empresas verificadas</span>
      </div>
    </section>
  </main>

  <footer class="login-footer">
    © 2026 Re.Source · Todos os direitos reservados ·
    <a href="/re.source/privacidade">Política de Privacidade</a>
  </footer>

  <!-- 🚀 CORREÇÃO: Adicionado o '?v=2' para forçar o navegador a carregar o seu JavaScript novo sem usar o cache -->
  <?php require_once __DIR__ . '/../components/accessibility.php'; ?>
  <script src="/re.source/public/js/login.js?v=3"></script>
</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Redefinir Senha — Re.Source</title>
  <link rel="icon" href="<?= htmlspecialchars(asset_url('/img/logos/favicon.svg'), ENT_QUOTES, 'UTF-8') ?>" type="image/svg+xml" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="stylesheet" href="/re.source/public/css/login.css" />
  <link rel="stylesheet" href="/re.source/public/css/reset.css" />
</head>
<body>

  <header>
    <div class="header-top">
      <div class="header-top-inner">
        <a href="/re.source/" class="logo">
          <span><img src="/public/img/logos/logo.png" alt="Re.Source" /></span>
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
        <h2 class="aside-title">Quase lá! Crie sua nova senha.</h2>
        <p class="aside-desc">Escolha uma senha forte para proteger sua conta na plataforma de economia circular.</p>
        <ul class="aside-benefits">
          <li>
            <span class="benefit-icon"><i data-lucide="shield-check"></i></span>
            <div>
              <strong>Mínimo 8 caracteres</strong>
              <span>Use letras, números e símbolos</span>
            </div>
          </li>
          <li>
            <span class="benefit-icon"><i data-lucide="lock"></i></span>
            <div>
              <strong>Sessões encerradas</strong>
              <span>Por segurança, todos os acessos ativos serão desconectados</span>
            </div>
          </li>
        </ul>
      </div>
      <div class="aside-orb aside-orb-1"></div>
      <div class="aside-orb aside-orb-2"></div>
    </aside>

    <section class="register-panel">
      <div class="register-card">

        <!-- TELA: TOKEN INVÁLIDO -->
        <div id="screenInvalid" class="auth-screen" style="display:none;">
          <div class="sent-container">
            <div class="sent-icon-wrap" style="background:rgba(220,53,69,.1);">
              <i data-lucide="x-circle" style="color:#dc3545;"></i>
            </div>
            <h1 class="card-title">Link inválido</h1>
            <p class="sent-desc" style="margin-bottom:1.5rem;">
              Este link de recuperação é inválido ou já expirou.<br>Solicite um novo.
            </p>
            <a href="/re.source/login" class="btn-submit" style="text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;">
              <i data-lucide="arrow-left"></i>
              <span>Voltar ao login</span>
            </a>
          </div>
        </div>

        <!-- TELA: FORMULÁRIO DE NOVA SENHA -->
        <div id="screenReset" class="auth-screen" style="display:none;">
          <div class="card-header">
            <div class="screen-icon-wrap">
              <i data-lucide="key-round"></i>
            </div>
            <h1 class="card-title">Criar nova senha</h1>
            <p class="card-sub">Defina uma senha segura para sua conta</p>
          </div>

          <div class="alert-box alert-danger" id="resetAlert" style="display:none;margin-bottom:1rem;"></div>

          <div class="form-body">
            <div class="form-field">
              <label for="novaSenha">Nova senha <span class="req">*</span></label>
              <div class="input-wrap">
                <i data-lucide="lock" class="input-icon"></i>
                <input type="password" id="novaSenha" placeholder="Mínimo 8 caracteres" autocomplete="new-password" />
                <button class="toggle-pw" type="button" id="toggleNova" aria-label="Mostrar senha">
                  <i data-lucide="eye"></i>
                </button>
              </div>
              <p class="field-error" id="novaSenhaError"></p>
            </div>

            <div class="form-field">
              <label for="confirmarSenha">Confirmar senha <span class="req">*</span></label>
              <div class="input-wrap">
                <i data-lucide="lock" class="input-icon"></i>
                <input type="password" id="confirmarSenha" placeholder="Repita a nova senha" autocomplete="new-password" />
                <button class="toggle-pw" type="button" id="toggleConfirmar" aria-label="Mostrar senha">
                  <i data-lucide="eye"></i>
                </button>
              </div>
              <p class="field-error" id="confirmarSenhaError"></p>
            </div>

            <div class="password-strength" id="strengthWrap" style="display:none;">
              <div class="strength-bar">
                <div class="strength-fill" id="strengthFill"></div>
              </div>
              <span class="strength-label" id="strengthLabel"></span>
            </div>

            <button class="btn-submit" id="btnReset" type="button">
              <i data-lucide="check-circle-2"></i>
              <span>Salvar nova senha</span>
            </button>
          </div>
        </div>

        <!-- TELA: SUCESSO -->
        <div id="screenSuccess" class="auth-screen" style="display:none;">
          <div class="sent-container">
            <div class="sent-icon-wrap">
              <i data-lucide="check-circle-2"></i>
            </div>
            <h1 class="card-title">Senha redefinida!</h1>
            <p class="sent-desc">Sua senha foi atualizada com sucesso.<br>Você será redirecionado para o login.</p>
            <div class="token-timer">
              <i data-lucide="clock"></i>
              <span>Redirecionando em <strong id="redirectCount">3</strong>s…</span>
            </div>
            <a href="/login" class="btn-submit btn-outline" style="text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;margin-top:1rem;">
              <i data-lucide="log-in"></i>
              <span>Ir para o login</span>
            </a>
          </div>
        </div>

        <!-- TELA: LOADING -->
        <div id="screenLoading" class="auth-screen active">
          <div class="sent-container">
            <div class="sent-icon-wrap">
              <i data-lucide="loader-2" class="spin"></i>
            </div>
            <p class="sent-desc">Validando link…</p>
          </div>
        </div>

      </div>
    </section>
  </main>

  <footer style="text-align:center;padding:1.5rem;font-size:0.8rem;color:#6C757D;background:var(--white);border-top:1px solid var(--border-color);">
    © 2026 Re.Source · Todos os direitos reservados ·
    <a href="/re.source/privacidade" style="color:var(--green);">Política de Privacidade</a>
  </footer>

  <script src="/re.source/public/js/reset.js?v=2"></script>
</body>
</html>

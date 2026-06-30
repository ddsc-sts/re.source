<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verificar E-mail — Re.Source</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/re.source/public/css/pendente.css" />
</head>
<body>

  <div class="pendente-card">

    <a href="/re.source/" class="logo-wrap">
      <span class="logo-text">Re.<strong>Source</strong></span>
    </a>

    <div class="envelope-icon" aria-hidden="true">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none">
        <rect x="4" y="14" width="56" height="38" rx="6" fill="var(--green)" opacity=".12"/>
        <rect x="4" y="14" width="56" height="38" rx="6" stroke="var(--green)" stroke-width="2.5"/>
        <polyline points="4,14 32,36 60,14" stroke="var(--green)" stroke-width="2.5" stroke-linejoin="round"/>
      </svg>
      <span class="ping"></span>
    </div>

    <h1 class="pendente-title">Confirme seu e-mail</h1>
    <p class="pendente-sub">
      Enviamos um código de 6 dígitos para<br>
      <strong id="emailDisplay">carregando...</strong>
    </p>

    <div class="alert-box alert-danger" id="alertBox" role="alert" hidden></div>

    <form id="formCodigo" method="POST" novalidate>
      <div class="code-inputs">
        <input type="text" inputmode="numeric" maxlength="1" class="code-digit" autocomplete="off" aria-label="Digito 1" />
        <input type="text" inputmode="numeric" maxlength="1" class="code-digit" autocomplete="off" aria-label="Digito 2" />
        <input type="text" inputmode="numeric" maxlength="1" class="code-digit" autocomplete="off" aria-label="Digito 3" />
        <input type="text" inputmode="numeric" maxlength="1" class="code-digit" autocomplete="off" aria-label="Digito 4" />
        <input type="text" inputmode="numeric" maxlength="1" class="code-digit" autocomplete="off" aria-label="Digito 5" />
        <input type="text" inputmode="numeric" maxlength="1" class="code-digit" autocomplete="off" aria-label="Digito 6" />
      </div>
      <input type="hidden" name="codigo" id="codigoHidden" />

      <button type="submit" class="btn-verificar" id="btnVerificar" disabled>
        <span class="btn-label">Verificar código</span>
        <span class="btn-spinner" hidden></span>
      </button>
    </form>

    <div class="reenviar-wrap">
      <span class="reenviar-text">Não recebeu?</span>
      <button type="button" class="btn-link" id="btnReenviar" disabled>Reenviar código</button>
      <span class="reenviar-countdown" id="countdown"></span>
    </div>

    <a href="/re.source/cadastro" class="link-voltar">← Voltar ao cadastro</a>

  </div>
    <script src="/re.source/public/js/pendente.js?v=3"></script>

</body>
</html>
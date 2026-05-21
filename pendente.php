<?php
// pendente.php — fica na RAIZ do projeto (RE.SOURCE/pendente.php)
// Exibida após o cadastro, antes da confirmação do e-mail

$email = htmlspecialchars($_GET['email'] ?? 'seu e-mail');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifique seu e-mail — Re.Source</title>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="FrontEnd/css/style.css">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: var(--bg, #f4f7f4);
      font-family: Inter, sans-serif;
      padding: 2rem;
    }
    .card {
      background: var(--white, #fff);
      border-radius: 20px;
      box-shadow: 0 6px 32px rgba(0,0,0,.09);
      padding: 3rem 2.5rem;
      max-width: 480px;
      width: 100%;
      text-align: center;
    }
    .logo {
      font-family: Sora, sans-serif;
      font-weight: 800;
      color: #157347;
      font-size: 1.4rem;
      display: block;
      margin-bottom: 2rem;
    }
    .envelope {
      font-size: 4rem;
      margin-bottom: 1rem;
      animation: balanco .8s ease-in-out infinite alternate;
    }
    @keyframes balanco {
      from { transform: rotate(-6deg); }
      to   { transform: rotate(6deg); }
    }
    h1 {
      font-family: Sora, sans-serif;
      color: #1a1a1a;
      font-size: 1.6rem;
      margin: .5rem 0 1rem;
    }
    p { color: #555; line-height: 1.7; }
    .email-tag {
      display: inline-block;
      background: #f0f7f3;
      color: #157347;
      border-radius: 8px;
      padding: .4rem 1rem;
      font-weight: 600;
      font-size: .95rem;
      margin: .75rem 0 1.5rem;
    }
    .steps {
      list-style: none;
      padding: 0;
      margin: 1.5rem 0;
      text-align: left;
    }
    .steps li {
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: .6rem 0;
      color: #444;
      border-bottom: 1px solid #f0f0f0;
      font-size: .95rem;
    }
    .steps li:last-child { border: none; }
    .step-num {
      background: #157347;
      color: #fff;
      border-radius: 50%;
      width: 26px;
      height: 26px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .8rem;
      font-weight: 700;
      flex-shrink: 0;
    }
    .btn-reenviar {
      background: none;
      border: 2px solid #157347;
      color: #157347;
      border-radius: 8px;
      padding: .75rem 1.5rem;
      font-weight: 600;
      cursor: pointer;
      font-size: .95rem;
      margin-top: 1rem;
      width: 100%;
      transition: all .2s;
    }
    .btn-reenviar:hover { background: #157347; color: #fff; }
    .note { font-size: .82rem; color: #aaa; margin-top: 1.25rem; }
    .note a { color: #157347; }
  </style>
</head>
<body>
  <div class="card">
    <span class="logo">Re.Source</span>
    <div class="envelope">📬</div>
    <h1>Confirme seu e-mail</h1>
    <p>Enviamos um link de ativação para:</p>
    <div class="email-tag"><?= $email ?></div>
    <p>Acesse seu e-mail e clique no botão para ativar sua conta gratuitamente.</p>

    <ul class="steps">
      <li><span class="step-num">1</span> Abra sua caixa de entrada</li>
      <li><span class="step-num">2</span> Procure um e-mail de <strong>noreply@re.source.com.br</strong></li>
      <li><span class="step-num">3</span> Clique em <strong>"Confirmar meu e-mail"</strong></li>
    </ul>

    <form action="/BackEnd/auth/reenviar.php" method="POST">
      <input type="hidden" name="email" value="<?= $email ?>">
      <button type="submit" class="btn-reenviar">
        🔁 Não recebi o e-mail — reenviar
      </button>
    </form>

    <p class="note">
      Verifique também a pasta de spam.<br>
      Dificuldades? <a href="mailto:suporte@re.source.com.br">suporte@re.source.com.br</a>
    </p>
  </div>
</body>
</html>
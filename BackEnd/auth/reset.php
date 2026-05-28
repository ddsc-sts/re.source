<?php
// BackEnd/auth/reset.php
ob_start();
session_start();
require_once __DIR__ . '/../config/conexao.php';

$raiz = rtrim(str_replace('BackEnd/auth/reset.php', '', $_SERVER['SCRIPT_NAME']), '/');

// POST logic (mesmo de antes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $token    = trim($_POST['token'] ?? '');
    $senha    = trim($_POST['password'] ?? '');
    $confirma = trim($_POST['confirm'] ?? '');

    if ($token === '' || $senha === '') {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
        exit;
    }

    if (strlen($senha) < 8) {
        echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 8 caracteres.']);
        exit;
    }

    if ($senha !== $confirma) {
        echo json_encode(['success' => false, 'message' => 'As senhas não coincidem.']);
        exit;
    }

    $tokenHash = hash('sha256', $token);

    try {
        $stmt = $pdo->prepare("SELECT id, user_id FROM password_resets WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1");
        $stmt->execute([':hash' => $tokenHash]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado.']);
            exit;
        }

        $novoHash = password_hash($senha, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id")
            ->execute([':hash' => $novoHash, ':id' => $reset['user_id']]);

        $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id")
            ->execute([':id' => $reset['id']]);

        $pdo->prepare("DELETE FROM user_sessions WHERE user_id = :uid")
            ->execute([':uid' => $reset['user_id']]);

        echo json_encode([
            'success'  => true,
            'message'  => 'Senha redefinida com sucesso!',
            'redirect' => $raiz . '/login.php?sucesso=' . urlencode('Senha atualizada. Faça login.')
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
    }
    exit;
}

// GET logic
$token = trim($_GET['token'] ?? '');
$erro  = '';

if ($token === '') {
    $erro = 'Token não informado.';
} else {
    try {
        $tokenHash = hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT id FROM password_resets WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1");
        $stmt->execute([':hash' => $tokenHash]);
        if (!$stmt->fetch()) {
            $erro = 'Este link é inválido ou já expirou. Solicite um novo.';
        }
    } catch (PDOException $e) {
        $erro = 'Erro interno. Tente novamente mais tarde.';
    }
}

ob_clean();
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Redefinir senha — Re.Source</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet" />

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green:   #157347;
      --green-d: #0D4A2E;
      --dark:    #343A40;
      --muted:   #6C757D;
      --bg:      #F8F9FA;
      --white:   #ffffff;
      --radius:  1rem;
      --font-main: 'Sora', sans-serif;
      --font-body: 'Inter', sans-serif;
      --shadow-card: 0 12px 30px rgba(0,0,0,0.1);
      --border-color: #eee;
    }

    html { font-size: 16px; }
    body {
      font-family: var(--font-body);
      background: var(--bg);
      color: var(--dark);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }

    .reset-container { width: 100%; max-width: 440px; }

    .auth-card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow-card);
      padding: 2.75rem 2rem;
      border: 1px solid var(--border-color);
    }

    .logo {
      display: flex;
      justify-content: center;
      margin-bottom: 2rem;
    }
    .logo img { height: 56px; }

    h1 {
      font-family: var(--font-main);
      font-size: 1.8rem;
      font-weight: 700;
      text-align: center;
      margin-bottom: 0.5rem;
    }

    .sub {
      text-align: center;
      color: var(--muted);
      margin-bottom: 2.25rem;
      font-size: 0.98rem;
    }

    .field {
      margin-bottom: 1.4rem;
      position: relative;
    }

    label {
      display: block;
      font-family: var(--font-main);
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--dark);
      margin-bottom: 8px;
    }

    .password-wrapper {
      position: relative;
    }

    input[type="password"], input[type="text"] {
      width: 100%;
      padding: 14px 48px 14px 16px;
      border: 1px solid var(--border-color);
      border-radius: 0.75rem;
      font-size: 1rem;
      background: var(--white);
    }

    input:focus {
      outline: none;
      border-color: var(--green);
      box-shadow: 0 0 0 3px rgba(21, 115, 71, 0.15);
    }

    .toggle-password {
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      font-size: 1.3rem;
      cursor: pointer;
      color: var(--muted);
    }

    .strength-meter {
      margin-top: 8px;
      height: 6px;
      border-radius: 999px;
      background: #eee;
      overflow: hidden;
    }

    .strength-bar {
      height: 100%;
      width: 0%;
      transition: all 0.3s ease;
    }

    .strength-text {
      font-size: 0.8rem;
      margin-top: 4px;
      font-weight: 500;
    }

    button {
      width: 100%;
      padding: 1.05rem;
      background: var(--green);
      color: white;
      border-radius: 0.75rem;
      font-size: 1.02rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      margin-top: 0.5rem;
    }

    button:hover { background: var(--green-d); transform: translateY(-2px); }
    button:disabled { background: var(--muted); cursor: not-allowed; }

    .msg { padding: 14px 16px; border-radius: 0.75rem; margin-bottom: 1.25rem; font-size: 0.92rem; display: none; }
    .msg.erro    { background: #fee2e2; color: #b91c1c; }
    .msg.sucesso { background: #dcfce7; color: #15803d; }

    .erro-pagina {
      text-align: center;
      color: #b91c1c;
      padding: 1.25rem;
      background: #fee2e2;
      border-radius: 0.75rem;
      margin-bottom: 1.5rem;
    }

    .voltar {
      display: block;
      text-align: center;
      margin-top: 1.75rem;
      color: var(--muted);
      font-size: 0.9rem;
    }
    .voltar:hover { color: var(--green); }
  </style>
</head>
<body>

<div class="reset-container">
  <div class="auth-card">

    <div class="logo">
      <img src="<?= $raiz ?>/assets/img/logo.svg" alt="Re.Source">
    </div>

    <?php if ($erro): ?>
      <div class="erro-pagina">⚠️ <?= htmlspecialchars($erro) ?></div>
      <a href="<?= $raiz ?>/login.php" class="voltar">← Voltar ao login</a>
    <?php else: ?>

      <h1>Redefinir senha</h1>
      <p class="sub">Digite sua nova senha abaixo.</p>

      <div id="msg" class="msg"></div>

      <div class="field">
        <label for="password">Nova senha</label>
        <div class="password-wrapper">
          <input type="password" id="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password" onkeyup="checkPasswordStrength()">
          <button type="button" class="toggle-password" id="toggle1">👁</button>
        </div>
        <div class="strength-meter">
          <div class="strength-bar" id="strengthBar"></div>
        </div>
        <div class="strength-text" id="strengthText"></div>
      </div>

      <div class="field">
        <label for="confirm">Confirmar senha</label>
        <div class="password-wrapper">
          <input type="password" id="confirm" placeholder="Repita a senha" autocomplete="new-password">
          <button type="button" class="toggle-password" id="toggle2">👁</button>
        </div>
      </div>

      <button id="btn" onclick="salvar()">Salvar nova senha</button>
      
      <a href="<?= $raiz ?>/login.php" class="voltar">← Voltar ao login</a>

    <?php endif; ?>

  </div>
</div>

<script>
const TOKEN = <?= json_encode($token) ?>;

// Toggle password visibility
document.getElementById('toggle1').addEventListener('click', function() {
  toggleVisibility('password', 'toggle1');
});

document.getElementById('toggle2').addEventListener('click', function() {
  toggleVisibility('confirm', 'toggle2');
});

function toggleVisibility(inputId, toggleId) {
  const input = document.getElementById(inputId);
  const toggle = document.getElementById(toggleId);
  
  if (input.type === 'password') {
    input.type = 'text';
    toggle.textContent = '🙈';
  } else {
    input.type = 'password';
    toggle.textContent = '👁';
  }
}

// Password strength checker
function checkPasswordStrength() {
  const password = document.getElementById('password').value;
  const bar = document.getElementById('strengthBar');
  const text = document.getElementById('strengthText');

  if (password.length === 0) {
    bar.style.width = '0%';
    bar.style.background = '#eee';
    text.textContent = '';
    return;
  }

  let strength = 0;
  if (password.length >= 8) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[0-9]/.test(password)) strength++;
  if (/[^A-Za-z0-9]/.test(password)) strength++;

  if (strength <= 1) {
    bar.style.width = '33%';
    bar.style.background = '#ef4444';
    text.textContent = 'Fraca';
    text.style.color = '#ef4444';
  } else if (strength === 2) {
    bar.style.width = '66%';
    bar.style.background = '#f59e0b';
    text.textContent = 'Média';
    text.style.color = '#f59e0b';
  } else {
    bar.style.width = '100%';
    bar.style.background = '#10b981';
    text.textContent = 'Forte';
    text.style.color = '#10b981';
  }
}

async function salvar() {
  const btn   = document.getElementById('btn');
  const msg   = document.getElementById('msg');
  const senha = document.getElementById('password').value.trim();
  const conf  = document.getElementById('confirm').value.trim();

  msg.style.display = 'none';

  if (senha.length < 8) {
    mostrarErro('A senha deve ter pelo menos 8 caracteres.');
    return;
  }
  if (senha !== conf) {
    mostrarErro('As senhas não coincidem.');
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Salvando...';

  try {
    const fd = new FormData();
    fd.append('token', TOKEN);
    fd.append('password', senha);
    fd.append('confirm', conf);

    const res = await fetch('', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      msg.className = 'msg sucesso';
      msg.textContent = data.message;
      msg.style.display = 'block';
      setTimeout(() => window.location.href = data.redirect, 1800);
    } else {
      mostrarErro(data.message);
      btn.disabled = false;
      btn.textContent = 'Salvar nova senha';
    }
  } catch (e) {
    mostrarErro('Erro de conexão. Tente novamente.');
    btn.disabled = false;
    btn.textContent = 'Salvar nova senha';
  }
}

function mostrarErro(texto) {
  const msg = document.getElementById('msg');
  msg.className = 'msg erro';
  msg.textContent = texto;
  msg.style.display = 'block';
}
</script>

</body>
</html>
lucide.createIcons();

// ── Telas ──
function showScreen(id) {
  document.querySelectorAll('.auth-screen').forEach(s => s.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  lucide.createIcons();
}

document.getElementById('btnForgot').addEventListener('click', () => showScreen('screenRecover'));
document.getElementById('btnBackFromRecover').addEventListener('click', () => showScreen('screenLogin'));
document.getElementById('linkBackLogin').addEventListener('click', e => { e.preventDefault(); showScreen('screenLogin'); });
document.getElementById('btnBackToLogin').addEventListener('click', () => showScreen('screenLogin'));

// ── Mostrar/ocultar senha ──
const toggleBtn  = document.getElementById('toggleLoginSenha');
const senhaInput = document.getElementById('loginSenha');
toggleBtn.addEventListener('click', () => {
  const show = senhaInput.type === 'password';
  senhaInput.type = show ? 'text' : 'password';
  toggleBtn.innerHTML = `<i data-lucide="${show ? 'eye-off' : 'eye'}"></i>`;
  toggleBtn.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
  lucide.createIcons({ nodes: [toggleBtn] });
});

// ── Checkbox lembrar ──
document.getElementById('chkLembrar').addEventListener('change', function () {
  document.getElementById('rememberLabel').classList.toggle('checked', this.checked);
});

// ── Helpers ──
function isValidEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }

// Ajustado para garantir o preventDefault na tecla Enter também
function setError(id, msg) {
  const el = document.getElementById(id);
  if (el) { el.textContent = msg; el.style.display = msg ? 'flex' : 'none'; }
}

function setInputState(inputId, state) {
  const input = document.getElementById(inputId);
  if (!input) return;
  input.classList.remove('input-ok', 'input-err');
  if (state) input.classList.add(state);
}

function showAlert(msg) {
  const el = document.getElementById('loginAlert');
  if (el) { el.textContent = msg; el.style.display = 'block'; }
}

function hideAlert() {
  const el = document.getElementById('loginAlert');
  if (el) el.style.display = 'none';
}

// ── LOGIN: Submit real ──
document.getElementById('btnLogin').addEventListener('click', async (e) => {
  // 🚀 CORREÇÃO: Impede o navegador de tentar disparar o formulário nativo do HTML
  e.preventDefault();

  const email = document.getElementById('loginEmail').value.trim();
  const senha = document.getElementById('loginSenha').value;
  let valid = true;

  setError('emailError', '');
  setError('senhaError', '');
  setInputState('loginEmail', null);
  setInputState('loginSenha', null);
  hideAlert();

  if (!email) {
    setError('emailError', 'Informe seu e-mail.');
    setInputState('loginEmail', 'input-err');
    valid = false;
  } else if (!isValidEmail(email)) {
    setError('emailError', 'E-mail inválido.');
    setInputState('loginEmail', 'input-err');
    valid = false;
  }

  if (!senha) {
    setError('senhaError', 'Informe sua senha.');
    setInputState('loginSenha', 'input-err');
    valid = false;
  }

  if (!valid) return;

  const btn = document.getElementById('btnLogin');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i><span>Verificando…</span>';
  lucide.createIcons();

  try {
    const fd = new FormData();
    fd.append('email', email);
    fd.append('password', senha);
    fd.append('csrf_token', document.getElementById('loginCsrf')?.value ?? '');

    // 🚀 CORREÇÃO: URL ajustada para a pasta do XAMPP
    const res = await fetch('/re.source/process?action=login', {
      method: 'POST',
      body:   fd,
    });

    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch {
      showAlert('Resposta inesperada do servidor. Tente novamente.');
      return;
    }

    if (data.success) {
      btn.classList.add('success');
      btn.innerHTML = '<i data-lucide="check-circle-2"></i><span>Acesso autorizado! Redirecionando…</span>';
      lucide.createIcons();
      setTimeout(() => { location.href = data.redirect; }, 800);
    } else {
      showAlert(data.message || 'E-mail ou senha inválidos.');
      setInputState('loginEmail', 'input-err');
      setInputState('loginSenha', 'input-err');
      btn.classList.add('shake');
      setTimeout(() => btn.classList.remove('shake'), 600);
    }

  } catch {
    showAlert('Falha na comunicação com o servidor. Tente novamente.');
  } finally {
    if (!document.getElementById('btnLogin').classList.contains('success')) {
      btn.disabled = false;
      btn.innerHTML = '<i data-lucide="log-in"></i><span>Entrar na plataforma</span>';
      lucide.createIcons();
    }
  }
});

// ── Enter dispara submit ──
document.getElementById('loginSenha').addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    e.preventDefault();
    document.getElementById('btnLogin').click();
  }
});
document.getElementById('recoverEmail').addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    e.preventDefault();
    document.getElementById('btnSendRecover').click();
  }
});

// ── RECUPERAÇÃO ──
let countdownInterval = null;

function startCountdown() {
  let total = 3600;
  const display = document.getElementById('countdown');
  if (countdownInterval) clearInterval(countdownInterval);
  countdownInterval = setInterval(() => {
    total--;
    const m = String(Math.floor(total / 60)).padStart(2, '0');
    const s = String(total % 60).padStart(2, '0');
    display.textContent = `${m}:${s}`;
    if (total <= 0) {
      clearInterval(countdownInterval);
      display.textContent = 'Expirado';
      document.getElementById('tokenTimer').classList.add('expired');
    }
  }, 1000);
}

async function sendRecoverEmail(email) {
  const btn = document.getElementById('btnSendRecover');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i><span>Enviando…</span>';
  lucide.createIcons();

  try {
    const fd = new FormData();
    fd.append('email', email);

    // 🚀 CORREÇÃO: URL ajustada para a pasta do XAMPP
    const res  = await fetch('/re.source/process?action=recover', { method: 'POST', body: fd });
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); }
    catch { setError('recoverEmailError', 'Resposta inesperada do servidor.'); return; }

    if (data.success) {
      document.getElementById('sentEmailDisplay').textContent = email;
      showScreen('screenSent');
      startCountdown();
    } else {
      setError('recoverEmailError', data.message || 'Erro ao enviar. Tente novamente.');
      setInputState('recoverEmail', 'input-err');
    }

  } catch {
    setError('recoverEmailError', 'Falha na comunicação com o servidor.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="send"></i><span>Enviar link de recuperação</span>';
    lucide.createIcons();
  }
}

document.getElementById('btnSendRecover').addEventListener('click', (e) => {
  e.preventDefault();
  const email = document.getElementById('recoverEmail').value.trim();
  setError('recoverEmailError', '');
  setInputState('recoverEmail', null);
  if (!email) { setError('recoverEmailError', 'Informe seu e-mail cadastrado.'); setInputState('recoverEmail', 'input-err'); return; }
  if (!isValidEmail(email)) { setError('recoverEmailError', 'E-mail inválido.'); setInputState('recoverEmail', 'input-err'); return; }
  setInputState('recoverEmail', 'input-ok');
  sendRecoverEmail(email);
});

document.getElementById('btnResend').addEventListener('click', (e) => {
  e.preventDefault();
  const email = document.getElementById('sentEmailDisplay').textContent;
  document.getElementById('tokenTimer').classList.remove('expired');
  sendRecoverEmail(email);
});

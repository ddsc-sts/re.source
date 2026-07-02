lucide.createIcons();

// ── Utils ──
function showScreen(id) {
  document.querySelectorAll('.auth-screen').forEach(s => {
    s.classList.remove('active');
    s.style.display = 'none';
  });
  const el = document.getElementById(id);
  el.style.display = '';
  el.classList.add('active');
  lucide.createIcons();
}

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

// ── Pega token da URL ──
const params = new URLSearchParams(window.location.search);
const token  = params.get('token') || '';

// ── Valida token ao carregar ──
async function validarToken() {
  if (!token) { showScreen('screenInvalid'); return; }
  try {
    const res  = await fetch(`/re.source/process?action=reset&validate=1&token=${encodeURIComponent(token)}`);
    const data = await res.json();
    showScreen(data.success ? 'screenReset' : 'screenInvalid');
  } catch {
    showScreen('screenInvalid');
  }
}
validarToken();

// ── Toggle senha ──
function makeToggle(btnId, inputId) {
  document.getElementById(btnId).addEventListener('click', () => {
    const input = document.getElementById(inputId);
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    document.querySelector(`#${btnId} i`).setAttribute('data-lucide', show ? 'eye-off' : 'eye');
    lucide.createIcons();
  });
}
makeToggle('toggleNova', 'novaSenha');
makeToggle('toggleConfirmar', 'confirmarSenha');

// ── Força da senha ──
document.getElementById('novaSenha').addEventListener('input', function () {
  const val   = this.value;
  const wrap  = document.getElementById('strengthWrap');
  const fill  = document.getElementById('strengthFill');
  const label = document.getElementById('strengthLabel');

  if (!val) { wrap.style.display = 'none'; return; }
  wrap.style.display = 'flex';

  let score = 0;
  if (val.length >= 8)          score++;
  if (val.length >= 12)         score++;
  if (/[A-Z]/.test(val))        score++;
  if (/[0-9]/.test(val))        score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const levels = [
    { pct: '20%',  color: '#dc3545', text: 'Muito fraca'  },
    { pct: '40%',  color: '#fd7e14', text: 'Fraca'        },
    { pct: '60%',  color: '#ffc107', text: 'Média'        },
    { pct: '80%',  color: '#20c997', text: 'Forte'        },
    { pct: '100%', color: '#157347', text: 'Muito forte'  },
  ];
  const lv = levels[Math.min(score, 4)];
  fill.style.width      = lv.pct;
  fill.style.background = lv.color;
  label.textContent     = lv.text;
  label.style.color     = lv.color;
});

// ── Submit redefinição ──
document.getElementById('btnReset').addEventListener('click', async () => {
  const nova      = document.getElementById('novaSenha').value;
  const confirmar = document.getElementById('confirmarSenha').value;
  let valid = true;

  setError('novaSenhaError', '');
  setError('confirmarSenhaError', '');
  setInputState('novaSenha', null);
  setInputState('confirmarSenha', null);
  document.getElementById('resetAlert').style.display = 'none';

  if (!nova) {
    setError('novaSenhaError', 'Informe a nova senha.');
    setInputState('novaSenha', 'input-err');
    valid = false;
  } else if (nova.length < 8) {
    setError('novaSenhaError', 'A senha deve ter no mínimo 8 caracteres.');
    setInputState('novaSenha', 'input-err');
    valid = false;
  }

  if (!confirmar) {
    setError('confirmarSenhaError', 'Confirme a nova senha.');
    setInputState('confirmarSenha', 'input-err');
    valid = false;
  } else if (nova !== confirmar) {
    setError('confirmarSenhaError', 'As senhas não coincidem.');
    setInputState('confirmarSenha', 'input-err');
    valid = false;
  }

  if (!valid) return;

  const btn = document.getElementById('btnReset');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i><span>Salvando…</span>';
  lucide.createIcons();

  try {
    const fd = new FormData();
    fd.append('token',            token);
    fd.append('password',         nova);
    fd.append('password_confirm', confirmar);

    const res  = await fetch('/re.source/process?action=reset', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      showScreen('screenSuccess');
      let count = 3;
      const iv = setInterval(() => {
        count--;
        document.getElementById('redirectCount').textContent = count;
        if (count <= 0) {
          clearInterval(iv);
          location.href = '/re.source/login';
        }
      }, 1000);
    } else {
      const alert = document.getElementById('resetAlert');
      alert.textContent   = data.message || 'Erro ao redefinir senha.';
      alert.style.display = 'block';
      btn.disabled = false;
      btn.innerHTML = '<i data-lucide="check-circle-2"></i><span>Salvar nova senha</span>';
      lucide.createIcons();
    }

  } catch {
    const alert = document.getElementById('resetAlert');
    alert.textContent   = 'Falha na comunicação com o servidor.';
    alert.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="check-circle-2"></i><span>Salvar nova senha</span>';
    lucide.createIcons();
  }
});

// ── Enter dispara submit ──
['novaSenha', 'confirmarSenha'].forEach(id => {
  document.getElementById(id).addEventListener('keydown', e => {
    if (e.key === 'Enter') document.getElementById('btnReset').click();
  });
});

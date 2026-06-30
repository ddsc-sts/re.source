(() => {
  'use strict';

  const digits    = document.querySelectorAll('.code-digit');
  const hidden    = document.getElementById('codigoHidden');
  const btnVer    = document.getElementById('btnVerificar');
  const btnRenv   = document.getElementById('btnReenviar');
  const alertBox  = document.getElementById('alertBox');
  const countdown = document.getElementById('countdown');
  const emailEl   = document.getElementById('emailDisplay');

  // ── Busca e-mail da sessão ──
  fetch('/re.source/process?action=sessao-info', {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(d => { if (d.ok) emailEl.textContent = d.email; })
  .catch(() => {});

  // ── Inputs do código ──
  digits.forEach((el, i) => {
    el.addEventListener('input', () => {
      el.value = el.value.replace(/\D/, '');
      if (el.value && i < digits.length - 1) digits[i + 1].focus();
      syncHidden();
    });
    el.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !el.value && i > 0) digits[i - 1].focus();
    });
    el.addEventListener('paste', e => {
      e.preventDefault();
      const txt = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
      txt.split('').forEach((c, j) => { if (digits[j]) digits[j].value = c; });
      syncHidden();
      if (digits[txt.length - 1]) digits[txt.length - 1].focus();
    });
  });

  function syncHidden() {
    const val = [...digits].map(d => d.value).join('');
    hidden.value = val;
    btnVer.disabled = val.length !== 6;
  }

  function showAlert(msg, tipo = 'danger') {
    alertBox.className = `alert-box alert-${tipo}`;
    alertBox.textContent = msg;
    alertBox.hidden = false;
  }

  // ── Submit verificação ──
  document.getElementById('formCodigo').addEventListener('submit', async e => {
    e.preventDefault();
    alertBox.hidden = true;

    const spinner = btnVer.querySelector('.btn-spinner');
    const label   = btnVer.querySelector('.btn-label');
    btnVer.disabled = true;
    spinner.hidden  = false;
    label.hidden    = true;

    try {
      const resp = await fetch('/re.source/process?action=verificar', {
        method:  'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    'codigo=' + encodeURIComponent(hidden.value),
      });
      const data = await resp.json();
      if (data.ok) {
        window.location.href = data.redirect ?? '/login';
      } else {
        showAlert(data.erro ?? 'Código inválido.');
        digits.forEach(d => d.classList.add('digit-error'));
        setTimeout(() => digits.forEach(d => d.classList.remove('digit-error')), 600);
      }
    } catch {
      showAlert('Erro de comunicação. Tente novamente.');
    } finally {
      btnVer.disabled = false;
      spinner.hidden  = true;
      label.hidden    = false;
    }
  });

  // ── Reenviar com countdown ──
  let timer = 60;
  function startCountdown() {
    btnRenv.disabled = true;
    countdown.textContent = `(${timer}s)`;
    const iv = setInterval(() => {
      timer--;
      countdown.textContent = `(${timer}s)`;
      if (timer <= 0) {
        clearInterval(iv);
        countdown.textContent = '';
        btnRenv.disabled = false;
        timer = 60;
      }
    }, 1000);
  }
  startCountdown();

  btnRenv.addEventListener('click', async () => {
    alertBox.hidden = true;
    btnRenv.disabled = true;
    try {
      const resp = await fetch('/re.source/process?action=reenviar', {
        method:  'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await resp.json();
      if (data.ok) {
        showAlert(data.msg ?? 'Código reenviado!', 'success');
        startCountdown();
      } else {
        showAlert(data.erro ?? 'Erro ao reenviar.');
        btnRenv.disabled = false;
      }
    } catch {
      showAlert('Erro de comunicação.');
      btnRenv.disabled = false;
    }
  });

})();
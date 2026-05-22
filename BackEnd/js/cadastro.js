/**
 * FrontEnd/js/cadastro.js
 * Gerencia o formulário de cadastro: máscaras, validações,
 * submit via fetch → exibe erros inline sem recarregar a página.
 */

(() => {
  'use strict';

  /* ─── Injeta CSS de erros inline ───────────────────────────── */
  const _style = document.createElement('link');
  _style.rel   = 'stylesheet';
  _style.href  = 'FrontEnd/css/cadastro-errors.css';
  document.head.appendChild(_style);

  /* ─── Helpers ──────────────────────────────────────────────── */
  const $  = id => document.getElementById(id);
  const qs = sel => document.querySelector(sel);

  /** Exibe o alertBox global */
  function showAlert(msg, type = 'erro') {
    const box = $('alertBox');
    box.className = `alert alert-${type}`;
    box.textContent = msg;
    box.style.display = 'block';
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  /** Esconde o alertBox global */
  function hideAlert() {
    const box = $('alertBox');
    box.style.display = 'none';
    box.textContent = '';
  }

  /** Marca um campo como inválido e exibe mensagem abaixo dele */
  function setFieldError(inputEl, msg) {
    const wrap = inputEl.closest('.form-field') ?? inputEl.parentElement;
    wrap.classList.add('field-error');
    let hint = wrap.querySelector('.field-hint');
    if (!hint) {
      hint = document.createElement('p');
      hint.className = 'field-hint';
      wrap.appendChild(hint);
    }
    hint.textContent = msg;
  }

  /** Remove marcação de erro de um campo */
  function clearFieldError(inputEl) {
    const wrap = inputEl.closest('.form-field') ?? inputEl.parentElement;
    wrap.classList.remove('field-error');
    const hint = wrap.querySelector('.field-hint');
    if (hint) hint.textContent = '';
  }

  /** Remove todos os erros de campo */
  function clearAllErrors() {
    document.querySelectorAll('.form-field.field-error').forEach(f => {
      f.classList.remove('field-error');
      const h = f.querySelector('.field-hint');
      if (h) h.textContent = '';
    });
  }

  /* ─── Lucide reload helper ──────────────────────────────────── */
  function reloadIcons() {
    if (window.lucide) lucide.createIcons();
  }

  /* ─── Tema ─────────────────────────────────────────────────── */
  const html      = document.documentElement;
  const themeIcon = $('themeIcon');
  $('themeToggle').addEventListener('click', () => {
    const dark = html.getAttribute('data-theme') === 'dark';
    html.setAttribute('data-theme', dark ? 'light' : 'dark');
    themeIcon.setAttribute('data-lucide', dark ? 'sun' : 'moon');
    reloadIcons();
  });

  /* ─── Tipo de conta ─────────────────────────────────────────── */
  const typeBtns   = document.querySelectorAll('.type-btn');
  const docLabel   = qs('#docField label');
  const docInput   = $('cnpj');
  const razaoField = $('razaoField');
  const razaoLabel = razaoField.querySelector('label');

  typeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      typeBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const t = btn.dataset.type;
      if (t === 'pessoa') {
        docLabel.innerHTML = 'CPF <span class="req">*</span>';
        docInput.placeholder = '000.000.000-00';
        docInput.maxLength = 14;
        razaoField.style.display = 'none';
      } else {
        docLabel.innerHTML = 'CNPJ <span class="req">*</span>';
        docInput.placeholder = '00.000.000/0001-00';
        docInput.maxLength = 18;
        razaoField.style.display = '';
        razaoLabel.innerHTML =
          (t === 'cooperativa' ? 'Nome da Cooperativa' : 'Razão Social') +
          ' <span class="req">*</span>';
      }
    });
  });

  /* ─── Máscara CNPJ / CPF ────────────────────────────────────── */
  docInput.addEventListener('input', e => {
    let v = e.target.value.replace(/\D/g, '');
    const isPessoa = qs('.type-btn.active').dataset.type === 'pessoa';
    if (isPessoa) {
      v = v.slice(0, 11)
           .replace(/(\d{3})(\d)/, '$1.$2')
           .replace(/(\d{3})(\d)/, '$1.$2')
           .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    } else {
      v = v.slice(0, 14)
           .replace(/(\d{2})(\d)/, '$1.$2')
           .replace(/(\d{3})(\d)/, '$1.$2')
           .replace(/(\d{3})(\d)/, '$1/$2')
           .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    }
    e.target.value = v;
    clearFieldError(docInput);
  });

  /* ─── Máscara Telefone ──────────────────────────────────────── */
  $('telefone').addEventListener('input', e => {
    let v = e.target.value.replace(/\D/g, '').slice(0, 11);
    v = v.replace(/(\d{2})(\d)/, '($1) $2')
         .replace(/(\d{5})(\d{1,4})$/, '$1-$2');
    e.target.value = v;
    clearFieldError($('telefone'));
  });

  /* ─── Mostrar / ocultar senha ───────────────────────────────── */
  function setupTogglePw(btnId, inputId) {
    const btn = $(btnId);
    const inp = $(inputId);
    btn.addEventListener('click', () => {
      const show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      btn.querySelector('i').setAttribute('data-lucide', show ? 'eye-off' : 'eye');
      reloadIcons();
    });
  }
  setupTogglePw('toggleSenha', 'senha');
  setupTogglePw('toggleConf',  'senhaConf');

  /* ─── Força de senha ────────────────────────────────────────── */
  const senhaInput = $('senha');
  const bar        = document.querySelectorAll('#strengthBar span');
  const lbl        = $('strengthLabel');
  const levels     = ['Muito fraca', 'Fraca', 'Razoável', 'Forte'];
  const colors     = ['#dc3545', '#fd7e14', '#ffc107', '#157347'];

  senhaInput.addEventListener('input', () => {
    const v = senhaInput.value;
    let score = 0;
    if (v.length >= 8)          score++;
    if (/[A-Z]/.test(v))        score++;
    if (/[0-9]/.test(v))        score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    bar.forEach((s, i) => { s.style.background = i < score ? colors[score - 1] : ''; });
    lbl.textContent = v.length ? (levels[score - 1] ?? '') : '';
    lbl.style.color = colors[score - 1] ?? '';
    clearFieldError(senhaInput);
  });

  /* ─── Limpar erro ao digitar (campos simples) ───────────────── */
  ['nome', 'sobrenome', 'email', 'senhaConf', 'razao', 'estado'].forEach(id => {
    const el = $(id);
    if (!el) return;
    el.addEventListener('input', () => clearFieldError(el));
    el.addEventListener('change', () => clearFieldError(el));
  });

  /* ─── Checkboxes customizados ───────────────────────────────── */
  document.querySelectorAll('.check-label input').forEach(cb => {
    cb.addEventListener('change', () =>
      cb.closest('.check-label').classList.toggle('checked', cb.checked)
    );
    if (cb.checked) cb.closest('.check-label').classList.add('checked');
  });

  /* ─── Validação client-side ─────────────────────────────────── */
  /**
   * Retorna array de {field, msg} com os erros encontrados.
   * field = id do input (ou null para erro global).
   */
  function validateForm() {
    const erros = [];

    const nome      = $('nome').value.trim();
    const sobrenome = $('sobrenome').value.trim();
    const email     = $('email').value.trim();
    const cnpj      = $('cnpj').value.replace(/\D/g, '');
    const razao     = $('razao')?.value.trim() ?? '';
    const telefone  = $('telefone').value.replace(/\D/g, '');
    const estado    = $('estado').value;
    const senha     = $('senha').value;
    const senhaConf = $('senhaConf').value;
    const termos    = $('chkTermos').checked;
    const isPessoa  = qs('.type-btn.active').dataset.type === 'pessoa';

    if (!nome)      erros.push({ field: 'nome',      msg: 'Nome é obrigatório.' });
    if (!sobrenome) erros.push({ field: 'sobrenome', msg: 'Sobrenome é obrigatório.' });

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
      erros.push({ field: 'email', msg: 'Informe um e-mail válido.' });

    if (isPessoa) {
      if (cnpj.length !== 11)
        erros.push({ field: 'cnpj', msg: 'CPF deve ter 11 dígitos.' });
    } else {
      if (cnpj.length !== 14)
        erros.push({ field: 'cnpj', msg: 'CNPJ deve ter 14 dígitos.' });
      if (!razao)
        erros.push({ field: 'razao', msg: 'Razão social é obrigatória.' });
    }

    if (!telefone || telefone.length < 10)
      erros.push({ field: 'telefone', msg: 'Telefone inválido.' });

    if (!estado)
      erros.push({ field: 'estado', msg: 'Selecione seu estado.' });

    if (senha.length < 8)
      erros.push({ field: 'senha', msg: 'Senha deve ter ao menos 8 caracteres.' });

    if (senha !== senhaConf)
      erros.push({ field: 'senhaConf', msg: 'As senhas não coincidem.' });

    if (!termos)
      erros.push({ field: null, msg: 'Você precisa aceitar os Termos de Uso para continuar.' });

    return erros;
  }

  /* ─── Submit via Fetch ──────────────────────────────────────── */
  const form   = $('formCadastro');
  const btnSub = $('btnSubmit');

  function setBtnLoading(on) {
    btnSub.disabled = on;
    btnSub.innerHTML = on
      ? '<i data-lucide="loader-2" class="spin"></i><span>Criando conta…</span>'
      : '<i data-lucide="rocket"></i><span>Criar minha conta</span>';
    reloadIcons();
  }

  function shakBtn() {
    btnSub.classList.add('shake');
    setTimeout(() => btnSub.classList.remove('shake'), 600);
  }

  form.addEventListener('submit', async e => {
    e.preventDefault();
    hideAlert();
    clearAllErrors();

    /* 1. Validação client-side */
    const erros = validateForm();
    if (erros.length) {
      erros.forEach(({ field, msg }) => {
        if (field) setFieldError($(field), msg);
      });
      // Mostra no alertBox o primeiro erro sem campo associado, ou resumo
      const global = erros.find(er => !er.field);
      showAlert(global ? global.msg : erros.map(er => er.msg).join(' · '));
      shakBtn();
      return;
    }

    /* 2. Envia para o back-end */
    setBtnLoading(true);

    try {
      const resp = await fetch(form.action, {
        method: 'POST',
        body:   new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });

      /* O PHP vai responder JSON quando detectar XHR */
      const data = await resp.json();

      if (data.ok) {
        /* Sucesso → redireciona para a página de pendente */
        window.location.href = data.redirect ?? '/pendente.php';
      } else {
        /* Erro(s) vindos do servidor */
        if (Array.isArray(data.campos)) {
          data.campos.forEach(({ field, msg }) => {
            const el = $(field);
            if (el) setFieldError(el, msg);
          });
        }
        showAlert(data.erro ?? 'Ocorreu um erro. Tente novamente.');
        shakBtn();
      }
    } catch {
      showAlert('Falha na comunicação com o servidor. Verifique sua conexão.');
      shakBtn();
    } finally {
      setBtnLoading(false);
    }
  });

  /* ─── Alerta via query string (fallback sem JS no envio) ────── */
  const params = new URLSearchParams(window.location.search);
  if (params.get('erro'))    showAlert(params.get('erro'), 'erro');
  if (params.get('sucesso')) showAlert(params.get('sucesso'), 'sucesso');

})();
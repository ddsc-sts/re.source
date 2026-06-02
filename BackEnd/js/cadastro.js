/**
 * BackEnd/js/cadastro.js
 * Gerencia o formulário de cadastro: máscaras, validações,
 * busca automática de CNPJ via BrasilAPI,
 * submit via fetch → exibe erros inline sem recarregar a página.
 */

(() => {
  'use strict';

  /* ─── Injeta CSS de erros inline ───────────────────────────── */
  const _style = document.createElement('link');
  _style.rel   = 'stylesheet';
  _style.href  = '/RE.SOURCE/FrontEnd/css/cadastro-errors.css';
  document.head.appendChild(_style);

  /* ─── Helpers ──────────────────────────────────────────────── */
  const $  = id => document.getElementById(id);
  const qs = sel => document.querySelector(sel);

  function showAlert(msg, type = 'erro') {
    const box = $('alertBox');
    box.className = `alert alert-${type}`;
    box.textContent = msg;
    box.style.display = 'block';
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function hideAlert() {
    const box = $('alertBox');
    box.style.display = 'none';
    box.textContent = '';
  }

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

  function clearFieldError(inputEl) {
    const wrap = inputEl.closest('.form-field') ?? inputEl.parentElement;
    wrap.classList.remove('field-error');
    const hint = wrap.querySelector('.field-hint');
    if (hint) hint.textContent = '';
  }

  function clearAllErrors() {
    document.querySelectorAll('.form-field.field-error').forEach(f => {
      f.classList.remove('field-error');
      const h = f.querySelector('.field-hint');
      if (h) h.textContent = '';
    });
  }

  function reloadIcons() {
    if (window.lucide) lucide.createIcons();
  }

  /* ─── Tipo de conta ─────────────────────────────────────────── */
  const form       = $('formCadastro');
  const typeBtns   = document.querySelectorAll('.type-btn');
  const docLabel   = qs('#docField label');
  const docInput   = $('cnpj');
  const razaoField = $('razaoField');
  const razaoLabel = razaoField.querySelector('label');

  function updateFormAction(tipo) {
    form.action = `/RE.SOURCE/BackEnd/auth/cadastro.php?tipo=${tipo}`;
  }

  typeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      typeBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const t = btn.dataset.type;

      updateFormAction(t);

      docLabel.innerHTML = 'CNPJ <span class="req">*</span>';
      docInput.placeholder = '00.000.000/0001-00';
      docInput.maxLength = 18;
      razaoField.style.display = '';
      razaoLabel.innerHTML =
        (t === 'cooperativa' ? 'Nome da Cooperativa' : 'Razão Social') +
        ' <span class="req">*</span>';
    });
  });

  /* ─── Máscara CNPJ ──────────────────────────────────────────── */
  docInput.addEventListener('input', e => {
    let v = e.target.value.replace(/\D/g, '');

    v = v.slice(0, 14)
         .replace(/(\d{2})(\d)/, '$1.$2')
         .replace(/(\d{3})(\d)/, '$1.$2')
         .replace(/(\d{3})(\d)/, '$1/$2')
         .replace(/(\d{4})(\d{1,2})$/, '$1-$2');

    const digits = v.replace(/\D/g, '');
    if (digits.length === 14) buscarCNPJ(digits);

    e.target.value = v;
    clearFieldError(docInput);
  });

  /* ─── Busca CNPJ via BrasilAPI ──────────────────────────────── */
  const UF_MAP = {
    'AC':'AC','AL':'AL','AP':'AP','AM':'AM','BA':'BA','CE':'CE',
    'DF':'DF','ES':'ES','GO':'GO','MA':'MA','MT':'MT','MS':'MS',
    'MG':'MG','PA':'PA','PB':'PB','PR':'PR','PE':'PE','PI':'PI',
    'RJ':'RJ','RN':'RN','RS':'RS','RO':'RO','RR':'RR','SC':'SC',
    'SP':'SP','SE':'SE','TO':'TO',
  };

  function setCnpjStatus(type, msg) {
    const tag = $('cnpjStatus');
    if (!tag) return;
    tag.textContent = msg;
    tag.className   = 'input-tag cnpj-tag-' + type;
  }

  async function buscarCNPJ(digits) {
    setCnpjStatus('loading', '⏳ Consultando...');

    try {
      const res  = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${digits}`);
      const data = await res.json();

      if (!res.ok || data.message) {
        setCnpjStatus('erro', '✗ CNPJ não encontrado');
        setFieldError(docInput, data.message ?? 'CNPJ inválido ou não encontrado.');
        return;
      }

      const razaoInput = $('razao');
      if (razaoInput && data.razao_social) {
        razaoInput.value = data.razao_social;
        clearFieldError(razaoInput);
      }

      const estadoSelect = $('estado');
      const uf = data.uf ? (UF_MAP[data.uf] ?? data.uf) : null;
      if (estadoSelect && uf) {
        const opt = Array.from(estadoSelect.options).find(o => o.value === uf || o.text === uf);
        if (opt) {
          estadoSelect.value = opt.value;
          clearFieldError(estadoSelect);
        }
      }

      const telInput = $('telefone');
      if (telInput && !telInput.value && data.ddd_telefone_1) {
        let tel = data.ddd_telefone_1.replace(/\D/g, '').slice(0, 11);
        tel = tel.replace(/(\d{2})(\d)/, '($1) $2')
                 .replace(/(\d{5})(\d{1,4})$/, '$1-$2')
                 .replace(/(\d{4})(\d{1,4})$/, '$1-$2');
        telInput.value = tel;
        clearFieldError(telInput);
      }

      setCnpjStatus('ok', '✓ CNPJ válido');
      clearFieldError(docInput);

    } catch {
      setCnpjStatus('erro', '✗ Falha na consulta');
    }
  }

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

  /* ─── Limpar erro ao digitar ────────────────────────────────── */
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

    if (!nome)      erros.push({ field: 'nome',      msg: 'Nome é obrigatório.' });
    if (!sobrenome) erros.push({ field: 'sobrenome', msg: 'Sobrenome é obrigatório.' });

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
      erros.push({ field: 'email', msg: 'Informe um e-mail válido.' });

    if (cnpj.length !== 14)
      erros.push({ field: 'cnpj', msg: 'CNPJ deve ter 14 dígitos.' });

    if (!razao)
      erros.push({ field: 'razao', msg: 'Razão social é obrigatória.' });

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

    const erros = validateForm();
    if (erros.length) {
      erros.forEach(({ field, msg }) => {
        if (field) setFieldError($(field), msg);
      });
      const global = erros.find(er => !er.field);
      showAlert(global ? global.msg : erros.map(er => er.msg).join(' · '));
      shakBtn();
      return;
    }

    setBtnLoading(true);

    try {
      const resp = await fetch(form.action, {
        method:  'POST',
        body:    new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });

      const contentType = resp.headers.get('content-type') ?? '';
      if (!contentType.includes('application/json')) {
        const texto = await resp.text();
        console.error('Resposta inesperada do servidor:', texto);
        showAlert('O servidor retornou uma resposta inesperada. Verifique o console.');
        shakBtn();
        return;
      }

      const data = await resp.json();

      if (data.ok) {
        window.location.href = data.redirect ?? '/RE.SOURCE/pendente.php';
      } else {
        if (Array.isArray(data.campos)) {
          data.campos.forEach(({ field, msg }) => {
            const el = $(field);
            if (el) setFieldError(el, msg);
          });
        }
        showAlert(data.erro ?? 'Ocorreu um erro. Tente novamente.');
        shakBtn();
      }
    } catch (err) {
      console.error('Erro no fetch:', err);
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
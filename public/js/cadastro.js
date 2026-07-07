/**
 * cadastro.js — Re.Source
 * Stepper em 3 etapas, validação inline, busca CNPJ via BrasilAPI,
 * máscaras de input e submit via fetch.
 */

(() => {
  'use strict';

  /* ─── Header fixo: aplica fundo sólido ao rolar ─────────── */
  const siteHeader = document.querySelector('.site-header');
  if (siteHeader) {
    const onHeaderScroll = () => {
      siteHeader.classList.toggle('scrolled', window.scrollY > 12);
    };
    window.addEventListener('scroll', onHeaderScroll, { passive: true });
    onHeaderScroll();
  }

  /* ─── Seletores ─────────────────────────────────────────── */
  const form         = document.getElementById('formCadastro');
  const alertBox     = document.getElementById('alertBox');
  const progressFill = document.getElementById('progressFill');
  const successScreen = document.getElementById('successScreen');
  const btnSubmit    = document.getElementById('btnSubmit');

  /* ─── Estado atual do stepper ───────────────────────────── */
  let currentStep = 1;
  const TOTAL_STEPS = 3;

  /* ─── Utilitários ───────────────────────────────────────── */
  function $(id) { return document.getElementById(id); }

  function reloadIcons() {
    if (window.lucide) lucide.createIcons();
  }

  /* ─── Alerta global ─────────────────────────────────────── */
  function showAlert(msg) {
    alertBox.textContent = msg;
    alertBox.style.display = 'flex';
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  function hideAlert() {
    alertBox.style.display = 'none';
    alertBox.textContent = '';
  }

  /* ─── Erros inline por campo ────────────────────────────── */
  function setError(fieldId, msg) {
    const errEl = $('err-' + fieldId);
    const field  = $( fieldId );
    if (errEl) errEl.textContent = msg;
    if (field) {
      const wrap = field.closest('.form-field');
      if (wrap) wrap.classList.add('has-error');
    }
  }

  function clearError(fieldId) {
    const errEl = $('err-' + fieldId);
    const field  = $(fieldId);
    if (errEl) errEl.textContent = '';
    if (field) {
      const wrap = field.closest('.form-field');
      if (wrap) wrap.classList.remove('has-error');
    }
  }

  function clearAllErrors() {
    document.querySelectorAll('.form-field.has-error').forEach(f => f.classList.remove('has-error'));
    document.querySelectorAll('.field-error').forEach(el => (el.textContent = ''));
  }

  /* ─── Stepper ───────────────────────────────────────────── */
  function goToStep(target) {
    if (target < 1 || target > TOTAL_STEPS) return;

    // valida etapa atual antes de avançar
    if (target > currentStep && !validateStep(currentStep)) return;

    // atualiza visual do step-item saindo
    const oldItem = $('si' + currentStep);
    oldItem.classList.remove('active');
    if (target > currentStep) oldItem.classList.add('done');
    else                        oldItem.classList.remove('done');

    // oculta pane antigo
    $('step' + currentStep).classList.remove('active');

    currentStep = target;

    // ativa pane novo
    $('step' + currentStep).classList.add('active');

    // atualiza visual do step-item chegando
    const newItem = $('si' + currentStep);
    newItem.classList.remove('done');
    newItem.classList.add('active');
    newItem.setAttribute('aria-current', 'step');

    // linhas de conexão
    for (let i = 1; i <= TOTAL_STEPS - 1; i++) {
      const line = $('sl' + i);
      if (i < currentStep) line.classList.add('done');
      else                  line.classList.remove('done');
    }

    // barra de progresso
    progressFill.style.width = ((currentStep / TOTAL_STEPS) * 100) + '%';

    // rola para o topo do painel
    document.querySelector('.register-panel')?.scrollTo({ top: 0, behavior: 'smooth' });

    hideAlert();
    reloadIcons();
  }

  /* ─── Validações por etapa ──────────────────────────────── */
  function validateStep(step) {
    clearAllErrors();
    let ok = true;

    if (step === 1) {
      const nome      = $('nome').value.trim();
      const sobrenome = $('sobrenome').value.trim();
      const email     = $('email').value.trim();
      const telefone  = $('telefone').value.replace(/\D/g, '');
      const tipo      = $('tipoConta').value;

      if (!nome)      { setError('nome', 'Nome é obrigatório.'); ok = false; }
      if (!sobrenome) { setError('sobrenome', 'Sobrenome é obrigatório.'); ok = false; }
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        setError('email', 'Informe um e-mail válido.'); ok = false;
      }
      if (!telefone || telefone.length < 10) {
        setError('telefone', 'Informe um telefone válido.'); ok = false;
      }
      if (!tipo) { setError('tipoConta', 'Selecione o perfil da conta.'); ok = false; }
    }

    if (step === 2) {
      const cnpj         = $('cnpj').value.replace(/\D/g, '');
      const razao        = $('razao').value.trim();
      const nomeFantasia = $('nomeFantasia').value.trim();
      const cep          = $('cep').value.replace(/\D/g, '');
      const endereco     = $('endereco').value.trim();
      const numero       = $('numero').value.trim();
      const estado       = $('estado').value;
      const cidade       = $('cidade').value.trim();
      const segmento     = $('segmento').value;

      if (cnpj.length !== 14) { setError('cnpj', 'CNPJ deve ter 14 dígitos.'); ok = false; }
      if (!razao)   { setError('razao', 'Razão social é obrigatória.'); ok = false; }
      if (!nomeFantasia) { setError('nomeFantasia', 'Informe o nome fantasia.'); ok = false; }
      if (cep.length !== 8) { setError('cep', 'CEP deve ter 8 dígitos.'); ok = false; }
      if (!endereco) { setError('endereco', 'Informe o endereço.'); ok = false; }
      if (!numero)   { setError('numero', 'Informe o número.'); ok = false; }
      if (!estado)  { setError('estado', 'Selecione o estado.'); ok = false; }
      if (!cidade)  { setError('cidade', 'Informe a cidade.'); ok = false; }
      if (!segmento){ setError('segmento', 'Selecione o segmento.'); ok = false; }
    }

    if (step === 3) {
      const senha     = $('senha').value;
      const senhaConf = $('senhaConf').value;
      const termos    = $('chkTermos').checked;

      if (senha.length < 8) {
        setError('senha', 'Senha deve ter ao menos 8 caracteres.'); ok = false;
      }
      if (senha !== senhaConf) {
        setError('senhaConf', 'As senhas não coincidem.'); ok = false;
      }
      if (!termos) {
        setError('termos', 'Você precisa aceitar os Termos de Uso.'); ok = false;
      }
    }

    if (!ok) {
      // foca o primeiro campo com erro
      const firstErr = document.querySelector('.form-field.has-error input, .form-field.has-error select');
      if (firstErr) firstErr.focus();
    }

    return ok;
  }

  /* ─── Botões de navegação ───────────────────────────────── */
  $('btnNext1')?.addEventListener('click', () => goToStep(2));
  $('btnBack2')?.addEventListener('click', () => goToStep(1));
  $('btnNext2')?.addEventListener('click', () => goToStep(3));
  $('btnBack3')?.addEventListener('click', () => goToStep(2));

  /* ─── Máscara CNPJ ──────────────────────────────────────── */
  $('cnpj').addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').slice(0, 14);
    v = v
      .replace(/(\d{2})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1/$2')
      .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    this.value = v;
    clearError('cnpj');

    const digits = v.replace(/\D/g, '');
    if (digits.length === 14) fetchCNPJ(digits);
  });

  /* ─── Busca CNPJ via BrasilAPI ──────────────────────────── */
  const UF_MAP = {
    AC:'AC', AL:'AL', AP:'AP', AM:'AM', BA:'BA', CE:'CE', DF:'DF', ES:'ES',
    GO:'GO', MA:'MA', MT:'MT', MS:'MS', MG:'MG', PA:'PA', PB:'PB', PR:'PR',
    PE:'PE', PI:'PI', RJ:'RJ', RN:'RN', RS:'RS', RO:'RO', RR:'RR', SC:'SC',
    SP:'SP', SE:'SE', TO:'TO',
  };

  function setCnpjStatus(type, msg) {
    const tag = $('cnpjStatus');
    if (!tag) return;
    tag.textContent = msg;
    tag.className = 'cnpj-tag' + (type === 'erro' ? ' erro' : '');
  }

  async function fetchCNPJ(digits) {
    setCnpjStatus('loading', '⏳ Consultando…');
    try {
      //  COMO DEVE FICAR:
      const res = await fetch(`/re.source/cnpj?cnpj=${digits}`);
      const data = await res.json();

      if (!res.ok || data.message) {
        setCnpjStatus('erro', '✗ CNPJ inválido');
        setError('cnpj', data.message ?? 'CNPJ não encontrado na Receita Federal.');
        return;
      }

      if (data.razao_social) {
        $('razao').value = data.razao_social;
        clearError('razao');
      }

      if (data.nome_fantasia && $('nomeFantasia') && !$('nomeFantasia').value) {
        $('nomeFantasia').value = data.nome_fantasia;
        clearError('nomeFantasia');
      }

      if (data.cep && $('cep') && !$('cep').value) {
        const cepDigits = String(data.cep).replace(/\D/g, '').slice(0, 8);
        $('cep').value = cepDigits.replace(/(\d{5})(\d)/, '$1-$2');
        clearError('cep');
      }

      if (data.logradouro && $('endereco') && !$('endereco').value) {
        $('endereco').value = toTitleCase(data.logradouro);
        clearError('endereco');
      }

      if (data.numero && $('numero') && !$('numero').value) {
        $('numero').value = data.numero;
        clearError('numero');
      }

      const uf = data.uf ? (UF_MAP[data.uf] ?? data.uf) : null;
      if (uf) {
        const sel = $('estado');
        const opt = Array.from(sel.options).find(o => o.value === uf || o.text === uf);
        if (opt) { sel.value = opt.value; clearError('estado'); }
      }

      if (data.municipio) {
        $('cidade').value = toTitleCase(data.municipio);
        clearError('cidade');
      }

      setCnpjStatus('ok', '✓ CNPJ válido');
      clearError('cnpj');

    } catch {
      setCnpjStatus('erro', '✗ Falha na consulta');
    }
  }

  function toTitleCase(str) {
    return str.toLowerCase().replace(/\b\w/g, c => c.toUpperCase());
  }

  /* ─── Máscara Telefone ──────────────────────────────────── */
  $('telefone').addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').slice(0, 11);
    v = v
      .replace(/(\d{2})(\d)/, '($1) $2')
      .replace(/(\d{5})(\d{1,4})$/, '$1-$2');
    this.value = v;
    clearError('telefone');
  });

  /* ─── Máscara CEP ───────────────────────────────────────── */
  $('cep')?.addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').slice(0, 8);
    v = v.replace(/(\d{5})(\d)/, '$1-$2');
    this.value = v;
    clearError('cep');
  });

  /* ─── Toggle senha ──────────────────────────────────────── */
  function setupToggle(btnId, inputId, iconId) {
    $(btnId)?.addEventListener('click', () => {
      const inp = $(inputId);
      const showing = inp.type === 'text';
      inp.type = showing ? 'password' : 'text';
      const icon = $(iconId);
      if (icon) icon.setAttribute('data-lucide', showing ? 'eye' : 'eye-off');
      reloadIcons();
    });
  }
  setupToggle('toggleSenha', 'senha', 'eyeSenha');
  setupToggle('toggleConf',  'senhaConf', 'eyeConf');

  /* ─── Força de senha ────────────────────────────────────── */
  const senhaInput  = $('senha');
  const strengthBar = document.querySelectorAll('#strengthBar span');
  const strengthHint = $('strengthHint');

  const STRENGTH_COLORS = ['#dc3545', '#fd7e14', '#ffc107', '#157347'];
  const STRENGTH_LABELS = ['Muito fraca', 'Fraca', 'Razoável', 'Forte'];

  senhaInput?.addEventListener('input', function () {
    const v = this.value;
    let score = 0;
    if (v.length >= 8)           score++;
    if (/[A-Z]/.test(v))         score++;
    if (/[0-9]/.test(v))         score++;
    if (/[^A-Za-z0-9]/.test(v))  score++;

    strengthBar.forEach((s, i) => {
      s.style.background = i < score ? STRENGTH_COLORS[score - 1] : '';
    });

    if (strengthHint) {
      strengthHint.textContent  = v.length ? (STRENGTH_LABELS[score - 1] ?? '') : '';
      strengthHint.style.color  = v.length ? STRENGTH_COLORS[score - 1] : '';
    }
    clearError('senha');
  });

  /* ─── Limpa erro ao editar qualquer campo ───────────────── */
  ['nome','sobrenome','email','cargo','cidade','razao','senhaConf'].forEach(id => {
    const el = $(id);
    if (!el) return;
    el.addEventListener('input',  () => clearError(id));
    el.addEventListener('change', () => clearError(id));
  });
  ['tipoConta','estado','segmento'].forEach(id => {
    $(id)?.addEventListener('change', () => clearError(id));
  });

  /* ─── Checkboxes acessíveis ─────────────────────────────── */
  document.querySelectorAll('.check-label input').forEach(cb => {
    // garante estado inicial
    if (cb.checked) cb.closest('.check-label')?.classList.add('checked');
    cb.addEventListener('change', () => {
      cb.closest('.check-label')?.classList.toggle('checked', cb.checked);
      if (cb.id === 'chkTermos') clearError('termos');
    });
  });

  /* ─── Submit ─────────────────────────────────────────────── */
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideAlert();

    if (!validateStep(3)) return;

    setLoading(true);

    try {
      const resp = await fetch(form.action, {
        method:  'POST',
        body:    new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });

      const contentType = resp.headers.get('content-type') ?? '';

      if (!contentType.includes('application/json')) {
        const texto = await resp.text();
        console.error('Resposta inesperada:', texto);
        showAlert('O servidor retornou uma resposta inesperada. Verifique o console.');
        return;
      }

      const data = await resp.json();

      if (data.ok) {
    window.location.href = data.redirect;
    }else {
        // erros de campo vindos do servidor
        if (Array.isArray(data.campos)) {
          data.campos.forEach(({ field, msg }) => setError(field, msg));
        }
        showAlert(data.erro ?? 'Ocorreu um erro. Tente novamente.');
      }

    } catch (err) {
      console.error('Erro no fetch:', err);
      showAlert('Falha na comunicação com o servidor. Verifique sua conexão.');
    } finally {
      setLoading(false);
    }
  });

  /* ─── Estado de loading do botão ────────────────────────── */
  function setLoading(on) {
    if (!btnSubmit) return;
    btnSubmit.disabled = on;
    btnSubmit.classList.toggle('loading', on);
    btnSubmit.innerHTML = on
      ? '<i data-lucide="loader-2" class="spin"></i><span>Criando conta…</span>'
      : '<i data-lucide="rocket"></i><span>Criar minha conta</span>';
    reloadIcons();
  }

  /* ─── Tela de sucesso ────────────────────────────────────── */
  function showSuccess() {
    // esconde todas as etapas e stepper
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    $('stepper').style.display = 'none';
    progressFill.style.width   = '100%';

    // exibe tela de sucesso
    successScreen.classList.add('active');
    successScreen.removeAttribute('aria-hidden');
    reloadIcons();
  }

  /* ─── Erros via query string (fallback no-JS) ────────────── */
  const params = new URLSearchParams(window.location.search);
  if (params.get('erro'))    showAlert(decodeURIComponent(params.get('erro')));

})();
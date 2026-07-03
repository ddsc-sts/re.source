<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cadastro — Re.Source</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=JetBrains+Mono:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link rel="stylesheet" href="/re.source/public/css/cadastro.css">
</head>
<body>

  <header class="site-header">
    <div class="header-inner">
      <a href="/re.source/" class="logo-mark">
        <svg width="30" height="30" viewBox="0 0 38 38" fill="none">
          <rect x="2" y="2" width="13" height="13" rx="1" stroke="currentColor" stroke-width="2" fill="none"/>
          <rect x="2" y="23" width="13" height="13" rx="1" stroke="currentColor" stroke-width="2" fill="none"/>
          <path d="M19 8.5 C27 8.5 27 19 27 19 C27 19 27 29.5 19 29.5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>
          <circle cx="19" cy="8.5" r="2.5" fill="currentColor"/>
          <circle cx="19" cy="29.5" r="2.5" fill="currentColor"/>
        </svg>
        <span>Re<span class="dot">.</span>Source</span>
      </a>
      <nav class="header-nav">
        <a href="/re.source/">← Voltar ao site</a>
        <a href="/re.source/login">Já tenho conta</a>
      </nav>
    </div>
  </header>

  <main class="register-main">

    <aside class="register-aside">
      <img class="aside-photo" src="https://images.unsplash.com/photo-1615797534094-7fde0a4861f3?w=900&q=80&auto=format&fit=crop" alt="" />
      <div class="aside-scrim"></div>

      <div class="aside-content">
        <div class="aside-badge">
          <span class="aside-dot"></span>
          Somente empresas verificadas
        </div>

        <h2 class="aside-title">
          Conecte resíduos a quem<br><em>realmente precisa.</em>
        </h2>

        <p class="aside-desc">
          Junte-se a mais de 12 000 empresas e cooperativas que já transformam descarte em oportunidade sustentável.
        </p>

        <ul class="aside-benefits">
          <li class="benefit">
            <span class="benefit-icon"><i data-lucide="leaf"></i></span>
            <div>
              <strong>Zero burocracia</strong>
              <span>Anuncie ou encontre resíduos em minutos</span>
            </div>
          </li>
          <li class="benefit">
            <span class="benefit-icon"><i data-lucide="shield-check"></i></span>
            <div>
              <strong>Empresa verificada</strong>
              <span>Seu CNPJ validado automaticamente</span>
            </div>
          </li>
          <li class="benefit">
            <span class="benefit-icon"><i data-lucide="trending-up"></i></span>
            <div>
              <strong>Relatório ESG gratuito</strong>
              <span>Métricas de impacto prontas para auditorias</span>
            </div>
          </li>
        </ul>

        <blockquote class="aside-quote">
          <p>"Reduzimos 40 t de resíduos no primeiro trimestre usando a plataforma."</p>
          <cite>— Mariana Costa, Diretora de Sustentabilidade · GreenPack</cite>
        </blockquote>

        <div class="aside-stats">
          <div class="aside-stat">
            <span class="aside-stat-val">12k+</span>
            <span class="aside-stat-label">Empresas</span>
          </div>
          <div class="aside-stat">
            <span class="aside-stat-val">98t</span>
            <span class="aside-stat-label">Resíduos/mês</span>
          </div>
          <div class="aside-stat">
            <span class="aside-stat-val">R$2M</span>
            <span class="aside-stat-label">Economizados</span>
          </div>
        </div>
      </div>
    </aside>

    <section class="register-panel">
      <div class="register-panel-bg-lines"></div>

      <div class="progress-bar">
        <div class="progress-fill" id="progressFill"></div>
      </div>

      <div class="panel-inner">

        <div class="stepper" id="stepper" aria-label="Etapas do cadastro">
          <div class="step-item active" id="si1" aria-current="step">
            <div class="step-circle" aria-hidden="true">1</div>
            <span class="step-label">Sobre você</span>
          </div>
          <div class="step-line" id="sl1" aria-hidden="true"></div>
          <div class="step-item" id="si2">
            <div class="step-circle" aria-hidden="true">2</div>
            <span class="step-label">Empresa</span>
          </div>
          <div class="step-line" id="sl2" aria-hidden="true"></div>
          <div class="step-item" id="si3">
            <div class="step-circle" aria-hidden="true">3</div>
            <span class="step-label">Acesso</span>
          </div>
        </div>

        <form id="formCadastro" novalidate
              action="/re.source/process?action=cadastro&tipo=empresa"
              method="POST">
          <?= csrf_field() ?>

          <fieldset class="form-step active" id="step1">
            <div class="step-heading">
              <h1>Fale sobre você</h1>
              <p>Vamos personalizar sua experiência na plataforma.</p>
            </div>

            <div class="fields">
              <div class="form-row">
                <div class="form-field">
                  <label for="nome">NOME <span class="req" aria-hidden="true">*</span></label>
                  <input type="text" id="nome" name="nome"
                         placeholder="Ex.: Maria"
                         autocomplete="given-name"
                         aria-required="true" />
                  <p class="field-error" id="err-nome" role="alert" aria-live="polite"></p>
                </div>
                <div class="form-field">
                  <label for="sobrenome">SOBRENOME <span class="req" aria-hidden="true">*</span></label>
                  <input type="text" id="sobrenome" name="sobrenome"
                         placeholder="Ex.: Silva"
                         autocomplete="family-name"
                         aria-required="true" />
                  <p class="field-error" id="err-sobrenome" role="alert" aria-live="polite"></p>
                </div>
              </div>

              <div class="form-field">
                <label for="email">E-MAIL CORPORATIVO <span class="req" aria-hidden="true">*</span></label>
                <input type="email" id="email" name="email"
                       placeholder="contato@empresa.com.br"
                       autocomplete="email"
                       aria-required="true" />
                <p class="field-error" id="err-email" role="alert" aria-live="polite"></p>
              </div>

              <div class="form-row">
                <div class="form-field">
                  <label for="cargo">CARGO</label>
                  <input type="text" id="cargo" name="cargo"
                         placeholder="Ex.: Gerente de Sustentabilidade"
                         autocomplete="organization-title" />
                </div>
                <div class="form-field">
                  <label for="telefone">TELEFONE <span class="req" aria-hidden="true">*</span></label>
                  <input type="tel" id="telefone" name="telefone"
                         placeholder="(00) 00000-0000"
                         maxlength="15"
                         autocomplete="tel"
                         aria-required="true" />
                  <p class="field-error" id="err-telefone" role="alert" aria-live="polite"></p>
                </div>
              </div>

              <div class="form-field">
                <label for="tipoConta">PERFIL DA CONTA <span class="req" aria-hidden="true">*</span></label>
                <div class="select-wrap">
                  <select id="tipoConta" name="tipo_conta" aria-required="true">
                    <option value="" disabled selected>Selecione seu perfil</option>
                    <option value="geradora">Empresa geradora de resíduos</option>
                    <option value="cooperativa">Cooperativa / Recicladora</option>
                    <option value="transportador">Transportador / Gestor logístico</option>
                    <option value="consultoria">Consultoria ESG</option>
                  </select>
                  <i data-lucide="chevron-down" class="select-arrow" aria-hidden="true"></i>
                </div>
                <p class="field-error" id="err-tipoConta" role="alert" aria-live="polite"></p>
              </div>
            </div>

            <div class="step-nav">
              <button type="button" class="btn-next" id="btnNext1">
                Continuar
                <i data-lucide="arrow-right" aria-hidden="true"></i>
              </button>
            </div>

            <p class="login-link">Já tem uma conta? <a href="/re.source/login">Entrar agora</a></p>
          </fieldset>

          <fieldset class="form-step" id="step2">
            <div class="step-heading">
              <h1>Dados da empresa</h1>
              <p>Seu CNPJ será validado automaticamente.</p>
            </div>

            <div class="fields">
              <div class="form-field">
                <label for="cnpj">CNPJ <span class="req" aria-hidden="true">*</span></label>
                <div class="input-suffix-wrap">
                  <input type="text" id="cnpj" name="cnpj"
                         placeholder="00.000.000/0001-00"
                         maxlength="18"
                         autocomplete="off"
                         aria-required="true" />
                  <span class="cnpj-tag" id="cnpjStatus" aria-live="polite"></span>
                </div>
                <p class="field-error" id="err-cnpj" role="alert" aria-live="polite"></p>
              </div>

              <div class="form-field">
                <label for="razao">RAZÃO SOCIAL <span class="req" aria-hidden="true">*</span></label>
                <input type="text" id="razao" name="razao_social"
                       placeholder="Nome da empresa ou cooperativa"
                       autocomplete="organization"
                       aria-required="true" />
                <p class="field-error" id="err-razao" role="alert" aria-live="polite"></p>
              </div>

              <div class="form-row">
                <div class="form-field">
                  <label for="estado">ESTADO <span class="req" aria-hidden="true">*</span></label>
                  <div class="select-wrap">
                    <select id="estado" name="estado" aria-required="true" autocomplete="address-level1">
                      <option value="" disabled selected>Selecione</option>
                      <option>AC</option><option>AL</option><option>AP</option><option>AM</option>
                      <option>BA</option><option>CE</option><option>DF</option><option>ES</option>
                      <option>GO</option><option>MA</option><option>MT</option><option>MS</option>
                      <option>MG</option><option>PA</option><option>PB</option><option>PR</option>
                      <option>PE</option><option>PI</option><option>RJ</option><option>RN</option>
                      <option>RS</option><option>RO</option><option>RR</option><option>SC</option>
                      <option>SP</option><option>SE</option><option>TO</option>
                    </select>
                    <i data-lucide="chevron-down" class="select-arrow" aria-hidden="true"></i>
                  </div>
                  <p class="field-error" id="err-estado" role="alert" aria-live="polite"></p>
                </div>
                <div class="form-field">
                  <label for="cidade">CIDADE <span class="req" aria-hidden="true">*</span></label>
                  <input type="text" id="cidade" name="cidade"
                         placeholder="Ex.: São Paulo"
                         autocomplete="address-level2"
                         aria-required="true" />
                  <p class="field-error" id="err-cidade" role="alert" aria-live="polite"></p>
                </div>
              </div>

              <div class="form-field">
                <label for="segmento">SEGMENTO DE RESÍDUOS <span class="req" aria-hidden="true">*</span></label>
                <div class="select-wrap">
                  <select id="segmento" name="segmento" aria-required="true">
                    <option value="" disabled selected>Qual tipo de resíduo você movimenta?</option>
                    <option value="plasticos">Plásticos e polímeros</option>
                    <option value="metais">Metais ferrosos e não-ferrosos</option>
                    <option value="papel">Papel e papelão</option>
                    <option value="organicos">Orgânicos / Biomassa</option>
                    <option value="eletronicos">Eletrônicos (e-waste)</option>
                    <option value="quimicos">Químicos / Perigosos</option>
                    <option value="textil">Têxtil</option>
                    <option value="outros">Outros</option>
                  </select>
                  <i data-lucide="chevron-down" class="select-arrow" aria-hidden="true"></i>
                </div>
                <p class="field-error" id="err-segmento" role="alert" aria-live="polite"></p>
              </div>
            </div>

            <div class="step-nav">
              <button type="button" class="btn-back" id="btnBack2">
                <i data-lucide="arrow-left" aria-hidden="true"></i>
                Voltar
              </button>
              <button type="button" class="btn-next" id="btnNext2">
                Continuar
                <i data-lucide="arrow-right" aria-hidden="true"></i>
              </button>
            </div>
          </fieldset>

          <fieldset class="form-step" id="step3">
            <div class="step-heading">
              <h1>Crie seu acesso</h1>
              <p>Escolha uma senha segura para proteger sua conta.</p>
            </div>

            <div class="fields">
              <div class="form-field">
                <label for="senha">SENHA <span class="req" aria-hidden="true">*</span></label>
                <div class="pw-wrap">
                  <input type="password" id="senha" name="senha"
                         placeholder="Mínimo 8 caracteres"
                         autocomplete="new-password"
                         aria-required="true" />
                  <button type="button" class="pw-toggle" id="toggleSenha"
                          aria-label="Mostrar senha">
                    <i data-lucide="eye" id="eyeSenha"></i>
                  </button>
                </div>
                <div class="strength-bar" id="strengthBar" aria-hidden="true">
                  <span></span><span></span><span></span><span></span>
                </div>
                <p class="strength-hint" id="strengthHint" aria-live="polite"></p>
                <p class="field-error" id="err-senha" role="alert" aria-live="polite"></p>
              </div>

              <div class="form-field">
                <label for="senhaConf">CONFIRMAR SENHA <span class="req" aria-hidden="true">*</span></label>
                <div class="pw-wrap">
                  <input type="password" id="senhaConf" name="senha_conf"
                         placeholder="Repita a senha"
                         autocomplete="new-password"
                         aria-required="true" />
                  <button type="button" class="pw-toggle" id="toggleConf"
                          aria-label="Mostrar confirmação de senha">
                    <i data-lucide="eye" id="eyeConf"></i>
                  </button>
                </div>
                <p class="field-error" id="err-senhaConf" role="alert" aria-live="polite"></p>
              </div>

              <div class="check-group">
                <label class="check-label">
                  <input type="checkbox" id="chkTermos" name="termos" aria-required="true" />
                  <span class="check-box">
                    <i data-lucide="check" class="check-icon" aria-hidden="true"></i>
                  </span>
                  <span>Li e aceito os <a href="/re.source/termos">Termos de Uso</a> e a <a href="/re.source/privacidade">Política de Privacidade</a></span>
                </label>
                <label class="check-label">
                  <input type="checkbox" id="chkNewsletter" name="newsletter" checked />
                  <span class="check-box">
                    <i data-lucide="check" class="check-icon" aria-hidden="true"></i>
                  </span>
                  <span>Quero receber novidades e alertas de resíduos por e-mail</span>
                </label>
              </div>

              <p class="field-error" id="err-termos" role="alert" aria-live="polite"></p>
            </div>

            <div class="step-nav">
              <button type="button" class="btn-back" id="btnBack3">
                <i data-lucide="arrow-left" aria-hidden="true"></i>
                Voltar
              </button>
              <button type="submit" class="btn-next btn-submit" id="btnSubmit">
                <i data-lucide="rocket" aria-hidden="true"></i>
                Criar minha conta
              </button>
            </div>
          </fieldset>

          <div class="success-screen" id="successScreen" aria-live="polite" aria-hidden="true">
            <div class="success-icon">
              <i data-lucide="circle-check-big" aria-hidden="true"></i>
            </div>
            <h2>Conta criada com sucesso!</h2>
            <p>Verifique seu e-mail para ativar o acesso e começar a usar a plataforma.</p>
            <a href="/re.source/marketplace" class="btn-next" style="display:inline-flex; margin-top:0.5rem;">
              Ir para o marketplace
              <i data-lucide="arrow-right" aria-hidden="true"></i>
            </a>
          </div>

        </form>

        <div class="alert-box" id="alertBox" role="alert" aria-live="assertive" style="display:none;"></div>

      </div>
    </section>

  </main>

  <footer class="site-footer">
    © 2026 Re.Source · Todos os direitos reservados ·
    <a href="/re.source/privacidade">Política de Privacidade</a>
  </footer>

  <script>lucide.createIcons();</script>
  <script src="/re.source/public/js/cadastro.js?v=2"></script>
</body>
</html>
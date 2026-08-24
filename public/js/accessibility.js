(() => {
  'use strict';
  const root = document.documentElement;
  const storeKey = 'resource-accessibility-v2';
  const defaults = { language:'pt-BR', font:'normal', colorVision:'default', contrast:false, readable:false, lineSpacing:false, highlightLinks:false, reduceMotion:false };
  let prefs = defaults;
  try { prefs = { ...defaults, ...JSON.parse(localStorage.getItem(storeKey) || '{}') }; } catch (_) {}

  const dictionary = {
    en: { accessibility:'Accessibility', accessibility_options:'Accessibility options', site_language:'Interface language', text_size:'Text size', color_vision:'Color perception', high_contrast:'High contrast', readable_font:'Highly readable font', line_spacing:'Increase line spacing', highlight_links:'Underline and highlight links', reduce_motion:'Reduce motion', open_guide:'Open site guide', reset_preferences:'Reset preferences', guide_title:'How to use Re.Source', guide_intro:'Choose what you want to do. You can return to this guide from the accessibility button.', find_materials:'Find materials', find_materials_desc:'Search by category, location and availability.', publish_material:'Publish material', publish_material_desc:'List waste or inputs for reuse.', negotiate:'Negotiate safely', negotiate_desc:'Track proposals and messages in one place.', impact:'View your impact', impact_desc:'See environmental indicators and ESG reports.', help_center:'Help center', help_center_desc:'Find answers and support channels.', close_guide:'Close guide',
      'Início':'Home','Materiais':'Materials','Sobre nós':'About us','Ajuda':'Help','Entrar':'Sign in','Criar conta':'Create account','Continuar':'Continue','Voltar':'Back','Entrar agora':'Sign in now','Buscar':'Search','Oportunidades':'Opportunities','Meus materiais':'My materials','Negociações':'Negotiations','Impacto':'Impact','Minha conta':'My account','Sair':'Sign out','Configurações':'Settings','Central de Ajuda':'Help center',
      '← Voltar ao site':'← Back to site','Já tenho conta':'I already have an account','Somente empresas verificadas':'Verified companies only','Conecte resíduos a quem realmente precisa.':'Connect waste with those who really need it.','Junte-se a mais de 12 000 empresas e cooperativas que já transformam descarte em oportunidade sustentável.':'Join companies and cooperatives already turning disposal into sustainable opportunities.','Zero burocracia':'No red tape','Anuncie ou encontre resíduos em minutos':'List or find waste in minutes','Empresa verificada':'Verified company','Seu CNPJ validado automaticamente':'Your company ID is validated automatically','Relatório ESG gratuito':'Free ESG report','Métricas de impacto prontas para auditorias':'Impact metrics ready for audits','Sobre você':'About you','Empresa':'Company','Acesso':'Access','Campos obrigatórios':'Required fields','Fale sobre você':'Tell us about yourself','Vamos personalizar sua experiência na plataforma.':'Let us personalize your platform experience.','NOME':'FIRST NAME','SOBRENOME':'LAST NAME','E-MAIL CORPORATIVO':'BUSINESS EMAIL','CARGO':'JOB TITLE','TELEFONE':'PHONE','PERFIL DA CONTA':'ACCOUNT TYPE','Selecione seu perfil':'Select your profile','Já tem uma conta?':'Already have an account?','Dados da empresa':'Company details','Seu CNPJ será validado automaticamente.':'Your company ID will be validated automatically.','RAZÃO SOCIAL':'LEGAL NAME','NOME FANTASIA':'TRADING NAME','NÚMERO':'NUMBER','ENDEREÇO (RUA/AV)':'ADDRESS (STREET/AVENUE)','BAIRRO':'DISTRICT','COMPLEMENTO':'ADDRESS DETAILS','ESTADO':'STATE','Selecione':'Select','CIDADE':'CITY','SEGMENTO DE RESÍDUOS':'WASTE SEGMENT','Crie seu acesso':'Create your access','Escolha uma senha segura para proteger sua conta.':'Choose a secure password to protect your account.','SENHA':'PASSWORD','CONFIRMAR SENHA':'CONFIRM PASSWORD','Criar minha conta':'Create my account','Política de Privacidade':'Privacy Policy','Termos de Uso':'Terms of Use','Todos os direitos reservados':'All rights reserved' },
    es: { accessibility:'Accesibilidad', accessibility_options:'Opciones de accesibilidad', site_language:'Idioma de la interfaz', text_size:'Tamaño del texto', color_vision:'Percepción del color', high_contrast:'Alto contraste', readable_font:'Fuente de alta legibilidad', line_spacing:'Aumentar el espacio entre líneas', highlight_links:'Subrayar y resaltar enlaces', reduce_motion:'Reducir animaciones', open_guide:'Abrir guía del sitio', reset_preferences:'Restaurar preferencias', guide_title:'Cómo usar Re.Source', guide_intro:'Elige lo que quieres hacer. Puedes volver a esta guía desde el botón de accesibilidad.', find_materials:'Encontrar materiales', find_materials_desc:'Busca por categoría, ubicación y disponibilidad.', publish_material:'Publicar material', publish_material_desc:'Registra un residuo o insumo para reutilización.', negotiate:'Negociar con seguridad', negotiate_desc:'Sigue propuestas y mensajes en un solo lugar.', impact:'Ver tu impacto', impact_desc:'Consulta indicadores ambientales e informes ESG.', help_center:'Centro de ayuda', help_center_desc:'Encuentra respuestas y canales de soporte.', close_guide:'Cerrar guía',
      'Início':'Inicio','Materiais':'Materiales','Sobre nós':'Sobre nosotros','Ajuda':'Ayuda','Entrar':'Ingresar','Criar conta':'Crear cuenta','Continuar':'Continuar','Voltar':'Volver','Entrar agora':'Ingresar ahora','Buscar':'Buscar','Oportunidades':'Oportunidades','Meus materiais':'Mis materiales','Negociações':'Negociaciones','Impacto':'Impacto','Minha conta':'Mi cuenta','Sair':'Salir','Configurações':'Configuración','Central de Ajuda':'Centro de ayuda',
      '← Voltar ao site':'← Volver al sitio','Já tenho conta':'Ya tengo una cuenta','Somente empresas verificadas':'Solo empresas verificadas','Conecte resíduos a quem realmente precisa.':'Conecta residuos con quienes realmente los necesitan.','Junte-se a mais de 12 000 empresas e cooperativas que já transformam descarte em oportunidade sustentável.':'Únete a empresas y cooperativas que transforman descartes en oportunidades sostenibles.','Zero burocracia':'Sin burocracia','Anuncie ou encontre resíduos em minutos':'Publica o encuentra residuos en minutos','Empresa verificada':'Empresa verificada','Seu CNPJ validado automaticamente':'Tu identificación empresarial se valida automáticamente','Relatório ESG gratuito':'Informe ESG gratuito','Métricas de impacto prontas para auditorias':'Métricas de impacto listas para auditorías','Sobre você':'Sobre ti','Empresa':'Empresa','Acesso':'Acceso','Campos obrigatórios':'Campos obligatorios','Fale sobre você':'Cuéntanos sobre ti','Vamos personalizar sua experiência na plataforma.':'Personalizaremos tu experiencia en la plataforma.','NOME':'NOMBRE','SOBRENOME':'APELLIDO','E-MAIL CORPORATIVO':'CORREO CORPORATIVO','CARGO':'CARGO','TELEFONE':'TELÉFONO','PERFIL DA CONTA':'TIPO DE CUENTA','Selecione seu perfil':'Selecciona tu perfil','Já tem uma conta?':'¿Ya tienes una cuenta?','Dados da empresa':'Datos de la empresa','Seu CNPJ será validado automaticamente.':'Tu identificación empresarial se validará automáticamente.','RAZÃO SOCIAL':'RAZÓN SOCIAL','NOME FANTASIA':'NOMBRE COMERCIAL','NÚMERO':'NÚMERO','ENDEREÇO (RUA/AV)':'DIRECCIÓN (CALLE/AV.)','BAIRRO':'BARRIO','COMPLEMENTO':'COMPLEMENTO','ESTADO':'ESTADO','Selecione':'Selecciona','CIDADE':'CIUDAD','SEGMENTO DE RESÍDUOS':'SEGMENTO DE RESIDUOS','Crie seu acesso':'Crea tu acceso','Escolha uma senha segura para proteger sua conta.':'Elige una contraseña segura para proteger tu cuenta.','SENHA':'CONTRASEÑA','CONFIRMAR SENHA':'CONFIRMAR CONTRASEÑA','Criar minha conta':'Crear mi cuenta','Política de Privacidade':'Política de privacidad','Termos de Uso':'Términos de uso','Todos os direitos reservados':'Todos los derechos reservados' }
  };
  const original = new WeakMap();
  function translate(lang) {
    root.lang = lang;
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.dataset.i18n;
      if (!original.has(el)) original.set(el, el.textContent);
      el.textContent = lang === 'pt-BR' ? original.get(el) : (dictionary[lang]?.[key] || original.get(el));
    });
    const map = dictionary[lang] || {};
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
      acceptNode(node) {
        const tag = node.parentElement?.tagName;
        if (!node.nodeValue.trim() || ['SCRIPT','STYLE','TEXTAREA'].includes(tag) || node.parentElement?.closest('[data-i18n]')) return NodeFilter.FILTER_REJECT;
        return NodeFilter.FILTER_ACCEPT;
      }
    });
    const nodes=[]; while(walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(node => {
      if (!original.has(node)) original.set(node, node.nodeValue);
      const raw=original.get(node), source=raw.trim(), translated=lang === 'pt-BR' ? source : (map[source] || source);
      node.nodeValue = raw.replace(source, translated);
    });
    document.querySelectorAll('input[placeholder], textarea[placeholder]').forEach(el => {
      if (!el.dataset.originalPlaceholder) el.dataset.originalPlaceholder=el.placeholder;
      const source=el.dataset.originalPlaceholder; el.placeholder=lang === 'pt-BR' ? source : (map[source] || source);
    });
  }
  function apply(save = true) {
    root.dataset.a11yFont = prefs.font;
    root.dataset.colorVision = prefs.colorVision;
    root.dataset.contrast = String(prefs.contrast);
    root.dataset.readable = String(prefs.readable);
    root.dataset.lineSpacing = String(prefs.lineSpacing);
    root.dataset.highlightLinks = String(prefs.highlightLinks);
    root.dataset.reduceMotion = String(prefs.reduceMotion);
    translate(prefs.language);
    document.querySelectorAll('[data-setting]').forEach(b => b.setAttribute('aria-pressed', String(!!prefs[b.dataset.setting])));
    document.querySelectorAll('[data-font]').forEach(b => b.setAttribute('aria-pressed', String(prefs.font === b.dataset.font)));
    const lang = document.getElementById('a11yLanguage'); if (lang) lang.value = prefs.language;
    const vision = document.getElementById('a11yColorVision'); if (vision) vision.value = prefs.colorVision;
    if (save) { try { localStorage.setItem(storeKey, JSON.stringify(prefs)); } catch (_) {} }
  }
  function announce(msg) { const live = document.getElementById('a11yLive'); if (live) live.textContent = msg; }
  function init() {
    if (!document.querySelector('.skip-link')) {
      const main = document.querySelector('main'); if (main && !main.id) main.id = 'main-content';
      if (main) { main.tabIndex = -1; const skip = document.createElement('a'); skip.className='skip-link'; skip.href='#'+main.id; skip.textContent='Pular para o conteúdo principal'; document.body.prepend(skip); }
    }
    const launcher=document.getElementById('a11yLauncher'), panel=document.getElementById('a11yPanel'), guide=document.getElementById('siteGuide');
    if (!launcher || !panel || !guide) return;
    root.dataset.a11yReady = 'true';
    const closePanel=()=>{ panel.hidden=true; launcher.setAttribute('aria-expanded','false'); launcher.focus(); };
    launcher?.addEventListener('click',()=>{ const opening=panel.hidden; panel.hidden=!opening; launcher.setAttribute('aria-expanded',String(opening)); if(opening) panel.querySelector('button,select')?.focus(); });
    panel?.querySelector('.a11y-close')?.addEventListener('click',closePanel);
    document.getElementById('a11yLanguage')?.addEventListener('change',e=>{prefs.language=e.target.value;apply();announce('Idioma atualizado.');});
    document.getElementById('a11yColorVision')?.addEventListener('change',e=>{prefs.colorVision=e.target.value;apply();announce('Modo de cores atualizado.');});
    document.querySelectorAll('[data-font]').forEach(b=>b.addEventListener('click',()=>{prefs.font=b.dataset.font;apply();announce('Tamanho do texto atualizado.');}));
    document.querySelectorAll('[data-setting]').forEach(b=>b.addEventListener('click',()=>{prefs[b.dataset.setting]=!prefs[b.dataset.setting];apply();announce('Preferência atualizada.');}));
    document.getElementById('resetA11y')?.addEventListener('click',()=>{prefs={...defaults};apply();announce('Preferências restauradas.');});
    document.getElementById('openSiteGuide')?.addEventListener('click',()=>{panel.hidden=true;launcher.setAttribute('aria-expanded','false');guide.hidden=false;guide.querySelector('a,button')?.focus();});
    const closeGuide=()=>{guide.hidden=true;launcher.focus();}; guide?.querySelector('.guide-close')?.addEventListener('click',closeGuide);
    guide?.addEventListener('click',e=>{if(e.target===guide)closeGuide();});
    document.addEventListener('keydown',e=>{
      if(e.key==='Escape'){if(!guide?.hidden)closeGuide();else if(!panel?.hidden)closePanel();}
      if(e.key==='Tab' && guide && !guide.hidden){
        const focusable=[...guide.querySelectorAll('a[href],button:not([disabled]),select,input,[tabindex]:not([tabindex="-1"])')].filter(el=>el.offsetParent!==null);
        if(!focusable.length)return; const first=focusable[0],last=focusable[focusable.length-1];
        if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}
        else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}
      }
    });
    apply(false);
    const initVLibras=()=>{ if(window.VLibras && !window.__resourceVLibras){ window.__resourceVLibras=true; new window.VLibras.Widget('https://vlibras.gov.br/app'); } };
    initVLibras();
    window.addEventListener('load',initVLibras,{once:true});
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',init); else init();
})();

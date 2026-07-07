<?php
// routes/web.php — Mapa de todas as rotas da aplicação

return [

    // ── Raiz ──────────────────────────────────────────────
    '/' => [
        'action' => ['HomeController', 'index'],
    ],

    '/sobre' => [
        'action' => ['BaseController', 'sobre'],
    ],

    '/contato' => [
        'action' => ['BaseController', 'contato'],
    ],

    '/termos' => [
        'action' => ['BaseController', 'termos'],
    ],

    '/privacidade' => [
        'action' => ['BaseController', 'privacidade'],
    ],

    // ── Auth (páginas) ────────────────────────────────────
    '/login' => [
        'action' => ['AuthController', 'login'],
    ],

    '/cadastro' => [
        'action' => ['AuthController', 'cadastro'],
    ],

    '/pendente' => [
        'action' => ['AuthController', 'pendente'],
    ],

    '/aguardando-aprovacao' => [
        'action'     => ['AuthController', 'aguardandoAprovacao'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/cadastro/reenviar-analise' => [
        'action'     => ['AuthController', 'reenviarAnalise'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/reset' => [
        'action' => ['AuthController', 'showReset'],
    ],

    '/logout' => [
        'action' => ['AuthController', 'logout'],
    ],

    // ── Process (todas as ações POST via fetch/form) ──────
    // Mantém o roteamento interno por $_GET['action']
    '/process' => [
        'action' => ['AuthController', 'process'],
    ],

    // ── Utilitário CNPJ (proxy BrasilAPI) ─────────────────
    '/cnpj' => [
        'action' => ['BaseController', 'cnpj'],
    ],

    // ── Dashboard usuário comum ───────────────────────────
    '/base' => [
        'action'     => ['BaseController', 'index'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    // ── Busca ─────────────────────────────────────────────
    '/busca' => [
        'action' => ['SearchController', 'index'],
    ],

    '/anuncio' => [
        'action' => ['ListingController', 'showDetail'],
    ],

    // ── Anúncios (área do usuário logado) ─────────────────
    // Lista os anúncios da própria empresa (era meusAnuncios.php)
    '/meus-anuncios' => [
        'action'     => ['ListingController', 'showMeus'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    // Form de criação de anúncio (era criarResiduo.php / novoAnuncio.php)
    '/anuncios/novo' => [
        'action'     => ['ListingController', 'showCreate'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    // Processa o POST de criação (era process create)
    '/anuncios/novo/processar' => [
        'action'     => ['ListingController', 'processCreate'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    // Form de edição de anúncio — usa ?id= (era editarResiduo.php)
    '/anuncios/editar' => [
        'action'     => ['ListingController', 'showEdit'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    // Processa o POST de edição
    '/anuncios/editar/processar' => [
        'action'     => ['ListingController', 'processEdit'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    // Exclusão de anúncio — usa ?id= (era deletar)
    '/anuncios/excluir' => [
        'action'     => ['ListingController', 'processDelete'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    // ── Conta / Configurações usuário ─────────────────────
    '/conta' => [
        'action'     => ['BaseController', 'conta'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/conta/atualizar' => [
        'action'     => ['BaseController', 'atualizarConta'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/conta/excluir' => [
        'action'     => ['BaseController', 'excluirConta'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/configuracoes' => [
        'action'     => ['BaseController', 'configuracoes'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/configuracoes/salvar' => [
        'action'     => ['BaseController', 'salvarPreferencias'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/estatisticas' => [
        'action'     => ['EstatisticasController', 'index'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/estatisticas/saque' => [
        'action'     => ['EstatisticasController', 'saque'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/estatisticas/processar-saque' => [
        'action'     => ['EstatisticasController', 'processarSaque'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/negociacoes' => [
        'action'     => ['ChatController', 'index'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/conversas' => [
        'action'     => ['ChatController', 'index'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/conversas/abrir' => [
        'action'     => ['ChatController', 'show'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/conversas/iniciar' => [
        'action'     => ['NegotiationController', 'start'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/conversas/enviar' => [
        'action'     => ['ChatController', 'send'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/conversas/mensagens' => [
        'action'     => ['ChatController', 'messages'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/notificacoes' => [
        'action'     => ['BaseController', 'notifications'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/notificacoes/marcar-lidas' => [
        'action'     => ['BaseController', 'markNotificationsRead'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/conversas/nao-lidas' => [
        'action'     => ['ChatController', 'unreadCount'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/conversas/lista' => [
        'action'     => ['ChatController', 'conversationList'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/negociacoes/proposta' => [
        'action'     => ['ProposalController', 'save'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/negociacoes/proposta/aceitar' => [
        'action'     => ['ProposalController', 'accept'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/negociacoes/proposta/recusar' => [
        'action'     => ['ProposalController', 'refuse'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/negociacoes/cancelar' => [
        'action'     => ['ProposalController', 'cancel'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/negociacoes/reabrir' => [
        'action'     => ['ProposalController', 'reopen'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/logistica' => [
        'action'     => ['DeliveryController', 'history'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/entregas' => [
        'action'     => ['DeliveryController', 'history'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/frete' => [
        'action'     => ['FreightController', 'quote'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/frete/contratar' => [
        'action'     => ['FreightController', 'contract'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/frete/acompanhar' => [
        'action'     => ['FreightController', 'show'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/frete/iniciar' => [
        'action'     => ['FreightController', 'startShipping'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/frete/codigo' => [
        'action'     => ['FreightController', 'generateDeliveryCode'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/impacto' => [
        'action'     => ['BaseController', 'impacto'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    '/suporte' => [
        'action'     => ['BaseController', 'suporte'],
        'middleware' => [['UserAuth', 'required'], ['ApprovedCompany', 'required']],
    ],

    // ── Admin ─────────────────────────────────────────────
    '/admin' => [
        'action'     => ['AdminController', 'dashboard'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/empresas' => [
        'action'     => ['AdminController', 'empresas'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/empresas/aprovar' => [
        'action'     => ['AdminController', 'aprovarEmpresa'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/empresas/solicitar-correcao' => [
        'action'     => ['AdminController', 'solicitarCorrecaoEmpresa'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/empresas/rejeitar' => [
        'action'     => ['AdminController', 'rejeitarEmpresa'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/empresas/suspender' => [
        'action'     => ['AdminController', 'suspenderEmpresa'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/empresas/reativar' => [
        'action'     => ['AdminController', 'reativarEmpresa'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/anuncios' => [
        'action'     => ['AdminController', 'anuncios'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/anuncios/ativar' => [
        'action'     => ['AdminController', 'ativarAnuncio'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/anuncios/pausar' => [
        'action'     => ['AdminController', 'pausarAnuncio'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/negociacoes' => [
        'action'     => ['AdminController', 'negociacoes'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/impacto' => [
        'action'     => ['AdminController', 'impacto'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/logistica' => [
        'action'     => ['DeliveryController', 'portal'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/saques' => [
        'action'     => ['AdminFinanceController', 'index'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/saques/aprovar' => [
        'action'     => ['AdminFinanceController', 'approve'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/saques/recusar' => [
        'action'     => ['AdminFinanceController', 'reject'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/saques/exportar' => [
        'action'     => ['AdminFinanceController', 'exportCsv'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/entregador' => [
        'action'     => ['DeliveryController', 'portal'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/entregador/validar' => [
        'action'     => ['DeliveryController', 'validate'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/suporte' => [
        'action'     => ['AdminController', 'suporte'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/configuracoes' => [
        'action'     => ['AdminController', 'configuracoes_admin'],
        'middleware' => [['AdminAuth', 'required']],
    ],

    '/admin/configuracoes/salvar' => [
        'action'     => ['AdminController', 'salvarConfiguracoesAdmin'],
        'middleware' => [['AdminAuth', 'required']],
    ],

];

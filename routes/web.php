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
        'middleware' => [['UserAuth', 'required']],
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
        'middleware' => [['UserAuth', 'required']],
    ],

    // Form de criação de anúncio (era criarResiduo.php / novoAnuncio.php)
    '/anuncios/novo' => [
        'action'     => ['ListingController', 'showCreate'],
        'middleware' => [['UserAuth', 'required']],
    ],

    // Processa o POST de criação (era process create)
    '/anuncios/novo/processar' => [
        'action'     => ['ListingController', 'processCreate'],
        'middleware' => [['UserAuth', 'required']],
    ],

    // Form de edição de anúncio — usa ?id= (era editarResiduo.php)
    '/anuncios/editar' => [
        'action'     => ['ListingController', 'showEdit'],
        'middleware' => [['UserAuth', 'required']],
    ],

    // Processa o POST de edição
    '/anuncios/editar/processar' => [
        'action'     => ['ListingController', 'processEdit'],
        'middleware' => [['UserAuth', 'required']],
    ],

    // Exclusão de anúncio — usa ?id= (era deletar)
    '/anuncios/excluir' => [
        'action'     => ['ListingController', 'processDelete'],
        'middleware' => [['UserAuth', 'required']],
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
        'middleware' => [['UserAuth', 'required']],
    ],

    '/estatisticas/saque' => [
        'action'     => ['EstatisticasController', 'saque'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/estatisticas/processar-saque' => [
        'action'     => ['EstatisticasController', 'processarSaque'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/negociacoes' => [
        'action'     => ['BaseController', 'negociacoes'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/logistica' => [
        'action'     => ['BaseController', 'logistica'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/impacto' => [
        'action'     => ['BaseController', 'impacto'],
        'middleware' => [['UserAuth', 'required']],
    ],

    '/suporte' => [
        'action'     => ['BaseController', 'suporte'],
        'middleware' => [['UserAuth', 'required']],
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

    '/admin/anuncios' => [
        'action'     => ['AdminController', 'anuncios'],
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
        'action'     => ['AdminController', 'logistica'],
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

];

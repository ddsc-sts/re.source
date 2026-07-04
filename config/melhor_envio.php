<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ambiente
    |--------------------------------------------------------------------------
    |
    | true  = Sandbox (homologação)
    | false = Produção
    |
    */

    'sandbox' => filter_var(
        env('MELHOR_ENVIO_SANDBOX', true),
        FILTER_VALIDATE_BOOLEAN
    ),

    /*
    |--------------------------------------------------------------------------
    | OAuth2
    |--------------------------------------------------------------------------
    */

    'client_id' => env('MELHOR_ENVIO_CLIENT_ID', ''),

    'client_secret' => env('MELHOR_ENVIO_CLIENT_SECRET', ''),

    'redirect_uri' => env('MELHOR_ENVIO_REDIRECT_URI', ''),

    /*
    |--------------------------------------------------------------------------
    | Tokens
    |--------------------------------------------------------------------------
    */

    'access_token' => env('MELHOR_ENVIO_ACCESS_TOKEN', ''),

    'refresh_token' => env('MELHOR_ENVIO_REFRESH_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | User-Agent obrigatório
    |--------------------------------------------------------------------------
    |
    | O Melhor Envio exige um User-Agent identificando a aplicação.
    |
    */

    'user_agent' => env(
        'MELHOR_ENVIO_USER_AGENT',
        'RE.SOURCE MVP (contato@resource.com.br)'
    ),

    /*
    |--------------------------------------------------------------------------
    | Timeout das requisições
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('MELHOR_ENVIO_TIMEOUT', 30),

];
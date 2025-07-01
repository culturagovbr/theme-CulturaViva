<?php 
use \MapasCulturais\i;

return [
    /* Primeira linha do logo configurável */
    'logo.title' => 'Rede',

    /* Segunda linha do logo configurável */
    'logo.subtitle' => 'Cultura Viva',

    /* 
    Define o nome do asset da imagem da logo do site - Substituirá a logo padrão

    ex: `img/meu-mapa-logo.jpg` (pasta assets/img/meu-mapa-logo.jpg do tema) 
    */
    'logo.image' => 'img/logo.png',

    /* Esconde o título e subtitulo */
    'logo.hideLabel' => true,
    "text:home-header.description" => i::__("TEXTO SOBRE A FIGURA DO TOPO"),    
    "text:home-entities.opportunities" => i::__("TESTES TESTE - Estamos no Cultura Viva."),

    /* campos a serem desabilitado na atualização da inscrição */
    'registrationEdit.disableFields' => [
        'field_560',
    ],

    /* Plano de Metas */
    'workplan' => [
        'enable_workplan_tutorial' => false
    ],
    # CONFIGURAÇÕES ESPECÍFICAS PARA O THEME CULTURA VIVA
    'auth.provider' => '\MultipleLocalAuth\Provider',
    'auth.config' => [
        'salt' => env('AUTH_SALT', 'SECURITY_SALT'),
        'wizard' => env('AUTH_WIZARD_ENABLED', false),
        'timeout' => '24 hours',
        'strategies' => [
            'Facebook' => [
                'app_id' => env('AUTH_FACEBOOK_APP_ID', null),
                'app_secret' => env('AUTH_FACEBOOK_APP_SECRET', null),
                'scope' => env('AUTH_FACEBOOK_SCOPE', 'email'),
            ],
            'Google' => [
                'client_id' => env('AUTH_GOOGLE_CLIENT_ID', null),
                'client_secret' => env('AUTH_GOOGLE_CLIENT_SECRET', null),
                'redirect_uri' => env('BASE_URL', '') . 'autenticacao/google/oauth2callback',
                'scope' => env('AUTH_GOOGLE_SCOPE', 'email'),
            ],

            'LinkedIn' => [
                'api_key' => env('AUTH_LINKEDIN_API_KEY', null),
                'secret_key' => env('AUTH_LINKEDIN_SECRET_KEY', null),
                'redirect_uri' => env('BASE_URL', '') . 'autenticacao/linkedin/oauth2callback',
                'scope' => env('AUTH_LINKEDIN_SCOPE', 'r_emailaddress')
            ],

            'Twitter' => [
                'app_id' => env('AUTH_TWITTER_APP_ID', null),
                'app_secret' => env('AUTH_TWITTER_APP_SECRET', null),
            ],
            'govbr' => [
                'visible' => env('AUTH_GOV_BR_VISIBLE', false),
                'response_type' => env('AUTH_GOV_BR_RESPONSE_TYPE', 'code'),

                'client_id' => env('RCV_AUTH_GOV_BR_CLIENT_ID', null),
                'client_secret' => env('RCV_AUTH_GOV_BR_SECRET', null),
                'redirect_uri' => env('RCV_AUTH_GOV_BR_REDIRECT_URI', null),

                'scope' => env('AUTH_GOV_BR_SCOPE', null),
                'auth_endpoint' => env('AUTH_GOV_BR_ENDPOINT', null),
                'token_endpoint' => env('AUTH_GOV_BR_TOKEN_ENDPOINT', null),
                'nonce' => env('AUTH_GOV_BR_NONCE', null),
                'code_verifier' => env('AUTH_GOV_BR_CODE_VERIFIER', null),
                'code_challenge' => env('AUTH_GOV_BR_CHALLENGE', null),
                'code_challenge_method' => env('AUTH_GOV_BR_CHALLENGE_METHOD', null),
                'userinfo_endpoint' => env('AUTH_GOV_BR_USERINFO_ENDPOINT', null),
                'state_salt' => env('AUTH_GOV_BR_STATE_SALT', null),
                'applySealId' => env('AUTH_GOV_BR_APPLY_SEAL_ID', null),
                'menssagem_authenticated' => env('AUTH_GOV_BR_MENSSAGEM_AUTHENTICATED', 'Usuário já se autenticou pelo GovBr'),

                'dic_agent_fields_update' => json_decode(env('AUTH_GOV_BR_DICT_AGENT_FIELDS_UPDATE', '{}'), true),
                'post_logout_redirect_uri' => env('post_logout_redirect_uri', null),
                'url_logout' => env('url_logout', 'https://sso.staging.acesso.gov.br/logout'),
            ]
        ]
    ]
];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Autorizadores de Rotulación de Fachadas
    |--------------------------------------------------------------------------
    |
    | Catálogo de personas que pueden autorizar una solicitud de rotulación.
    | El nombre y correo se resuelven aquí en el servidor a partir de la
    | clave (authorizer_key) que el formulario envía; nunca se confía en un
    | nombre/correo enviado directamente desde el navegador.
    |
    */

    'authorizers' => [
        'adrian_preciado' => [
            'name' => 'Adrian Preciado',
            'email' => env('FACHADAS_AUTHORIZER_ADRIAN_EMAIL'),
        ],
        'pastor_martinez' => [
            'name' => 'Pastor Martínez',
            'email' => env('FACHADAS_AUTHORIZER_PASTOR_EMAIL'),
        ],
        'mario_cervera' => [
            'name' => 'Mario Cervera',
            'email' => env('FACHADAS_AUTHORIZER_MARIO_EMAIL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mercadotecnia
    |--------------------------------------------------------------------------
    |
    | Recibe copia (Cc) del correo de autorización, además del autorizador
    | seleccionado. Un solo correo, no una lista.
    |
    */

    'marketing_email' => env('FACHADAS_MARKETING_EMAIL'),

];

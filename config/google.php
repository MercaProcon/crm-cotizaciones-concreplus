<?php

return [
    /*
    |----------------------------------------------------------------------------
    | Google application name
    |----------------------------------------------------------------------------
    */
    'application_name' => env('GOOGLE_APPLICATION_NAME', ''),

    /*
    |----------------------------------------------------------------------------
    | Google OAuth 2.0 access
    |----------------------------------------------------------------------------
    |
    | Keys for OAuth 2.0 access, see the API console at
    | https://developers.google.com/console
    |
    */
    'client_id' => env('GOOGLE_CLIENT_ID', ''),
    'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
    'redirect_uri' => env('GOOGLE_REDIRECT', ''),

    /*
    | El CRM solo lee cotizaciones desde Google Sheets. Se usa el scope de
    | solo lectura a propósito: aunque hubiera un bug en el código, la API
    | de Google rechazaría cualquier intento de escritura con este scope.
    */
    'scopes' => [\Google\Service\Sheets::SPREADSHEETS_READONLY],
    'access_type' => 'offline',
    'prompt' => 'consent select_account',

    /*
    |----------------------------------------------------------------------------
    | Google developer key
    |----------------------------------------------------------------------------
    |
    | Simple API access key, also from the API console. Ensure you get
    | a Server key, and not a Browser key.
    |
    */
    'developer_key' => env('GOOGLE_DEVELOPER_KEY', ''),

    /*
    |----------------------------------------------------------------------------
    | Google service account
    |----------------------------------------------------------------------------
    |
    | Set the credentials JSON's location to use assert credentials, otherwise
    | app engine or compute engine will be used.
    |
    */
    'service' => [
        /*
        | El CRM siempre usa cuenta de servicio (sin login de usuario), así
        | que esto queda fijo en true en vez de depender de una variable
        | de entorno adicional.
        */
        'enable' => true,

        /*
         * Ruta al JSON de la cuenta de servicio. Usamos la misma variable
         * GOOGLE_SHEETS_CREDENTIALS_PATH definida en .env para no duplicar
         * el dato en dos nombres de variable distintos.
         */
        'file' => env('GOOGLE_SHEETS_CREDENTIALS_PATH')
            ? base_path(env('GOOGLE_SHEETS_CREDENTIALS_PATH'))
            : '',
    ],

    /*
    |----------------------------------------------------------------------------
    | Additional config for the Google Client
    |----------------------------------------------------------------------------
    |
    | Set any additional config variables supported by the Google Client
    | Details can be found here:
    | https://github.com/google/google-api-php-client/blob/master/src/Google/Client.php
    |
    | NOTE: If client id is specified here, it will get over written by the one above.
    |
    */
    'config' => [],
];

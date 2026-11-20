<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reporte Semanal Comercial
    |--------------------------------------------------------------------------
    |
    | Destinatarios del reporte semanal automático para Dirección Comercial.
    | Separados por coma en el .env. Si 'emails' queda vacío, el comando no
    | envía nada (registra un warning en laravel.log y no falla el sistema).
    |
    */

    'weekly_commercial' => [
        'emails' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('COMMERCIAL_WEEKLY_REPORT_EMAILS', ''))
        ))),

        'cc' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('COMMERCIAL_WEEKLY_REPORT_CC', ''))
        ))),

        'recipient_name' => trim((string) env('COMMERCIAL_WEEKLY_REPORT_RECIPIENT_NAME', '')),
    ],

];

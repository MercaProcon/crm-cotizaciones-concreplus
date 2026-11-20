<?php

return [
    /*
    |----------------------------------------------------------------------------
    | Google Sheets - Cotizaciones
    |----------------------------------------------------------------------------
    |
    | ID de la hoja y nombres exactos de las pestañas operativas que lee
    | sheets:import. Se leen aquí (y no con env() directo dentro del comando)
    | para que sigan funcionando correctamente si en producción se corre
    | php artisan config:cache.
    |
    */
    'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),

    'tabs' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('GOOGLE_SHEETS_TABS', ''))
    ))),
];

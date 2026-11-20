<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fase 8 (Concreplus): única tarea programada por ahora. Importa solo la
// pestaña "Cotizaciones" (sin zonas) y, para cada fila creada/actualizada,
// intenta autoasignar a la vendedora de CRM_DEFAULT_VENDEDORA_EMAIL (ver
// AssignmentService::autoAssignToDefaultVendor()). Log propio, separado del
// de PROCON.
$sheetsImport = Schedule::command('sheets:import --tab=Cotizaciones')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sheets-import-concreplus.log'));

// Fase 15: heartbeat externo (healthchecks.io). Si el cron deja de correr
// por completo -- el fallo real que tuvimos, 17 dias en silencio -- el
// servicio avisa por correo sin depender de que Hostinger detecte nada.
// Si la URL no esta configurada (ej. en local), simplemente no se registra.
if ($healthcheckUrl = config('services.healthchecks.sheets_import_url')) {
    $sheetsImport->pingOnSuccess($healthcheckUrl);
}

// Heredados de PROCON, deliberadamente INACTIVOS por ahora (Fase 8 solo
// autorizó el importador):
// - quotations:send-reminders: generaría de golpe recordatorios para las 52
//   cotizaciones ya asignadas a Inés (llevan asignadas desde Fase 7, todas
//   pasarían el umbral de "sin atender"). No se ha pedido activar
//   recordatorios todavía.
// - reports:send-weekly-commercial: reporte semanal, explícitamente pospuesto
//   ("no activar reportes semanales todavía").
// Reactivar cuando se autorice explícitamente, no antes.
//
// Schedule::command('quotations:send-reminders')->everyMinute();
//
// Schedule::command('reports:send-weekly-commercial')
//     ->weeklyOn(1, '08:00')
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/weekly-commercial-report.log'));

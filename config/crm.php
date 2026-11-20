<?php

return [
    /*
    |----------------------------------------------------------------------------
    | Corte operativo
    |----------------------------------------------------------------------------
    |
    | Fecha a partir de la cual las pantallas de operación diaria (Cotizaciones,
    | Panel de asignación, Duplicados, Dashboard, Reportes) muestran
    | cotizaciones. No borra ni oculta nada a nivel de base de datos: es un
    | filtro que aplica Quotation::scopeOperationalVisible(). El importador de
    | Google Sheets y el historial por teléfono no usan este filtro, así que
    | siempre ven el histórico completo sin importar este valor.
    |
    | Configurable por CRM_OPERATIONAL_START_DATE en .env. El valor por defecto
    | aquí (2026-02-01) es el que corresponde a Concreplus, cuyo histórico real
    | importado desde Google Sheets empieza en esa fecha.
    |
    */
    'operational_start_date' => env('CRM_OPERATIONAL_START_DATE', '2026-02-01'),

    /*
    |----------------------------------------------------------------------------
    | Autoasignación simple (Fase 7)
    |----------------------------------------------------------------------------
    |
    | Concreplus todavía no tiene un ciclo de asignación por vendedoras ni
    | zonas — mientras eso no se defina, toda cotización nueva sin asignación
    | vigente se asigna directo a esta vendedora (por correo, nunca por ID:
    | los IDs difieren entre entornos). Ver App\Services\AssignmentService.
    | Deliberadamente NO es un round-robin ni reutiliza nada de PROCON.
    |
    */
    'default_vendedora_email' => env('CRM_DEFAULT_VENDEDORA_EMAIL', 'IGonzalez@concreplus.com.mx'),
];

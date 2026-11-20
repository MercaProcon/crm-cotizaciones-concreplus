<?php

return [
    /*
    |----------------------------------------------------------------------------
    | Tiempos de recordatorio (minutos)
    |----------------------------------------------------------------------------
    |
    | Tiempo desde que se asigna una cotización (assignments.assigned_at) hasta
    | que se considera que necesita un recordatorio, si la vendedora asignada
    | actualmente no ha registrado ningún seguimiento desde esa asignación.
    |
    */
    'first_minutes' => (int) env('REMINDER_FIRST_MINUTES', 5),

    'second_minutes' => (int) env('REMINDER_SECOND_MINUTES', 15),

    'notify_coordinator_minutes' => (int) env('REMINDER_NOTIFY_COORDINATOR_MINUTES', 30),
];

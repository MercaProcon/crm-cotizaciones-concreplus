@echo off
REM Fase 9: dispara el scheduler de Laravel para el CRM de Concreplus.
REM Ruta de PHP absoluta a proposito (no depender del PATH cuando corre
REM sin sesion interactiva via Tarea Programada de Windows).
cd /d C:\laragon\www\crm-cotizaciones-concreplus
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan schedule:run >> storage\logs\scheduler-concreplus.log 2>&1

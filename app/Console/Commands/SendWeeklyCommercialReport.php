<?php

namespace App\Console\Commands;

use App\Mail\WeeklyCommercialReportMail;
use App\Models\WeeklyReportLog;
use App\Services\CommercialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('reports:send-weekly-commercial
    {--start= : Fecha de inicio del periodo (YYYY-MM-DD), opcional}
    {--end= : Fecha de fin del periodo (YYYY-MM-DD), opcional}
    {--force : Reenviar aunque ya exista un envío exitoso registrado para ese periodo}')]
#[Description('Genera el PDF y envía por correo el reporte semanal comercial a Dirección Comercial.')]
class SendWeeklyCommercialReport extends Command
{
    private const REPORT_TYPE = 'weekly_commercial';

    /**
     * Execute the console command.
     */
    public function handle(CommercialReportService $reports): int
    {
        [$periodStart, $periodEnd] = $this->resolvePeriod();

        if ($periodStart->greaterThan($periodEnd)) {
            $this->error('La fecha de inicio no puede ser posterior a la fecha de fin.');

            return self::FAILURE;
        }

        $existing = WeeklyReportLog::where('report_type', self::REPORT_TYPE)
            ->whereDate('period_start', $periodStart)
            ->whereDate('period_end', $periodEnd)
            ->where('status', WeeklyReportLog::STATUS_SENT)
            ->first();

        if ($existing && ! $this->option('force')) {
            $message = sprintf(
                'Ya existe un envío exitoso para el periodo %s a %s (enviado el %s). Usa --force para reenviar.',
                $periodStart->format('Y-m-d'),
                $periodEnd->format('Y-m-d'),
                $existing->sent_at?->format('Y-m-d H:i')
            );

            $this->warn($message);

            Log::warning('[reports:send-weekly-commercial] Envío omitido: ya existía un envío exitoso para este periodo.', [
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ]);

            return self::SUCCESS;
        }

        $emails = config('reports.weekly_commercial.emails', []);
        $cc = config('reports.weekly_commercial.cc', []);
        $recipientName = config('reports.weekly_commercial.recipient_name', '');

        $filters = [
            'start_date' => $periodStart->toDateString(),
            'end_date' => $periodEnd->toDateString(),
        ];

        $data = $reports->buildGeneralReportData($filters);
        $data['summary'] = array_merge($data['summary'], $reports->weeklySummaryExtras($data['byVendedora']));

        if (empty($emails)) {
            $this->warn('No hay destinatarios configurados en COMMERCIAL_WEEKLY_REPORT_EMAILS. No se envía ningún correo.');

            Log::warning('[reports:send-weekly-commercial] Sin destinatarios configurados; correo no enviado.', [
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'total_quotations' => $data['summary']['total'],
            ]);

            WeeklyReportLog::updateOrCreate(
                [
                    'report_type' => self::REPORT_TYPE,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                ],
                [
                    'recipients' => null,
                    'sent_at' => null,
                    'status' => WeeklyReportLog::STATUS_SKIPPED_NO_RECIPIENTS,
                    'error_message' => null,
                ]
            );

            return self::SUCCESS;
        }

        $recipientsForLog = implode(',', array_merge($emails, $cc));

        try {
            $alerts = $reports->weeklyAlerts($filters);
            $observations = $reports->observations($data['summary'], $data['byVendedora'], $data['byZone'], $data['byStatus']);

            $pdf = Pdf::loadView('reports.pdf.weekly-commercial', array_merge($data, [
                'alerts' => $alerts,
                'observations' => $observations,
                'logoDataUri' => $reports->logoDataUri(),
                'periodStart' => $periodStart,
                'periodEnd' => $periodEnd,
                'generatedAt' => now(),
            ]));

            $filename = 'reporte-semanal-'.$periodStart->format('Ymd').'-'.$periodEnd->format('Ymd').'.pdf';
            $storagePath = 'reports/weekly/'.$filename;
            Storage::disk('local')->put($storagePath, $pdf->output());
            $absolutePath = Storage::disk('local')->path($storagePath);

            Mail::to($emails)->send(new WeeklyCommercialReportMail(
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                summary: $data['summary'],
                pdfPath: $absolutePath,
                pdfFilename: $filename,
                ccAddresses: $cc,
                recipientName: $recipientName,
            ));

            WeeklyReportLog::updateOrCreate(
                [
                    'report_type' => self::REPORT_TYPE,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                ],
                [
                    'recipients' => $recipientsForLog,
                    'sent_at' => now(),
                    'status' => WeeklyReportLog::STATUS_SENT,
                    'error_message' => null,
                ]
            );

            $this->info(sprintf(
                'Reporte semanal enviado (%s a %s). Total cotizaciones: %d. PDF: %s',
                $periodStart->format('Y-m-d'),
                $periodEnd->format('Y-m-d'),
                $data['summary']['total'],
                $storagePath
            ));

            Log::info('[reports:send-weekly-commercial] Enviado correctamente.', [
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'recipients' => $emails,
                'cc' => $cc,
                'total_quotations' => $data['summary']['total'],
                'pdf_path' => $storagePath,
            ]);

            return self::SUCCESS;
        } catch (Throwable $e) {
            WeeklyReportLog::updateOrCreate(
                [
                    'report_type' => self::REPORT_TYPE,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                ],
                [
                    'recipients' => $recipientsForLog,
                    'sent_at' => null,
                    'status' => WeeklyReportLog::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                ]
            );

            $this->error('Error al generar/enviar el reporte semanal: '.$e->getMessage());

            Log::error('[reports:send-weekly-commercial] Error al generar/enviar el reporte.', [
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Sin --start/--end: semana anterior completa (lunes 00:00:00 a domingo
     * 23:59:59). Con ambos: el rango exacto pedido, para pruebas manuales.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(): array
    {
        $startOption = $this->option('start');
        $endOption = $this->option('end');

        if (filled($startOption) && filled($endOption)) {
            return [
                Carbon::parse($startOption)->startOfDay(),
                Carbon::parse($endOption)->endOfDay(),
            ];
        }

        $lastMonday = now()->subWeek()->startOfWeek(Carbon::MONDAY);
        $lastSunday = now()->subWeek()->endOfWeek(Carbon::SUNDAY);

        return [$lastMonday, $lastSunday];
    }
}

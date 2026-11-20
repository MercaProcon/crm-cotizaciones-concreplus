<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('Reporte semanal de cotizaciones Concreplus') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#003060; padding:20px 24px; text-align:center;">
                            <img src="{{ $message->embed(public_path('images/concreplus-logo-blanco.png')) }}" alt="Concreplus" style="height:40px; display:block; margin:0 auto 6px auto;">
                            <div style="color:#FDF1E1; font-size:12px; margin-top:2px;">{{ __('Reporte semanal de cotizaciones') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="font-size:14px; color:#333; margin:0 0 16px 0;">
                                @if (filled($recipientName ?? null))
                                    {{ __('Hola :name,', ['name' => $recipientName]) }}
                                @else
                                    {{ __('Hola,') }}
                                @endif
                            </p>
                            <p style="font-size:14px; color:#333; margin:0 0 16px 0;">
                                {{ __('Se adjunta el reporte semanal de cotizaciones Concreplus correspondiente al periodo :start al :end.', [
                                    'start' => $periodStart->format('d/m/Y'),
                                    'end' => $periodEnd->format('d/m/Y'),
                                ]) }}
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase;">{{ __('Total de cotizaciones') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#003060; font-weight:bold; text-align:right;">{{ $summary['total'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase; border-top:1px solid #eee;">{{ __('Ganadas') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#111; text-align:right; border-top:1px solid #eee;">{{ $summary['won'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase; border-top:1px solid #eee;">{{ __('Perdidas') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#111; text-align:right; border-top:1px solid #eee;">{{ $summary['lost'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase; border-top:1px solid #eee;">{{ __('Sin seguimiento') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#111; text-align:right; border-top:1px solid #eee;">{{ $summary['without_follow_up'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase; border-top:1px solid #eee;">{{ __('Conversión') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#111; text-align:right; border-top:1px solid #eee;">
                                        {{ $summary['conversion'] !== null ? $summary['conversion'].'%' : __('Sin datos') }}
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:12px; color:#888; margin:0;">
                                {{ __('El detalle completo (por vendedora y alertas) está en el PDF adjunto a este correo.') }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px; background-color:#f9fafb; border-top:1px solid #eee;">
                            <p style="font-size:11px; color:#999; margin:0; text-align:center;">
                                {{ __('Este correo fue generado automáticamente por el CRM Concreplus.') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

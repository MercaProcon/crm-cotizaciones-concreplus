<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('Cotización asignada') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#003060; padding:20px 24px; text-align:center;">
                            <img src="{{ $message->embed(public_path('images/concreplus-logo-blanco.png')) }}" alt="Concreplus" style="height:40px; display:block; margin:0 auto 6px auto;">
                            <div style="color:#FDF1E1; font-size:12px; margin-top:2px;">
                                {{ $isReassignment ? __('Cotización reasignada a ti') : __('Nueva cotización asignada') }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="font-size:14px; color:#333; margin:0 0 16px 0;">
                                {{ __('Hola :name,', ['name' => $assignment->assignedTo->name]) }}
                            </p>
                            <p style="font-size:14px; color:#333; margin:0 0 20px 0;">
                                {{ $isReassignment
                                    ? __('Se te reasignó la siguiente cotización. Favor de atenderla lo antes posible.')
                                    : __('Se registró una nueva cotización y quedó asignada a ti. Favor de atenderla lo antes posible.') }}
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px; border-collapse:collapse;">
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase; width:160px;">{{ __('Cliente') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#003060; font-weight:bold;">{{ $quotation->client_name ?: __('Sin nombre') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase;">{{ __('Teléfono') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#333;">{{ $quotation->phone_raw }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase; vertical-align:top;">{{ __('Solicitud') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#333;">{{ $quotation->description ?: __('Sin detalle') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase;">{{ __('Asignada por') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#333;">{{ $assignment->assignedBy->name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase;">{{ __('Hora de asignación') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#333;">{{ $assignment->assigned_at->format('d/m/Y h:i A') }}</td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background-color:#F08C21; border-radius:6px;">
                                        <a href="{{ route('my-quotations.index') }}" style="display:inline-block; padding:10px 20px; font-size:14px; color:#ffffff; text-decoration:none; font-weight:bold;">
                                            {{ __('Ver mis cotizaciones') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

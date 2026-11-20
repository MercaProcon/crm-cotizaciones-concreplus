<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('Solicitud de rotulación pendiente de autorización') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    <tr>
                        <td style="background-color:#00438A; padding:20px 24px;">
                            <span style="color:#ffffff; font-size:20px; font-weight:bold; letter-spacing:1px;">PROCON</span>
                            <div style="color:#E8EEF6; font-size:12px; margin-top:2px;">{{ __('Solicitud de Rotulación de Fachadas') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="font-size:14px; color:#333; margin:0 0 16px 0;">
                                {{ __('Hola') }} {{ $facadeRequest->authorizer_name }},
                            </p>
                            <p style="font-size:14px; color:#333; margin:0 0 16px 0;">
                                {{ __('Se te ha asignado como autorizador de la siguiente solicitud de rotulación de fachada. Por favor revisa los datos y el PDF adjunto.') }}
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase;">{{ __('Folio') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#00438A; font-weight:bold; text-align:right;">{{ $facadeRequest->folio }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase; border-top:1px solid #eee;">{{ __('Tienda') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#111; text-align:right; border-top:1px solid #eee;">{{ $facadeRequest->store_name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase; border-top:1px solid #eee;">{{ __('Cliente') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#111; text-align:right; border-top:1px solid #eee;">{{ $facadeRequest->client_name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:12px; color:#666; text-transform:uppercase; border-top:1px solid #eee;">{{ __('Agente') }}</td>
                                    <td style="padding:6px 0; font-size:14px; color:#111; text-align:right; border-top:1px solid #eee;">{{ $facadeRequest->agent_name }}</td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 20px auto;">
                                <tr>
                                    <td style="background-color:#007856; border-radius:6px;">
                                        <a href="{{ $authorizationUrl }}"
                                           style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                                            {{ __('Ver solicitud') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:12px; color:#888; margin:0;">
                                {{ __('También puedes revisar el PDF adjunto a este correo con todos los detalles de la solicitud.') }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 24px; background-color:#f9fafb; border-top:1px solid #eee;">
                            <p style="font-size:11px; color:#999; margin:0; text-align:center;">
                                {{ __('Este correo fue generado automáticamente por el CRM Cotizaciones PROCON.') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

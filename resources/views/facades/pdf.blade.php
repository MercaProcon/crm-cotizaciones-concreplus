<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ __('Solicitud de rotulación') }} {{ $facadeRequest->folio }}</title>
<style>
    @page { margin: 28px 32px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }

    .header { border-bottom: 3px solid #00438A; padding-bottom: 8px; margin-bottom: 14px; }
    .header .logo-img { height: 32px; }
    .header .subtitle { font-size: 10px; color: #555; margin-top: 4px; }
    .header table { width: 100%; }
    .header td { vertical-align: top; }
    .header .meta { text-align: right; font-size: 10px; color: #333; }
    .header .meta strong { color: #00438A; }

    h2.section {
        background-color: #E6F4EF;
        color: #00438A;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 4px 8px;
        margin: 14px 0 6px 0;
        border-left: 4px solid #007856;
    }

    table.fields { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    table.fields td { padding: 3px 6px; vertical-align: top; width: 50%; }
    table.fields td .label { color: #666; font-size: 9px; text-transform: uppercase; }
    table.fields td .value { font-size: 11px; color: #111; }

    .note {
        background-color: #F7F9FC;
        border: 1px solid #e2e8f0;
        border-left: 3px solid #00438A;
        padding: 8px;
        font-size: 10px;
        color: #444;
        margin-bottom: 6px;
    }

    .status-badge {
        display: inline-block;
        background-color: #E6F4EF;
        color: #007856;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 10px;
        line-height: 10px;
        font-weight: bold;
    }

    .photo-table { width: 100%; border-collapse: collapse; }
    .photo-cell { width: 50%; padding: 6px; vertical-align: top; }
    .photo-box {
        width: 100%;
        height: 160px;
        border: 1px solid #dddddd;
        background: #ffffff;
        text-align: center;
        vertical-align: middle;
        overflow: hidden;
        line-height: 160px;
    }
    .photo-box img {
        max-width: 100%;
        max-height: 150px;
        width: auto;
        height: auto;
        vertical-align: middle;
    }
    .photo-name { font-size: 9px; color: #666666; text-align: center; margin-top: 4px; word-wrap: break-word; }

    .signatures { width: 100%; margin-top: 6px; }
    .signatures td { width: 50%; padding: 6px; vertical-align: top; }
    .signatures .box { border: 1px solid #ccc; padding: 6px; min-height: 90px; text-align: center; }
    .signatures img { max-height: 70px; max-width: 100%; }
    .signatures .signer-name { margin-top: 4px; font-size: 10px; color: #333; }
    .signatures .signer-role { font-size: 9px; color: #00438A; font-weight: bold; text-transform: uppercase; }
    .signatures .empty { color: #aaa; font-size: 10px; padding-top: 30px; }

    .footer { margin-top: 16px; font-size: 8px; color: #999; text-align: center; border-top: 1px solid #ddd; padding-top: 6px; }
</style>
</head>
<body>

    @php
        $pdfLogoDataUri = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/procon-logo.png')));
    @endphp

    <div class="header">
        <table>
            <tr>
                <td>
                    <img class="logo-img" src="{{ $pdfLogoDataUri }}" alt="PROCON">
                    <div class="subtitle">{{ __('Solicitud de Rotulación de Fachadas') }}</div>
                </td>
                <td class="meta">
                    <strong>{{ __('Folio') }}:</strong> {{ $facadeRequest->folio }}<br>
                    <strong>{{ __('Fecha') }}:</strong> {{ $facadeRequest->submitted_at?->format('d/m/Y H:i') }}<br>
                    <strong>{{ __('Estado') }}:</strong> <span class="status-badge">{{ $statusLabel }}</span>
                </td>
            </tr>
        </table>
    </div>

    <h2 class="section">{{ __('Datos del agente') }}</h2>
    <table class="fields">
        <tr>
            <td><div class="label">{{ __('Nombre') }}</div><div class="value">{{ $facadeRequest->agent_name }}</div></td>
            <td><div class="label">{{ __('Correo') }}</div><div class="value">{{ $facadeRequest->agent_email }}</div></td>
        </tr>
        <tr>
            <td><div class="label">{{ __('Teléfono') }}</div><div class="value">{{ $facadeRequest->agent_phone ?? '—' }}</div></td>
            <td><div class="label">{{ __('Zona') }}</div><div class="value">{{ $facadeRequest->zone ?? '—' }}</div></td>
        </tr>
    </table>

    <h2 class="section">{{ __('Cliente y tienda') }}</h2>
    <table class="fields">
        <tr>
            <td><div class="label">{{ __('Tienda') }}</div><div class="value">{{ $facadeRequest->store_name }}</div></td>
            <td><div class="label">{{ __('Cliente') }}</div><div class="value">{{ $facadeRequest->client_name }}</div></td>
        </tr>
        <tr>
            <td><div class="label">{{ __('Teléfono cliente') }}</div><div class="value">{{ $facadeRequest->client_phone }}</div></td>
            <td><div class="label">{{ __('Encargado') }}</div><div class="value">{{ $facadeRequest->manager_name }} ({{ $facadeRequest->manager_phone }})</div></td>
        </tr>
        <tr>
            <td><div class="label">{{ __('Cliente registrado fiscalmente') }}</div><div class="value">{{ $facadeRequest->client_registered_tax ? __('Sí') : __('No') }}</div></td>
        </tr>
    </table>

    <h2 class="section">{{ __('Ubicación') }}</h2>
    <table class="fields">
        <tr>
            <td colspan="2">
                <div class="label">{{ __('Dirección') }}</div>
                <div class="value">
                    {{ $facadeRequest->street }} {{ $facadeRequest->number }}
                    @if ($facadeRequest->cross_streets)
                        , {{ __('entre') }} {{ $facadeRequest->cross_streets }}
                    @endif
                    , {{ $facadeRequest->neighborhood }}, {{ $facadeRequest->city }}, {{ $facadeRequest->municipality }}, {{ $facadeRequest->state }}
                    @if ($facadeRequest->postal_code)
                        CP {{ $facadeRequest->postal_code }}
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2"><div class="label">{{ __('Google Maps') }}</div><div class="value">{{ $facadeRequest->google_maps_url }}</div></td>
        </tr>
    </table>

    <h2 class="section">{{ __('Características de la ubicación') }}</h2>
    <table class="fields">
        <tr>
            <td><div class="label">{{ __('Esquina') }}</div><div class="value">{{ $facadeRequest->is_corner ? __('Sí') : __('No') }}</div></td>
            <td><div class="label">{{ __('Media cuadra') }}</div><div class="value">{{ $facadeRequest->is_mid_block ? __('Sí') : __('No') }}</div></td>
        </tr>
        <tr>
            <td><div class="label">{{ __('Final de calle') }}</div><div class="value">{{ $facadeRequest->is_end_of_street ? __('Sí') : __('No') }}</div></td>
            <td><div class="label">{{ __('Cerca de otra fachada PROCON') }}</div><div class="value">{{ $facadeRequest->near_existing_procon_facade ? __('Sí') : __('No') }}</div></td>
        </tr>
        <tr>
            <td><div class="label">{{ __('Avenida principal') }}</div><div class="value">{{ $facadeRequest->on_main_avenue ? __('Sí') : __('No') }}</div></td>
            <td><div class="label">{{ __('Visibilidad clara') }}</div><div class="value">{{ $facadeRequest->has_clear_visibility ? __('Sí') : __('No') }}</div></td>
        </tr>
        @if ($facadeRequest->location_notes)
            <tr>
                <td colspan="2"><div class="label">{{ __('Notas de ubicación') }}</div><div class="value">{{ $facadeRequest->location_notes }}</div></td>
            </tr>
        @endif
    </table>

    <h2 class="section">{{ __('Relación PROCON - cliente') }}</h2>
    <table class="fields">
        <tr>
            <td><div class="label">{{ __('Meses como distribuidor') }}</div><div class="value">{{ $facadeRequest->months_as_distributor }}</div></td>
            <td><div class="label">{{ __('Consumo mensual estimado') }}</div><div class="value">{{ $facadeRequest->estimated_monthly_consumption }}</div></td>
        </tr>
        <tr>
            <td colspan="2"><div class="label">{{ __('Productos frecuentes') }}</div><div class="value">{{ $facadeRequest->frequent_products }}</div></td>
        </tr>
        @if ($facadeRequest->additional_comments)
            <tr>
                <td colspan="2"><div class="label">{{ __('Comentarios adicionales') }}</div><div class="value">{{ $facadeRequest->additional_comments }}</div></td>
            </tr>
        @endif
    </table>

    <h2 class="section">{{ __('Autorización') }}</h2>
    <table class="fields">
        <tr>
            <td><div class="label">{{ __('Autorizador seleccionado') }}</div><div class="value">{{ $facadeRequest->authorizer_name }}</div></td>
            <td><div class="label">{{ __('Correo del autorizador') }}</div><div class="value">{{ $facadeRequest->authorizer_email }}</div></td>
        </tr>
    </table>

    <h2 class="section">{{ __('Evidencia fotográfica') }}</h2>
    @if (empty($photos))
        <p style="color:#999;">{{ __('Sin fotos.') }}</p>
    @else
        <table class="photo-table">
            @foreach ($photos as $index => $photo)
                @if ($index % 2 === 0)
                    <tr>
                @endif

                <td class="photo-cell">
                    <div class="photo-box">
                        <img src="{{ $photo['data_uri'] }}" alt="{{ $photo['original_name'] }}">
                    </div>
                    <div class="photo-name">{{ $photo['original_name'] }}</div>
                </td>

                @if ($index % 2 === 1)
                    </tr>
                @endif
            @endforeach

            @if (count($photos) % 2 === 1)
                <td class="photo-cell"></td></tr>
            @endif
        </table>
    @endif

    <h2 class="section">{{ __('Nota de compromisos') }}</h2>
    <div class="note">
        {{ __('Al firmar, el cliente y el agente confirman que la información capturada en esta solicitud es correcta y que autorizan su envío para revisión.') }}
    </div>

    <h2 class="section">{{ __('Firmas') }}</h2>
    <table class="signatures">
        <tr>
            <td>
                <div class="box">
                    @if ($clientSignatureDataUri)
                        <img src="{{ $clientSignatureDataUri }}" alt="{{ __('Firma del cliente') }}">
                        <div class="signer-name">{{ $facadeRequest->client_signature_name }}</div>
                    @else
                        <div class="empty">{{ __('Sin firma') }}</div>
                    @endif
                </div>
                <div class="signer-role">{{ __('Firma del cliente') }}</div>
            </td>
            <td>
                <div class="box">
                    @if ($agentSignatureDataUri)
                        <img src="{{ $agentSignatureDataUri }}" alt="{{ __('Firma del agente') }}">
                        <div class="signer-name">{{ $facadeRequest->agent_signature_name }}</div>
                    @else
                        <div class="empty">{{ __('Sin firma') }}</div>
                    @endif
                </div>
                <div class="signer-role">{{ __('Firma del agente') }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ __('Documento generado automáticamente por el CRM Cotizaciones PROCON.') }}
    </div>

</body>
</html>

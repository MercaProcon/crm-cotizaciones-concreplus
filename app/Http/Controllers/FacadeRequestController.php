<?php

namespace App\Http\Controllers;

use App\Mail\FacadeAuthorizationRequestedMail;
use App\Models\FacadeRequest;
use App\Models\FacadeRequestEvent;
use App\Models\FacadeRequestPhoto;
use App\Services\FacadeRequestPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacadeRequestController extends Controller
{
    public const STATUS_LABELS = [
        FacadeRequest::STATUS_CAPTURADA => 'Capturada',
        FacadeRequest::STATUS_PENDIENTE_AUTORIZACION => 'Pendiente de autorización',
        FacadeRequest::STATUS_AUTORIZADA => 'Autorizada',
        FacadeRequest::STATUS_RECHAZADA => 'Rechazada',
        FacadeRequest::STATUS_CANCELADA => 'Cancelada',
        FacadeRequest::STATUS_EN_PRODUCCION => 'En producción',
        FacadeRequest::STATUS_INSTALADA => 'Instalada',
    ];

    public function __construct(private readonly FacadeRequestPdfService $pdfService)
    {
    }

    /**
     * Admin/coordinadora (facades.view.all) ven todas las solicitudes;
     * vendedora (solo facades.view) ve únicamente las suyas.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = FacadeRequest::query()->latest('submitted_at');

        if (! $user->can('facades.view.all')) {
            $query->where('agent_user_id', $user->id);
        }

        $facadeRequests = $query->paginate(20)->withQueryString();

        return view('facades.index', [
            'facadeRequests' => $facadeRequests,
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    public function show(Request $request, FacadeRequest $facadeRequest): View
    {
        abort_unless($facadeRequest->canBeViewedBy($request->user()), 403);

        $facadeRequest->load('photos');

        return view('facades.show', [
            'facadeRequest' => $facadeRequest,
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    public function create(): View
    {
        return view('facades.create', [
            'authorizers' => config('fachadas.authorizers'),
        ]);
    }

    /**
     * El agente siempre se toma de auth()->user() (todas las rutas de
     * /fachadas están detrás de 'auth'), nunca de datos del formulario.
     * El nombre/correo del autorizador se resuelve aquí a partir de la
     * clave enviada, nunca se confía en un nombre/correo del navegador.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'agent_phone' => ['nullable', 'string', 'max:20'],
            'zone' => ['nullable', 'string', 'max:100'],

            'store_name' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:20'],
            'manager_name' => ['required', 'string', 'max:255'],
            'manager_phone' => ['required', 'string', 'max:20'],
            'client_registered_tax' => ['required', 'boolean'],

            'street' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:50'],
            'cross_streets' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'google_maps_url' => ['required', 'url'],

            'is_corner' => ['required', 'boolean'],
            'is_mid_block' => ['required', 'boolean'],
            'is_end_of_street' => ['required', 'boolean'],
            'near_existing_procon_facade' => ['required', 'boolean'],
            'on_main_avenue' => ['required', 'boolean'],
            'has_clear_visibility' => ['required', 'boolean'],
            'location_notes' => ['nullable', 'string'],

            'months_as_distributor' => ['required', 'string', 'max:50'],
            'estimated_monthly_consumption' => ['required', 'string', 'max:100'],
            'frequent_products' => ['required', 'string'],
            'additional_comments' => ['nullable', 'string'],

            'photos' => ['required', 'array', 'min:2', 'max:8'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'authorizer_key' => ['required', Rule::in(array_keys(config('fachadas.authorizers')))],

            'client_signature_name' => ['required', 'string', 'max:255'],
            'client_signature_touched' => ['required', 'in:1'],
            'client_signature_data' => ['nullable', 'string'],

            'agent_signature_touched' => ['required', 'in:1'],
            'agent_signature_data' => ['nullable', 'string'],
        ], [
            'store_name.required' => 'El nombre de la tienda es obligatorio.',
            'client_name.required' => 'El nombre del cliente es obligatorio.',
            'client_phone.required' => 'El teléfono del cliente es obligatorio.',
            'manager_name.required' => 'El nombre del encargado es obligatorio.',
            'manager_phone.required' => 'El teléfono del encargado es obligatorio.',
            'client_registered_tax.required' => 'Indica si el cliente está registrado fiscalmente.',

            'street.required' => 'La calle es obligatoria.',
            'number.required' => 'El número es obligatorio.',
            'neighborhood.required' => 'La colonia es obligatoria.',
            'city.required' => 'La ciudad es obligatoria.',
            'municipality.required' => 'El municipio es obligatorio.',
            'state.required' => 'El estado es obligatorio.',
            'google_maps_url.required' => 'El enlace de Google Maps es obligatorio.',
            'google_maps_url.url' => 'El enlace de Google Maps no es válido.',

            'is_corner.required' => 'Indica si es esquina.',
            'is_mid_block.required' => 'Indica si es media cuadra.',
            'is_end_of_street.required' => 'Indica si es final de calle.',
            'near_existing_procon_facade.required' => 'Indica si está cerca de otra fachada PROCON.',
            'on_main_avenue.required' => 'Indica si está sobre avenida principal.',
            'has_clear_visibility.required' => 'Indica si tiene visibilidad clara.',

            'months_as_distributor.required' => 'Indica los meses como distribuidor.',
            'estimated_monthly_consumption.required' => 'Indica el consumo mensual estimado.',
            'frequent_products.required' => 'Indica los productos frecuentes.',

            'photos.required' => 'Debes subir al menos 2 fotografías del negocio.',
            'photos.array' => 'Debes subir fotografías válidas.',
            'photos.min' => 'Debes subir mínimo 2 fotografías del negocio.',
            'photos.max' => 'Puedes subir máximo 8 fotografías.',
            'photos.*.image' => 'Cada archivo debe ser una imagen.',
            'photos.*.mimes' => 'Las fotos deben ser formato JPG, PNG o WEBP.',
            'photos.*.max' => 'Cada foto debe pesar máximo 5 MB.',

            'authorizer_key.required' => 'Selecciona quién debe autorizar esta solicitud.',
            'authorizer_key.in' => 'Selecciona un autorizador válido.',

            'client_signature_name.required' => 'El nombre de quien firma por el cliente es obligatorio.',
            'client_signature_touched.required' => 'La firma del cliente es obligatoria.',
            'client_signature_touched.in' => 'La firma del cliente es obligatoria.',
            'agent_signature_touched.required' => 'La firma del agente es obligatoria.',
            'agent_signature_touched.in' => 'La firma del agente es obligatoria.',
        ]);

        $clientSignaturePng = $this->decodeSignaturePng($validated['client_signature_data'] ?? null);

        if ($clientSignaturePng === null) {
            throw ValidationException::withMessages([
                'client_signature_data' => 'La firma del cliente no es válida, intenta firmar de nuevo.',
            ]);
        }

        $agentSignaturePng = $this->decodeSignaturePng($validated['agent_signature_data'] ?? null);

        if ($agentSignaturePng === null) {
            throw ValidationException::withMessages([
                'agent_signature_data' => 'La firma del agente no es válida, intenta firmar de nuevo.',
            ]);
        }

        $authorizer = config('fachadas.authorizers')[$validated['authorizer_key']];

        $facadeRequest = DB::transaction(function () use ($validated, $request, $authorizer, $clientSignaturePng, $agentSignaturePng) {
            $user = $request->user();

            do {
                $folio = 'FCH-'.now()->format('Ymd').'-'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            } while (FacadeRequest::where('folio', $folio)->exists());

            $facadeRequest = FacadeRequest::create([
                'folio' => $folio,
                'status' => FacadeRequest::STATUS_CAPTURADA,
                'submitted_at' => now(),

                'agent_user_id' => $user->id,
                'agent_name' => $user->name,
                'agent_email' => $user->email,
                'agent_phone' => $validated['agent_phone'] ?? null,
                'zone' => $validated['zone'] ?? null,

                'store_name' => $validated['store_name'],
                'client_name' => $validated['client_name'],
                'client_phone' => $validated['client_phone'],
                'manager_name' => $validated['manager_name'],
                'manager_phone' => $validated['manager_phone'],
                'client_registered_tax' => $validated['client_registered_tax'],

                'street' => $validated['street'],
                'number' => $validated['number'],
                'cross_streets' => $validated['cross_streets'] ?? null,
                'neighborhood' => $validated['neighborhood'],
                'city' => $validated['city'],
                'municipality' => $validated['municipality'],
                'state' => $validated['state'],
                'postal_code' => $validated['postal_code'] ?? null,
                'google_maps_url' => $validated['google_maps_url'],

                'is_corner' => $validated['is_corner'],
                'is_mid_block' => $validated['is_mid_block'],
                'is_end_of_street' => $validated['is_end_of_street'],
                'near_existing_procon_facade' => $validated['near_existing_procon_facade'],
                'on_main_avenue' => $validated['on_main_avenue'],
                'has_clear_visibility' => $validated['has_clear_visibility'],
                'location_notes' => $validated['location_notes'] ?? null,

                'months_as_distributor' => $validated['months_as_distributor'],
                'estimated_monthly_consumption' => $validated['estimated_monthly_consumption'],
                'frequent_products' => $validated['frequent_products'],
                'additional_comments' => $validated['additional_comments'] ?? null,

                'authorizer_key' => $validated['authorizer_key'],
                'authorizer_name' => $authorizer['name'],
                'authorizer_email' => $authorizer['email'],
            ]);

            foreach ($request->file('photos', []) as $file) {
                $path = $file->store("facade-requests/{$facadeRequest->id}/photos", 'local');

                $facadeRequest->photos()->create([
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            $clientSignaturePath = "facade-requests/{$facadeRequest->id}/signatures/client.png";
            $agentSignaturePath = "facade-requests/{$facadeRequest->id}/signatures/agent.png";

            Storage::disk('local')->put($clientSignaturePath, $clientSignaturePng);
            Storage::disk('local')->put($agentSignaturePath, $agentSignaturePng);

            $facadeRequest->update([
                'client_signature_path' => $clientSignaturePath,
                'client_signature_name' => $validated['client_signature_name'],
                'agent_signature_path' => $agentSignaturePath,
                'agent_signature_name' => $request->user()->name,
            ]);

            return $facadeRequest;
        });

        $this->generatePdfSafely($facadeRequest);

        $authorizationResult = $this->attemptSendAuthorizationEmail($facadeRequest, $request->user()->id);

        return redirect()->route('facades.show', $facadeRequest)->with(
            $authorizationResult['sent'] ? 'status' : 'error',
            $authorizationResult['sent']
                ? 'Solicitud de rotulación registrada y enviada a autorización correctamente.'
                : 'La solicitud fue guardada, pero no se pudo enviar a autorización. Revisa la configuración de correo.'
        );
    }

    /**
     * Genera el PDF fuera de la transacción de store() (para no alargarla)
     * justo después de crear la solicitud. Si falla, se registra en log y
     * la creación de la solicitud NO se ve afectada: pdf_path queda vacío
     * y downloadPdf() lo regenerará al vuelo en el primer intento de
     * descarga.
     */
    private function generatePdfSafely(FacadeRequest $facadeRequest): void
    {
        try {
            $path = $this->pdfService->generate($facadeRequest, self::STATUS_LABELS[$facadeRequest->status] ?? $facadeRequest->status);
            $facadeRequest->update(['pdf_path' => $path]);
        } catch (\Throwable $e) {
            Log::error('No se pudo generar el PDF de la solicitud de fachada al crearla.', [
                'facade_request_id' => $facadeRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Descarga el PDF de la solicitud. Si todavía no existe (por ejemplo,
     * falló la generación automática, o es un registro anterior a esta
     * fase), se regenera al vuelo antes de servirlo.
     */
    public function downloadPdf(Request $request, FacadeRequest $facadeRequest): StreamedResponse
    {
        abort_unless($facadeRequest->canBeViewedBy($request->user()), 403);

        if (! $facadeRequest->pdf_path || ! Storage::disk('local')->exists($facadeRequest->pdf_path)) {
            $this->generatePdfSafely($facadeRequest);
            $facadeRequest->refresh();
        }

        abort_if(! $facadeRequest->pdf_path || ! Storage::disk('local')->exists($facadeRequest->pdf_path), 500, 'No se pudo generar el PDF de esta solicitud.');

        return Storage::disk('local')->download($facadeRequest->pdf_path, $facadeRequest->folio.'.pdf');
    }

    /**
     * Reintento manual (botón de respaldo): solo tiene efecto si el envío
     * automático de store() no llegó a completarse y la solicitud sigue
     * en capturada. Usa los mismos mensajes específicos que antes, ya que
     * aquí el usuario SÍ necesita saber la razón exacta para corregirla.
     */
    public function sendAuthorizationEmail(Request $request, FacadeRequest $facadeRequest): RedirectResponse
    {
        abort_unless($facadeRequest->canBeViewedBy($request->user()), 403);

        if ($facadeRequest->status !== FacadeRequest::STATUS_CAPTURADA) {
            return redirect()->route('facades.show', $facadeRequest)
                ->with('error', 'Esta solicitud ya fue enviada a autorización.');
        }

        $result = $this->attemptSendAuthorizationEmail($facadeRequest, $request->user()->id);

        if ($result['sent']) {
            return redirect()->route('facades.show', $facadeRequest)
                ->with('status', 'Se envió el correo de autorización a '.$facadeRequest->authorizer_name.'.');
        }

        return redirect()->route('facades.show', $facadeRequest)
            ->with('error', 'No se pudo enviar: '.$result['reason']);
    }

    /**
     * Valida, genera token/PDF si hace falta, envía el correo (To:
     * autorizador seleccionado, Cc: mercadotecnia si está configurada) y
     * registra el evento. Se usa tanto automáticamente desde store() como
     * desde el botón de reintento manual — sin checks de permiso propios,
     * esos son responsabilidad de quien la llama.
     *
     * @return array{sent: bool, reason: ?string}
     */
    private function attemptSendAuthorizationEmail(FacadeRequest $facadeRequest, ?int $triggeredByUserId): array
    {
        if (! $facadeRequest->authorizer_email) {
            return $this->authorizationFailure($facadeRequest, 'el correo del autorizador no está configurado.');
        }

        if ($facadeRequest->photos()->count() < 2) {
            return $this->authorizationFailure($facadeRequest, 'la solicitud no tiene mínimo 2 fotos.');
        }

        if (! $facadeRequest->client_signature_path || ! $facadeRequest->agent_signature_path) {
            return $this->authorizationFailure($facadeRequest, 'la solicitud no tiene las firmas completas.');
        }

        if (! $facadeRequest->pdf_path || ! Storage::disk('local')->exists($facadeRequest->pdf_path)) {
            $this->generatePdfSafely($facadeRequest);
            $facadeRequest->refresh();
        }

        if (! $facadeRequest->pdf_path || ! Storage::disk('local')->exists($facadeRequest->pdf_path)) {
            return $this->authorizationFailure($facadeRequest, 'no se pudo generar el PDF.');
        }

        if (! $facadeRequest->authorization_token || $facadeRequest->authorization_token_expires_at?->isPast()) {
            $facadeRequest->update([
                'authorization_token' => Str::random(64),
                'authorization_token_expires_at' => now()->addDays(7),
            ]);
        }

        try {
            $mail = Mail::to($facadeRequest->authorizer_email);

            if (config('fachadas.marketing_email')) {
                $mail->cc(config('fachadas.marketing_email'));
            }

            $mail->send(new FacadeAuthorizationRequestedMail($facadeRequest));
        } catch (\Throwable $e) {
            return $this->authorizationFailure($facadeRequest, 'no se pudo enviar el correo. Intenta de nuevo más tarde.', $e->getMessage());
        }

        DB::transaction(function () use ($facadeRequest, $triggeredByUserId) {
            $facadeRequest->update([
                'authorization_email_sent_at' => now(),
                'status' => FacadeRequest::STATUS_PENDIENTE_AUTORIZACION,
            ]);

            $facadeRequest->events()->create([
                'user_id' => $triggeredByUserId,
                'event_type' => FacadeRequestEvent::EVENT_AUTHORIZATION_EMAIL_SENT,
                'metadata' => [
                    'authorizer_email' => $facadeRequest->authorizer_email,
                    'authorizer_name' => $facadeRequest->authorizer_name,
                    'marketing_cc' => config('fachadas.marketing_email'),
                ],
            ]);
        });

        Log::info('Correo de autorización enviado para solicitud de fachada.', [
            'facade_request_id' => $facadeRequest->id,
            'authorizer_email' => $facadeRequest->authorizer_email,
            'marketing_cc' => config('fachadas.marketing_email'),
        ]);

        return ['sent' => true, 'reason' => null];
    }

    /**
     * @return array{sent: bool, reason: string}
     */
    private function authorizationFailure(FacadeRequest $facadeRequest, string $reason, ?string $exceptionMessage = null): array
    {
        Log::error('No se pudo enviar el correo de autorización de la solicitud de fachada.', [
            'facade_request_id' => $facadeRequest->id,
            'reason' => $reason,
            'exception' => $exceptionMessage,
        ]);

        return ['sent' => false, 'reason' => $reason];
    }

    /**
     * Sirve una foto desde el disco privado. Verifica permiso sobre la
     * solicitud Y que la foto realmente pertenezca a esa solicitud (no
     * solo el permiso general), para no exponer fotos de otra solicitud
     * adivinando IDs en la URL.
     */
    public function photo(Request $request, FacadeRequest $facadeRequest, FacadeRequestPhoto $photo): StreamedResponse
    {
        abort_unless($facadeRequest->canBeViewedBy($request->user()), 403);
        abort_unless($photo->facade_request_id === $facadeRequest->id, 404);

        return Storage::disk('local')->response($photo->path, $photo->original_name);
    }

    /**
     * Sirve la firma (cliente o agente) desde el disco privado, con la
     * misma verificación de permiso que las fotos.
     */
    public function signature(Request $request, FacadeRequest $facadeRequest, string $type): StreamedResponse
    {
        abort_unless($facadeRequest->canBeViewedBy($request->user()), 403);

        $path = $type === 'client' ? $facadeRequest->client_signature_path : $facadeRequest->agent_signature_path;

        abort_if($path === null, 404);

        return Storage::disk('local')->response($path);
    }

    /**
     * Decodifica un data URL "data:image/png;base64,...." y confirma que
     * los bytes resultantes son realmente un PNG (cabecera mágica), antes
     * de guardarlo. Devuelve null si el valor está vacío o no es un PNG
     * válido (canvas nunca dibujado, dato manipulado, etc.).
     */
    private function decodeSignaturePng(?string $dataUrl): ?string
    {
        $prefix = 'data:image/png;base64,';

        if (! $dataUrl || ! str_starts_with($dataUrl, $prefix)) {
            return null;
        }

        $decoded = base64_decode(substr($dataUrl, strlen($prefix)), true);

        if ($decoded === false || ! str_starts_with($decoded, "\x89PNG\r\n\x1a\n")) {
            return null;
        }

        return $decoded;
    }
}

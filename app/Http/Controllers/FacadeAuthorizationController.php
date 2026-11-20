<?php

namespace App\Http\Controllers;

use App\Mail\FacadeRequestApprovedMail;
use App\Mail\FacadeRequestRejectedMail;
use App\Models\FacadeRequest;
use App\Models\FacadeRequestEvent;
use App\Services\FacadeRequestPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacadeAuthorizationController extends Controller
{
    public function __construct(private readonly FacadeRequestPdfService $pdfService)
    {
    }

    /**
     * Pantalla pública (sin sesión) para que el autorizador revise la
     * solicitud a partir del token que recibió por correo. Si ya está
     * resuelta (autorizada/rechazada), se muestra en solo lectura con el
     * resultado, sin botones.
     */
    public function show(string $token): View
    {
        $facadeRequest = $this->findByToken($token);

        $media = $this->pdfService->preparePhotosAndSignatures($facadeRequest);

        return view('facades.authorization.show', [
            'facadeRequest' => $facadeRequest,
            'statusLabel' => FacadeRequestController::STATUS_LABELS[$facadeRequest->status] ?? $facadeRequest->status,
            'photos' => $media['photos'],
            'clientSignatureDataUri' => $media['clientSignatureDataUri'],
            'agentSignatureDataUri' => $media['agentSignatureDataUri'],
            'canDecide' => $this->canBeDecided($facadeRequest),
        ]);
    }

    /**
     * Descarga del PDF desde la misma pantalla pública, validando el
     * token igual que show() (el autorizador no tiene sesión ni permiso
     * de Laravel, el token es la única credencial).
     */
    public function downloadPdf(string $token): StreamedResponse
    {
        $facadeRequest = $this->findByToken($token);

        if (! $facadeRequest->pdf_path || ! Storage::disk('local')->exists($facadeRequest->pdf_path)) {
            $path = $this->pdfService->generate(
                $facadeRequest,
                FacadeRequestController::STATUS_LABELS[$facadeRequest->status] ?? $facadeRequest->status
            );
            $facadeRequest->update(['pdf_path' => $path]);
        }

        return Storage::disk('local')->download($facadeRequest->pdf_path, $facadeRequest->folio.'.pdf');
    }

    /**
     * Autorizar: comentario opcional.
     */
    public function approve(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'approve_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->resolve($token, FacadeRequest::STATUS_AUTORIZADA, $validated['approve_comment'] ?? null);
    }

    /**
     * Rechazar: comentario obligatorio. Campo con nombre distinto al del
     * formulario de autorizar ("reject_comment" vs "approve_comment")
     * para que los errores de validación de un formulario no se mezclen
     * visualmente con el otro en la misma página.
     */
    public function reject(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'reject_comment' => ['required', 'string', 'max:2000'],
        ], [
            'reject_comment.required' => 'Debes indicar el motivo del rechazo.',
        ]);

        return $this->resolve($token, FacadeRequest::STATUS_RECHAZADA, $validated['reject_comment']);
    }

    /**
     * Lógica compartida por approve()/reject(): bloquea el doble uso,
     * guarda la decisión y el token consumido, registra el evento, y
     * manda el correo final a agente + mercadotecnia. Si el correo falla,
     * la decisión ya guardada NO se revierte (el fallo solo se registra
     * en log y en el propio evento).
     */
    private function resolve(string $token, string $outcome, ?string $comment): RedirectResponse
    {
        $facadeRequest = $this->findByToken($token);

        if (! $this->canBeDecided($facadeRequest)) {
            return redirect()->route('facades.authorization.show', $token)
                ->with('error', 'Esta solicitud ya fue resuelta anteriormente.');
        }

        DB::transaction(function () use ($facadeRequest, $outcome, $comment) {
            $facadeRequest->update([
                'status' => $outcome,
                'authorization_comment' => $comment,
                'authorized_at' => $outcome === FacadeRequest::STATUS_AUTORIZADA ? now() : null,
                'rejected_at' => $outcome === FacadeRequest::STATUS_RECHAZADA ? now() : null,
                'authorization_token_used_at' => now(),
            ]);
        });

        [$notified, $notificationError] = $this->sendFinalNotification($facadeRequest, $outcome);

        $facadeRequest->events()->create([
            'event_type' => $outcome === FacadeRequest::STATUS_AUTORIZADA ? FacadeRequestEvent::EVENT_APPROVED : FacadeRequestEvent::EVENT_REJECTED,
            'metadata' => [
                'comment' => $comment,
                'notified' => $notified,
                'notification_error' => $notificationError,
            ],
        ]);

        return redirect()->route('facades.authorization.show', $token)
            ->with('status', $outcome === FacadeRequest::STATUS_AUTORIZADA
                ? 'Solicitud autorizada correctamente.'
                : 'Solicitud rechazada correctamente.');
    }

    /**
     * Envía el correo final (To: agente, Cc: mercadotecnia si está
     * configurada) con el PDF adjunto. Devuelve [bool $notified, ?string
     * $error] para que resolve() lo registre en el evento sin que un
     * fallo de correo afecte la decisión ya guardada.
     *
     * @return array{0: bool, 1: ?string}
     */
    private function sendFinalNotification(FacadeRequest $facadeRequest, string $outcome): array
    {
        try {
            $mail = Mail::to($facadeRequest->agent_email);

            if (config('fachadas.marketing_email')) {
                $mail->cc(config('fachadas.marketing_email'));
            }

            $mail->send(
                $outcome === FacadeRequest::STATUS_AUTORIZADA
                    ? new FacadeRequestApprovedMail($facadeRequest)
                    : new FacadeRequestRejectedMail($facadeRequest)
            );

            return [true, null];
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo final de la solicitud de fachada.', [
                'facade_request_id' => $facadeRequest->id,
                'outcome' => $outcome,
                'error' => $e->getMessage(),
            ]);

            return [false, $e->getMessage()];
        }
    }

    /**
     * Solo se puede aprobar/rechazar si sigue pendiente y el token no se
     * ha usado todavía. Doble verificación (estado + token) para que un
     * fallo parcial no deje la puerta abierta a un segundo uso.
     */
    private function canBeDecided(FacadeRequest $facadeRequest): bool
    {
        return $facadeRequest->status === FacadeRequest::STATUS_PENDIENTE_AUTORIZACION
            && $facadeRequest->authorization_token_used_at === null;
    }

    private function findByToken(string $token): FacadeRequest
    {
        return FacadeRequest::query()
            ->whereNotNull('authorization_token')
            ->where('authorization_token', $token)
            ->where(function ($query) {
                $query->whereNull('authorization_token_expires_at')
                    ->orWhere('authorization_token_expires_at', '>=', now());
            })
            ->firstOrFail();
    }
}

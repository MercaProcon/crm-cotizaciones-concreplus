<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FollowUp;
use App\Models\Quotation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FollowUpController extends Controller
{
    /**
     * Agrega un seguimiento y, opcionalmente, cambia el estado CRM.
     *
     * Reglas: solo quien tenga permiso quotations.add.followup y cumpla la
     * regla de propiedad (Quotation::canReceiveFollowUpFrom) puede llamar
     * esto. Si es el primer seguimiento de la cotización, se registra
     * first_attention_at. Si cambia el estado, se registra un ActivityLog.
     * Todo corre en una transacción.
     */
    public function store(Request $request, Quotation $quotation): RedirectResponse
    {
        abort_unless($quotation->canReceiveFollowUpFrom($request->user()), 403);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
            'crm_status' => ['nullable', Rule::in(Quotation::FOLLOW_UP_STATUSES)],
        ]);

        DB::transaction(function () use ($request, $quotation, $validated) {
            $oldStatus = $quotation->crm_status;
            $newStatus = $validated['crm_status'] ?? null;
            $statusChanged = $newStatus !== null && $newStatus !== $oldStatus;
            $isFirstAttention = is_null($quotation->first_attention_at);

            $updates = [];

            if ($statusChanged) {
                $updates['crm_status'] = $newStatus;
            }

            if ($isFirstAttention) {
                $updates['first_attention_at'] = now();
            }

            if ($updates !== []) {
                $quotation->update($updates);
            }

            if ($statusChanged) {
                ActivityLog::create([
                    'quotation_id' => $quotation->id,
                    'user_id' => $request->user()->id,
                    'action' => 'status_changed',
                    'old_value' => QuotationController::STATUS_LABELS[$oldStatus] ?? $oldStatus,
                    'new_value' => QuotationController::STATUS_LABELS[$newStatus] ?? $newStatus,
                ]);
            }

            FollowUp::create([
                'quotation_id' => $quotation->id,
                'user_id' => $request->user()->id,
                'status' => $statusChanged ? $newStatus : $oldStatus,
                'comment' => $validated['comment'],
            ]);
        });

        return back()->with('status', __('Seguimiento registrado correctamente.'));
    }
}

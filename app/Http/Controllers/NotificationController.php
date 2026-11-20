<?php

namespace App\Http\Controllers;

use App\Models\QuotationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Marca una notificación como leída y redirige al detalle de su cotización.
     */
    public function open(Request $request, QuotationNotification $quotationNotification): RedirectResponse
    {
        abort_unless($quotationNotification->user_id === $request->user()->id, 403);

        if (is_null($quotationNotification->read_at)) {
            $quotationNotification->update(['read_at' => now()]);
        }

        return redirect()->route('quotations.show', $quotationNotification->quotation_id);
    }

    /**
     * Marca todas las notificaciones no leídas del usuario actual como leídas.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()
            ->quotationNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}

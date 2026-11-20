<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyQuotationController extends Controller
{
    /**
     * Cotizaciones cuya asignación vigente pertenece al usuario actual.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $crmStatus = $request->query('crm_status');

        $quotations = Quotation::query()
            ->operationalVisible()
            ->whereHas('currentAssignment', function ($query) use ($user) {
                $query->where('assigned_to_user_id', $user->id);
            })
            ->when(filled($crmStatus), fn ($query) => $query->where('crm_status', $crmStatus))
            ->latest('registered_at')
            ->paginate(20)
            ->withQueryString();

        return view('my-quotations.index', [
            'quotations' => $quotations,
            'statusLabels' => QuotationController::STATUS_LABELS,
            'crmStatus' => $crmStatus,
        ]);
    }
}

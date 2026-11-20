<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DuplicateController extends Controller
{
    /**
     * Lista los teléfonos (phone_normalized) que tienen más de una cotización,
     * con un buscador simple por teléfono o nombre de cliente.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $query = Quotation::query()->operationalVisible();

        if ($search !== '') {
            $matchingPhones = Quotation::query()
                ->operationalVisible()
                ->where(function ($subQuery) use ($search) {
                    $subQuery->where('phone_normalized', 'like', "%{$search}%")
                        ->orWhere('phone_raw', 'like', "%{$search}%")
                        ->orWhere('client_name', 'like', "%{$search}%");
                })
                ->pluck('phone_normalized')
                ->unique();

            $query->whereIn('phone_normalized', $matchingPhones);
        }

        $groups = $query
            ->select('phone_normalized')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('phone_normalized')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('total')
            ->paginate(20)
            ->withQueryString();

        $phones = collect($groups->items())->pluck('phone_normalized');

        $quotationsByPhone = Quotation::whereIn('phone_normalized', $phones)
            ->operationalVisible()
            ->orderByDesc('registered_at')
            ->get()
            ->groupBy('phone_normalized');

        return view('duplicates.index', [
            'groups' => $groups,
            'quotationsByPhone' => $quotationsByPhone,
            'statusLabels' => QuotationController::STATUS_LABELS,
            'search' => $search,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\User;
use App\Services\CommercialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(private readonly CommercialReportService $reports)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->resolveFilters($request);

        return view('reports.index', array_merge(
            $this->reports->buildGeneralReportData($filters),
            [
                'filters' => $filters,
                'zones' => QuotationController::ZONES,
                'statusLabels' => QuotationController::STATUS_LABELS,
                'vendedoras' => User::role('vendedora')->orderBy('name')->get(['id', 'name']),
            ]
        ));
    }

    public function pdf(Request $request): Response
    {
        $filters = $this->resolveFilters($request);

        $data = array_merge($this->reports->buildGeneralReportData($filters), [
            'filters' => $filters,
            'statusLabels' => QuotationController::STATUS_LABELS,
            'logoDataUri' => $this->reports->logoDataUri(),
            'generatedAt' => now(),
        ]);

        return Pdf::loadView('reports.pdf.general', $data)
            ->download('reporte-comercial-'.now()->format('Ymd-His').'.pdf');
    }

    public function vendor(Request $request, User $user): View
    {
        abort_unless($user->hasRole('vendedora'), 404);

        $filters = $this->resolveFilters($request);

        return view('reports.vendor', array_merge(
            $this->reports->buildVendorReportData($user, $filters),
            [
                'vendedora' => $user,
                'filters' => $filters,
                'zones' => QuotationController::ZONES,
                'statusLabels' => QuotationController::STATUS_LABELS,
            ]
        ));
    }

    public function vendorPdf(Request $request, User $user): Response
    {
        abort_unless($user->hasRole('vendedora'), 404);

        $filters = $this->resolveFilters($request);

        $data = array_merge($this->reports->buildVendorReportData($user, $filters), [
            'vendedora' => $user,
            'filters' => $filters,
            'statusLabels' => QuotationController::STATUS_LABELS,
            'logoDataUri' => $this->reports->logoDataUri(),
            'generatedAt' => now(),
        ]);

        return Pdf::loadView('reports.pdf.vendor', $data)
            ->download('reporte-'.Str::slug($user->name).'-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Filtros compartidos por las 4 acciones. El periodo siempre se aplica
     * sobre la fecha base de la cotización (registered_at, o created_at si
     * no existe) — nunca sobre assigned_at ni sobre el follow_up.
     */
    private function resolveFilters(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'vendedora' => ['nullable', 'integer', 'exists:users,id'],
            'zone' => ['nullable', 'string', Rule::in(QuotationController::ZONES)],
            'status' => ['nullable', 'string', Rule::in(Quotation::STATUSES)],
        ]);

        return [
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'vendedora' => $validated['vendedora'] ?? null,
            'zone' => $validated['zone'] ?? null,
            'status' => $validated['status'] ?? null,
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Muestra un dashboard distinto según el rol: completo para quien tiene
     * quotations.view.all (admin/coordinadora), personal para vendedora,
     * mensaje simple sin módulos para agente, y simple (sin indicadores del
     * equipo) para el resto (ventanilla).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->can('quotations.view.all')) {
            return view('dashboard', [
                'variant' => 'global',
                'stats' => $this->globalStats(),
            ]);
        }

        if ($user->hasRole('vendedora')) {
            return view('dashboard', [
                'variant' => 'vendedora',
                'stats' => $this->vendedoraStats($user->id),
            ]);
        }

        if ($user->hasRole('agente')) {
            return view('dashboard', [
                'variant' => 'agente',
                'stats' => [],
            ]);
        }

        return view('dashboard', [
            'variant' => 'simple',
            'stats' => [],
        ]);
    }

    private function globalStats(): array
    {
        $unassigned = Quotation::operationalVisible()
            ->where('crm_status', Quotation::STATUS_NUEVA)
            ->whereDoesntHave('currentAssignment')
            ->count();

        $assignedToday = Assignment::whereDate('assigned_at', today())
            ->whereHas('quotation', fn ($query) => $query->operationalVisible())
            ->count();

        $pending = Quotation::query()
            ->operationalVisible()
            ->whereNotIn('crm_status', Quotation::CLOSED_STATUSES)
            ->whereHas('currentAssignment')
            ->with('currentAssignment')
            ->get()
            ->filter(fn (Quotation $quotation) => $quotation->isPendingAttention());

        $firstMinutes = config('reminders.first_minutes');
        $coordinatorMinutes = config('reminders.notify_coordinator_minutes');

        return [
            'unassigned' => $unassigned,
            'assigned_today' => $assignedToday,
            'pending' => $pending->count(),
            'over_first_minutes' => $this->countOverMinutes($pending, $firstMinutes),
            'over_coordinator_minutes' => $this->countOverMinutes($pending, $coordinatorMinutes),
            'duplicates' => $this->duplicatePhoneCount(),
            'won' => Quotation::operationalVisible()->where('crm_status', Quotation::STATUS_GANADA)->count(),
            'lost' => Quotation::operationalVisible()->where('crm_status', Quotation::STATUS_PERDIDA)->count(),
        ];
    }

    private function vendedoraStats(int $userId): array
    {
        $myQuotations = Quotation::query()
            ->operationalVisible()
            ->whereHas('currentAssignment', fn ($query) => $query->where('assigned_to_user_id', $userId))
            ->with('currentAssignment')
            ->get();

        $pending = $myQuotations->filter(fn (Quotation $quotation) => $quotation->isPendingAttention());

        return [
            'assigned' => $myQuotations->count(),
            'pending' => $pending->count(),
            'over_first_minutes' => $this->countOverMinutes($pending, config('reminders.first_minutes')),
            'won' => $myQuotations->where('crm_status', Quotation::STATUS_GANADA)->count(),
            'lost' => $myQuotations->where('crm_status', Quotation::STATUS_PERDIDA)->count(),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Quotation>  $quotations
     */
    private function countOverMinutes($quotations, int $minutes): int
    {
        return $quotations->filter(function (Quotation $quotation) use ($minutes) {
            $assignedAt = $quotation->currentAssignment?->assigned_at;

            return $assignedAt && abs($assignedAt->diffInMinutes(now())) >= $minutes;
        })->count();
    }

    private function duplicatePhoneCount(): int
    {
        return Quotation::query()
            ->operationalVisible()
            ->select('phone_normalized')
            ->groupBy('phone_normalized')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
    }
}

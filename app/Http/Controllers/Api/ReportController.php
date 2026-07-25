<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function revenue(Request $request)
    {
        [$start, $end] = $this->periodRange($request);

        $invoices = Invoice::with('customer')
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->orderByDesc('paid_at')
            ->get();

        return response()->json([
            'start_period' => $start->format('Y-m'),
            'end_period' => $end->format('Y-m'),
            'summary' => $this->monthlySummary($invoices, $start, $end),
            'total_revenue' => $invoices->sum('total_amount'),
            'total_invoices' => $invoices->count(),
            'invoices' => $invoices,
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodRange(Request $request): array
    {
        $startPeriod = $request->input('start_period') ?: now()->format('Y-m');
        $endPeriod = $request->input('end_period') ?: $startPeriod;

        $start = Carbon::createFromFormat('Y-m', $startPeriod)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $endPeriod)->endOfMonth();

        return $start->lte($end) ? [$start, $end] : [$end->copy()->startOfMonth(), $start->copy()->endOfMonth()];
    }

    private function monthlySummary($invoices, Carbon $start, Carbon $end): array
    {
        $grouped = $invoices->groupBy(fn (Invoice $invoice) => $invoice->paid_at->format('Y-m'));

        $summary = [];
        $cursor = $start->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $group = $grouped->get($cursor->format('Y-m'), collect());

            $summary[] = [
                'label' => $cursor->translatedFormat('F Y'),
                'count' => $group->count(),
                'total' => $group->sum('total_amount'),
            ];

            $cursor->addMonthNoOverflow();
        }

        return $summary;
    }
}

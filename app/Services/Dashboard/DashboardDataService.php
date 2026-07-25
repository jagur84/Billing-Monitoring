<?php

namespace App\Services\Dashboard;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardDataService
{
    /**
     * Shared by the full (admin/super-admin) dashboard and the finance-only dashboard.
     */
    public function financialData(): array
    {
        $month = now()->month;
        $year = now()->year;

        $monthlyRevenue = Invoice::where('status', 'paid')
            ->whereMonth('paid_at', $month)
            ->whereYear('paid_at', $year)
            ->sum('total_amount');

        $monthlyExpense = Expense::whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->sum('amount');

        $unpaidThisMonthCount = Invoice::where('period_month', $month)
            ->where('period_year', $year)
            ->whereIn('status', ['unpaid', 'overdue', 'partial'])
            ->count();

        $paidThisMonthCount = Invoice::where('period_month', $month)
            ->where('period_year', $year)
            ->where('status', 'paid')
            ->count();

        // Total outstanding across all periods (not just this month), so arrears carried
        // over from previous months are reflected too.
        $totalArrears = Invoice::whereIn('status', ['unpaid', 'overdue', 'partial'])
            ->withSum(['payments as paid_amount' => fn ($q) => $q->where('status', 'paid')], 'amount')
            ->get()
            ->sum(fn ($invoice) => max((float) $invoice->total_amount - (float) ($invoice->paid_amount ?? 0), 0));

        $recentPayments = Payment::with('invoice.customer')
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->take(10)
            ->get();

        $unpaidInvoices = Invoice::with('customer')
            ->whereIn('status', ['unpaid', 'overdue', 'partial'])
            ->withSum(['payments as paid_amount' => fn ($q) => $q->where('status', 'paid')], 'amount')
            ->orderBy('due_date')
            ->take(10)
            ->get()
            ->map(function ($invoice) {
                $invoice->remaining_amount = max((float) $invoice->total_amount - (float) ($invoice->paid_amount ?? 0), 0);

                return $invoice;
            });

        $revenueTrend = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = Carbon::now()->subMonths($monthsAgo);

            return [
                'label' => $date->translatedFormat('M Y'),
                'total' => (float) Invoice::where('status', 'paid')
                    ->whereMonth('paid_at', $date->month)
                    ->whereYear('paid_at', $date->year)
                    ->sum('total_amount'),
            ];
        });

        return [
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyExpense' => $monthlyExpense,
            'cashBalance' => $monthlyRevenue - $monthlyExpense,
            'unpaidThisMonthCount' => $unpaidThisMonthCount,
            'paidThisMonthCount' => $paidThisMonthCount,
            'totalArrears' => $totalArrears,
            'recentPayments' => $recentPayments,
            'unpaidInvoices' => $unpaidInvoices,
            'revenueTrendLabels' => $revenueTrend->pluck('label'),
            'revenueTrendData' => $revenueTrend->pluck('total'),
        ];
    }

    public function customerData(): array
    {
        $month = now()->month;
        $year = now()->year;

        return [
            'totalCustomers' => Customer::count(),
            'activeCustomers' => Customer::where('status', 'active')->count(),
            'newCustomersThisMonth' => Customer::whereMonth('created_at', $month)->whereYear('created_at', $year)->count(),
            'overdueInvoices' => Invoice::where('status', 'overdue')->count(),
        ];
    }

    public function technicianData(User $user): array
    {
        $myTickets = Ticket::with('customer')
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->orderByRaw("field(priority, 'high', 'medium', 'low')")
            ->orderBy('created_at')
            ->get();

        $resolvedThisMonth = Ticket::where('assigned_to', $user->id)
            ->whereIn('status', ['resolved', 'closed'])
            ->whereMonth('resolved_at', now()->month)
            ->whereYear('resolved_at', now()->year)
            ->count();

        $openTicketsTotal = Ticket::whereIn('status', ['open', 'in_progress'])->count();

        $lowStockItems = InventoryItem::whereColumn('stock_qty', '<=', 'min_stock')
            ->orderBy('stock_qty')
            ->take(10)
            ->get();

        return [
            'myTickets' => $myTickets,
            'myOpenCount' => $myTickets->whereIn('status', ['open', 'in_progress'])->count(),
            'myHighPriorityCount' => $myTickets->where('priority', 'high')->count(),
            'resolvedThisMonth' => $resolvedThisMonth,
            'openTicketsTotal' => $openTicketsTotal,
            'lowStockItems' => $lowStockItems,
        ];
    }
}

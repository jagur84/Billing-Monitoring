<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $paidSum = fn ($q) => $q->where('status', 'paid');

        $invoices = Invoice::with('customer')
            ->withSum(['payments as paid_amount' => $paidSum], 'amount')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->through(function ($invoice) {
                $paid = (float) ($invoice->paid_amount ?? 0);
                $invoice->total_paid = $paid;
                $invoice->remaining_amount = max((float) $invoice->total_amount - $paid, 0);

                return $invoice;
            });

        return response()->json($invoices);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'package', 'payments' => fn ($q) => $q->with('bankAccount')->orderByDesc('id')]);

        $paid = (float) $invoice->payments->where('status', 'paid')->sum('amount');
        $invoice->total_paid = $paid;
        $invoice->remaining_amount = max((float) $invoice->total_amount - $paid, 0);

        return response()->json($invoice);
    }
}

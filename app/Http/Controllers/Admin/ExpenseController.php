<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $forMonth = function ($query) use ($month) {
            [$year, $monthNum] = explode('-', $month);
            $query->whereYear('expense_date', $year)->whereMonth('expense_date', $monthNum);
        };

        $expenses = Expense::with('recordedBy')
            ->tap($forMonth)
            ->when($request->filled('search'), fn ($query) => $query->where('category', 'like', '%'.$request->string('search').'%'))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $monthlyTotal = Expense::tap($forMonth)->sum('amount');

        return view('admin.expenses.index', [
            'expenses' => $expenses,
            'month' => $month,
            'monthlyTotal' => $monthlyTotal,
        ]);
    }

    public function create()
    {
        return view('admin.expenses.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['recorded_by'] = $request->user()->id;

        Expense::create($data);

        return redirect()->route('expenses.index')->with('status', 'Pengeluaran berhasil dicatat.');
    }

    public function edit(Expense $expense)
    {
        return view('admin.expenses.edit', compact('expense'));
    }

    public function update(Request $request, Expense $expense)
    {
        $expense->update($this->validated($request));

        return redirect()->route('expenses.index')->with('status', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('status', 'Pengeluaran berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;

class ExpenseController extends Controller
{
    public function index()
    {
        return response()->json(
            Expense::with('recordedBy')->orderByDesc('expense_date')->paginate(25)
        );
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $bankAccounts = BankAccount::orderByDesc('is_active')->orderBy('bank_name')->get();

        return view('admin.settings.bank-accounts', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        BankAccount::create($data);

        return redirect()->route('settings.bank-accounts.index')->with('status', 'Rekening bank berhasil ditambahkan.');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $data = $this->validated($request);

        $bankAccount->update($data);

        return redirect()->route('settings.bank-accounts.index')->with('status', 'Rekening bank berhasil diperbarui.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return redirect()->route('settings.bank-accounts.index')->with('status', 'Rekening bank berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}

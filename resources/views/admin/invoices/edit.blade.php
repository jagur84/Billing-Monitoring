<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Ubah Tagihan" :subtitle="$invoice->invoice_number" />
    </x-slot>

    <x-panel>
        <form action="{{ route('invoices.update', $invoice) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <x-input-label for="amount" value="Biaya Langganan (Rp)" />
                    <x-text-input id="amount" name="amount" type="number" step="0.01" class="mt-1 block w-full" value="{{ old('amount', $invoice->amount) }}" required />
                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="tax_amount" value="Pajak (Rp)" />
                    <x-text-input id="tax_amount" name="tax_amount" type="number" step="0.01" class="mt-1 block w-full" value="{{ old('tax_amount', $invoice->tax_amount) }}" required />
                    <x-input-error :messages="$errors->get('tax_amount')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="discount_amount" value="Diskon (Rp)" />
                    <x-text-input id="discount_amount" name="discount_amount" type="number" step="0.01" class="mt-1 block w-full" value="{{ old('discount_amount', $invoice->discount_amount) }}" required />
                    <x-input-error :messages="$errors->get('discount_amount')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="due_date" value="Jatuh Tempo" />
                    <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}" required />
                    <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="notes" value="Catatan" />
                    <x-textarea-input id="notes" name="notes" rows="3" class="mt-1 block w-full">{{ old('notes', $invoice->notes) }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('invoices.show', $invoice) }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Simpan Perubahan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>

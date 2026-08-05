<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$invoice->invoice_number" :subtitle="$invoice->customer->name">
            <x-slot name="actions">
                <a href="{{ route('invoices.print', $invoice) }}" target="_blank">
                    <x-secondary-button type="button">Print Invoice</x-secondary-button>
                </a>
                @if ($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                    <a href="{{ route('invoices.pay', $invoice) }}">
                        <x-primary-button type="button">Bayar via Tripay</x-primary-button>
                    </a>
                    <x-primary-button type="button" x-data="" x-on:click="$dispatch('open-modal', 'record-payment')">Catat Pembayaran</x-primary-button>
                    <form action="{{ route('invoices.send-email-reminder', $invoice) }}" method="POST" onsubmit="return confirm('Kirim pengingat email ke pelanggan ini sekarang?');">
                        @csrf
                        <x-secondary-button type="submit" :disabled="! $invoice->customer->email">Kirim Email Reminder</x-secondary-button>
                    </form>
                    <form action="{{ route('invoices.send-whatsapp-reminder', $invoice) }}" method="POST" onsubmit="return confirm('Kirim pengingat WhatsApp ke pelanggan ini sekarang?');">
                        @csrf
                        <x-secondary-button type="submit" :disabled="! $invoice->customer->phone">Kirim WhatsApp Reminder</x-secondary-button>
                    </form>
                @endif
                @if ($invoice->status === 'unpaid')
                    <a href="{{ route('invoices.edit', $invoice) }}"><x-secondary-button type="button">Ubah</x-secondary-button></a>
                @endif
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <x-panel title="Rincian Tagihan" class="lg:col-span-2">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500">Periode</dt><dd class="font-medium text-gray-900">{{ $invoice->period_month }}/{{ $invoice->period_year }}</dd></div>
                    <div><dt class="text-gray-500">Jatuh Tempo</dt><dd class="font-medium text-gray-900">{{ $invoice->due_date->format('d M Y') }}</dd></div>
                    <div><dt class="text-gray-500">Paket</dt><dd class="font-medium text-gray-900">{{ $invoice->package_name_snapshot ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Status</dt>
                        <dd>
                            <x-badge :color="match($invoice->status) { 'paid' => 'green', 'partial' => 'amber', 'overdue' => 'red', 'cancelled' => 'gray', default => 'amber' }">
                                {{ match($invoice->status) { 'unpaid' => 'Belum Bayar', 'paid' => 'Lunas', 'partial' => 'Cicilan', 'overdue' => 'Jatuh Tempo', default => 'Dibatalkan' } }}
                            </x-badge>
                        </dd>
                    </div>
                </dl>

                <div class="mt-6 space-y-2 border-t border-gray-100 pt-4 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Biaya Langganan</span><span>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Pajak</span><span>Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Diskon</span><span>- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</span></div>
                    @if ($invoice->carry_over_amount > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-500" title="{{ $invoice->carry_over_note }}">Sisa Tagihan Sebelumnya</span>
                            <span>Rp {{ number_format($invoice->carry_over_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-semibold text-gray-900"><span>Total</span><span>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span></div>
                    @if ($totalPaid > 0 && $invoice->status !== 'paid')
                        <div class="flex justify-between text-emerald-600"><span>Sudah Dibayar</span><span>Rp {{ number_format($totalPaid, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between font-semibold text-rose-600"><span>Sisa Tagihan</span><span>Rp {{ number_format($remaining, 0, ',', '.') }}</span></div>
                    @endif
                </div>
            </x-panel>

            <x-panel title="Pelanggan">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Nama</dt><dd class="font-medium text-gray-900">
                        <a href="{{ route('customers.show', $invoice->customer) }}" class="text-indigo-600 hover:text-indigo-800">{{ $invoice->customer->name }}</a>
                    </dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Kode</dt><dd class="text-gray-900">{{ $invoice->customer->customer_code }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Telepon</dt><dd class="text-gray-900">{{ $invoice->customer->phone ?? '-' }}</dd></div>
                </dl>
            </x-panel>
        </div>

        <x-panel title="Riwayat Pembayaran">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-2 pr-4">Tanggal</th>
                            <th class="py-2 pr-4">Metode</th>
                            <th class="py-2 pr-4">Referensi</th>
                            <th class="py-2 pr-4">Jumlah</th>
                            <th class="py-2 pr-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($invoice->payments as $payment)
                            <tr>
                                <td class="py-2 pr-4 text-gray-600">{{ optional($payment->paid_at)->format('d M Y H:i') ?? '-' }}</td>
                                <td class="py-2 pr-4 text-gray-600">
                                    {{ match(true) {
                                        $payment->gateway === 'manual' && $payment->gateway_payment_method === 'cash' => 'Tunai',
                                        $payment->gateway === 'manual' && $payment->gateway_payment_method === 'transfer' => 'Transfer Bank'.($payment->bankAccount ? " ({$payment->bankAccount->bank_name})" : ''),
                                        default => ucfirst($payment->gateway).($payment->gateway_payment_method ? " ({$payment->gateway_payment_method})" : ''),
                                    } }}
                                </td>
                                <td class="py-2 pr-4 text-gray-600">{{ $payment->gateway_reference ?? '-' }}</td>
                                <td class="py-2 pr-4 text-gray-600">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                <td class="py-2 pr-4">
                                    <x-badge :color="match($payment->status) { 'paid' => 'green', 'failed', 'expired' => 'red', default => 'amber' }">
                                        {{ ucfirst($payment->status) }}
                                    </x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-500">Belum ada pembayaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>
    </div>

    @if ($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
        <x-modal name="record-payment" maxWidth="md">
            <form method="POST" action="{{ route('invoices.mark-paid', $invoice) }}" class="p-6" x-data="{ method: '{{ old('method', 'cash') }}' }">
                @csrf

                <h2 class="text-lg font-medium text-gray-900">Catat Pembayaran</h2>
                <p class="mt-1 text-sm text-gray-500">Sisa tagihan saat ini: <span class="font-semibold text-gray-900">Rp {{ number_format($remaining, 0, ',', '.') }}</span></p>

                <div class="mt-6 space-y-4">
                    <div>
                        <x-input-label for="payment_amount" value="Jumlah Bayar" />
                        <x-text-input id="payment_amount" name="amount" type="number" step="0.01" min="0.01" max="{{ $remaining }}"
                                      class="mt-1 block w-full" value="{{ old('amount', $remaining) }}" required />
                        <p class="mt-1 text-xs text-gray-500">Isi kurang dari sisa tagihan untuk mencatat pembayaran cicilan.</p>
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="payment_method" value="Metode Pembayaran" />
                        <x-select-input id="payment_method" name="method" class="mt-1 block w-full" x-model="method">
                            <option value="cash">Tunai (Cash)</option>
                            <option value="transfer">Transfer Bank</option>
                        </x-select-input>
                    </div>
                    <div x-show="method === 'transfer'" x-cloak>
                        <x-input-label for="payment_bank_account" value="Rekening Tujuan" />
                        <x-select-input id="payment_bank_account" name="bank_account_id" class="mt-1 block w-full">
                            <option value="">Pilih rekening...</option>
                            @forelse ($bankAccounts as $account)
                                <option value="{{ $account->id }}" @selected((string) old('bank_account_id') === (string) $account->id)>
                                    {{ $account->bank_name }} - {{ $account->account_number }} @if($account->account_holder) (a.n. {{ $account->account_holder }}) @endif
                                </option>
                            @empty
                                <option value="" disabled>Belum ada rekening aktif — tambahkan di Pengaturan → Rekening Bank</option>
                            @endforelse
                        </x-select-input>
                        <x-input-error :messages="$errors->get('bank_account_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="payment_note" value="Catatan (opsional)" />
                        <textarea id="payment_note" name="note" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('note') }}</textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">Kembali</x-secondary-button>
                    <x-primary-button type="submit">Simpan Pembayaran</x-primary-button>
                </div>
            </form>
        </x-modal>
    @endif
</x-app-layout>

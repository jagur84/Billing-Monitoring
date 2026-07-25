<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\GenieacsDeviceController;
use App\Http\Controllers\Admin\InventoryItemController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\MikrotikRouterController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PppoeSecretController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WhatsAppSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPaymentController;
use App\Http\Controllers\TripayWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::post('/webhooks/tripay', [TripayWebhookController::class, 'handle'])->name('tripay.webhook');

Route::middleware('signed')->group(function () {
    Route::get('/pay/{invoice}', [PublicPaymentController::class, 'show'])->name('public.invoice.pay');
    Route::get('/pay/{invoice}/checkout', [PublicPaymentController::class, 'checkout'])->name('public.invoice.checkout');
    Route::get('/pay/{invoice}/pdf', [PublicPaymentController::class, 'pdf'])->name('public.invoice.pdf');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('permission:packages')->group(function () {
        Route::resource('packages', PackageController::class);
    });

    Route::middleware('permission:customers')->group(function () {
        Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
        Route::resource('customers', CustomerController::class);
        Route::post('/customers/{customer}/isolate', [CustomerController::class, 'isolate'])->name('customers.isolate');
        Route::post('/customers/{customer}/restore', [CustomerController::class, 'restore'])->name('customers.restore');
    });

    Route::middleware('permission:invoices')->group(function () {
        Route::resource('invoices', InvoiceController::class);
        Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::get('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::post('/invoices/{invoice}/send-email-reminder', [InvoiceController::class, 'sendEmailReminder'])->name('invoices.send-email-reminder');
        Route::post('/invoices/{invoice}/send-whatsapp-reminder', [InvoiceController::class, 'sendWhatsappReminder'])->name('invoices.send-whatsapp-reminder');
    });

    Route::middleware('permission:payments')->group(function () {
        Route::resource('payments', PaymentController::class)->only(['index']);
    });

    Route::middleware('permission:reports')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue');
        Route::get('/revenue/export', [ReportController::class, 'exportRevenue'])->name('revenue.export');
    });

    Route::middleware('permission:mikrotik')->group(function () {
        Route::resource('mikrotik-routers', MikrotikRouterController::class)->except(['show']);
        Route::get('/mikrotik-routers/{mikrotikRouter}/pppoe', [MikrotikRouterController::class, 'pppoe'])->name('mikrotik-routers.pppoe');
        Route::get('/mikrotik-routers/{mikrotikRouter}/pppoe/data', [MikrotikRouterController::class, 'pppoeData'])->name('mikrotik-routers.pppoe.data');
        Route::get('/mikrotik-routers/{mikrotikRouter}/routing', [MikrotikRouterController::class, 'routing'])->name('mikrotik-routers.routing');
        Route::get('/mikrotik-routers/{mikrotikRouter}/routing/data', [MikrotikRouterController::class, 'routingData'])->name('mikrotik-routers.routing.data');
        Route::get('/mikrotik-routers/{mikrotikRouter}/interfaces', [MikrotikRouterController::class, 'interfaces'])->name('mikrotik-routers.interfaces');
        Route::get('/mikrotik-routers/{mikrotikRouter}/interfaces/data', [MikrotikRouterController::class, 'interfacesData'])->name('mikrotik-routers.interfaces.data');

        Route::prefix('mikrotik-routers/{mikrotikRouter}/secrets')->name('mikrotik-routers.secrets.')->group(function () {
            Route::get('/', [PppoeSecretController::class, 'index'])->name('index');
            Route::get('/data', [PppoeSecretController::class, 'data'])->name('data');
            Route::post('/', [PppoeSecretController::class, 'store'])->name('store');
            Route::put('/{secretId}', [PppoeSecretController::class, 'update'])->name('update');
            Route::delete('/{secretId}', [PppoeSecretController::class, 'destroy'])->name('destroy');
            Route::post('/{secretId}/toggle', [PppoeSecretController::class, 'toggle'])->name('toggle');
        });
    });

    Route::middleware('permission:genieacs')->group(function () {
        Route::resource('genieacs-devices', GenieacsDeviceController::class);
        Route::post('/genieacs-devices/{genieacsDevice}/reboot', [GenieacsDeviceController::class, 'reboot'])->name('genieacs-devices.reboot');
    });

    Route::middleware('permission:tickets')->group(function () {
        Route::resource('tickets', TicketController::class);
        Route::post('/tickets/{ticket}/logs', [TicketController::class, 'addLog'])->name('tickets.logs.store');
    });

    Route::middleware('permission:inventory')->group(function () {
        Route::resource('inventory-items', InventoryItemController::class);
        Route::post('/inventory-items/{inventoryItem}/transactions', [InventoryItemController::class, 'addTransaction'])->name('inventory-items.transactions.store');
    });

    Route::middleware('permission:expenses')->group(function () {
        Route::resource('expenses', ExpenseController::class)->except(['show']);
    });

    Route::middleware('role:super-admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);

        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::delete('/activity-logs', [ActivityLogController::class, 'clear'])->name('activity-logs.clear');

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/general', [SettingsController::class, 'general'])->name('general');
            Route::put('/general', [SettingsController::class, 'updateGeneral'])->name('general.update');

            Route::get('/smtp', [SettingsController::class, 'smtp'])->name('smtp');
            Route::put('/smtp', [SettingsController::class, 'updateSmtp'])->name('smtp.update');
            Route::post('/smtp/test', [SettingsController::class, 'testSmtp'])->name('smtp.test');

            Route::get('/tripay', [SettingsController::class, 'tripay'])->name('tripay');
            Route::put('/tripay', [SettingsController::class, 'updateTripay'])->name('tripay.update');
            Route::post('/tripay/test', [SettingsController::class, 'testTripay'])->name('tripay.test');

            Route::get('/whatsapp', [WhatsAppSettingsController::class, 'show'])->name('whatsapp');
            Route::get('/whatsapp/qr', [WhatsAppSettingsController::class, 'qr'])->name('whatsapp.qr');
            Route::get('/whatsapp/status', [WhatsAppSettingsController::class, 'status'])->name('whatsapp.status');

            Route::get('/billing', [SettingsController::class, 'billing'])->name('billing');
            Route::put('/billing', [SettingsController::class, 'updateBilling'])->name('billing.update');

            Route::prefix('bank-accounts')->name('bank-accounts.')->group(function () {
                Route::get('/', [BankAccountController::class, 'index'])->name('index');
                Route::post('/', [BankAccountController::class, 'store'])->name('store');
                Route::put('/{bankAccount}', [BankAccountController::class, 'update'])->name('update');
                Route::delete('/{bankAccount}', [BankAccountController::class, 'destroy'])->name('destroy');
            });

            Route::get('/templates', [SettingsController::class, 'templates'])->name('templates');
            Route::put('/templates', [SettingsController::class, 'updateTemplates'])->name('templates.update');
        });
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

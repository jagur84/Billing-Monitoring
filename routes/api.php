<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InventoryItemController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/dashboard', DashboardController::class);

    Route::middleware('permission:customers')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    });

    Route::middleware('permission:invoices')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    });

    Route::middleware('permission:payments')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index']);
    });

    Route::middleware('permission:tickets')->group(function () {
        Route::get('/tickets', [TicketController::class, 'index']);
        Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
    });

    Route::middleware('permission:packages')->group(function () {
        Route::get('/packages', [PackageController::class, 'index']);
    });

    Route::middleware('permission:inventory')->group(function () {
        Route::get('/inventory-items', [InventoryItemController::class, 'index']);
    });

    Route::middleware('permission:expenses')->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index']);
    });

    Route::middleware('permission:reports')->group(function () {
        Route::get('/reports/revenue', [ReportController::class, 'revenue']);
    });
});

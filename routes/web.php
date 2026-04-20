<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GasController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TransactionRecordController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::middleware('pos.auth')->group(function () {
        Route::redirect('/', '/sales');

        Route::get('gas', [GasController::class, 'index'])->name('gas.index');
        Route::post('gas/input', [GasController::class, 'storeInput'])->name('gas.input.store');
        Route::post('gas/sale', [GasController::class, 'storeSale'])->name('gas.sale.store');
        Route::get('restock', [RestockController::class, 'index'])->name('restock.index');
        Route::post('restock', [RestockController::class, 'store'])->name('restock.store');
        Route::get('warehouse-invoices/{sale}/receipt', [WarehouseInvoiceController::class, 'receipt'])->name('warehouse-invoices.receipt');
        Route::patch('warehouse-invoices/{sale}/payment-status', [WarehouseInvoiceController::class, 'updatePaymentStatus'])->name('warehouse-invoices.update-payment-status');
        Route::resource('warehouse-invoices', WarehouseInvoiceController::class)
            ->parameters(['warehouse-invoices' => 'sale'])
            ->only(['index', 'store', 'show']);
        Route::resource('customers', CustomerController::class)->except(['show', 'create']);
        Route::get('sales/export/recent-transactions', [SaleController::class, 'exportRecentTransactions'])->name('sales.export-recent-transactions');
        Route::get('sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
        Route::resource('sales', SaleController::class)->only(['index', 'store', 'show']);

        Route::middleware('pos.developer')->group(function () {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');
            Route::resource('users', UserController::class)->except(['show', 'create']);
            Route::resource('categories', CategoryController::class)->except(['show', 'create']);
            Route::get('products/export/excel', [ProductController::class, 'export'])->name('products.export');
            Route::resource('products', ProductController::class)->except(['show', 'create']);
            Route::get('warehouse', [WarehouseController::class, 'index'])->name('warehouse.index');
            Route::post('warehouse', [WarehouseController::class, 'store'])->name('warehouse.store');
            Route::get('transaction-records/export/excel', [TransactionRecordController::class, 'export'])->name('transaction-records.export');
            Route::resource('transaction-records', TransactionRecordController::class)->except(['show', 'create']);
        });
    });
});

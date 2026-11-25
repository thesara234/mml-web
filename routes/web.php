<?php

use App\Http\Controllers\Portal;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\Shared\LookupController;
use App\Http\Controllers\Staff;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website (replaces the old mmltonga.com pages)
|--------------------------------------------------------------------------
*/
Route::controller(PublicSiteController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/rates', 'rates')->name('rates');
    Route::get('/shop', 'shop')->name('shop');
    Route::get('/locations', 'locations')->name('locations');
    Route::match(['get', 'post'], '/track', 'track')->name('track')->middleware('throttle:20,1');
});
Route::post('/quote', [LookupController::class, 'quote'])->name('public.quote')->middleware('throttle:60,1');

/*
|--------------------------------------------------------------------------
| Staff console  (/staff)  — office & store staff
|--------------------------------------------------------------------------
*/
Route::prefix('staff')->name('staff.')->group(function () {
    Route::middleware('guest:staff')->group(function () {
        Route::get('login', [Staff\AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [Staff\AuthController::class, 'login'])->middleware('throttle:10,1');
    });

    Route::middleware('auth:staff')->group(function () {
        Route::post('logout', [Staff\AuthController::class, 'logout'])->name('logout');
        Route::get('/', [Staff\DashboardController::class, 'index'])->name('dashboard');

        // Send money for a walk-in customer
        Route::get('send', [Staff\SendController::class, 'create'])->name('send');
        Route::post('send', [Staff\SendController::class, 'store'])->name('send.store');

        // Payout desk (money and/or shopping)
        Route::get('payout', [Staff\PayoutController::class, 'index'])->name('payout');
        Route::post('payout/{transaction}', [Staff\PayoutController::class, 'complete'])->name('payout.complete');

        // Transactions
        Route::get('transactions', [Staff\TransactionController::class, 'index'])->name('transactions.index');
        Route::get('transactions/{transaction}', [Staff\TransactionController::class, 'show'])->name('transactions.show');
        Route::post('transactions/{transaction}/confirm-payment', [Staff\TransactionController::class, 'confirmPayment'])->name('transactions.confirm-payment');
        Route::post('transactions/{transaction}/hold', [Staff\TransactionController::class, 'hold'])->name('transactions.hold');
        Route::post('transactions/{transaction}/release', [Staff\TransactionController::class, 'release'])->name('transactions.release');
        Route::post('transactions/{transaction}/cancel', [Staff\TransactionController::class, 'cancel'])->name('transactions.cancel');
        Route::post('transactions/{transaction}/refund', [Staff\TransactionController::class, 'refund'])->name('transactions.refund');
        Route::get('transactions/{transaction}/receipt', [Staff\TransactionController::class, 'receipt'])->name('transactions.receipt');

        // Customers (new customers can only be created here or in the customer portal)
        Route::get('customers', [Staff\CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/create', [Staff\CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [Staff\CustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/{customer}', [Staff\CustomerController::class, 'show'])->name('customers.show');
        Route::get('customers/{customer}/edit', [Staff\CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [Staff\CustomerController::class, 'update'])->name('customers.update');
        Route::post('customers/{customer}/verify', [Staff\CustomerController::class, 'verify'])->name('customers.verify');
        Route::post('customers/{customer}/receivers', [Staff\CustomerController::class, 'linkReceiver'])->name('customers.receivers.link');

        // Chat with customers
        Route::get('messages', [Staff\ChatController::class, 'index'])->name('messages.index');
        Route::get('messages/{conversation}', [Staff\ChatController::class, 'show'])->name('messages.show');
        Route::post('customers/{customer}/chat', [Staff\ChatController::class, 'start'])->name('messages.start');

        // JSON helpers used by the console's interactive screens
        Route::prefix('api')->name('api.')->group(function () {
            Route::post('quote', [LookupController::class, 'quote'])->name('quote');
            Route::get('stores/{office}/items', [LookupController::class, 'storeItems'])->name('store-items');
            Route::get('customers/search', [Staff\CustomerController::class, 'search'])->name('customers.search');
            Route::get('customers/{customer}/receivers', [Staff\CustomerController::class, 'receiversJson'])->name('customers.receivers');
            Route::get('messages/{conversation}', [Staff\ChatController::class, 'poll'])->name('messages.poll');
            Route::post('messages/{conversation}', [Staff\ChatController::class, 'send'])->name('messages.send');
            Route::get('unread', [Staff\ChatController::class, 'unread'])->name('unread');
        });

        // Settings — managers & admins
        Route::middleware('staff.manager')->prefix('settings')->name('settings.')->group(function () {
            Route::get('rates', [Staff\Settings\RateController::class, 'index'])->name('rates');
            Route::post('rates', [Staff\Settings\RateController::class, 'store'])->name('rates.store');
            Route::get('fees', [Staff\Settings\FeeController::class, 'index'])->name('fees');
            Route::post('fees', [Staff\Settings\FeeController::class, 'store'])->name('fees.store');
            Route::delete('fees/{feeTier}', [Staff\Settings\FeeController::class, 'destroy'])->name('fees.destroy');
            Route::get('stock', [Staff\Settings\StockController::class, 'index'])->name('stock');
            Route::post('stock', [Staff\Settings\StockController::class, 'store'])->name('stock.store');
            Route::put('stock/{stockItem}', [Staff\Settings\StockController::class, 'update'])->name('stock.update');
            Route::get('offices', [Staff\Settings\OfficeController::class, 'index'])->name('offices');
            Route::post('offices', [Staff\Settings\OfficeController::class, 'store'])->name('offices.store');
            Route::put('offices/{office}', [Staff\Settings\OfficeController::class, 'update'])->name('offices.update');
            Route::get('staff', [Staff\Settings\StaffController::class, 'index'])->name('staff');
            Route::post('staff', [Staff\Settings\StaffController::class, 'store'])->name('staff.store');
            Route::put('staff/{staffUser}', [Staff\Settings\StaffController::class, 'update'])->name('staff.update');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Customer web portal (/portal)
|--------------------------------------------------------------------------
| Sender customers register new receivers here, send money, track transfers and chat.
*/
Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('login', [Portal\AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [Portal\AuthController::class, 'login'])->middleware('throttle:10,1');
    });

    Route::middleware('auth:customer')->group(function () {
        Route::post('logout', [Portal\AuthController::class, 'logout'])->name('logout');
        Route::get('/', [Portal\DashboardController::class, 'index'])->name('dashboard');

        Route::get('send', [Portal\SendController::class, 'create'])->name('send');
        Route::post('send', [Portal\SendController::class, 'store'])->name('send.store');

        Route::get('receivers', [Portal\ReceiverController::class, 'index'])->name('receivers.index');
        Route::get('receivers/create', [Portal\ReceiverController::class, 'create'])->name('receivers.create');
        Route::post('receivers', [Portal\ReceiverController::class, 'store'])->name('receivers.store');
        Route::post('receivers/link', [Portal\ReceiverController::class, 'link'])->name('receivers.link');

        Route::get('transfers', [Portal\TransferController::class, 'index'])->name('transfers.index');
        Route::get('transfers/{transaction}', [Portal\TransferController::class, 'show'])->name('transfers.show');
        Route::post('transfers/{transaction}/cancel', [Portal\TransferController::class, 'cancel'])->name('transfers.cancel');

        Route::get('chat', [Portal\ChatController::class, 'index'])->name('chat');
        Route::get('profile', [Portal\DashboardController::class, 'profile'])->name('profile');

        Route::prefix('api')->name('api.')->group(function () {
            Route::post('quote', [LookupController::class, 'quote'])->name('quote');
            Route::get('stores/{office}/items', [LookupController::class, 'storeItems'])->name('store-items');
            Route::get('receivers/search', [Portal\ReceiverController::class, 'search'])->name('receivers.search');
            Route::get('chat', [Portal\ChatController::class, 'poll'])->name('chat.poll');
            Route::post('chat', [Portal\ChatController::class, 'send'])->name('chat.send');
        });
    });
});

<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API  —  /api/v1/*   (Android customer app, Sanctum bearer tokens)
|--------------------------------------------------------------------------
| There is deliberately no "register" endpoint: new customers are created on the
| web app only (by the sender office, or by a sender customer in the portal).
*/

Route::post('login', [Api\AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [Api\AuthController::class, 'logout']);
    Route::get('me', [Api\AuthController::class, 'me']);

    // Reference data for the app (offices, rates, purposes …) in one call
    Route::get('bootstrap', [Api\LookupController::class, 'bootstrap']);
    Route::post('quote', [Api\LookupController::class, 'quote']);
    Route::get('stores', [Api\LookupController::class, 'stores']);
    Route::get('stores/{office}/items', [Api\LookupController::class, 'storeItems']);

    // Receivers already in the system
    Route::get('receivers', [Api\ReceiverController::class, 'index']);
    Route::get('receivers/search', [Api\ReceiverController::class, 'search']);
    Route::post('receivers/{customer}', [Api\ReceiverController::class, 'link']);

    // Send money  (action = "draft" → save for later, "send" → process)
    Route::get('transactions', [Api\TransactionController::class, 'index']);
    Route::post('transactions', [Api\TransactionController::class, 'store']);
    Route::get('transactions/{transaction}', [Api\TransactionController::class, 'show']);
    Route::put('transactions/{transaction}', [Api\TransactionController::class, 'update']);
    Route::post('transactions/{transaction}/cancel', [Api\TransactionController::class, 'cancel']);
    Route::delete('transactions/{transaction}', [Api\TransactionController::class, 'destroyDraft']);

    // Receive money / receive shopping (collected at an MML office)
    Route::get('incoming', [Api\TransactionController::class, 'incoming']);

    // Chat with MML staff
    Route::get('chat/messages', [Api\ChatController::class, 'index']);
    Route::post('chat/messages', [Api\ChatController::class, 'store']);
});

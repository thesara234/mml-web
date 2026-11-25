<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user('customer');
        $with = ['senderCustomer', 'receiverCustomer', 'receiverOffice', 'senderOffice', 'items'];

        return view('portal.dashboard', [
            'me' => $me,
            'rates' => ExchangeRate::board(),
            'recent' => Transaction::with($with)->where('sender_customer_id', $me->id)->where('status', '!=', 'draft')->latest('transaction_date')->limit(5)->get(),
            'drafts' => Transaction::with($with)->where('sender_customer_id', $me->id)->where('status', 'draft')->latest('updated_at')->limit(3)->get(),
            'incomingMoney' => Transaction::where('receiver_customer_id', $me->id)->where('transfer_type', 'money')->where('status', Transaction::READY)->count(),
            'incomingGoods' => Transaction::where('receiver_customer_id', $me->id)->where('transfer_type', 'goods')->where('status', Transaction::READY)->count(),
            'receiverCount' => $me->receivers()->count(),
        ]);
    }

    public function profile(Request $request)
    {
        return view('portal.profile', ['me' => $request->user('customer')->load('createdByOffice')]);
    }
}

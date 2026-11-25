<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ExchangeRate;
use App\Models\Message;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $staff = $request->user('staff');
        $office = $staff->office;
        $oid = $office->id;

        $stats = [
            'awaiting_payment' => Transaction::where('sender_office_id', $oid)->where('status', Transaction::AWAITING_PAYMENT)->count(),
            'ready_here' => Transaction::where('receiver_office_id', $oid)->where('status', Transaction::READY)->count(),
            'on_hold' => Transaction::forOffice($oid)->where('status', Transaction::ON_HOLD)->count(),
            'completed_today' => Transaction::forOffice($oid)->where('status', Transaction::COMPLETED)->whereDate('completed_at', today())->count(),
            'sent_today_value' => (float) Transaction::where('sender_office_id', $oid)->where('payment_status', 'paid')->whereDate('paid_at', today())->sum('transfer_amount'),
            'new_customers_week' => Customer::where('created_at', '>=', now()->subDays(7))->count(),
            'unread_messages' => Message::where('sender_type', 'customer')->whereNull('read_at')->count(),
        ];

        // last 14 days of paid transfers handled by this office (for the mini chart)
        $from = now()->subDays(13)->startOfDay();
        $daily = Transaction::forOffice($oid)->where('payment_status', 'paid')->where('paid_at', '>=', $from)
            ->get(['paid_at', 'transfer_amount'])
            ->groupBy(fn ($t) => $t->paid_at->toDateString());
        $trend = collect(range(0, 13))->map(function ($i) use ($from, $daily) {
            $d = $from->copy()->addDays($i);
            $rows = $daily->get($d->toDateString(), collect());

            return ['date' => $d, 'count' => $rows->count(), 'value' => (float) $rows->sum('transfer_amount')];
        });

        $with = ['senderCustomer', 'receiverCustomer', 'senderOffice', 'receiverOffice'];
        $awaiting = Transaction::with($with)->where('sender_office_id', $oid)->where('status', Transaction::AWAITING_PAYMENT)->latest('transaction_date')->limit(6)->get();
        $ready = Transaction::with($with)->where('receiver_office_id', $oid)->where('status', Transaction::READY)->latest('paid_at')->limit(6)->get();
        $recent = Transaction::with($with)->forOffice($oid)->visibleToStaff()->latest('updated_at')->limit(8)->get();

        return view('staff.dashboard', [
            'staff' => $staff, 'office' => $office, 'stats' => $stats, 'trend' => $trend,
            'awaiting' => $awaiting, 'ready' => $ready, 'recent' => $recent, 'rates' => ExchangeRate::board(),
        ]);
    }
}

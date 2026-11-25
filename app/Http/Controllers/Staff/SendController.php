<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRules;
use App\Models\Customer;
use App\Models\ExchangeRate;
use App\Models\Office;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;

/** Counter screen: staff send money / shopping for a walk-in sender. */
class SendController extends Controller
{
    public function create(Request $request)
    {
        $staff = $request->user('staff');
        $sender = $request->filled('sender') ? Customer::find($request->integer('sender')) : null;

        return view('staff.send', [
            'staff' => $staff,
            'sender' => $sender,
            'sendingOffices' => Office::active()->sending()->orderBy('name')->get(),
            'receivingOffices' => Office::active()->receiving()->orderBy('name')->get(),
            'corridors' => ExchangeRate::board(),
        ]);
    }

    public function store(Request $request, TransactionService $service)
    {
        $data = $request->validate(TransferRules::rules(staff: true) + [
            'sender_customer_id' => ['required', 'integer', 'exists:customers,id'],
            'paid_now' => ['nullable', 'boolean'],
        ]);

        $sender = Customer::findOrFail($data['sender_customer_id']);
        abort_unless($sender->is_active, 422, 'This customer account is inactive.');

        $t = $service->createAtCounter($request->user('staff'), $sender, $data, (bool) ($data['paid_now'] ?? false));

        // keep the receiver in the sender's address book for next time
        if ($t->receiver_customer_id) {
            $sender->receivers()->syncWithoutDetaching([$t->receiver_customer_id => ['preferred_office_id' => $t->receiver_office_id]]);
        }

        $url = route('staff.transactions.show', $t);
        session()->flash('success', "Transfer {$t->reference_no} created — ".$t->fresh()->statusLabel().'.');

        return $request->expectsJson() ? response()->json(['redirect' => $url, 'reference_no' => $t->reference_no]) : redirect($url);
    }
}

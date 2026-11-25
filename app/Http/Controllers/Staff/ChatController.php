<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        return $this->inbox($request, null);
    }

    public function show(Request $request, Conversation $conversation)
    {
        return $this->inbox($request, $conversation);
    }

    private function inbox(Request $request, ?Conversation $active)
    {
        $conversations = Conversation::with(['customer', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_type', 'customer')->whereNull('read_at')])
            ->when($request->filled('q'), fn ($q) => $q->whereHas('customer', fn ($c) => $c->search($request->query('q'))))
            ->orderByDesc('last_message_at')->limit(60)->get();

        $active ??= $conversations->first();
        $active?->load('customer');

        return view('staff.messages', compact('conversations', 'active'));
    }

    /** Open (or create) a chat with a customer from their profile. */
    public function start(Customer $customer)
    {
        return redirect()->route('staff.messages.show', Conversation::forCustomer($customer));
    }

    public function poll(Request $request, Conversation $conversation)
    {
        $after = (int) $request->query('after_id', 0);
        $messages = $conversation->messages()->with('staff', 'conversation.customer')->where('id', '>', $after)->orderBy('id')->limit(200)->get();
        $conversation->messages()->where('sender_type', 'customer')->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['messages' => $messages->map->toChatArray()->values()]);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $body = $request->validate(['body' => ['required', 'string', 'max:2000']])['body'];
        $message = $conversation->messages()->create([
            'sender_type' => 'staff',
            'staff_user_id' => $request->user('staff')->id,
            'body' => trim($body),
        ]);
        $conversation->update(['last_message_at' => now(), 'status' => 'open']);

        return response()->json($message->load('staff', 'conversation.customer')->toChatArray(), 201);
    }

    public function unread()
    {
        return response()->json(['count' => Message::where('sender_type', 'customer')->whereNull('read_at')->count()]);
    }
}

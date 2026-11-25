<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        return view('portal.chat', ['conversation' => Conversation::forCustomer($request->user('customer'))]);
    }

    public function poll(Request $request)
    {
        $c = Conversation::forCustomer($request->user('customer'));
        $messages = $c->messages()->with('staff', 'conversation.customer')->where('id', '>', (int) $request->query('after_id', 0))->orderBy('id')->limit(200)->get();
        $c->messages()->where('sender_type', 'staff')->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['messages' => $messages->map->toChatArray()->values()]);
    }

    public function send(Request $request)
    {
        $body = $request->validate(['body' => ['required', 'string', 'max:2000']])['body'];
        $c = Conversation::forCustomer($request->user('customer'));
        $m = $c->messages()->create(['sender_type' => 'customer', 'body' => trim($body)]);
        $c->update(['last_message_at' => now(), 'status' => 'open']);

        return response()->json($m->load('conversation.customer')->toChatArray(), 201);
    }
}

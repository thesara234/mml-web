<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

/** Customer ⇄ MML staff chat. The app polls ?after_id= every few seconds. */
class ChatController extends Controller
{
    public function index(Request $request)
    {
        $conversation = Conversation::forCustomer($request->user());
        $afterId = (int) $request->query('after_id', 0);

        $messages = $conversation->messages()->with('staff', 'conversation.customer')
            ->where('id', '>', $afterId)->orderBy('id')->limit(200)->get();

        // staff messages are now read by the customer
        $conversation->messages()->where('sender_type', 'staff')->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $messages->map->toChatArray()->values(),
            'poll_ms' => config('mml.chat_poll_ms'),
        ]);
    }

    public function store(Request $request)
    {
        $body = $request->validate(['body' => ['required', 'string', 'max:2000']])['body'];
        $conversation = Conversation::forCustomer($request->user());

        $message = $conversation->messages()->create(['sender_type' => 'customer', 'body' => trim($body)]);
        $conversation->update(['last_message_at' => now(), 'status' => 'open']);

        return response()->json($message->load('conversation.customer')->toChatArray(), 201);
    }
}

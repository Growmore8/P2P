<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ChatMail;
use App\Models\ActivityLog;
use App\Models\ChatConversation;
use App\Support\Notify;
use Illuminate\Http\Request;

/** Admin live-chat inbox. */
class ChatController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');
        $chats = ChatConversation::with(['user:id,name,email', 'messages' => fn ($q) => $q->latest('id')->limit(1)])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
            ->orderByDesc('unread_admin')->latest('last_message_at')->paginate(25)->withQueryString();

        return view('admin.chats.index', ['chats' => $chats, 'status' => $status]);
    }

    public function show(ChatConversation $chat)
    {
        $chat->update(['unread_admin' => 0]);

        return view('admin.chats.show', ['chat' => $chat->load('messages', 'user')]);
    }

    /** New messages for the open admin view (polled every few seconds). */
    public function poll(Request $request, ChatConversation $chat)
    {
        $msgs = $chat->messages()->where('id', '>', (int) $request->query('after', 0))->get();
        if ($msgs->isNotEmpty()) {
            $chat->update(['unread_admin' => 0]);
        }

        return response()->json(['messages' => $msgs->map->toWidget()->map(function ($m) {
            $m['name'] = $m['from'] === 'visitor' ? 'Visitor' : $m['name'];

            return $m;
        })]);
    }

    public function reply(Request $request, ChatConversation $chat)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);
        $msg = $chat->messages()->create(['sender' => 'admin', 'user_id' => $request->user()->id, 'body' => trim($data['message'])]);
        $chat->update(['status' => 'open', 'last_message_at' => now(), 'unread_visitor' => $chat->unread_visitor + 1, 'unread_admin' => 0]);

        $mailed = Notify::mail($chat->contactEmail(), new ChatMail($chat, $msg, 'reply'));
        ActivityLog::log('chat.reply', "Replied to chat #{$chat->id} ({$chat->displayName()})", $chat);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'mailed' => $mailed, 'message' => $msg->toWidget()]);
        }

        return back()->with('success', 'Reply sent' . ($mailed ? ' and emailed to ' . $chat->contactEmail() : '') . '.');
    }

    public function close(ChatConversation $chat)
    {
        $chat->update(['status' => $chat->status === 'closed' ? 'open' : 'closed']);

        return back()->with('success', 'Chat ' . $chat->status . '.');
    }
}

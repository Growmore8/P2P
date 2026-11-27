<?php

namespace App\Http\Controllers;

use App\Mail\ChatMail;
use App\Models\ChatConversation;
use App\Support\ChatBot;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/** Public website chat widget (visitor side). JSON only. */
class ChatController extends Controller
{
    private const COOKIE = 'srm_chat';

    private function conversation(Request $request): ?ChatConversation
    {
        $token = $request->cookie(self::COOKIE);

        return $token ? ChatConversation::where('token', $token)->first() : null;
    }

    /** Current chat (messages after ?after=id) — the widget polls this. */
    public function poll(Request $request)
    {
        $c = $this->conversation($request);
        if (! $c) {
            return response()->json(['started' => false, 'greeting' => ChatBot::greeting(), 'needContact' => ! $request->user()]);
        }
        $after = (int) $request->query('after', 0);
        $msgs = $c->messages()->where('id', '>', $after)->get()->map->toWidget();
        if ($request->boolean('open') && $c->unread_visitor) {
            $c->update(['unread_visitor' => 0]);
        }

        return response()->json(['started' => true, 'status' => $c->status, 'unread' => $c->unread_visitor, 'messages' => $msgs]);
    }

    public function send(Request $request)
    {
        // Honeypot: real visitors never fill this hidden field.
        if ($request->filled('website')) {
            return response()->json(['ok' => true, 'messages' => []]);
        }
        $user = $request->user();
        $c = $this->conversation($request);
        $isNew = ! $c;

        $rules = ['message' => ['required', 'string', 'min:1', 'max:1000']];
        if ($isNew && ! $user) {
            $rules['name'] = ['required', 'string', 'max:80'];
            $rules['email'] = ['required', 'email', 'max:150'];
        }
        $data = $request->validate($rules, [
            'name.required' => 'Please tell us your name.',
            'email.required' => 'Please add your email so we can reply.',
        ]);

        $cookie = null;
        if ($isNew) {
            $c = ChatConversation::create([
                'token' => Str::random(48),
                'user_id' => $user?->id,
                'name' => $user ? $user->name : $data['name'],
                'email' => $user ? $user->email : Str::lower($data['email']),
                'ip' => $request->ip(),
                'page' => substr((string) $request->header('referer'), 0, 250),
            ]);
            $c->messages()->create(['sender' => 'bot', 'body' => ChatBot::greeting()]);
            $cookie = Cookie::make(self::COOKIE, $c->token, 60 * 24 * 60, null, null, $request->isSecure(), true, false, 'lax');
        }
        $last = (int) $request->input('after', 0);
        $wasIdle = ! $c->last_message_at || $c->last_message_at->lt(now()->subMinutes(30));

        $msg = $c->messages()->create(['sender' => 'visitor', 'body' => trim($data['message'])]);
        $c->update(['status' => 'open', 'last_message_at' => now(), 'unread_admin' => $c->unread_admin + 1]);

        // Instant answer for common questions; otherwise (once in a while) tell them the team will reply.
        $answer = ChatBot::reply($data['message']);
        if ($answer) {
            $c->messages()->create(['sender' => 'bot', 'body' => $answer]);
        } elseif ($isNew || $wasIdle || ! $c->messages()->where('sender', 'bot')->where('created_at', '>=', now()->subMinutes(10))->where('body', ChatBot::handoff())->exists()) {
            $c->messages()->create(['sender' => 'bot', 'body' => ChatBot::handoff()]);
        }

        // Alert the team inbox at the start of a conversation or after a quiet period.
        if ($isNew || $wasIdle) {
            Notify::staff(new ChatMail($c, $msg, 'staff'));
        }

        $response = response()->json([
            'ok' => true,
            'messages' => $c->messages()->where('id', '>', $last)->get()->map->toWidget(),
        ]);

        return $cookie ? $response->withCookie($cookie) : $response;
    }
}

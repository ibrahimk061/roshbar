<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('f');

        return view('admin.messages.index', [
            'messages' => Message::query()
                ->when($filter === 'unread', fn ($q) => $q->where('is_read', false))
                ->when(in_array($filter, Message::TOPICS, true), fn ($q) => $q->where('topic', $filter))
                ->latest('id')
                ->paginate(30)
                ->withQueryString(),
            'filter' => $filter,
            'unread' => Message::query()->where('is_read', false)->count(),
        ]);
    }

    public function show(Message $message): View
    {
        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return view('admin.messages.show', ['m' => $message]);
    }

    public function unread(Message $message): RedirectResponse
    {
        $message->update(['is_read' => false]);

        return redirect()->route('admin.messages.index')->with('status', 'Marked as unread.');
    }

    public function destroy(Message $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('status', 'Message deleted.');
    }
}

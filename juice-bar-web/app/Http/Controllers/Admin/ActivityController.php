<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Staff activity log. Owner only. */
class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ActivityLog::query()->with('user')
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', (int) $request->query('user')))
            ->when(isset(ActivityLog::ACTIONS[$request->query('action', '')]), fn ($q) => $q->where('action', $request->query('action')))
            ->latest('id')
            ->paginate(50)->withQueryString();

        return view('admin.activity.index', [
            'logs' => $logs,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}

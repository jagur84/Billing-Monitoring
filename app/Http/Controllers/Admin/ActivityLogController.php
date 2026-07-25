<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where('description', 'like', "%{$search}%");
            })
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.activity-logs.index', compact('logs'));
    }

    public function clear()
    {
        ActivityLog::query()->delete();

        return redirect()->route('activity-logs.index')->with('status', 'Semua log aktivitas berhasil dihapus.');
    }
}

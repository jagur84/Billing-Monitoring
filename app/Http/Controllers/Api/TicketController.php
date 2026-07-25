<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = Ticket::with('customer', 'assignee')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_to', $request->user()->id))
            ->orderByDesc('id')
            ->paginate(25);

        return response()->json($tickets);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['customer', 'assignee', 'logs' => fn ($q) => $q->orderByDesc('id')]);

        return response()->json($ticket);
    }
}

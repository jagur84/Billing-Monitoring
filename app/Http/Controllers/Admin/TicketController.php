<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppMessage;
use App\Mail\TicketAssignedMail;
use App\Mail\TicketClosedMail;
use App\Mail\TicketOpenedMail;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Notifications\TemplateRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = Ticket::with(['customer', 'assignee'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tickets.index', compact('tickets'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $technicians = User::role(['technician', 'admin'])->orderBy('name')->get();

        return view('admin.tickets.create', compact('customers', 'technicians'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'in:connectivity,installation,billing,other'],
            'priority' => ['required', 'in:low,medium,high'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $data['ticket_number'] = $this->generateTicketNumber();
        $data['status'] = 'open';

        $ticket = Ticket::create($data);

        $ticket->logs()->create([
            'user_id' => $request->user()->id,
            'status' => 'open',
            'note' => 'Tiket dibuat.',
        ]);

        $this->notifyTicketEvent($ticket, 'ticket_opened');

        if ($ticket->assigned_to) {
            $this->notifyAssignment($ticket);
        }

        return redirect()->route('tickets.show', $ticket)->with('status', 'Tiket berhasil dibuat.');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['customer', 'assignee', 'logs' => fn ($q) => $q->with('user')->orderByDesc('id')]);
        $technicians = User::role(['technician', 'admin'])->orderBy('name')->get();

        return view('admin.tickets.show', compact('ticket', 'technicians'));
    }

    public function edit(Ticket $ticket)
    {
        $customers = Customer::orderBy('name')->get();
        $technicians = User::role(['technician', 'admin'])->orderBy('name')->get();

        return view('admin.tickets.edit', compact('ticket', 'customers', 'technicians'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'in:connectivity,installation,billing,other'],
            'priority' => ['required', 'in:low,medium,high'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $originalAssignedTo = $ticket->assigned_to;

        $ticket->update($data);

        if ($ticket->assigned_to && $ticket->assigned_to !== $originalAssignedTo) {
            $this->notifyAssignment($ticket);
        }

        return redirect()->route('tickets.show', $ticket)->with('status', 'Tiket berhasil diperbarui.');
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return redirect()->route('tickets.index')->with('status', 'Tiket berhasil dihapus.');
    }

    public function addLog(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'note' => ['required', 'string'],
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $wasClosed = in_array($ticket->status, ['resolved', 'closed']);

        $ticket->logs()->create([
            'user_id' => $request->user()->id,
            'status' => $data['status'],
            'note' => $data['note'],
        ]);

        $ticket->update([
            'status' => $data['status'],
            'resolved_at' => in_array($data['status'], ['resolved', 'closed']) ? ($ticket->resolved_at ?? now()) : null,
        ]);

        if (! $wasClosed && in_array($data['status'], ['resolved', 'closed'])) {
            $this->notifyTicketEvent($ticket, 'ticket_closed');
        }

        return redirect()->route('tickets.show', $ticket)->with('status', 'Catatan berhasil ditambahkan.');
    }

    private function generateTicketNumber(): string
    {
        $prefix = Setting::get('ticket_number_prefix', 'TKT');

        do {
            $number = "{$prefix}-".strtoupper(Str::random(6));
        } while (Ticket::where('ticket_number', $number)->exists());

        return $number;
    }

    private function notifyAssignment(Ticket $ticket): void
    {
        $assignee = User::find($ticket->assigned_to);

        if (! $assignee) {
            return;
        }

        if ($assignee->email) {
            Mail::to($assignee->email)->queue(new TicketAssignedMail($ticket, $assignee));
        }

        if ($assignee->phone) {
            $rendered = app(TemplateRenderer::class)->render('whatsapp_ticket_assigned', [
                'assignee_name' => $assignee->name,
                'customer_name' => $ticket->customer->name,
                'ticket_number' => $ticket->ticket_number,
                'ticket_subject' => $ticket->subject,
                'ticket_priority' => ucfirst($ticket->priority),
                'app_name' => config('app.name'),
            ]);

            SendWhatsAppMessage::dispatch($assignee->phone, $rendered['body']);
        }
    }

    private function notifyTicketEvent(Ticket $ticket, string $type): void
    {
        $customer = $ticket->customer;

        if (! $customer) {
            return;
        }

        if (! NotificationLog::record($ticket, 'email', $type)) {
            return;
        }

        if ($customer->email) {
            Mail::to($customer->email)->queue(
                $type === 'ticket_opened' ? new TicketOpenedMail($ticket) : new TicketClosedMail($ticket)
            );
        }

        if ($customer->phone) {
            $rendered = app(TemplateRenderer::class)->render(
                $type === 'ticket_opened' ? 'whatsapp_ticket_opened' : 'whatsapp_ticket_closed',
                [
                    'customer_name' => $customer->name,
                    'ticket_number' => $ticket->ticket_number,
                    'ticket_subject' => $ticket->subject,
                    'app_name' => config('app.name'),
                ]
            );

            SendWhatsAppMessage::dispatch($customer->phone, $rendered['body']);
        }
    }
}

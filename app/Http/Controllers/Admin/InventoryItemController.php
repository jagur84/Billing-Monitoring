<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Ticket;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use RuntimeException;

class InventoryItemController extends Controller
{
    public function __construct(private InventoryService $inventoryService)
    {
    }

    public function index()
    {
        $items = InventoryItem::orderBy('name')->paginate(15);

        return view('admin.inventory-items.index', compact('items'));
    }

    public function create()
    {
        return view('admin.inventory-items.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $request->validate(['sku' => ['unique:inventory_items,sku']]);

        InventoryItem::create($data);

        return redirect()->route('inventory-items.index')->with('status', 'Item berhasil ditambahkan.');
    }

    public function show(InventoryItem $inventoryItem)
    {
        $inventoryItem->load(['transactions' => fn ($q) => $q->with(['customer', 'ticket', 'createdBy'])->orderByDesc('id')->limit(30)]);
        $customers = Customer::orderBy('name')->get();
        $tickets = Ticket::orderByDesc('id')->limit(50)->get();

        return view('admin.inventory-items.show', ['item' => $inventoryItem, 'customers' => $customers, 'tickets' => $tickets]);
    }

    public function edit(InventoryItem $inventoryItem)
    {
        return view('admin.inventory-items.edit', ['item' => $inventoryItem]);
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $data = $this->validated($request);
        $request->validate(['sku' => ['unique:inventory_items,sku,'.$inventoryItem->id]]);
        unset($data['stock_qty']);

        $inventoryItem->update($data);

        return redirect()->route('inventory-items.index')->with('status', 'Item berhasil diperbarui.');
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        $inventoryItem->delete();

        return redirect()->route('inventory-items.index')->with('status', 'Item berhasil dihapus.');
    }

    public function addTransaction(Request $request, InventoryItem $inventoryItem)
    {
        $data = $request->validate([
            'type' => ['required', 'in:in,out'],
            'qty' => ['required', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'ticket_id' => ['nullable', 'exists:tickets,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->inventoryService->recordTransaction(
                $inventoryItem,
                $data['type'],
                (int) $data['qty'],
                $data['customer_id'] ?? null,
                $data['ticket_id'] ?? null,
                $request->user()->id,
                $data['note'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inventory-items.show', $inventoryItem)->with('status', 'Transaksi berhasil dicatat.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'sku' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:ont,cable,router,other'],
            'unit' => ['required', 'string', 'max:50'],
            'stock_qty' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
        ]);
    }
}

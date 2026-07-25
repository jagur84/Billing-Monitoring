<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function recordTransaction(InventoryItem $item, string $type, int $qty, ?int $customerId, ?int $ticketId, ?int $createdBy, ?string $note): InventoryTransaction
    {
        return DB::transaction(function () use ($item, $type, $qty, $customerId, $ticketId, $createdBy, $note) {
            $item = InventoryItem::whereKey($item->id)->lockForUpdate()->first();

            if ($type === 'out' && $item->stock_qty < $qty) {
                throw new RuntimeException('Stok tidak mencukupi untuk transaksi ini.');
            }

            $item->update([
                'stock_qty' => $type === 'in' ? $item->stock_qty + $qty : $item->stock_qty - $qty,
            ]);

            return $item->transactions()->create([
                'type' => $type,
                'qty' => $qty,
                'customer_id' => $customerId,
                'ticket_id' => $ticketId,
                'created_by' => $createdBy,
                'note' => $note,
            ]);
        });
    }
}

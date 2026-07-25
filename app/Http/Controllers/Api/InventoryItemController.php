<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;

class InventoryItemController extends Controller
{
    public function index()
    {
        return response()->json(InventoryItem::orderBy('name')->paginate(25));
    }
}

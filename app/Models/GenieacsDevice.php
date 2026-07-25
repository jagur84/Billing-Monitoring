<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenieacsDevice extends Model
{
    use LogsActivity;

    protected $fillable = [
        'customer_id',
        'device_id',
        'serial_number',
        'product_class',
        'manufacturer',
        'last_inform_at',
    ];

    protected function casts(): array
    {
        return [
            'last_inform_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

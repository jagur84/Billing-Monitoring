<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use LogsActivity;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'package_id',
        'package_name_snapshot',
        'period_month',
        'period_year',
        'amount',
        'tax_amount',
        'discount_amount',
        'carry_over_amount',
        'carry_over_note',
        'total_amount',
        'due_date',
        'status',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'carry_over_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

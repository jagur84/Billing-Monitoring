<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Customer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'customer_code',
        'name',
        'email',
        'phone',
        'nik',
        'address',
        'package_id',
        'pppoe_username',
        'pppoe_password',
        'router_id',
        'ip_address',
        'installation_date',
        'billing_due_day',
        'status',
        'notes',
    ];

    protected $hidden = [
        'pppoe_password',
    ];

    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(MikrotikRouter::class, 'router_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function genieacsDevices(): HasMany
    {
        return $this->hasMany(GenieacsDevice::class);
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public static function generateCode(): string
    {
        $prefix = Setting::get('customer_code_prefix', 'CUST');

        do {
            $code = "{$prefix}-".strtoupper(Str::random(6));
        } while (self::where('customer_code', $code)->exists());

        return $code;
    }
}

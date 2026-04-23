<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Quotation extends Model
{
    protected $fillable = [
        'tenant_id',
        'public_id',
        'customer_id',
        'manual_customer_data',
        'subject',
        'items',
        'sub_total',
        'total',
        'status',
        'expiry_date',
        'notes',
        'terms',
    ];

    protected $casts = [
        'items' => 'array',
        'manual_customer_data' => 'array',
        'expiry_date' => 'date',
    ];

    public static function generatePublicId(): string
    {
        $lastId = self::latest()->first()?->id ?? 0;
        return 'EST-' . str_pad((string)($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }
}

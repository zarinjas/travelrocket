<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use BelongsToTenant;

    public const BOOKING_STATUS_PENDING = 'pending';
    public const BOOKING_STATUS_CONFIRMED = 'confirmed';
    public const BOOKING_STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';
    public const PAYMENT_STATUS_PARTIAL = 'partial';
    public const PAYMENT_STATUS_PAID = 'paid';

    protected $fillable = [
        'tenant_id',
        'package_id',
        'lead_customer_id',
        'booking_number',
        'total_pax',
        'total_price',
        'balance_due',
        'booking_status',
        'payment_status',
        'customer_id',
        'travel_date',
        'departure_date',
        'return_date',
        'flight_name',
        'flight_number',
        'status',
        'notes',
        'source',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
    ];

    protected function casts(): array
    {
        return [
            'travel_date' => 'date',
            'departure_date' => 'date',
            'return_date' => 'date',
            'total_price' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function leadCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'lead_customer_id');
    }

    public function passengers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'booking_passenger')
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

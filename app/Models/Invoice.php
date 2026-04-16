<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    protected $fillable = [
        'tenant_id',
        'public_id',
        'quote_id',
        'booking_id',
        'customer_id',
        'lead_customer_id',
        'manual_customer_data',
        'subject',
        'items',
        'sub_total',
        'total',
        'deposit_amount',
        'paid_amount',
        'status',
        'issued_date',
        'due_date',
        'payment_proof',
        'payment_details',
        'notes',
        'terms',
    ];

    protected $casts = [
        'items' => 'array',
        'manual_customer_data' => 'array',
        'payment_details' => 'array',
        'issued_date' => 'date',
        'due_date' => 'date',
    ];

    public static function generatePublicId(): string
    {
        $lastId = self::latest()->first()?->id ?? 0;
        return 'INV-' . str_pad((string)($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'lead_customer_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(InvoiceReminderLog::class);
    }

    public function getDueAmountAttribute(): float
    {
        return (float)$this->total_amount - (float)$this->amount_paid;
    }
}

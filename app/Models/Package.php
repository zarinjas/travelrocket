<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'tenant_id',
        'category',
        'type',
        'name',
        'destination',
        'start_date',
        'end_date',
        'itinerary',
        'itinerary_days',
        'inclusions',
        'exclusions',
        'pricing_tiers',
        'terms_conditions',
        'booking_capacity',
        'current_bookings',
        'price',
        'brochure_path',
        'cover_image_path',
        'status',
        'description',
    ];

    protected $appends = [
        'available_seats',
        'is_sold_out',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'booking_capacity' => 'integer',
            'current_bookings' => 'integer',
            'price' => 'decimal:2',
            'itinerary_days' => 'array',
            'inclusions' => 'array',
            'exclusions' => 'array',
            'pricing_tiers' => 'array',
        ];
    }

    public function getAvailableSeatsAttribute(): int
    {
        return max(0, (int) $this->booking_capacity - (int) $this->current_bookings);
    }

    public function getIsSoldOutAttribute(): bool
    {
        return $this->available_seats <= 0;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'full_name',
        'passport_number',
        'date_of_birth',
        'passport_copy_path',
        'address',
        'email',
        'phone',
        'emergency_name',
        'emergency_phone',
        'emergency_relation',
        'emergency_address',
        'tags',
        'allow_marketing',
        'document_no',
        'nationality',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'allow_marketing' => 'boolean',
        ];
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return $query;
        }

        return $query->where(function (Builder $nested) use ($keyword): void {
            $nested
                ->where('name', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%")
                ->orWhere('passport_number', 'like', "%{$keyword}%");
        });
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

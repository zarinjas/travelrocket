<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomingList extends Model
{
    protected $fillable = [
        'tenant_id',
        'package_id',
        'rooms',
    ];

    protected function casts(): array
    {
        return [
            'rooms' => 'array',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

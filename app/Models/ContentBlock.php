<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentBlock extends Model
{
    protected $fillable = [
        'page',
        'block_key',
        'payload',
        'is_active',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public static function value(string $page, string $blockKey, mixed $fallback = null): mixed
    {
        $record = static::query()
            ->where('page', $page)
            ->where('block_key', $blockKey)
            ->where('is_active', true)
            ->first();

        return data_get($record?->payload, 'value', $fallback);
    }
}

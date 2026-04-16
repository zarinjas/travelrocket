<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $user = auth()->user();

            if ($user?->tenant_id) {
                $builder->where(
                    $builder->getModel()->qualifyColumn('tenant_id'),
                    $user->tenant_id
                );
            }
        });

        static::creating(function (mixed $model): void {
            $user = auth()->user();

            if ($user?->tenant_id && empty($model->tenant_id)) {
                $model->tenant_id = $user->tenant_id;
            }
        });
    }
}

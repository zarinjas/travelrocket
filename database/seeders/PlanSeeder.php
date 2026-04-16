<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('travelrocket.plans', []) as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [
                    'name' => $plan['name'],
                    'price_amount' => (int) preg_replace('/\D/', '', $plan['price']),
                    'currency' => 'MYR',
                    'billing_period' => ltrim($plan['billing_period'], '/'),
                    'description' => $plan['description'],
                    'features' => $plan['features'],
                    'is_active' => true,
                    'is_trial' => $plan['is_trial'] ?? false,
                    'trial_days' => $plan['trial_days'] ?? null,
                    'sort_order' => $plan['sort_order'] ?? 0,
                ]
            );
        }
    }
}

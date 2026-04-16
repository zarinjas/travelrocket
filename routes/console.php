<?php

use App\Models\Customer;
use App\Models\Quotation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('customers:purge-soft-deleted {--days=30}', function (): void {
    $days = max(1, (int) $this->option('days'));
    $cutoff = now()->subDays($days);
    $totalPurged = 0;

    Customer::onlyTrashed()
        ->where('deleted_at', '<=', $cutoff)
        ->orderBy('id')
        ->chunkById(200, function ($customers) use (&$totalPurged): void {
            foreach ($customers as $customer) {
                if ($customer->passport_copy_path) {
                    Storage::disk('public')->delete($customer->passport_copy_path);
                }

                $customer->forceDelete();
                $totalPurged++;
            }
        });

    $this->info("Purged {$totalPurged} customer(s) soft-deleted before {$cutoff->toDateTimeString()}.");
})->purpose('Purge soft-deleted customers older than N days and cleanup passport files');

Artisan::command('quotations:expire', function (): void {
    $expired = Quotation::query()
        ->where('status', Quotation::STATUS_PENDING)
        ->whereDate('valid_until', '<', now()->toDateString())
        ->update(['status' => Quotation::STATUS_EXPIRED]);

    $this->info("Marked {$expired} quotation(s) as expired.");
})->purpose('Mark pending quotations past valid_until as expired');

Schedule::command('quotations:expire')
    ->dailyAt('01:30')
    ->withoutOverlapping();

Schedule::command('customers:purge-soft-deleted --days=30')
    ->dailyAt('02:30')
    ->withoutOverlapping();

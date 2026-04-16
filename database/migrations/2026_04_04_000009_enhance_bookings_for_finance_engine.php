<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreignId('lead_customer_id')->nullable()->after('package_id')->constrained('customers')->cascadeOnDelete();
            $table->string('booking_number', 40)->nullable()->unique()->after('lead_customer_id');
            $table->unsignedInteger('total_pax')->default(1)->after('booking_number');
            $table->decimal('total_price', 12, 2)->default(0)->after('total_pax');
            $table->decimal('balance_due', 12, 2)->default(0)->after('total_price');
            $table->enum('booking_status', ['pending', 'confirmed', 'cancelled'])->default('pending')->after('balance_due');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->after('booking_status');
        });

        DB::table('bookings')->update([
            'lead_customer_id' => DB::raw('customer_id'),
        ]);

        DB::table('bookings')
            ->where('status', 'paid')
            ->update([
                'booking_status' => 'confirmed',
                'payment_status' => 'paid',
            ]);

        DB::table('bookings')
            ->where('status', 'cancelled')
            ->update([
                'booking_status' => 'cancelled',
            ]);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('lead_customer_id');
            $table->dropUnique(['booking_number']);
            $table->dropColumn([
                'booking_number',
                'total_pax',
                'total_price',
                'balance_due',
                'booking_status',
                'payment_status',
            ]);
        });
    }
};

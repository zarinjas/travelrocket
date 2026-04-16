<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->string('destination')->nullable()->after('type');
            $table->date('start_date')->nullable()->after('destination');
            $table->date('end_date')->nullable()->after('start_date');
            $table->text('itinerary')->nullable()->after('description');
            $table->unsignedInteger('booking_capacity')->default(0)->after('itinerary');
            $table->unsignedInteger('current_bookings')->default(0)->after('booking_capacity');
            $table->decimal('price', 12, 2)->default(0)->after('current_bookings');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->dropColumn([
                'destination',
                'start_date',
                'end_date',
                'itinerary',
                'booking_capacity',
                'current_bookings',
                'price',
            ]);
        });
    }
};
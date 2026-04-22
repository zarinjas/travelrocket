<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->json('highlights')->nullable()->after('description');
            $table->string('meal_plan')->nullable()->after('highlights');
            $table->json('hotel_details')->nullable()->after('meal_plan');
            $table->json('flight_info')->nullable()->after('hotel_details');
            $table->json('visa_info')->nullable()->after('flight_info');
            $table->unsignedSmallInteger('min_pax')->default(1)->after('visa_info');
            $table->unsignedSmallInteger('max_pax')->nullable()->after('min_pax');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table): void {
            $table->dropColumn(['highlights', 'meal_plan', 'hotel_details', 'flight_info', 'visa_info', 'min_pax', 'max_pax']);
        });
    }
};

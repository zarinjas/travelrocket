<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'departure_date')) {
                $table->date('departure_date')->nullable()->after('travel_date');
            }

            if (! Schema::hasColumn('bookings', 'return_date')) {
                $table->date('return_date')->nullable()->after('departure_date');
            }

            if (! Schema::hasColumn('bookings', 'flight_name')) {
                $table->string('flight_name')->nullable()->after('return_date');
            }

            if (! Schema::hasColumn('bookings', 'flight_number')) {
                $table->string('flight_number')->nullable()->after('flight_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            foreach (['departure_date', 'return_date', 'flight_name', 'flight_number'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

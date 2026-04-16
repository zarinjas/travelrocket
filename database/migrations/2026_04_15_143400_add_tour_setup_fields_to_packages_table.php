<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // Structured itinerary: [{ day: 1, title: "Arrival", description: "...", activities: ["Check-in", "Dinner"] }]
            $table->json('itinerary_days')->nullable()->after('itinerary');

            // Inclusions: ["Flight tickets", "Hotel 4-star", "Meals", "Visa", "Transport"]
            $table->json('inclusions')->nullable()->after('itinerary_days');

            // Exclusions: ["Travel insurance", "Personal expenses", "Tips"]
            $table->json('exclusions')->nullable()->after('inclusions');

            // Pricing tiers: { adult: 4999.00, child: 3999.00, infant: 999.00 }
            $table->json('pricing_tiers')->nullable()->after('exclusions');

            // Terms & conditions text
            $table->text('terms_conditions')->nullable()->after('pricing_tiers');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'itinerary_days',
                'inclusions',
                'exclusions',
                'pricing_tiers',
                'terms_conditions',
            ]);
        });
    }
};

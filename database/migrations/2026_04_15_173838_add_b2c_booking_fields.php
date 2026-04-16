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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('source', 20)->default('workspace')->after('payment_status');
            $table->string('buyer_name')->nullable()->after('source');
            $table->string('buyer_email')->nullable()->after('buyer_name');
            $table->string('buyer_phone', 30)->nullable()->after('buyer_email');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['source', 'buyer_name', 'buyer_email', 'buyer_phone']);
        });
    }
};

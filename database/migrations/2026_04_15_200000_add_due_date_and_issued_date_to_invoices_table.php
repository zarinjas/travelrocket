<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'issued_date')) {
                $table->date('issued_date')->nullable()->after('status');
            }
            if (!Schema::hasColumn('invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('issued_date');
            }
            if (!Schema::hasColumn('invoices', 'payment_details')) {
                $table->json('payment_details')->nullable()->after('payment_proof');
            }
            if (!Schema::hasColumn('invoices', 'booking_id')) {
                $table->foreignId('booking_id')->nullable()->after('quote_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('invoices', 'lead_customer_id')) {
                $table->foreignId('lead_customer_id')->nullable()->after('customer_id')->constrained('customers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['lead_customer_id']);
            $table->dropColumn(['issued_date', 'due_date', 'payment_details', 'booking_id', 'lead_customer_id']);
        });
    }
};

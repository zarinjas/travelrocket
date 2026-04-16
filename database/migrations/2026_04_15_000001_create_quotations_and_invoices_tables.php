<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $blueprint->string('public_id')->unique(); // EST-xxxx
            $blueprint->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $blueprint->json('manual_customer_data')->nullable(); // For free-text customers
            $blueprint->string('subject')->nullable();
            $blueprint->json('items'); // [{description, qty, rate, amount}]
            $blueprint->decimal('sub_total', 15, 2);
            $blueprint->decimal('total', 15, 2);
            $blueprint->string('status')->default('Draft'); // Draft, Sent, Expired
            $blueprint->date('expiry_date');
            $blueprint->text('notes')->nullable();
            $blueprint->text('terms')->nullable();
            $blueprint->timestamps();
        });

        Schema::create('invoices', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $blueprint->string('public_id')->unique(); // INV-xxxx
            $blueprint->foreignId('quote_id')->nullable()->constrained('quotations')->nullOnDelete();
            $blueprint->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $blueprint->json('manual_customer_data')->nullable();
            $blueprint->string('subject')->nullable();
            $blueprint->json('items');
            $blueprint->decimal('sub_total', 15, 2);
            $blueprint->decimal('total', 15, 2);
            $blueprint->decimal('deposit_amount', 15, 2)->default(0);
            $blueprint->decimal('paid_amount', 15, 2)->default(0);
            $blueprint->string('status')->default('Unpaid'); // Unpaid, Partially Paid, Fully Paid
            $blueprint->string('payment_proof')->nullable(); // Image path
            $blueprint->text('notes')->nullable();
            $blueprint->text('terms')->nullable();
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quotations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('invoice_reminder_logs');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quotations');

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('public_id')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('manual_customer_data')->nullable();
            $table->string('subject')->nullable();
            $table->json('items');
            $table->decimal('sub_total', 15, 2);
            $table->decimal('total', 15, 2);
            $table->string('status')->default('Draft');
            $table->date('expiry_date');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('public_id')->unique();
            $table->foreignId('quote_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->json('manual_customer_data')->nullable();
            $table->string('subject')->nullable();
            $table->json('items');
            $table->decimal('sub_total', 15, 2);
            $table->decimal('total', 15, 2);
            $table->decimal('deposit_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status')->default('Unpaid');
            $table->date('issued_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('payment_proof')->nullable();
            $table->json('payment_details')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('channel')->default('internal');
            $table->string('stage')->nullable();
            $table->string('recipient')->nullable();
            $table->string('status')->default('sent');
            $table->text('message_preview')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reminder_logs');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quotations');
    }
};

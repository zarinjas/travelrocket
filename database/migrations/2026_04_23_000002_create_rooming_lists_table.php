<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooming_lists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->json('rooms')->default('[]');
            $table->timestamps();
            $table->unique(['tenant_id', 'package_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooming_lists');
    }
};

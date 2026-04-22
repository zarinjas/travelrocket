<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('passport_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }
        });
    }
};

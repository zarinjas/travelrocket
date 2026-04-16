<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'tags')) {
                $table->json('tags')->nullable()->after('emergency_address');
            }

            if (! Schema::hasColumn('customers', 'allow_marketing')) {
                $table->boolean('allow_marketing')->default(true)->after('tags');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'allow_marketing')) {
                $table->dropColumn('allow_marketing');
            }

            if (Schema::hasColumn('customers', 'tags')) {
                $table->dropColumn('tags');
            }
        });
    }
};

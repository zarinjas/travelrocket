<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('packages', 'category')) {
            Schema::table('packages', function (Blueprint $table): void {
                $table->string('category')->default('Umrah')->after('type');
            });

            DB::table('packages')
                ->whereNull('category')
                ->update(['category' => DB::raw('type')]);
        }

        if (! Schema::hasColumn('packages', 'brochure_path')) {
            Schema::table('packages', function (Blueprint $table): void {
                $table->string('brochure_path')->nullable()->after('category');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('packages', 'brochure_path')) {
            Schema::table('packages', function (Blueprint $table): void {
                $table->dropColumn('brochure_path');
            });
        }

        if (Schema::hasColumn('packages', 'category')) {
            Schema::table('packages', function (Blueprint $table): void {
                $table->dropColumn('category');
            });
        }
    }
};

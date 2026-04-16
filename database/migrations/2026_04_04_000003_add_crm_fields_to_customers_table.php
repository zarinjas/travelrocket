<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'name')) {
                $table->string('name')->nullable()->after('tenant_id');
            }

            if (! Schema::hasColumn('customers', 'passport_number')) {
                $table->string('passport_number')->nullable()->after('name');
            }

            if (! Schema::hasColumn('customers', 'passport_copy_path')) {
                $table->string('passport_copy_path')->nullable()->after('passport_number');
            }

            if (! Schema::hasColumn('customers', 'address')) {
                $table->text('address')->nullable()->after('passport_copy_path');
            }

            if (! Schema::hasColumn('customers', 'emergency_name')) {
                $table->string('emergency_name')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('customers', 'emergency_phone')) {
                $table->string('emergency_phone')->nullable()->after('emergency_name');
            }

            if (! Schema::hasColumn('customers', 'emergency_relation')) {
                $table->string('emergency_relation')->nullable()->after('emergency_phone');
            }

            if (! Schema::hasColumn('customers', 'emergency_address')) {
                $table->text('emergency_address')->nullable()->after('emergency_relation');
            }
        });

        DB::table('customers')
            ->whereNull('name')
            ->update([
                'name' => DB::raw("coalesce(full_name, '')"),
                'passport_number' => DB::raw('document_no'),
            ]);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $dropColumns = [
                'name',
                'passport_number',
                'passport_copy_path',
                'address',
                'emergency_name',
                'emergency_phone',
                'emergency_relation',
                'emergency_address',
            ];

            foreach ($dropColumns as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

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
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('discount_type', ['percent', 'nominal'])->default('percent')->after('billing_due_day');
            $table->decimal('discount_nominal', 12, 2)->default(0)->after('discount_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_nominal']);
        });
    }
};

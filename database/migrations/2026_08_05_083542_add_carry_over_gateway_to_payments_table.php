<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY gateway ENUM('tripay', 'manual', 'carry_over') NOT NULL DEFAULT 'manual'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE payments SET gateway = 'manual' WHERE gateway = 'carry_over'");
        DB::statement("ALTER TABLE payments MODIFY gateway ENUM('tripay', 'manual') NOT NULL DEFAULT 'manual'");
    }
};

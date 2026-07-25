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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('nik')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pppoe_username')->nullable();
            $table->unsignedBigInteger('router_id')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->date('installation_date')->nullable();
            $table->unsignedTinyInteger('billing_due_day')->default(1);
            $table->enum('status', ['active', 'isolated', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('payment_type')->nullable();
            $table->string('rid')->nullable();
            $table->string('mid')->nullable();
            $table->string('saralPeID')->nullable();
            $table->string('payer_name')->nullable();
            $table->string('payee_upi')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_mobile')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('paygicReferenceId')->nullable();
            $table->string('merchantReferenceId')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('utr')->nullable();
            $table->timestamp('success_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

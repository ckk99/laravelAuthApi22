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
        Schema::create('callback_responses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('user_unq_code')->nullable();
            $table->string('user_upi_id')->nullable();
            $table->string('user_unique_code')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->string('utr')->nullable();
            $table->string('mid')->nullable();
            $table->string('vpa_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('callback_responses');
    }
};

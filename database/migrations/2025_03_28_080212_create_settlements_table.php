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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('mid');
            $table->string('rid');
            $table->decimal('amount', 10, 2);
            $table->string('utr')->nullable();
            $table->string('paygicReferenceNumber')->unique();
            $table->string('bankReferenceNumber')->nullable();
            $table->string('mode')->nullable();
            $table->timestamp('initiationDate')->nullable();
            $table->enum('status', ['SUCCESS', 'FAIL', 'PENDING'])->default('PENDING');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};

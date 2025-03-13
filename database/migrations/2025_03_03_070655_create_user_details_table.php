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
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // FK to users
            $table->string('bname')->nullable(); // Business name
            $table->string('lname')->nullable(); // Legal name
            $table->string('phone')->nullable();
            $table->string('mcc')->nullable();
            $table->string('type')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('stateCode')->nullable();
            $table->string('pincode')->nullable();
            $table->string('bpan')->nullable();
            $table->string('gst')->nullable();
            $table->string('account')->nullable();
            $table->string('ifsc')->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('cin')->nullable();
            $table->string('msme')->nullable();
            $table->string('dob')->nullable();
            $table->string('doi')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};

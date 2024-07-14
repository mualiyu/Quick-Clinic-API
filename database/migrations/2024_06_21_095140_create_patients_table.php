<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->string('firstname');
            $table->string('lastname');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->string('contact_number')->nullable();
            $table->string('address')->nullable();
            $table->text('health_conditions')->nullable();
            $table->date('registered_date')->default(now());
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};

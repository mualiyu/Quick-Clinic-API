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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('specialization');
            $table->string('license_number');
            $table->string('phone');
            $table->string('address')->nullable();
            $table->string('gender')->nullable();

            $table->string('education_qualifications')->nullable();
            $table->string('years_of_experience')->nullable();
            $table->longText('doctor_description')->nullable();
            $table->integer('basic_pay_amount')->nullable();

            $table->longText('id_card')->nullable();
            $table->longText('license_document')->nullable();
            $table->longText('document1')->nullable();
            $table->longText('document2')->nullable();
            $table->longText('document3')->nullable();
            $table->longText('document4')->nullable();
            $table->longText('document5')->nullable();

            $table->date('registered_date')->default(now());
            $table->boolean('is_approved')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};

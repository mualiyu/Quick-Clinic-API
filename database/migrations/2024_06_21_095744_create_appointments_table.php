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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id');
            $table->foreignId('doctor_id');
            $table->string('appointment_date');
            $table->string('appointment_time');
            $table->enum('status', ['Pending', 'Scheduled', 'No Response', 'Ongoing', 'Completed', 'Cancelled']);

            $table->string('description_of_problem')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('type', ['Voice', 'Video', 'Message']);
            // $table->string('attachment');

            $table->text('doctor_remark')->nullable();
            $table->string('report_url')->nullable();
            $table->string('prescription_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

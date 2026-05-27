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
        Schema::create('service_schedules', function (Blueprint $table) {
            $table->id('schedule_id');

            $table->foreignId('branch_id')
                ->constrained('branches', 'branch_id')
                ->onDelete('cascade');
            
            $table->foreignId('employee_id')
                ->constrained('employee_infos', 'employee_id')
                ->onDelete('cascade');

            $table->string('service_type');

            $table->string('description')->nullable();

            $table->dateTime('schedule_start');

            $table->dateTime('estimated_end');

            $table->string('status')
                ->default('Scheduled');

            $table->string('reschedule_reason')
                ->nullable();

            $table->foreignId('created_by')
                ->constrained('employee_accounts', 'employeeAccount_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_schedules');
    }
};

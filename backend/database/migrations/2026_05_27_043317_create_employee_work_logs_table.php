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
        Schema::create('employee_work_logs', function (Blueprint $table) {
            $table->id('workLog_id');

            $table->foreignId('schedule_id')
                ->constrained('service_schedules', 'schedule_id')
                ->onDelete('cascade');

            $table->foreignId('employee_id')
                ->constrained('employee_infos', 'employee_id')
                ->onDelete('cascade');

            $table->dateTime('actual_work_start')
                ->nullable();

            $table->dateTime('actual_work_end')
                ->nullable();

            $table->decimal('total_work_hours', 5, 2)
                ->nullable();

            $table->text('remarks')
                ->nullable();

            $table->decimal('gps_latitude', 10, 7)
                ->nullable();

            $table->decimal('gps_longitude', 10, 7)
                ->nullable();

            $table->string('approval_status')
                ->default('Pending');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('employee_accounts', 'employeeAccount_id');

            $table->dateTime('reviewed_at')
                ->nullable();

            $table->text('rejection_reason')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_work_logs');
    }
};

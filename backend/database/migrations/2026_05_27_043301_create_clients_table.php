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
        Schema::create('clients', function (Blueprint $table) {
            $table->id('client_id');

            $table->string('client_name');
            $table->string('tin_number')
                ->unique()
                ->nullable();

            $table->string('client_address');
            $table->string('contact_person');
            $table->string('contact_number');
            $table->string('email')
                ->unique()
                ->nullable();

            $table->string('status')
                ->default('Active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};

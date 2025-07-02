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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->references('id')->on('devices')->onDelete('cascade');

            $table->timestamp('time_stamp')->nullable();
            $table->float('volt')->default(0);
            $table->float('ampere')->default(0);
            $table->float('power')->default(0);
            $table->float('energy')->default(0);
            $table->float('frequency')->default(0);
            $table->float('power_factor')->default(0);
            $table->float('temperature')->default(0);
            $table->float('humidity')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};

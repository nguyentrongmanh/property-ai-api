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
        Schema::create('buildings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('type')->nullable()->index();
            $table->string('status')->default('active')->index();
            $table->string('city')->nullable()->index();
            $table->unsignedInteger('units')->nullable();
            $table->decimal('occupancy_rate', 3, 2)->nullable()->index();
            $table->json('amenities')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};

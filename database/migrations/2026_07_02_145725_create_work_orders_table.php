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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('property_id');
            $table->foreign('property_id')->references('id')->on('buildings')->cascadeOnDelete();
            $table->text('source_text');
            $table->string('requester_email');
            $table->string('title');
            $table->string('category')->index();
            $table->string('priority')->index();
            $table->text('summary');
            $table->string('status')->default('open')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};

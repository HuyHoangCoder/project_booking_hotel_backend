<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_details', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('unit')->default('item'); // item, hour, day, etc.
            $table->decimal('duration', 10, 2)->nullable(); // Duration in hours
            $table->boolean('is_available')->default(true);
            $table->boolean('requires_booking')->default(false);
            $table->integer('min_booking_hours')->nullable();
            $table->integer('max_booking_hours')->nullable();
            $table->json('operating_hours')->nullable(); // JSON structure for operating hours
            $table->json('requirements')->nullable(); // JSON structure for service requirements
            $table->json('included_items')->nullable(); // JSON structure for included items
            $table->json('additional_charges')->nullable(); // JSON structure for additional charges
            $table->json('images')->nullable(); // JSON array of image URLs
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_details');
    }
};
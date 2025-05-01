<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('cascade');
            $table->foreignId('floor_id')->constrained('floors')->onDelete('cascade');
            $table->integer('capacity');
            $table->decimal('price_per_night', 10, 2);
            $table->json('amenities')->nullable();
            $table->json('images')->nullable();
            $table->enum('status', ['available', 'occupied', 'maintenance', 'cleaning'])->default('available');
            $table->boolean('is_smoking_allowed')->default(false);
            $table->boolean('has_balcony')->default(false);
            $table->boolean('has_view')->default(false);
            $table->integer('size')->nullable(); // in square meters
            $table->integer('bed_count');
            $table->string('bed_type'); // single, double, queen, king
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
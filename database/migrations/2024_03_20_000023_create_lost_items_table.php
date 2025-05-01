<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_number')->unique();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // electronics, clothing, documents, etc.
            $table->string('location_found')->nullable();
            $table->date('date_found');
            $table->string('status')->default('pending'); // pending, claimed, disposed
            $table->string('storage_location')->nullable();
            $table->string('finder_name')->nullable();
            $table->string('finder_contact')->nullable();
            $table->string('claimer_name')->nullable();
            $table->string('claimer_contact')->nullable();
            $table->date('claim_date')->nullable();
            $table->text('claim_notes')->nullable();
            $table->json('images')->nullable(); // For storing item images
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_items');
    }
};
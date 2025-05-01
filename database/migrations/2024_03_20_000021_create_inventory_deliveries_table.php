<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number')->unique();
            $table->date('delivery_date');
            $table->string('recipient_name');
            $table->string('recipient_contact')->nullable();
            $table->string('recipient_address')->nullable();
            $table->string('delivery_method')->nullable(); // truck, air, sea, etc.
            $table->string('tracking_number')->nullable();
            $table->string('status')->default('pending'); // pending, delivered, cancelled
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable(); // For storing document references
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_deliveries');
    }
};
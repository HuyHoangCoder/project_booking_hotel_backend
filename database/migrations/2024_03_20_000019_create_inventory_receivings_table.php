<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_receivings', function (Blueprint $table) {
            $table->id();
            $table->string('receiving_number')->unique();
            $table->date('receiving_date');
            $table->string('supplier_name');
            $table->string('supplier_contact')->nullable();
            $table->string('supplier_address')->nullable();
            $table->string('delivery_method')->nullable(); // truck, air, sea, etc.
            $table->string('tracking_number')->nullable();
            $table->string('status')->default('pending'); // pending, received, cancelled
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable(); // For storing document references
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_receivings');
    }
};
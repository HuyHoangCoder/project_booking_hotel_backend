<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->integer('max_capacity');
            $table->json('default_amenities')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });




        // Insert default room types
        DB::table('room_types')->insert([
            [
                'name' => 'Standard Room',
                'description' => 'A comfortable room with basic amenities',
                'base_price' => 100.00,
                'max_capacity' => 2,
                'default_amenities' => json_encode(['TV', 'WiFi', 'Air Conditioning', 'Mini Bar']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Deluxe Room',
                'description' => 'A spacious room with premium amenities',
                'base_price' => 150.00,
                'max_capacity' => 2,
                'default_amenities' => json_encode(['TV', 'WiFi', 'Air Conditioning', 'Mini Bar', 'Safe', 'Coffee Maker']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Suite',
                'description' => 'A luxurious suite with separate living area',
                'base_price' => 250.00,
                'max_capacity' => 4,
                'default_amenities' => json_encode(['TV', 'WiFi', 'Air Conditioning', 'Mini Bar', 'Safe', 'Coffee Maker', 'Kitchenette', 'Living Room']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Family Room',
                'description' => 'A large room suitable for families',
                'base_price' => 200.00,
                'max_capacity' => 4,
                'default_amenities' => json_encode(['TV', 'WiFi', 'Air Conditioning', 'Mini Bar', 'Safe', 'Coffee Maker', 'Extra Beds']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
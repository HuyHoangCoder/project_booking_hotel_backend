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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('citizen_identity')->unique();
            $table->string('fullname');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('dob');
            $table->string('email')->unique();
            $table->string('avatar')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->foreignId('role_id')->constrained('roles')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
}; 
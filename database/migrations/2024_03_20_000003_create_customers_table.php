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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->string('citizen_identity')->unique();
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->date('dob');
            $table->foreignId('customer_type')->constrained('customer_types');
            $table->timestamps();
        });

        // Create group_members table for many-to-many relationship
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('customer_groups')->onDelete('cascade');
            $table->string('fullname');
            $table->string('relationship');
            $table->timestamps();

            $table->unique(['customer_id', 'group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('customers');
    }
};
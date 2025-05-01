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
        Schema::create('authorities', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('permission');
            $table->text('details')->nullable();
            $table->timestamps();

            $table->foreign('username')
                  ->references('username')
                  ->on('accounts')
                  ->onDelete('cascade');

            $table->unique(['username', 'permission']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authorities');
    }
}; 
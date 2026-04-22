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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vault_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['login', 'note']);
            $table->text('encrypted_data');
            $table->string('iv', 24); // base64 encoded 12 bytes
            $table->string('tag', 24); // base64 encoded 16 bytes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};

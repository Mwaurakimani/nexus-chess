<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('onit_reference')->unique(); // transaction ref from Onit
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'successful', 'failed'])->default('pending');
            $table->string('channel')->nullable(); // e.g. MPESA, CARD
            $table->string('narration')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

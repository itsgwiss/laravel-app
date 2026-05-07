<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_name')->nullable();
            $table->string('from_email');
            $table->string('to_email');
            $table->string('subject');
            $table->text('body');
            $table->enum('type', ['inbox', 'sent'])->default('inbox');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('to_email');
            $table->index('from_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

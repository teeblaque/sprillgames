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
        Schema::create('one_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users','id')->onDelete('cascade');
            $table->integer('referrer_id')->nullable();
            $table->integer('initial_value');
            $table->integer('second_value')->nullable();
            $table->float('amount')->default(0);
            $table->float('amount_earned')->default(0);
            $table->float('odds');
            $table->string('winner')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('one_bets');
    }
};

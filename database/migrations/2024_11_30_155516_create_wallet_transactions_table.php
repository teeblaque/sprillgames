<?php

use App\Constants\TransactionSource;
use App\Constants\TransactionStatus;
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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets','id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users','id')->onDelete('cascade');
            $table->string('trx_reference')->unique();
            $table->string('trx_type');//debit or credit
            $table->string('trx_status')->default(TransactionStatus::PENDING);
            $table->string('trx_source')->default(TransactionSource::WALLET_TO_WALLET);
            $table->string('gateway_response')->nullable();
            $table->string('payment_channel')->nullable();
            $table->decimal('amount', 13, 2)->default(0);
            $table->decimal('balance_before', 13, 2)->default(0);
            $table->decimal('balance_after', 13, 2)->default(0);
            $table->string('ip_address')->nullable();
            $table->string('domain')->nullable();
            $table->string('narration')->nullable();
            $table->boolean('is_active')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};

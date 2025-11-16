<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payer_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payer_id')->constrained('payers')->onDelete('cascade');
            $table->foreignId('rule_type_id')->constrained('rule_types')->onDelete('cascade');
            $table->json('conditions')->nullable();
            $table->json('actions')->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['payer_id', 'rule_type_id']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payer_rules');
    }
};

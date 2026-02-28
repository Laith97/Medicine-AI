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
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->integer('quantity')->nullable()->after('duration');
            $table->integer('refills')->default(0)->after('quantity');
            $table->string('route')->nullable()->after('refills'); // e.g., oral, topical
            $table->string('form')->nullable()->after('route'); // e.g., tablet, capsule
            $table->text('instructions')->nullable()->after('form'); // specific patient instructions
            $table->string('indication')->nullable()->after('instructions'); // diagnosis/reason
            $table->date('start_date')->nullable()->after('indication');
            $table->boolean('generic_allowed')->default(true)->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn([
                'quantity',
                'refills',
                'route',
                'form',
                'instructions',
                'indication',
                'start_date',
                'generic_allowed'
            ]);
        });
    }
};

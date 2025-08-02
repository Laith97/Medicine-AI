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
        Schema::table('patient_data', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_patient_id')->nullable()->after('user_id');
            $table->foreign('assigned_patient_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_data', function (Blueprint $table) {
            $table->dropForeign(['assigned_patient_id']);
            $table->dropColumn('assigned_patient_id');
        });
    }
};

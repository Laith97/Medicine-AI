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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('primary_doctor_id')->nullable()->after('role');
            $table->unsignedBigInteger('parent_user_id')->nullable()->after('primary_doctor_id');
            $table->string('sub_user_role')->nullable()->after('parent_user_id');
            $table->boolean('is_sub_user')->default(false)->after('sub_user_role');

            $table->foreign('primary_doctor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('parent_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['role', 'primary_doctor_id']);
            $table->index(['parent_user_id', 'is_sub_user']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['primary_doctor_id']);
            $table->dropForeign(['parent_user_id']);
            $table->dropIndex(['role', 'primary_doctor_id']);
            $table->dropIndex(['parent_user_id', 'is_sub_user']);
            $table->dropColumn(['primary_doctor_id', 'parent_user_id', 'sub_user_role', 'is_sub_user']);
        });
    }
};

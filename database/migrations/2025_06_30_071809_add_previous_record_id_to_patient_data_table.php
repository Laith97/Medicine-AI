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
            // Add previous_record_id field to track patient history
            // This creates a self-referencing foreign key
            $table->unsignedBigInteger('previous_record_id')->nullable()->after('user_id');
            
            // Add foreign key constraint that references the same table
            $table->foreign('previous_record_id')
                  ->references('id')
                  ->on('patient_data')
                  ->onDelete('set null'); // If a record is deleted, don't delete linked records
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_data', function (Blueprint $table) {
            if (Schema::hasColumn('patient_data', 'previous_record_id')) {
                // Remove the foreign key constraint first (if it exists)
                try {
                    $table->dropForeign(['previous_record_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue with column drop
                }
                
                // Then drop the column
                $table->dropColumn('previous_record_id');
            }
        });
    }
};

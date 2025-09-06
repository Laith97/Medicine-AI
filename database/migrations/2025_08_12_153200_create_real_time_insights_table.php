<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('real_time_insights', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('session_id')->constrained('ambient_recording_sessions')->onDelete('cascade');
            $table->enum('insight_type', ['symptom', 'diagnosis', 'medication', 'test', 'alert']);
            $table->json('insight_data');
            $table->decimal('confidence', 5, 2);
            $table->timestamp('timestamp');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_time_insights');
    }
};

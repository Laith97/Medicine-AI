<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ambient_recording_chunks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('session_id')->constrained('ambient_recording_sessions')->onDelete('cascade');
            $table->binary('chunk_data'); // LONGBLOB equivalent handled by driver for large payloads
            $table->integer('duration');
            $table->timestamp('recorded_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ambient_recording_chunks');
    }
};

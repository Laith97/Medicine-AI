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
        // Add foreign key constraints after all tables are created

        // blog_posts -> doctors
        if (Schema::hasTable('blog_posts') && Schema::hasTable('doctors')) {
            if (!$this->foreignKeyExists('blog_posts', 'blog_posts_doctor_id_foreign')) {
                Schema::table('blog_posts', function (Blueprint $table) {
                    $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
                });
            }
        }

        // chat_sessions -> doctors
        if (Schema::hasTable('chat_sessions') && Schema::hasTable('doctors')) {
            if (!$this->foreignKeyExists('chat_sessions', 'chat_sessions_doctor_id_foreign')) {
                Schema::table('chat_sessions', function (Blueprint $table) {
                    $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
                });
            }
        }

        // chat_messages -> chat_sessions
        if (Schema::hasTable('chat_messages') && Schema::hasTable('chat_sessions')) {
            if (!$this->foreignKeyExists('chat_messages', 'chat_messages_chat_session_id_foreign')) {
                Schema::table('chat_messages', function (Blueprint $table) {
                    $table->foreign('chat_session_id')->references('id')->on('chat_sessions')->onDelete('cascade');
                });
            }
        }

        // landing_page_visits -> doctors
        if (Schema::hasTable('landing_page_visits') && Schema::hasTable('doctors')) {
            if (!$this->foreignKeyExists('landing_page_visits', 'landing_page_visits_doctor_id_foreign')) {
                Schema::table('landing_page_visits', function (Blueprint $table) {
                    $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
                });
            }
        }

        // doctor_blog_posts -> doctors
        if (Schema::hasTable('doctor_blog_posts') && Schema::hasTable('doctors')) {
            if (!$this->foreignKeyExists('doctor_blog_posts', 'doctor_blog_posts_doctor_id_foreign')) {
                Schema::table('doctor_blog_posts', function (Blueprint $table) {
                    $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
                });
            }
        }

        // doctor_chat_messages -> doctors
        if (Schema::hasTable('doctor_chat_messages') && Schema::hasTable('doctors')) {
            if (!$this->foreignKeyExists('doctor_chat_messages', 'doctor_chat_messages_doctor_id_foreign')) {
                Schema::table('doctor_chat_messages', function (Blueprint $table) {
                    $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
                });
            }
        }

        // doctor_landing_pages -> doctors
        if (Schema::hasTable('doctor_landing_pages') && Schema::hasTable('doctors')) {
            if (!$this->foreignKeyExists('doctor_landing_pages', 'doctor_landing_pages_doctor_id_foreign')) {
                Schema::table('doctor_landing_pages', function (Blueprint $table) {
                    $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
                });
            }
        }

        // doctor_notes -> doctors
        if (Schema::hasTable('doctor_notes') && Schema::hasTable('doctors')) {
            if (!$this->foreignKeyExists('doctor_notes', 'doctor_notes_doctor_id_foreign')) {
                Schema::table('doctor_notes', function (Blueprint $table) {
                    $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints
        $constraints = [
            'blog_posts' => 'blog_posts_doctor_id_foreign',
            'chat_sessions' => 'chat_sessions_doctor_id_foreign',
            'chat_messages' => 'chat_messages_chat_session_id_foreign',
            'landing_page_visits' => 'landing_page_visits_doctor_id_foreign',
            'doctor_blog_posts' => 'doctor_blog_posts_doctor_id_foreign',
            'doctor_chat_messages' => 'doctor_chat_messages_doctor_id_foreign',
            'doctor_landing_pages' => 'doctor_landing_pages_doctor_id_foreign',
            'doctor_notes' => 'doctor_notes_doctor_id_foreign',
        ];

        foreach ($constraints as $table => $constraint) {
            if (Schema::hasTable($table) && $this->foreignKeyExists($table, $constraint)) {
                Schema::table($table, function (Blueprint $table) use ($constraint) {
                    $table->dropForeign($constraint);
                });
            }
        }
    }

    /**
     * Check if a foreign key constraint exists
     */
    private function foreignKeyExists(string $table, string $constraint): bool
    {
        try {
            $connection = Schema::getConnection();
            $database = $connection->getDatabaseName();

            if ($connection->getDriverName() === 'mysql') {
                $result = $connection->select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = ?
                    AND TABLE_NAME = ?
                    AND CONSTRAINT_NAME = ?
                ", [$database, $table, $constraint]);

                return !empty($result);
            }

            // For SQLite and other databases, assume constraint doesn't exist
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
};

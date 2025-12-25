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
        Schema::create('analytics_dashboards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->json('widgets')->nullable();
            $table->json('filters')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_public')->default(false);
            $table->string('slug')->unique();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dashboard_id');
            $table->string('widget_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('config')->nullable();
            $table->json('data_source')->nullable();
            $table->integer('position')->default(0);
            $table->integer('width')->default(6);
            $table->integer('height')->default(4);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('dashboard_id')
                ->references('id')
                ->on('analytics_dashboards')
                ->onDelete('cascade');

            // Indexes
            $table->index('widget_type');
            $table->index('dashboard_id');
        });

        Schema::create('analytics_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dashboard_id')->nullable();
            $table->string('metric_name')->index();
            $table->string('metric_type');
            $table->decimal('value', 15, 2);
            $table->string('unit')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('recorded_at');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('dashboard_id')
                ->references('id')
                ->on('analytics_dashboards')
                ->onDelete('set null');

            // Indexes for query optimization
            $table->index(['metric_name', 'recorded_at']);
            $table->index('metric_type');
            $table->index('recorded_at');
        });

        Schema::create('dashboard_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dashboard_id');
            $table->string('filter_name');
            $table->string('filter_type');
            $table->json('filter_values')->nullable();
            $table->string('comparison_operator')->default('equals');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('dashboard_id')
                ->references('id')
                ->on('analytics_dashboards')
                ->onDelete('cascade');

            // Indexes
            $table->index('dashboard_id');
            $table->index('filter_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_filters');
        Schema::dropIfExists('analytics_metrics');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('analytics_dashboards');
    }
};

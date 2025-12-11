<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip this migration for SQLite as it causes issues
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Handle MySQL/MariaDB
        if (DB::getDriverName() === 'mysql') {
            // SQLite doesn't support MODIFY COLUMN directly
            // Create a new table with the updated enum and copy data
            Schema::create('users_new', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->enum('role', ['patient', 'doctor', 'admin', 'sub_user'])->default('patient');
                $table->string('phone')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->enum('gender', ['male', 'female', 'other'])->nullable();
                $table->text('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('zip_code')->nullable();
                $table->string('emergency_contact_name')->nullable();
                $table->string('emergency_contact_phone')->nullable();
                $table->string('stripe_customer_id')->nullable();
                $table->decimal('monthly_cost_limit', 8, 2)->nullable();
                $table->timestamp('subscription_ends_at')->nullable();
                $table->boolean('subscription_active')->default(false);
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });

            // Copy data from old table to new table - only copy existing columns
            $existingColumns = collect(DB::select("SHOW COLUMNS FROM users"))->pluck('Field')->toArray();

            // Define target columns in the new table
            $targetColumns = ['id', 'name', 'email', 'email_verified_at', 'password', 'role', 'phone', 'date_of_birth', 'gender', 'address', 'city', 'state', 'zip_code', 'emergency_contact_name', 'emergency_contact_phone', 'stripe_customer_id', 'monthly_cost_limit', 'subscription_ends_at', 'subscription_active', 'hospital_id', 'remember_token', 'created_at', 'updated_at'];

            // Find intersection of existing and target columns
            $columnsToCopy = array_intersect($targetColumns, $existingColumns);

            // Build INSERT statement with only existing columns
            if (!empty($columnsToCopy)) {
                $columnsList = implode(', ', $columnsToCopy);
                DB::statement("INSERT INTO users_new ($columnsList) SELECT $columnsList FROM users");
            }

            // Disable foreign key checks temporarily to allow table drop
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Drop old table and rename new table
            Schema::drop('users');
            Schema::rename('users_new', 'users');

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            // MySQL/MariaDB approach
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin', 'sub_user') DEFAULT 'patient'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip this migration for SQLite as it causes issues
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Handle MySQL/MariaDB
        if (DB::getDriverName() === 'mysql') {
            // SQLite doesn't support MODIFY COLUMN directly
            // Create a new table with the original enum and copy data
            Schema::create('users_new', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->enum('role', ['patient', 'doctor', 'admin'])->default('patient');
                $table->string('phone')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->enum('gender', ['male', 'female', 'other'])->nullable();
                $table->text('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('zip_code')->nullable();
                $table->string('emergency_contact_name')->nullable();
                $table->string('emergency_contact_phone')->nullable();
                $table->string('stripe_customer_id')->nullable();
                $table->decimal('monthly_cost_limit', 8, 2)->nullable();
                $table->timestamp('subscription_ends_at')->nullable();
                $table->boolean('subscription_active')->default(false);
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });

            // Copy data from old table to new table - only copy existing columns
            $existingColumns = collect(DB::select("SHOW COLUMNS FROM users"))->pluck('Field')->toArray();

            // Define target columns in the new table
            $targetColumns = ['id', 'name', 'email', 'email_verified_at', 'password', 'role', 'phone', 'date_of_birth', 'gender', 'address', 'city', 'state', 'zip_code', 'emergency_contact_name', 'emergency_contact_phone', 'stripe_customer_id', 'monthly_cost_limit', 'subscription_ends_at', 'subscription_active', 'hospital_id', 'remember_token', 'created_at', 'updated_at'];

            // Find intersection of existing and target columns
            $columnsToCopy = array_intersect($targetColumns, $existingColumns);

            // Build INSERT statement with only existing columns
            if (!empty($columnsToCopy)) {
                $columnsList = implode(', ', $columnsToCopy);
                DB::statement("INSERT INTO users_new ($columnsList) SELECT $columnsList FROM users");
            }

            // Disable foreign key checks temporarily to allow table drop
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Drop old table and rename new table
            Schema::drop('users');
            Schema::rename('users_new', 'users');

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            // MySQL/MariaDB approach
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin') DEFAULT 'patient'");
        }
    }
};

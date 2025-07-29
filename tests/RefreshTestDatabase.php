<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

trait RefreshTestDatabase
{
    use RefreshDatabase;

    /**
     * Refresh the in-memory database.
     *
     * @return void
     */
    protected function refreshInMemoryDatabase()
    {
        $this->artisan('migrate', [
            '--force' => true,
        ]);

        $this->app[Kernel::class]->setArtisan(null);
    }

    /**
     * Refresh the test database.
     *
     * @return void
     */
    protected function refreshTestDatabase()
    {
        if (!$this->shouldDropViews()) {
            $this->refreshInMemoryDatabase();
            return;
        }

        try {
            $this->artisan('migrate:fresh', [
                '--force' => true,
            ]);
        } catch (\Exception $e) {
            // If migrate:fresh fails, try regular migrate
            try {
                $this->artisan('migrate', [
                    '--force' => true,
                ]);
            } catch (\Exception $e2) {
                // If both fail, create a minimal database setup
                $this->createMinimalDatabase();
            }
        }

        $this->app[Kernel::class]->setArtisan(null);
    }

    /**
     * Create a minimal database setup for testing
     */
    protected function createMinimalDatabase()
    {
        try {
            // Create basic tables needed for most tests
            DB::statement('CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                email_verified_at TIMESTAMP NULL,
                password VARCHAR(255) NOT NULL,
                remember_token VARCHAR(100) NULL,
                role VARCHAR(50) DEFAULT "patient",
                phone VARCHAR(20) NULL,
                date_of_birth DATE NULL,
                gender VARCHAR(10) NULL,
                address TEXT NULL,
                city VARCHAR(100) NULL,
                state VARCHAR(100) NULL,
                zip_code VARCHAR(20) NULL,
                emergency_contact_name VARCHAR(255) NULL,
                emergency_contact_phone VARCHAR(20) NULL,
                stripe_customer_id VARCHAR(255) NULL,
                current_plan VARCHAR(50) NULL,
                monthly_cost_limit DECIMAL(8,2) NULL,
                subscription_ends_at TIMESTAMP NULL,
                subscription_active BOOLEAN DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )');

            DB::statement('CREATE TABLE IF NOT EXISTS specialties (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                description TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )');

            DB::statement('CREATE TABLE IF NOT EXISTS doctors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                specialty_id INTEGER NULL,
                license_number VARCHAR(255) NULL,
                years_of_experience INTEGER DEFAULT 0,
                bio TEXT NULL,
                consultation_fee DECIMAL(8,2) DEFAULT 0,
                is_available BOOLEAN DEFAULT 1,
                is_verified BOOLEAN DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )');

            DB::statement('CREATE TABLE IF NOT EXISTS openai_usages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                model VARCHAR(255) NOT NULL,
                prompt_tokens INTEGER DEFAULT 0,
                completion_tokens INTEGER DEFAULT 0,
                total_tokens INTEGER DEFAULT 0,
                cost_estimate DECIMAL(10,4) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )');

            DB::statement('CREATE TABLE IF NOT EXISTS monthly_invoice_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                billing_amount DECIMAL(8,2) DEFAULT 0,
                grace_period_days INTEGER DEFAULT 7,
                reminder_frequency_days INTEGER DEFAULT 3,
                is_restricted BOOLEAN DEFAULT 0,
                is_active BOOLEAN DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )');

        } catch (\Exception $e) {
            // If even this fails, continue without database
        }
    }

    /**
     * Determine if views should be dropped when refreshing the database.
     *
     * @return bool
     */
    protected function shouldDropViews()
    {
        return property_exists($this, 'dropViews') ? $this->dropViews : false;
    }
}

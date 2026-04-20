<?php

namespace Tests\Unit\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_sms_provider_country_table_exists()
    {
        $this->assertTrue(Schema::hasTable('sms_provider_country'));
    }

    /** @test */
    public function test_sms_provider_country_table_has_correct_columns()
    {
        $columns = [
            'id',
            'provider_key',
            'country_code',
            'country_name',
            'is_active',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('sms_provider_country', $column),
                "Column {$column} should exist in sms_provider_country table"
            );
        }
    }

    /** @test */
    public function test_sms_provider_country_table_has_unique_constraint()
    {
        // This test verifies that the unique constraint exists
        // by attempting to insert duplicate records
        \DB::table('sms_provider_country')->insert([
            'provider_key' => 'twilio',
            'country_code' => 'US',
            'country_name' => 'United States',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // This should fail due to unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);

        \DB::table('sms_provider_country')->insert([
            'provider_key' => 'twilio',
            'country_code' => 'US',
            'country_name' => 'United States',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function test_doctors_table_has_sms_provider_column()
    {
        $this->assertTrue(Schema::hasColumn('doctors', 'sms_provider'));
    }

    /** @test */
    public function test_hospitals_table_has_sms_provider_column()
    {
        $this->assertTrue(Schema::hasColumn('hospitals', 'sms_provider'));
    }

    /** @test */
    public function test_sms_configuration_logs_table_exists()
    {
        $this->assertTrue(Schema::hasTable('sms_configuration_logs'));
    }

    /** @test */
    public function test_sms_send_logs_table_exists()
    {
        $this->assertTrue(Schema::hasTable('sms_send_logs'));
    }
}

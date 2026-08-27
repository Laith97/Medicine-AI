<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\DataMigration;
use App\Models\DataMigrationRecord;
use App\Models\DataMigrationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;

class AdminDataMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = Admin::factory()->create();
        // Ensure a User exists with same ID for FK (data_migrations.user_id references users)
        if (!\App\Models\User::where('id', $this->admin->id)->exists()) {
            \App\Models\User::factory()->create(['id' => $this->admin->id, 'role' => 'admin']);
        }
    }

    protected function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'admin');
    }

    // === INDEX ===

    public function test_admin_can_access_data_migration_index()
    {
        DataMigration::factory()->count(2)->create(['user_id' => $this->admin->id]);
        $response = $this->actingAsAdmin()->get(route('admin.data-migration.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.data-migration.index');
        $response->assertViewHas('stats');
        $response->assertViewHas('migrations');
    }

    public function test_index_stats_counts_correctly()
    {
        DataMigration::factory()->create(['status' => 'completed', 'user_id' => $this->admin->id]);
        DataMigration::factory()->create(['status' => 'failed', 'user_id' => $this->admin->id]);
        DataMigration::factory()->create(['status' => 'in_progress', 'user_id' => $this->admin->id]);
        DataMigration::factory()->create(['status' => 'pending', 'user_id' => $this->admin->id]);

        $response = $this->actingAsAdmin()->get(route('admin.data-migration.index'));
        $this->assertEquals(4, $response->viewData('stats')['total_migrations']);
        $this->assertEquals(1, $response->viewData('stats')['completed']);
        $this->assertEquals(1, $response->viewData('stats')['failed']);
        $this->assertEquals(1, $response->viewData('stats')['in_progress']);
    }

    public function test_guest_cannot_access_data_migration()
    {
        $response = $this->get(route('admin.data-migration.index'));
        $this->assertTrue(in_array($response->status(), [302, 401]));
    }

    // === CREATE ===

    public function test_admin_can_access_create_page()
    {
        $response = $this->actingAsAdmin()->get(route('admin.data-migration.create'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.data-migration.create');
        $response->assertViewHas('entityTypes');
    }

    // === STORE ===

    public function test_store_creates_migration_with_csv()
    {
        $file = UploadedFile::fake()->create('patients.csv', 10, 'text/csv');
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.store'), [
            'name' => 'Test Migration CSV',
            'description' => 'Test desc',
            'source_type' => 'csv',
            'file' => $file,
            'entity_type' => 'patient',
            'incremental_sync' => false,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('data_migrations', [
            'name' => 'Test Migration CSV',
            'source_type' => 'csv',
            'entity_type' => 'patient',
            'status' => 'pending',
        ]);
        $migration = DataMigration::first();
        Storage::disk('local')->assertExists($migration->source_path);
    }

    public function test_store_creates_migration_with_excel()
    {
        $file = UploadedFile::fake()->create('doctors.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.store'), [
            'name' => 'Excel Migration',
            'source_type' => 'excel',
            'file' => $file,
            'entity_type' => 'doctor',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('data_migrations', ['name' => 'Excel Migration', 'source_type' => 'excel']);
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.store'), []);
        $response->assertSessionHasErrors(['name', 'source_type', 'entity_type']);
    }

    public function test_store_rejects_invalid_entity_type()
    {
        $file = UploadedFile::fake()->create('test.csv', 10, 'text/csv');
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.store'), [
            'name' => 'Invalid Entity',
            'source_type' => 'csv',
            'file' => $file,
            'entity_type' => 'invalid_type',
        ]);
        $response->assertSessionHasErrors(['entity_type']);
    }

    public function test_store_rejects_coming_soon_source_types()
    {
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.store'), [
            'name' => 'API Source',
            'source_type' => 'api',
            'entity_type' => 'patient',
        ]);
        $response->assertSessionHasErrors(['source_type']);

        $response2 = $this->actingAsAdmin()->post(route('admin.data-migration.store'), [
            'name' => 'SQL Source',
            'source_type' => 'sql_database',
            'entity_type' => 'patient',
        ]);
        $response2->assertSessionHasErrors(['source_type']);
    }

    public function test_store_rejects_file_without_source_type()
    {
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.store'), [
            'name' => 'No file',
            'source_type' => 'csv',
            'entity_type' => 'patient',
            // no file
        ]);
        $response->assertSessionHasErrors(['file']);
    }

    public function test_store_handles_incremental_sync_flag()
    {
        $file = UploadedFile::fake()->create('test.csv', 10, 'text/csv');
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.store'), [
            'name' => 'Incremental Test',
            'source_type' => 'csv',
            'file' => $file,
            'entity_type' => 'patient',
            'incremental_sync' => true,
        ]);
        $this->assertDatabaseHas('data_migrations', ['name' => 'Incremental Test', 'incremental_sync' => true]);
    }

    public function test_store_with_template()
    {
        $template = DataMigrationTemplate::factory()->create([
            'entity_type' => 'patient',
            'field_mapping' => ['name' => 'first_name'],
            'validation_rules' => [],
        ]);
        $file = UploadedFile::fake()->create('test.csv', 10, 'text/csv');
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.store'), [
            'name' => 'Template Migration',
            'source_type' => 'csv',
            'file' => $file,
            'entity_type' => 'patient',
            'template_id' => $template->id,
        ]);
        $response->assertRedirect(route('admin.data-migration.preview', DataMigration::first()));
        $this->assertEquals($template->field_mapping, DataMigration::first()->field_mapping);
    }

    public function test_store_rejects_invalid_template_id()
    {
        $file = UploadedFile::fake()->create('test.csv', 10, 'text/csv');
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.store'), [
            'name' => 'Bad Template',
            'source_type' => 'csv',
            'file' => $file,
            'entity_type' => 'patient',
            'template_id' => 99999,
        ]);
        $response->assertSessionHasErrors(['template_id']);
    }

    // === PREVIEW ===

    public function test_preview_parses_csv_and_returns_view()
    {
        Storage::fake('local');
        $content = "first_name,last_name,email\nJohn,Doe,john@example.com\nJane,Smith,jane@example.com\n";
        $file = UploadedFile::fake()->createWithContent('patients.csv', $content);
        $this->actingAsAdmin()->post(route('admin.data-migration.store'), [
            'name' => 'Preview Test',
            'source_type' => 'csv',
            'file' => $file,
            'entity_type' => 'patient',
        ]);
        $migration = DataMigration::first();
        $response = $this->actingAsAdmin()->get(route('admin.data-migration.preview', $migration));
        $response->assertStatus(200);
        $response->assertViewIs('admin.data-migration.preview');
        $response->assertViewHas('headers');
        $response->assertViewHas('rows');
        $this->assertEquals(['first_name','last_name','email'], $response->viewData('headers'));
    }

    public function test_preview_handles_missing_file_gracefully()
    {
        $migration = DataMigration::factory()->create([
            'user_id' => $this->admin->id,
            'source_path' => 'nonexistent.csv',
            'entity_type' => 'patient',
        ]);
        $response = $this->actingAsAdmin()->get(route('admin.data-migration.preview', $migration));
        // Should throw or handle error - we expect exception or redirect; but should not be 200 blank
        // Currently it will throw FileNotFound - we check it doesn't return 200 silently
        $this->assertTrue(in_array($response->status(), [500, 404, 302]));
    }

    // === START ===

    public function test_start_dispatches_job_and_sets_in_progress()
    {
        Queue::fake();
        $migration = DataMigration::factory()->create([
            'user_id' => $this->admin->id,
            'status' => 'pending',
            'entity_type' => 'patient',
        ]);
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.start', $migration), [
            'field_mapping' => ['first_name' => 'first_name', 'email' => 'email'],
        ]);
        $response->assertRedirect(route('admin.data-migration.show', $migration));
        $this->assertDatabaseHas('data_migrations', ['id' => $migration->id, 'status' => 'in_progress']);
        Queue::assertPushed(\App\Jobs\ProcessDataMigration::class);
    }

    public function test_start_rejects_if_already_in_progress()
    {
        $migration = DataMigration::factory()->create([
            'user_id' => $this->admin->id,
            'status' => 'in_progress',
        ]);
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.start', $migration), [
            'field_mapping' => ['a' => 'b'],
        ]);
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('data_migrations', ['id' => $migration->id, 'status' => 'in_progress']);
    }

    public function test_start_validates_field_mapping_required()
    {
        $migration = DataMigration::factory()->create(['user_id' => $this->admin->id, 'status' => 'pending']);
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.start', $migration), []);
        $response->assertSessionHasErrors(['field_mapping']);
    }

    // === SHOW ===

    public function test_show_displays_migration_details()
    {
        $migration = DataMigration::factory()->create(['user_id' => $this->admin->id]);
        DataMigrationRecord::factory()->count(3)->create([
            'data_migration_id' => $migration->id,
            'status' => 'pending',
        ]);
        $response = $this->actingAsAdmin()->get(route('admin.data-migration.show', $migration));
        $response->assertStatus(200);
        $response->assertViewIs('admin.data-migration.show');
        $response->assertViewHas('records');
        $response->assertViewHas('failedRecords');
    }

    // === CANCEL ===

    public function test_cancel_pending_migration()
    {
        $migration = DataMigration::factory()->create(['user_id' => $this->admin->id, 'status' => 'pending']);
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.cancel', $migration));
        $response->assertRedirect();
        $this->assertDatabaseHas('data_migrations', ['id' => $migration->id, 'status' => 'cancelled']);
    }

    public function test_cancel_in_progress_migration()
    {
        $migration = DataMigration::factory()->create(['user_id' => $this->admin->id, 'status' => 'in_progress']);
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.cancel', $migration));
        $this->assertDatabaseHas('data_migrations', ['id' => $migration->id, 'status' => 'cancelled']);
    }

    public function test_cancel_rejects_completed_migration()
    {
        $migration = DataMigration::factory()->create(['user_id' => $this->admin->id, 'status' => 'completed']);
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.cancel', $migration));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('data_migrations', ['id' => $migration->id, 'status' => 'completed']);
    }

    // === DESTROY ===

    public function test_destroy_deletes_migration_and_file()
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('test.csv', 10);
        $path = $file->storeAs('data-migrations', 'testfile.csv');
        $migration = DataMigration::factory()->create([
            'user_id' => $this->admin->id,
            'source_path' => $path,
        ]);
        $response = $this->actingAsAdmin()->delete(route('admin.data-migration.destroy', $migration));
        $response->assertRedirect(route('admin.data-migration.index'));
        $this->assertDatabaseMissing('data_migrations', ['id' => $migration->id]);
        Storage::disk('local')->assertMissing($path);
    }

    // === EXPORT ERRORS ===

    public function test_export_errors_returns_csv()
    {
        $migration = DataMigration::factory()->create(['user_id' => $this->admin->id]);
        DataMigrationRecord::factory()->create([
            'data_migration_id' => $migration->id,
            'status' => 'failed',
            'source_id' => 'SRC123',
            'validation_errors' => [['message' => 'Invalid email']],
            'source_data' => ['email' => 'bad'],
        ]);
        $response = $this->actingAsAdmin()->get(route('admin.data-migration.export-errors', $migration));
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('SRC123', $response->streamedContent() ?? $response->getContent());
    }

    // === DOWNLOAD TEMPLATE ===

    public function test_download_template_returns_csv_for_valid_type()
    {
        foreach (['patient', 'doctor', 'appointment', 'department'] as $type) {
            $response = $this->actingAsAdmin()->get(route('admin.data-migration.download-template', ['entityType' => $type]));
            $response->assertStatus(200);
            $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
            $this->assertStringContainsString("import_template_{$type}.csv", $response->headers->get('Content-Disposition'));
        }
    }

    public function test_download_template_rejects_invalid_type()
    {
        $response = $this->actingAsAdmin()->get(route('admin.data-migration.download-template', ['entityType' => 'invalid_type']));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // === FIELD OPTIONS ===

    public function test_field_options_returns_json_for_valid_type()
    {
        $response = $this->actingAsAdmin()->get(route('admin.data-migration.field-options', ['entityType' => 'patient']));
        $response->assertStatus(200);
        $response->assertJsonStructure(['first_name', 'last_name', 'email']);
        $this->assertEquals('First Name', $response->json('first_name'));
    }

    public function test_field_options_returns_empty_for_invalid_type()
    {
        $response = $this->actingAsAdmin()->get(route('admin.data-migration.field-options', ['entityType' => 'unknown']));
        $response->assertStatus(200);
        $this->assertEquals([], $response->json());
    }

    // === EDGE CASES & SECURITY ===

    public function test_store_rejects_oversized_file()
    {
        // Create a fake file larger than 50MB (51200 KB) -> 60MB
        $file = UploadedFile::fake()->create('big.csv', 60000, 'text/csv');
        $response = $this->actingAsAdmin()->post(route('admin.data-migration.store'), [
            'name' => 'Big file',
            'source_type' => 'csv',
            'file' => $file,
            'entity_type' => 'patient',
        ]);
        $response->assertSessionHasErrors(['file']);
    }

    public function test_non_admin_cannot_access_data_migration()
    {
        $user = User::factory()->create(['role' => 'patient']);
        $response = $this->actingAs($user)->get(route('admin.data-migration.index'));
        // Admin middleware should block -> 403 or redirect
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    public function test_migration_progress_percentage_calculation()
    {
        $migration = DataMigration::factory()->create([
            'user_id' => $this->admin->id,
            'total_records' => 100,
            'processed_records' => 25,
        ]);
        $this->assertEquals(25.0, $migration->getProgressPercentage());
        $this->assertEquals(75, $migration->getRemainingRecords());
    }

    public function test_migration_zero_total_returns_zero_percent()
    {
        $migration = DataMigration::factory()->create([
            'user_id' => $this->admin->id,
            'total_records' => 0,
            'processed_records' => 0,
        ]);
        $this->assertEquals(0, $migration->getProgressPercentage());
    }

    public function test_status_badge_classes()
    {
        $this->assertEquals('bg-secondary', (new DataMigration(['status' => 'pending']))->getStatusBadgeClass());
        $this->assertEquals('bg-primary', (new DataMigration(['status' => 'in_progress']))->getStatusBadgeClass());
        $this->assertEquals('bg-success', (new DataMigration(['status' => 'completed']))->getStatusBadgeClass());
        $this->assertEquals('bg-danger', (new DataMigration(['status' => 'failed']))->getStatusBadgeClass());
        $this->assertEquals('bg-warning', (new DataMigration(['status' => 'cancelled']))->getStatusBadgeClass());
    }
}

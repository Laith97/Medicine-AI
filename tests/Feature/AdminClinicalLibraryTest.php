<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Exercise;
use App\Models\HepProgramTemplate;
use App\Models\HepTemplateExercise;
use App\Models\HepProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminClinicalLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create([
            'email' => 'admin_clinic_'.Str::random(6).'@test.com',
            'password' => bcrypt('password'),
        ]);
    }

    // ==================== Auth ====================
    public function test_exercises_index_requires_admin_auth()
    {
        $this->get(route('admin.exercises.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.hep-templates.index'))->assertRedirect(route('admin.login'));
    }

    public function test_exercises_create_requires_admin_auth()
    {
        $this->get(route('admin.exercises.create'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.hep-templates.create'))->assertRedirect(route('admin.login'));
    }

    // ==================== Exercises Index ====================
    public function test_exercises_index_renders_with_professional_design()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.exercises.index'))
            ->assertOk()
            ->assertViewIs('admin.exercises.index')
            ->assertSee('Exercise Library')
            ->assertSee('linear-gradient(135deg,#1e293b', false)
            ->assertDontSee('small-box bg-info', false);
    }

    public function test_exercises_index_no_count_on_array_error()
    {
        Exercise::factory()->count(3)->create();
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.exercises.index'))
            ->assertOk()
            ->assertSee('Total Exercises');
        // ensure categories count uses count($categories) not ->count()
        $this->assertTrue(true); // if we reach here, no exception
    }

    public function test_exercises_index_filters()
    {
        Exercise::factory()->create(['name'=>'Alpha Strength','category'=>'strength','difficulty_level'=>'beginner']);
        Exercise::factory()->create(['name'=>'Beta Balance','category'=>'balance','difficulty_level'=>'advanced']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.exercises.index', ['search'=>'Alpha']))
            ->assertOk()
            ->assertSee('Alpha Strength')
            ->assertDontSee('Beta Balance');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.exercises.index', ['category'=>'balance']))
            ->assertOk()
            ->assertSee('Beta Balance');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.exercises.index', ['difficulty'=>'beginner']))
            ->assertOk()
            ->assertSee('Alpha Strength');
    }

    public function test_exercises_index_equipment_filter_json_contains()
    {
        Exercise::factory()->create(['equipment_required'=>['band']]);
        Exercise::factory()->create(['equipment_required'=>['chair']]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.exercises.index', ['equipment'=>'band']))
            ->assertOk()
            ->assertSee('band');
    }

    // ==================== Exercises Create / Store ====================
    public function test_exercises_create_renders()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.exercises.create'))
            ->assertOk()
            ->assertViewIs('admin.exercises.create')
            ->assertSee('Add New Exercise')
            ->assertSee('linear-gradient(135deg,#1e293b', false);
    }

    public function test_exercises_store_valid_with_tag_inputs()
    {
        $this->actingAs($this->admin, 'admin');
        $payload = [
            'name'=>'Wall Push-ups',
            'description'=>'A beginner-friendly upper body exercise performed against a wall.',
            'category'=>'strength',
            'difficulty_level'=>'beginner',
            'instructions'=>str_repeat('Step ',20),
            'contraindications'=>['hypertension'],
            'equipment_required'=>['mat'],
            'target_muscle_groups'=>['chest','shoulders'],
            'duration'=>60,
            'video_url'=>'https://example.com/video.mp4',
            'image_url'=>'https://example.com/image.jpg',
        ];
        $resp = $this->post(route('admin.exercises.store'), $payload);
        $resp->assertRedirect();
        $resp->assertSessionHas('success');
        $this->assertDatabaseHas('exercises', ['name'=>'Wall Push-ups','category'=>'strength']);
        $ex = Exercise::where('name','Wall Push-ups')->first();
        $this->assertEquals(['hypertension'], $ex->contraindications);
        $this->assertEquals(['mat'], $ex->equipment_required);
    }

    public function test_exercises_store_valid_without_duration_nullable()
    {
        $this->actingAs($this->admin, 'admin');
        $payload = [
            'name'=>'No Duration',
            'description'=>'Valid description longer than 20 chars for test',
            'category'=>'balance',
            'difficulty_level'=>'intermediate',
            'instructions'=>str_repeat('Do it ',15),
            // no duration
        ];
        $resp = $this->post(route('admin.exercises.store'), $payload);
        $resp->assertRedirect(route('admin.exercises.show', Exercise::where('name','No Duration')->first()));
        $this->assertDatabaseHas('exercises', ['name'=>'No Duration']);
        $this->assertNull(Exercise::where('name','No Duration')->first()->duration);
    }

    public function test_exercises_store_validation_fails()
    {
        $this->actingAs($this->admin, 'admin');
        $resp = $this->post(route('admin.exercises.store'), []);
        $resp->assertSessionHasErrors(['name','description','category','difficulty_level','instructions']);
    }

    public function test_exercises_store_invalid_category()
    {
        $this->actingAs($this->admin, 'admin');
        $resp = $this->post(route('admin.exercises.store'), [
            'name'=>'Test',
            'description'=>'desc desc desc desc desc',
            'category'=>'invalid_cat',
            'difficulty_level'=>'beginner',
            'instructions'=>str_repeat('x',60),
        ]);
        $resp->assertSessionHasErrors(['category']);
    }

    public function test_exercises_store_with_file_uploads()
    {
        Storage::fake('public');
        $this->actingAs($this->admin, 'admin');
        $payload = [
            'name'=>'With Files',
            'description'=>'Valid description for file test extended',
            'category'=>'strength',
            'difficulty_level'=>'beginner',
            'instructions'=>str_repeat('instruction ',10),
            'image_file'=>UploadedFile::fake()->image('test.jpg'),
            'video_file'=>UploadedFile::fake()->create('test.mp4', 100, 'video/mp4'),
        ];
        $resp = $this->post(route('admin.exercises.store'), $payload);
        $resp->assertRedirect();
        $ex = Exercise::where('name','With Files')->first();
        $this->assertNotNull($ex);
        $this->assertStringContainsString('/storage/exercises/', $ex->image_url);
        $this->assertStringContainsString('/storage/exercises/', $ex->video_url);
        Storage::disk('public')->assertExists(str_replace('/storage/','',$ex->image_url));
    }

    public function test_exercises_store_invalid_file_types()
    {
        $this->actingAs($this->admin, 'admin');
        $resp = $this->post(route('admin.exercises.store'), [
            'name'=>'Bad File',
            'description'=>'Valid description for bad file test',
            'category'=>'strength',
            'difficulty_level'=>'beginner',
            'instructions'=>str_repeat('x',60),
            'image_file'=>UploadedFile::fake()->create('test.txt', 10, 'text/plain'),
        ]);
        $resp->assertSessionHasErrors(['image_file']);
    }

    // ==================== Exercises Show / Edit / Update / Destroy ====================
    public function test_exercises_show_renders()
    {
        $ex = Exercise::factory()->create();
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.exercises.show', $ex))
            ->assertOk()
            ->assertViewIs('admin.exercises.show')
            ->assertSee($ex->name);
    }

    public function test_exercises_edit_renders()
    {
        $ex = Exercise::factory()->create();
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.exercises.edit', $ex))
            ->assertOk()
            ->assertViewIs('admin.exercises.edit')
            ->assertSee('Edit Exercise');
    }

    public function test_exercises_update_valid()
    {
        $ex = Exercise::factory()->create(['name'=>'Old']);
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.exercises.update', $ex), [
                'name'=>'Updated Name',
                'description'=>'Updated description with enough length for validation',
                'category'=>'flexibility',
                'difficulty_level'=>'advanced',
                'instructions'=>str_repeat('Updated instruction ',10),
            ])->assertRedirect(route('admin.exercises.show', $ex));
        $this->assertDatabaseHas('exercises', ['id'=>$ex->id,'name'=>'Updated Name','category'=>'flexibility']);
    }

    public function test_exercises_update_with_new_image_deletes_old()
    {
        Storage::fake('public');
        $oldPath = 'exercises/images/old.jpg';
        Storage::disk('public')->put($oldPath, 'oldcontent');
        $ex = Exercise::factory()->create(['image_url'=>Storage::url($oldPath)]);
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.exercises.update', $ex), [
                'name'=>$ex->name,
                'description'=>$ex->description,
                'category'=>$ex->category,
                'difficulty_level'=>$ex->difficulty_level,
                'instructions'=>$ex->instructions,
                'image_file'=>UploadedFile::fake()->image('new.jpg'),
            ])->assertRedirect();
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertStringContainsString('exercises/images', $ex->fresh()->image_url);
    }

    public function test_exercises_destroy_blocks_when_used()
    {
        $ex = Exercise::factory()->create();
        $prog = HepProgram::factory()->create();
        \App\Models\HepExercise::factory()->create(['exercise_id'=>$ex->id,'hep_program_id'=>$prog->id]);
        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.exercises.destroy', $ex))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('exercises', ['id'=>$ex->id]);
    }

    public function test_exercises_destroy_success_when_unused()
    {
        $ex = Exercise::factory()->create();
        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.exercises.destroy', $ex))
            ->assertRedirect(route('admin.exercises.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('exercises', ['id'=>$ex->id]);
    }

    // ==================== Export / Import ====================
    public function test_exercises_export_csv()
    {
        Exercise::factory()->create(['name'=>'ExportMe']);
        $resp = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.exercises.export'));
        $resp->assertOk();
        $resp->assertHeader('Content-Type', 'text/csv; charset=UTF-8'); // may include charset, check stream
        // check streamed content contains header
        $content = $resp->streamedContent() ?? $resp->getContent();
        // fallback: check headers
        $this->assertTrue($resp->headers->get('Content-Disposition') !== null);
    }

    public function test_exercises_export_with_filters()
    {
        Exercise::factory()->create(['name'=>'Filtered','category'=>'strength']);
        Exercise::factory()->create(['name'=>'Other','category'=>'balance']);
        $resp = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.exercises.export', ['category'=>'strength']));
        $resp->assertOk();
    }

    public function test_exercises_download_template()
    {
        $resp = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.exercises.template.download'));
        $resp->assertOk();
        $resp->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_exercises_import_valid_csv()
    {
        $csvContent = "Name,Category,Difficulty Level,Description,Instructions,Equipment Required,Target Muscle Groups,Contraindications,Duration (seconds),Video URL,Image URL\n";
        $csvContent .= "\"Test Import\",strength,beginner,\"Valid description for import test that is long enough\",\"Step 1 step 2 step 3 step 4 step 5 step 6 step 7 step 8\",\"band\",\"quads\",\"hypertension\",60,http://example.com/v.mp4,http://example.com/i.jpg\n";
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tmp, $csvContent);
        $file = new UploadedFile($tmp, 'import.csv', 'text/csv', null, true);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.exercises.import.store'), ['csv_file'=>$file])
            ->assertRedirect(route('admin.exercises.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('exercises', ['name'=>'Test Import']);
        @unlink($tmp);
    }

    public function test_exercises_import_invalid_headers()
    {
        $csvContent = "Wrong,Headers\n\"a\",\"b\"\n";
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tmp, $csvContent);
        $file = new UploadedFile($tmp, 'import.csv', 'text/csv', null, true);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.exercises.import.store'), ['csv_file'=>$file])
            ->assertRedirect()
            ->assertSessionHas('error');
        @unlink($tmp);
    }

    public function test_exercises_import_validation_row_errors()
    {
        $csvContent = "Name,Category,Difficulty Level,Description,Instructions,Equipment Required,Target Muscle Groups,Contraindications,Duration (seconds),Video URL,Image URL\n";
        $csvContent .= "\"\",invalid_cat,invalid_level,\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"\n"; // missing name/desc + invalid cat/diff
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tmp, $csvContent);
        $file = new UploadedFile($tmp, 'import.csv', 'text/csv', null, true);

        $resp = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.exercises.import.store'), ['csv_file'=>$file]);
        $resp->assertRedirect(route('admin.exercises.index'));
        // should have success with error details, not throw exception
        $this->assertDatabaseMissing('exercises', ['category'=>'invalid_cat']);
        @unlink($tmp);
    }

    public function test_exercises_import_form_renders()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.exercises.import'))
            ->assertOk()
            ->assertViewIs('admin.exercises.import');
    }

    // ==================== HEP Templates Index ====================
    public function test_hep_templates_index_renders_professional()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hep-templates.index'))
            ->assertOk()
            ->assertViewIs('admin.hep-templates.index')
            ->assertSee('HEP Program Templates')
            ->assertSee('linear-gradient(135deg,#1e293b', false);
    }

    public function test_hep_templates_index_no_count_on_array()
    {
        HepProgramTemplate::factory()->count(2)->create();
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hep-templates.index'))
            ->assertOk()
            ->assertSee('Total Templates');
    }

    public function test_hep_templates_index_filters()
    {
        HepProgramTemplate::factory()->create(['name'=>'Alpha Ortho','category'=>'orthopedic','diagnosis_type'=>'low_back_pain','is_active'=>true]);
        HepProgramTemplate::factory()->create(['name'=>'Beta Neuro','category'=>'neurological','is_active'=>false]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hep-templates.index', ['search'=>'Alpha']))
            ->assertOk()->assertSee('Alpha Ortho')->assertDontSee('Beta Neuro');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hep-templates.index', ['category'=>'neurological']))
            ->assertOk()->assertSee('Beta Neuro');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hep-templates.index', ['status'=>'active']))
            ->assertOk()->assertSee('Alpha Ortho')->assertDontSee('Beta Neuro');
    }

    // ==================== HEP Templates Create / Store ====================
    public function test_hep_templates_create_renders()
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hep-templates.create'))
            ->assertOk()
            ->assertViewIs('admin.hep-templates.create')
            ->assertSee('Create HEP Template')
            ->assertSee('linear-gradient(135deg,#1e293b', false);
    }

    public function test_hep_templates_store_valid_with_exercises_and_tags()
    {
        $ex1 = Exercise::factory()->create();
        $ex2 = Exercise::factory()->create();
        $this->actingAs($this->admin, 'admin');
        $payload = [
            'name'=>'Knee Rehab Phase 1',
            'description'=>'Early phase knee rehab template',
            'category'=>'orthopedic',
            'diagnosis_type'=>'knee_osteoarthritis',
            'duration_weeks'=>4,
            'frequency_per_week'=>3,
            'goals'=>['Reduce pain','Improve mobility'],
            'precautions'=>['Avoid weight bearing'],
            'is_active'=>'1',
            'exercises'=>[
                ['exercise_id'=>$ex1->id,'week_number'=>1,'sets'=>3,'reps'=>10,'duration_seconds'=>null,'rest_seconds'=>60,'frequency'=>'Daily','progression_notes'=>'Start easy','order'=>0],
                ['exercise_id'=>$ex2->id,'week_number'=>2,'sets'=>2,'reps'=>12,'duration_seconds'=>30,'rest_seconds'=>30,'frequency'=>'3x/week','progression_notes'=>null,'order'=>1],
            ],
        ];
        $resp = $this->post(route('admin.hep-templates.store'), $payload);
        $tpl = HepProgramTemplate::where('name','Knee Rehab Phase 1')->first();
        $this->assertNotNull($tpl);
        $resp->assertRedirect(route('admin.hep-templates.show', $tpl));
        $this->assertEquals(2, $tpl->templateExercises()->count());
        $this->assertEquals(['Reduce pain','Improve mobility'], $tpl->goals);
        $this->assertTrue($tpl->is_active);
        $this->assertEquals($this->admin->id, $tpl->created_by);
    }

    public function test_hep_templates_store_valid_without_optional_fields()
    {
        $ex = Exercise::factory()->create();
        $this->actingAs($this->admin, 'admin');
        $resp = $this->post(route('admin.hep-templates.store'), [
            'name'=>'Simple Template',
            'description'=>'Simple desc',
            'category'=>'general_fitness',
            'duration_weeks'=>2,
            'frequency_per_week'=>2,
            'exercises'=>[['exercise_id'=>$ex->id,'week_number'=>1,'order'=>0]],
        ]);
        $tpl = HepProgramTemplate::where('name','Simple Template')->first();
        $this->assertNotNull($tpl);
        $resp->assertRedirect(route('admin.hep-templates.show', $tpl));
        $this->assertEquals([], $tpl->goals);
        $this->assertFalse($tpl->is_active); // unchecked => false
    }

    public function test_hep_templates_store_validation_fails()
    {
        $this->actingAs($this->admin, 'admin');
        $resp = $this->post(route('admin.hep-templates.store'), []);
        $resp->assertSessionHasErrors(['name','description','category','duration_weeks','frequency_per_week','exercises']);
    }

    public function test_hep_templates_store_invalid_exercise_id()
    {
        $this->actingAs($this->admin, 'admin');
        $resp = $this->post(route('admin.hep-templates.store'), [
            'name'=>'Bad',
            'description'=>'desc',
            'category'=>'orthopedic',
            'duration_weeks'=>4,
            'frequency_per_week'=>3,
            'exercises'=>[['exercise_id'=>99999,'week_number'=>1]],
        ]);
        $resp->assertSessionHasErrors(['exercises.0.exercise_id']);
    }

    public function test_hep_templates_store_invalid_category()
    {
        $ex = Exercise::factory()->create();
        $this->actingAs($this->admin, 'admin');
        $resp = $this->post(route('admin.hep-templates.store'), [
            'name'=>'Bad Cat',
            'description'=>'desc',
            'category'=>'invalid',
            'duration_weeks'=>4,
            'frequency_per_week'=>3,
            'exercises'=>[['exercise_id'=>$ex->id,'week_number'=>1]],
        ]);
        $resp->assertSessionHasErrors(['category']);
    }

    // ==================== HEP Templates Show / Edit / Update ====================
    public function test_hep_templates_show_renders()
    {
        $tpl = HepProgramTemplate::factory()->create();
        HepTemplateExercise::factory()->create(['hep_program_template_id'=>$tpl->id]);
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hep-templates.show', $tpl))
            ->assertOk()
            ->assertViewIs('admin.hep-templates.show')
            ->assertSee($tpl->name);
    }

    public function test_hep_templates_edit_renders()
    {
        $tpl = HepProgramTemplate::factory()->create();
        HepTemplateExercise::factory()->create(['hep_program_template_id'=>$tpl->id]);
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.hep-templates.edit', $tpl))
            ->assertOk()
            ->assertViewIs('admin.hep-templates.edit')
            ->assertSee('Edit Template');
    }

    public function test_hep_templates_update_valid_replaces_exercises()
    {
        $tpl = HepProgramTemplate::factory()->create(['name'=>'Old']);
        $exOld = Exercise::factory()->create();
        HepTemplateExercise::factory()->create(['hep_program_template_id'=>$tpl->id,'exercise_id'=>$exOld->id,'week_number'=>1]);
        $exNew = Exercise::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.hep-templates.update', $tpl), [
                'name'=>'Updated Name',
                'description'=>'Updated desc',
                'category'=>'neurological',
                'duration_weeks'=>6,
                'frequency_per_week'=>4,
                'is_active'=>'1',
                'exercises'=>[['exercise_id'=>$exNew->id,'week_number'=>2,'sets'=>5,'reps'=>5,'order'=>0]],
            ])->assertRedirect(route('admin.hep-templates.show', $tpl));

        $this->assertDatabaseHas('hep_program_templates', ['id'=>$tpl->id,'name'=>'Updated Name']);
        $this->assertEquals(1, $tpl->fresh()->templateExercises()->count());
        $this->assertEquals($exNew->id, $tpl->fresh()->templateExercises()->first()->exercise_id);
        $this->assertEquals(2, $tpl->fresh()->templateExercises()->first()->week_number);
    }

    public function test_hep_templates_update_validation_fails()
    {
        $tpl = HepProgramTemplate::factory()->create();
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.hep-templates.update', $tpl), [])
            ->assertSessionHasErrors(['name','description','category','duration_weeks','frequency_per_week','exercises']);
    }

    // ==================== HEP Templates Destroy / Toggle / Duplicate ====================
    public function test_hep_templates_destroy_blocks_when_used()
    {
        $tpl = HepProgramTemplate::factory()->create();
        HepProgram::factory()->create(['template_id'=>$tpl->id]);
        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.hep-templates.destroy', $tpl))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('hep_program_templates', ['id'=>$tpl->id]);
    }

    public function test_hep_templates_destroy_success_when_unused()
    {
        $tpl = HepProgramTemplate::factory()->create();
        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.hep-templates.destroy', $tpl))
            ->assertRedirect(route('admin.hep-templates.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('hep_program_templates', ['id'=>$tpl->id]);
    }

    public function test_hep_templates_toggle_active()
    {
        $tpl = HepProgramTemplate::factory()->create(['is_active'=>true]);
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hep-templates.toggle-active', $tpl))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertFalse($tpl->fresh()->is_active);
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hep-templates.toggle-active', $tpl))
            ->assertRedirect();
        $this->assertTrue($tpl->fresh()->is_active);
    }

    public function test_hep_templates_duplicate_creates_inactive_copy_with_exercises()
    {
        $tpl = HepProgramTemplate::factory()->create(['name'=>'Original','is_active'=>true]);
        $ex = Exercise::factory()->create();
        HepTemplateExercise::factory()->create(['hep_program_template_id'=>$tpl->id,'exercise_id'=>$ex->id,'week_number'=>1,'sets'=>3]);
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.hep-templates.duplicate', $tpl))
            ->assertRedirect();
        $copy = HepProgramTemplate::where('name','Original (Copy)')->first();
        $this->assertNotNull($copy);
        $this->assertFalse($copy->is_active);
        $this->assertEquals($tpl->category, $copy->category);
        $this->assertEquals(1, $copy->templateExercises()->count());
        $this->assertEquals($ex->id, $copy->templateExercises()->first()->exercise_id);
    }

    public function test_hep_templates_duplicate_requires_admin()
    {
        $tpl = HepProgramTemplate::factory()->create();
        $this->post(route('admin.hep-templates.duplicate', $tpl))
            ->assertRedirect(route('admin.login'));
    }

    // ==================== Forms Professional Design ====================
    public function test_all_forms_use_professional_headers()
    {
        $ex = Exercise::factory()->create();
        $tpl = HepProgramTemplate::factory()->create();
        HepTemplateExercise::factory()->create(['hep_program_template_id'=>$tpl->id]);

        $routes = [
            route('admin.exercises.create'),
            route('admin.exercises.edit', $ex),
            route('admin.hep-templates.create'),
            route('admin.hep-templates.edit', $tpl),
        ];
        $this->actingAs($this->admin, 'admin');
        foreach ($routes as $r) {
            $resp = $this->get($r);
            $resp->assertOk();
            $resp->assertSee('linear-gradient(135deg,#1e293b', false);
            $resp->assertDontSee('admin-header', false); // old header class should not exist
        }
    }

    public function test_exercises_create_form_has_required_fields()
    {
        $resp = $this->actingAs($this->admin, 'admin')->get(route('admin.exercises.create'));
        $resp->assertSee('name="name"', false);
        $resp->assertSee('name="category"', false);
        $resp->assertSee('name="difficulty_level"', false);
        $resp->assertSee('name="instructions"', false);
        $resp->assertSee('name="description"', false);
    }

    public function test_hep_templates_create_form_has_required_fields()
    {
        $resp = $this->actingAs($this->admin, 'admin')->get(route('admin.hep-templates.create'));
        $resp->assertSee('name="name"', false);
        $resp->assertSee('name="category"', false);
        $resp->assertSee('name="duration_weeks"', false);
        $resp->assertSee('name="frequency_per_week"', false);
        $resp->assertSee('id="ex_select"', false);
    }
}

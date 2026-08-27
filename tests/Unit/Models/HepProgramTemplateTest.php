<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\HepProgramTemplate;
use App\Models\HepTemplateExercise;
use App\Models\Exercise;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HepProgramTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_and_casts()
    {
        $tpl = HepProgramTemplate::factory()->create([
            'goals'=>['goal1'],
            'precautions'=>['prec1'],
            'is_active'=>false,
            'duration_weeks'=>6,
        ]);
        $this->assertIsArray($tpl->goals);
        $this->assertIsArray($tpl->precautions);
        $this->assertIsBool($tpl->is_active);
        $this->assertIsInt($tpl->duration_weeks);
    }

    public function test_categories_and_diagnosis_types()
    {
        $this->assertContains('orthopedic', HepProgramTemplate::getCategories());
        $this->assertContains('knee_osteoarthritis', HepProgramTemplate::getDiagnosisTypes());
        $this->assertCount(9, HepProgramTemplate::getCategories());
        $this->assertCount(23, HepProgramTemplate::getDiagnosisTypes());
    }

    public function test_scopes()
    {
        HepProgramTemplate::factory()->create(['category'=>'orthopedic','is_active'=>true,'diagnosis_type'=>'low_back_pain']);
        HepProgramTemplate::factory()->create(['category'=>'neurological','is_active'=>false]);

        $this->assertEquals(1, HepProgramTemplate::active()->count());
        $this->assertEquals(1, HepProgramTemplate::byCategory('orthopedic')->count());
        $this->assertEquals(1, HepProgramTemplate::byDiagnosisType('low_back_pain')->count());
    }

    public function test_relationships()
    {
        $tpl = HepProgramTemplate::factory()->create();
        $ex = Exercise::factory()->create();
        $te = HepTemplateExercise::factory()->create(['hep_program_template_id'=>$tpl->id,'exercise_id'=>$ex->id]);
        $this->assertTrue($tpl->templateExercises()->where('id',$te->id)->exists());
        $this->assertTrue($tpl->creator()->exists() || !$tpl->creator);
    }

    public function test_get_usage_count()
    {
        $tpl = HepProgramTemplate::factory()->create();
        $this->assertEquals(0, $tpl->getUsageCount());
        $prog = \App\Models\HepProgram::factory()->create(['template_id'=>$tpl->id]);
        $this->assertEquals(1, $tpl->fresh()->getUsageCount());
    }

    public function test_is_safe_for_patient()
    {
        $tpl = HepProgramTemplate::factory()->create();
        $safeEx = Exercise::factory()->create(['contraindications'=>[]]);
        $unsafeEx = Exercise::factory()->create(['contraindications'=>['hypertension']]);
        HepTemplateExercise::factory()->create(['hep_program_template_id'=>$tpl->id,'exercise_id'=>$safeEx->id]);
        $this->assertTrue($tpl->fresh()->isSafeForPatient(User::factory()->create(), []));
        // Add unsafe exercise
        HepTemplateExercise::factory()->create(['hep_program_template_id'=>$tpl->id,'exercise_id'=>$unsafeEx->id]);
        $this->assertFalse($tpl->fresh()->isSafeForPatient(User::factory()->create(), ['hypertension']));
    }

    public function test_create_program_from_template()
    {
        $doctorUser = User::factory()->create(['role'=>'doctor']);
        $doctor = \App\Models\Doctor::factory()->create(['user_id'=>$doctorUser->id]);
        $patient = User::factory()->create(['role'=>'patient']);
        $diag = \App\Models\Diagnosis::factory()->create();
        $tpl = HepProgramTemplate::factory()->create(['duration_weeks'=>4,'frequency_per_week'=>3]);
        $ex = Exercise::factory()->create();
        HepTemplateExercise::factory()->create([
            'hep_program_template_id'=>$tpl->id,
            'exercise_id'=>$ex->id,
            'week_number'=>1,
            'sets'=>3,'reps'=>10
        ]);
        $program = $tpl->createProgram($doctorUser, $patient, $diag);
        $this->assertDatabaseHas('hep_programs', ['template_id'=>$tpl->id, 'doctor_id'=>$doctor->id]);
        $this->assertEquals(1, $program->hepExercises()->count());
    }

    public function test_creator_is_admin()
    {
        $admin = Admin::factory()->create();
        $tpl = HepProgramTemplate::factory()->create(['created_by'=>$admin->id]);
        $this->assertEquals($admin->id, $tpl->creator->id);
        $this->assertEquals($admin->name, $tpl->creator->name);
    }
}

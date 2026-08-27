<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\HepTemplateExercise;
use App\Models\HepProgramTemplate;
use App\Models\Exercise;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HepTemplateExerciseTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_and_casts()
    {
        $te = HepTemplateExercise::factory()->create([
            'week_number'=>2,
            'order'=>1,
            'sets'=>3,
        ]);
        $this->assertIsInt($te->week_number);
        $this->assertIsInt($te->order);
        $this->assertIsInt($te->sets);
    }

    public function test_relationships()
    {
        $ex = Exercise::factory()->create();
        $tpl = HepProgramTemplate::factory()->create();
        $te = HepTemplateExercise::factory()->create(['exercise_id'=>$ex->id,'hep_program_template_id'=>$tpl->id]);
        $this->assertEquals($ex->id, $te->exercise->id);
        $this->assertEquals($tpl->id, $te->template->id);
    }

    public function test_scopes()
    {
        $tpl = HepProgramTemplate::factory()->create();
        HepTemplateExercise::factory()->create(['hep_program_template_id'=>$tpl->id,'week_number'=>1]);
        HepTemplateExercise::factory()->create(['hep_program_template_id'=>$tpl->id,'week_number'=>2]);
        $this->assertEquals(1, HepTemplateExercise::byWeek(1)->count());
        $this->assertEquals(2, HepTemplateExercise::byTemplate($tpl->id)->count());
    }

    public function test_total_duration_calculation()
    {
        $te = HepTemplateExercise::factory()->create(['sets'=>3,'duration_seconds'=>30,'rest_seconds'=>60]);
        // (3*30)+(2*60)=210
        $this->assertEquals(210, $te->getTotalDurationSeconds());

        $te2 = HepTemplateExercise::factory()->create(['sets'=>null,'duration_seconds'=>45,'rest_seconds'=>10]);
        $this->assertEquals(45, $te2->getTotalDurationSeconds());
    }

    public function test_formatted_duration()
    {
        $te = HepTemplateExercise::factory()->create(['sets'=>1,'duration_seconds'=>30,'rest_seconds'=>0]);
        $this->assertEquals('30 seconds', $te->getFormattedDuration());

        $te2 = HepTemplateExercise::factory()->create(['sets'=>2,'duration_seconds'=>60,'rest_seconds'=>0]);
        // 2*60=120 => 2 minutes
        $this->assertEquals('2 minutes', $te2->getFormattedDuration());

        $te3 = HepTemplateExercise::factory()->create(['sets'=>1,'duration_seconds'=>90,'rest_seconds'=>0]);
        $this->assertEquals('1m 30s', $te3->getFormattedDuration());
    }

    public function test_exercise_description()
    {
        $ex = Exercise::factory()->create(['name'=>'Squat']);
        $te = HepTemplateExercise::factory()->create(['exercise_id'=>$ex->id,'sets'=>3,'reps'=>10,'frequency'=>'Daily']);
        $this->assertStringContainsString('Squat', $te->getExerciseDescription());
        $this->assertStringContainsString('3 sets of 10 reps', $te->getExerciseDescription());
        $this->assertStringContainsString('Daily', $te->getExerciseDescription());
    }
}

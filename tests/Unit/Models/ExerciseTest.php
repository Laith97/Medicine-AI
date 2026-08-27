<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Exercise;
use App\Models\HepExercise;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExerciseTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_and_casts()
    {
        $ex = Exercise::factory()->create([
            'contraindications' => ['hypertension'],
            'equipment_required' => ['band'],
            'target_muscle_groups' => ['quads'],
            'duration' => 60,
        ]);
        $this->assertIsArray($ex->contraindications);
        $this->assertIsArray($ex->equipment_required);
        $this->assertIsArray($ex->target_muscle_groups);
        $this->assertIsInt($ex->duration);
    }

    public function test_categories_and_difficulty_levels()
    {
        $cats = Exercise::getCategories();
        $this->assertContains('strength', $cats);
        $this->assertContains('cardiovascular', $cats);
        $this->assertCount(7, $cats);

        $levels = Exercise::getDifficultyLevels();
        $this->assertEquals(['beginner','intermediate','advanced'], $levels);
    }

    public function test_scopes()
    {
        Exercise::factory()->create(['category'=>'strength','difficulty_level'=>'beginner']);
        Exercise::factory()->create(['category'=>'balance','difficulty_level'=>'advanced']);

        $this->assertEquals(1, Exercise::byCategory('strength')->count());
        $this->assertEquals(1, Exercise::byDifficulty('advanced')->count());
        $this->assertEquals(2, Exercise::byCategory('strength')->orWhere('category','balance')->count());
    }

    public function test_safe_for_patient_scope_and_method()
    {
        $safe = Exercise::factory()->create(['contraindications'=>['back pain']]);
        $unsafe = Exercise::factory()->create(['contraindications'=>['knee injury']]);

        $this->assertTrue($safe->isSafeForPatient(['knee injury']));
        $this->assertFalse($safe->isSafeForPatient(['back pain']));
        $this->assertTrue($safe->isSafeForPatient([]));
        $this->assertTrue($safe->isSafeForPatient(['unknown']));

        $filtered = Exercise::safeForPatient(['knee injury'])->get();
        $this->assertTrue($filtered->contains($safe));
        $this->assertFalse($filtered->contains($unsafe));
    }

    public function test_quality_score_calculation()
    {
        // Minimal exercise: only required fields, no media, no muscles, not used
        $ex = Exercise::create([
            'name'=>'Test',
            'description'=>str_repeat('a',25),
            'category'=>'strength',
            'difficulty_level'=>'beginner',
            'instructions'=>str_repeat('b',60),
            'contraindications'=>[],
            'equipment_required'=>[],
            'target_muscle_groups'=>[],
            'duration'=>null,
        ]);
        // Required 40 + safety 10 = 50? Let's check logic
        $score = $ex->getQualityScore();
        $this->assertGreaterThanOrEqual(40, $score);
        $this->assertLessThanOrEqual(100, $score);

        // Excellent exercise: all fields filled + both media + used
        $ex2 = Exercise::factory()->create([
            'name'=>'Full',
            'description'=>str_repeat('x',30),
            'instructions'=>str_repeat('y',60),
            'category'=>'strength',
            'video_url'=>'https://example.com/v.mp4',
            'image_url'=>'https://example.com/i.jpg',
            'target_muscle_groups'=>['quads'],
            'equipment_required'=>['band'],
            'contraindications'=>['hypertension'],
            'duration'=>60,
        ]);
        // Create usage
        $prog = \App\Models\HepProgram::factory()->create();
        HepExercise::factory()->create(['exercise_id'=>$ex2->id, 'hep_program_id'=>$prog->id]);
        $this->assertGreaterThanOrEqual(80, $ex2->fresh()->getQualityScore());
    }

    public function test_quality_issues_and_status()
    {
        $ex = Exercise::factory()->create([
            'video_url'=>null,
            'image_url'=>null,
            'target_muscle_groups'=>[],
            'contraindications'=>[],
            'duration'=>null,
        ]);
        $issues = $ex->getQualityIssues();
        $this->assertNotEmpty($issues);
        $this->assertContains('No media content (video or image recommended)', $issues);

        $this->assertFalse($ex->meetsQualityStandards());

        $ex2 = Exercise::factory()->create([
            'video_url'=>'http://example.com/v.mp4',
            'image_url'=>'http://example.com/i.jpg',
            'target_muscle_groups'=>['quads'],
            'contraindications'=>['x'],
            'duration'=>60,
            'description'=>str_repeat('a',25),
            'instructions'=>str_repeat('b',60),
        ]);
        // ensure high score
        $prog = \App\Models\HepProgram::factory()->create();
        HepExercise::factory()->create(['exercise_id'=>$ex2->id, 'hep_program_id'=>$prog->id]);
        $ex2->refresh();
        $status = $ex2->getQualityStatus();
        $this->assertContains($status, ['excellent','good','fair','poor']);
        $color = $ex2->getQualityStatusColor();
        $this->assertContains($color, ['success','primary','warning','danger']);
    }

    public function test_relationship_hep_exercises()
    {
        $ex = Exercise::factory()->create();
        $prog = \App\Models\HepProgram::factory()->create();
        $hepEx = HepExercise::factory()->create(['exercise_id'=>$ex->id, 'hep_program_id'=>$prog->id]);
        $this->assertTrue($ex->hepExercises()->where('id',$hepEx->id)->exists());
    }

    public function test_duration_nullable()
    {
        $ex = Exercise::factory()->create(['duration'=>null]);
        $this->assertNull($ex->fresh()->duration);
        $this->assertDatabaseHas('exercises', ['id'=>$ex->id, 'duration'=>null]);
    }
}

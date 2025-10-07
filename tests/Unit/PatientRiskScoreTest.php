<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\PatientRiskScore;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PatientRiskScoreTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'patient_id',
            'appointment_id',
            'no_show_risk',
            'hospitalization_risk',
        ];

        $this->assertEquals($fillable, (new PatientRiskScore())->getFillable());
    }

    /** @test */
    public function it_can_be_created_with_mass_assignment()
    {
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create();

        $data = [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'no_show_risk' => 0.25,
            'hospitalization_risk' => 0.15,
        ];

        $riskScore = PatientRiskScore::create($data);

        $this->assertInstanceOf(PatientRiskScore::class, $riskScore);
        $this->assertEquals($patient->id, $riskScore->patient_id);
        $this->assertEquals($appointment->id, $riskScore->appointment_id);
        $this->assertEquals(0.25, $riskScore->no_show_risk);
        $this->assertEquals(0.15, $riskScore->hospitalization_risk);
    }

    /** @test */
    public function it_belongs_to_user_patient()
    {
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create();

        $riskScore = PatientRiskScore::factory()->create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
        ]);

        $this->assertInstanceOf(User::class, $riskScore->user);
        $this->assertEquals($patient->id, $riskScore->user->id);
    }

    /** @test */
    public function it_belongs_to_appointment()
    {
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create();

        $riskScore = PatientRiskScore::factory()->create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
        ]);

        $this->assertInstanceOf(Appointment::class, $riskScore->appointment);
        $this->assertEquals($appointment->id, $riskScore->appointment->id);
    }

    /** @test */
    public function it_can_store_decimal_values_for_risks()
    {
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create();

        $riskScore = new PatientRiskScore([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'no_show_risk' => 0.1234,
            'hospitalization_risk' => 0.5678,
        ]);

        $riskScore->save();

        $savedRiskScore = PatientRiskScore::find($riskScore->id);

        $this->assertEquals(0.1234, $savedRiskScore->no_show_risk);
        $this->assertEquals(0.5678, $savedRiskScore->hospitalization_risk);
    }

    /** @test */
    public function it_can_handle_null_risk_values()
    {
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create();

        $riskScore = PatientRiskScore::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'no_show_risk' => null,
            'hospitalization_risk' => null,
        ]);

        $this->assertNull($riskScore->no_show_risk);
        $this->assertNull($riskScore->hospitalization_risk);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $riskScore = new PatientRiskScore();
        $this->assertEquals('patient_risk_scores', $riskScore->getTable());
    }

    /** @test */
    public function it_uses_has_factory_trait()
    {
        $riskScore = new PatientRiskScore();
        $this->assertContains('HasFactory', class_uses($riskScore));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create();

        $beforeCreate = now();

        $riskScore = PatientRiskScore::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'no_show_risk' => 0.5,
            'hospitalization_risk' => 0.3,
        ]);

        $this->assertNotNull($riskScore->created_at);
        $this->assertNotNull($riskScore->updated_at);
        $this->assertGreaterThanOrEqual($beforeCreate, $riskScore->created_at);
    }

    /** @test */
    public function it_can_be_updated()
    {
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create();

        $riskScore = PatientRiskScore::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'no_show_risk' => 0.1,
            'hospitalization_risk' => 0.2,
        ]);

        $riskScore->update([
            'no_show_risk' => 0.8,
            'hospitalization_risk' => 0.9,
        ]);

        $riskScore->refresh();

        $this->assertEquals(0.8, $riskScore->no_show_risk);
        $this->assertEquals(0.9, $riskScore->hospitalization_risk);
    }

    /** @test */
    public function it_can_be_deleted()
    {
        $patient = User::factory()->create();
        $appointment = Appointment::factory()->create();

        $riskScore = PatientRiskScore::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'no_show_risk' => 0.5,
            'hospitalization_risk' => 0.3,
        ]);

        $riskScoreId = $riskScore->id;

        $riskScore->delete();

        $this->assertDatabaseMissing('patient_risk_scores', ['id' => $riskScoreId]);
    }

    /** @test */
    public function it_enforces_foreign_key_constraints()
    {
        // This test verifies that foreign key constraints are properly set up
        // If we try to create a PatientRiskScore with invalid foreign keys,
        // it should either fail or the database should enforce the constraints

        $this->expectException(\Illuminate\Database\QueryException::class);

        // Try to create with non-existent patient_id
        PatientRiskScore::create([
            'patient_id' => 99999,
            'appointment_id' => 1,
            'no_show_risk' => 0.5,
            'hospitalization_risk' => 0.3,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Claim;
use App\Models\User;
use App\Models\ClearinghouseSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Claim>
 */
class ClaimFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Claim::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $serviceDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $submissionDate = (clone $serviceDate)->modify('+1 day');
        $expectedAmount = $this->faker->numberBetween(100, 10000);
        $paidAmount = $this->faker->numberBetween(50, $expectedAmount);

        return [
            'claim_id' => 'CLM-' . $this->faker->unique()->numerify('########'),
            'patient_id' => User::factory(),
            'diagnosis_text' => $this->faker->sentence(),
            'procedure_text' => $this->faker->sentence(),
            'icd10_codes' => [$this->faker->bothify('##.##')],
            'cpt_codes' => [$this->faker->bothify('#####')],
            'payer' => $this->faker->company(),
            'claim_status' => $this->faker->randomElement(['pending', 'submitted', 'paid', 'denied', 'partially_paid']),
            'denial_reason' => $this->faker->optional()->sentence(),
            'raw_denial_code' => $this->faker->optional()->bothify('##'),
            'normalized_denial_category' => $this->faker->optional()->randomElement([
                'documentation_missing', 'coding_error', 'coverage_issue', 'medical_necessity', 'timely_filing'
            ]),
            'expected_amount' => $expectedAmount,
            'paid_amount' => $paidAmount,
            'payment_difference' => $expectedAmount - $paidAmount,
            'era_eob_data' => [],
            'service_date' => $serviceDate,
            'submission_date' => $submissionDate,
            'payment_date' => $this->faker->optional()->dateTimeBetween($submissionDate, 'now'),
            'eligibility_warning' => $this->faker->boolean(10),
            'version' => 1,
        ];
    }

    /**
     * Indicate that the claim is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'claim_status' => 'paid',
            'payment_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the claim is denied.
     */
    public function denied(): static
    {
        return $this->state(fn (array $attributes) => [
            'claim_status' => 'denied',
            'denial_reason' => $this->faker->sentence(),
            'raw_denial_code' => $this->faker->bothify('##'),
            'normalized_denial_category' => $this->faker->randomElement([
                'documentation_missing', 'coding_error', 'coverage_issue', 'medical_necessity', 'timely_filing'
            ]),
        ]);
    }

    /**
     * Indicate that the claim is partially paid.
     */
    public function partiallyPaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'claim_status' => 'partially_paid',
            'paid_amount' => $this->faker->numberBetween(50, $attributes['expected_amount'] ?? 5000),
            'payment_difference' => -$this->faker->numberBetween(50, 500),
            'payment_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the claim has an eligibility warning.
     */
    public function withEligibilityWarning(): static
    {
        return $this->state(fn (array $attributes) => [
            'eligibility_warning' => true,
        ]);
    }
}

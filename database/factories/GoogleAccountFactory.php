<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\GoogleAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GoogleAccount>
 */
class GoogleAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GoogleAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'google_account_id' => $this->faker->uuid(),
            'business_account_id' => $this->faker->optional()->uuid(),
            'location_id' => $this->faker->optional()->uuid(),
            'access_token' => $this->faker->sha256(),
            'refresh_token' => $this->faker->sha256(),
            'token_expires_at' => $this->faker->dateTimeBetween('+1 hour', '+1 day'),
            'scopes' => ['https://www.googleapis.com/auth/business.manage'],
            'is_active' => true,
            'last_sync_at' => $this->faker->optional()->dateTime(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\ClearinghouseAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

class ClearinghouseAccountFactory extends Factory
{
    protected $model = ClearinghouseAccount::class;

    public function definition(): array
    {
        return [
            'provider' => $this->faker->randomElement(['availity', 'change_healthcare', 'trizetto']),
            'name' => $this->faker->company(),
            'credentials' => json_encode(Crypt::encryptString(json_encode([
                'username' => $this->faker->userName(),
                'password' => $this->faker->password(),
            ]))),
            'settings' => ['sandbox' => true],
            'is_active' => true,
            'last_used_at' => $this->faker->optional()->dateTimeThisYear(),
        ];
    }
}
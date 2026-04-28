<?php

namespace Database\Factories;

use App\Models\MtConfig;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends Factory<MtConfig>
 */
class MtConfigFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'deepl',
            'api_key_enc' => Crypt::encryptString(fake()->uuid()),
            'is_active' => true,
            'usage_monthly_chars' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function provider(string $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
        ]);
    }
}

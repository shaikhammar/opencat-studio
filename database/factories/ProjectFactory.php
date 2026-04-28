<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'source_lang' => 'en',
            'target_lang' => 'fr',
            'status' => 'active',
            'qa_config' => [],
            'use_global_tm' => true,
            'mt_provider' => null,
            'mt_prefill' => false,
            'char_limit_per_segment' => null,
            'char_limit_warning_pct' => 90,
            'tm_min_match_pct' => 75,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }
}

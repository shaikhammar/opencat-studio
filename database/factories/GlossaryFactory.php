<?php

namespace Database\Factories;

use App\Models\Glossary;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Glossary>
 */
class GlossaryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'project_id' => null,
            'name' => fake()->words(2, true).' Glossary',
            'source_lang' => 'en',
            'target_lang' => 'fr',
            'term_count' => 0,
            'sqlite_path' => 'glossaries/'.fake()->uuid().'.sqlite',
            'is_global' => false,
        ];
    }

    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_global' => true,
            'project_id' => null,
        ]);
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
            'team_id' => $project->team_id,
            'is_global' => false,
        ]);
    }
}

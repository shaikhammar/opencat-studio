<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Segment>
 */
class SegmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'file_id' => ProjectFile::factory(),
            'project_id' => Project::factory(),
            'segment_number' => fake()->unique()->numberBetween(1, 9999),
            'source_text' => fake()->sentence(),
            'target_text' => null,
            'source_tags' => [],
            'target_tags' => [],
            'status' => 'untranslated',
            'word_count' => fake()->numberBetween(1, 20),
            'char_count' => fake()->numberBetween(10, 150),
            'tm_match_percent' => null,
            'tm_match_origin' => null,
            'context_before' => null,
            'context_after' => null,
            'note' => null,
            'locked' => false,
            'bookmarked' => false,
        ];
    }

    public function translated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'translated',
            'target_text' => fake()->sentence(),
        ]);
    }

    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reviewed',
            'target_text' => fake()->sentence(),
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'locked' => true,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectFile>
 */
class ProjectFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'original_name' => fake()->word().'.xlf',
            'storage_path' => 'uploads/'.fake()->uuid().'.xlf',
            'file_format' => 'xliff',
            'mime_type' => 'text/xml',
            'file_size_bytes' => fake()->numberBetween(1000, 100000),
            'skeleton_store' => 'filesystem',
            'skeleton_path' => null,
            'skeleton_blob' => null,
            'word_count' => 0,
            'segment_count' => 0,
            'translated_count' => 0,
            'status' => 'pending',
            'error_message' => null,
            'processed_at' => null,
            'export_path' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processed',
            'processed_at' => now(),
            'word_count' => fake()->numberBetween(100, 5000),
            'segment_count' => fake()->numberBetween(10, 200),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => fake()->sentence(),
        ]);
    }

    public function exported(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'exported',
            'export_path' => 'exports/'.fake()->uuid().'.xlf',
            'processed_at' => now(),
        ]);
    }
}

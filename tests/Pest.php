<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

require_once __DIR__.'/Stubs/QualityRunnerStub.php';

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function actingAsUser(): User
{
    $user = User::factory()->create();
    test()->actingAs($user);

    return $user;
}

function actingAsViewer(Project $project): User
{
    $viewer = User::factory()->create();
    test()->actingAs($viewer);

    return $viewer;
}

function something()
{
    // ..
}

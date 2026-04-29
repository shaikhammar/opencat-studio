<?php

use App\Models\Glossary;
use App\Models\Project;
use App\Models\User;
use App\Services\GlossaryService;

test('glossary show page renders when project has no glossary', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $this->get(route('glossary.show', $project))->assertOk();
});

test('non owner cannot view project glossary', function () {
    $owner = User::factory()->create();
    actingAsUser();
    $project = Project::factory()->create(['user_id' => $owner->id, 'team_id' => $owner->team_id]);

    $this->get(route('glossary.show', $project))->assertForbidden();
});

test('term can be added to project glossary', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);
    Glossary::factory()->forProject($project)->create();

    $this->mock(GlossaryService::class, function ($mock) {
        $mock->shouldReceive('addTerm')->once();
    });

    $response = $this->post(route('glossary.terms.store', $project), [
        'source' => 'Hello',
        'target' => 'Bonjour',
        'domain' => 'general',
    ]);

    $response->assertRedirect();
});

test('term addition validates required fields', function () {
    $user = actingAsUser();
    $project = Project::factory()->create(['user_id' => $user->id, 'team_id' => $user->team_id]);

    $response = $this->post(route('glossary.terms.store', $project), []);

    $response->assertSessionHasErrors(['source', 'target']);
});

test('global glossary page renders', function () {
    actingAsUser();

    $this->get(route('glossary.global'))->assertOk();
});

<?php

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTeam
{
    public function handle(User $user, string $name): Team
    {
        return DB::transaction(function () use ($user, $name) {
            $team = Team::create([
                'name' => $name,
                'slug' => Str::slug($name) ?: Str::lower(Str::random(8)),
                'plan' => 'free',
                'owner_id' => $user->id,
            ]);

            DB::table('team_user')->insert([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => 'translator',
                'created_at' => now(),
            ]);

            $user->update(['team_id' => $team->id]);

            return $team;
        });
    }
}

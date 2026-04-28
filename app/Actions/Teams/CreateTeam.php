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
                'slug' => $this->uniqueSlug($name),
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

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: Str::lower(Str::random(8));

        if (! Team::where('slug', $base)->exists()) {
            return $base;
        }

        $max = Team::where('slug', 'like', $base.'-%')
            ->get('slug')
            ->map(fn (Team $t) => (int) substr($t->slug, strlen($base) + 1))
            ->filter(fn (int $n) => $n > 0)
            ->max() ?? 0;

        return $base.'-'.($max + 1);
    }
}

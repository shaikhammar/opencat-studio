<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The original CreateTeam action incorrectly set role='translator' (not a valid TeamRole).
// Team creators are owners, so migrate any 'translator' rows to 'owner'.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('team_user')
            ->where('role', 'translator')
            ->update(['role' => 'owner']);
    }

    public function down(): void
    {
        // Non-reversible: we cannot know which 'owner' rows were originally 'translator'.
    }
};

<?php

namespace App\Models;

use App\Enums\TeamRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamMemberPivot extends Pivot
{
    protected $table = 'team_user';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
        ];
    }
}

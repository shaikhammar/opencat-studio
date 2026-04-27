<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationMemory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'team_id',
        'project_id',
        'name',
        'source_lang',
        'target_lang',
        'entry_count',
        'is_global',
    ];

    protected function casts(): array
    {
        return [
            'entry_count' => 'integer',
            'is_global' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

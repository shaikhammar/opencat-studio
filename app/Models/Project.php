<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'team_id',
        'user_id',
        'name',
        'description',
        'source_lang',
        'target_lang',
        'status',
        'qa_config',
        'use_global_tm',
        'mt_provider',
        'mt_prefill',
        'char_limit_per_segment',
        'char_limit_warning_pct',
        'tm_min_match_pct',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'qa_config' => 'array',
            'use_global_tm' => 'boolean',
            'mt_prefill' => 'boolean',
            'char_limit_per_segment' => 'integer',
            'char_limit_warning_pct' => 'integer',
            'tm_min_match_pct' => 'integer',
            'last_activity_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function translationMemories(): HasMany
    {
        return $this->hasMany(TranslationMemory::class);
    }

    public function glossaries(): HasMany
    {
        return $this->hasMany(Glossary::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(Segment::class);
    }

    public function projectTm(): HasOne
    {
        return $this->hasOne(TranslationMemory::class)->where('is_global', false);
    }

    public function projectGlossary(): HasOne
    {
        return $this->hasOne(Glossary::class)->where('is_global', false);
    }
}

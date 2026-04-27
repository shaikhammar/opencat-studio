<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Segment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'file_id',
        'project_id',
        'segment_number',
        'source_text',
        'target_text',
        'source_tags',
        'target_tags',
        'status',
        'word_count',
        'char_count',
        'tm_match_percent',
        'tm_match_origin',
        'context_before',
        'context_after',
        'note',
        'locked',
        'bookmarked',
    ];

    protected function casts(): array
    {
        return [
            'source_tags' => 'array',
            'target_tags' => 'array',
            'word_count' => 'integer',
            'char_count' => 'integer',
            'tm_match_percent' => 'integer',
            'locked' => 'boolean',
            'bookmarked' => 'boolean',
        ];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class, 'file_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isTranslated(): bool
    {
        return in_array($this->status, ['translated', 'reviewed', 'approved']);
    }
}

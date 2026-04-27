<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectFile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'project_id',
        'user_id',
        'original_name',
        'storage_path',
        'file_format',
        'mime_type',
        'file_size_bytes',
        'skeleton_store',
        'skeleton_path',
        'skeleton_blob',
        'word_count',
        'segment_count',
        'translated_count',
        'status',
        'error_message',
        'processed_at',
        'export_path',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'word_count' => 'integer',
            'segment_count' => 'integer',
            'translated_count' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(Segment::class, 'file_id')->orderBy('segment_number');
    }

    public function translationProgress(): float
    {
        if ($this->segment_count === 0) {
            return 0.0;
        }

        return round($this->translated_count / $this->segment_count * 100, 1);
    }
}

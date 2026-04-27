<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class MtConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'api_key_enc',
        'is_active',
        'usage_monthly_chars',
    ];

    protected $hidden = ['api_key_enc'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'usage_monthly_chars' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getApiKey(): string
    {
        return Crypt::decryptString($this->api_key_enc);
    }

    public function setApiKey(string $plaintext): void
    {
        $this->api_key_enc = Crypt::encryptString($plaintext);
    }
}

<?php

namespace App\Services;

use App\Models\MtConfig;
use App\Models\Project;
use App\Models\Segment;
use App\Models\User;
use App\Support\FrameworkBridge;

class MtService
{
    public function __construct(
        private readonly FrameworkBridge $bridge,
    ) {}

    public function resolveAdapter(User $user, Project $project): mixed
    {
        $provider = $project->mt_provider
            ?? $user->getSetting('default_mt_provider');

        if (! $provider) {
            return null;
        }

        $config = MtConfig::where('user_id', $user->id)
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();

        if (! $config) {
            return null;
        }

        return $this->bridge->makeMtAdapter($provider, $config->getApiKey());
    }

    public function translate(Segment $segment, mixed $adapter): array
    {
        $result = $adapter->translate($segment->source_text);

        return [
            'suggestion' => $result->text,
            'provider' => $adapter->getName(),
            'tagWarning' => $result->tagsStripped ?? false,
        ];
    }
}

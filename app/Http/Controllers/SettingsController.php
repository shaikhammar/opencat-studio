<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMtRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateQaRequest;
use App\Models\MtConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $mtConfigs = $user->mtConfigs()
            ->get(['id', 'provider', 'is_active', 'usage_monthly_chars', 'updated_at'])
            ->keyBy('provider');

        $qaDefaults = array_merge(
            config('catframework.qa.default_checks'),
            json_decode($user->getSetting('qa_defaults', '{}'), true) ?? [],
        );

        return Inertia::render('settings/index', [
            'user' => $user->only('id', 'name', 'email', 'locale'),
            'mtConfigs' => $mtConfigs,
            'qaDefaults' => $qaDefaults,
            'mtProviders' => array_keys(config('catframework.mt.providers')),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('status', 'Profile updated.');
    }

    public function updateMt(UpdateMtRequest $request): RedirectResponse
    {
        $user = $request->user();
        $config = MtConfig::firstOrNew(['user_id' => $user->id, 'provider' => $request->provider]);
        $config->api_key_enc = Crypt::encryptString($request->api_key);
        $config->is_active = true;
        $config->save();

        return back()->with('status', 'MT provider saved.');
    }

    public function updateQa(UpdateQaRequest $request): RedirectResponse
    {
        $request->user()->settings()->updateOrCreate(
            ['key' => 'qa_defaults'],
            ['value' => json_encode($request->validated())],
        );

        return back()->with('status', 'QA defaults saved.');
    }
}

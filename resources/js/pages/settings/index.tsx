import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, Eye, EyeOff, XCircle } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';

// ── Types ──────────────────────────────────────────────────────────────────────

interface MtConfig {
    id: number;
    provider: string;
    is_active: boolean;
    usage_monthly_chars: number;
    updated_at: string;
}

interface UserData {
    id: string;
    name: string;
    email: string;
    locale: string;
}

interface Props {
    user: UserData;
    mtConfigs: Record<string, MtConfig>;
    qaDefaults: Record<string, boolean | number>;
    mtProviders: string[];
}

type Tab = 'profile' | 'mt' | 'qa';

// ── Helpers ────────────────────────────────────────────────────────────────────

const PROVIDER_LABELS: Record<string, string> = {
    deepl: 'DeepL',
    google: 'Google Translate',
    azure: 'Azure Translator',
};

const QA_CHECK_LABELS: Record<string, string> = {
    tag_consistency: 'Tag consistency',
    length_ratio: 'Length ratio check',
    trailing_spaces: 'Trailing spaces',
    double_spaces: 'Double spaces',
    terminology: 'Terminology consistency',
    number_consistency: 'Number consistency',
    punctuation_parity: 'Punctuation parity',
};

// ── Tab bar ────────────────────────────────────────────────────────────────────

function TabBar({ active, onChange }: { active: Tab; onChange: (t: Tab) => void }) {
    const tabs: { id: Tab; label: string }[] = [
        { id: 'profile', label: 'Profile' },
        { id: 'mt', label: 'Machine Translation' },
        { id: 'qa', label: 'QA Defaults' },
    ];
    return (
        <div className="flex border-b border-stone-200">
            {tabs.map(({ id, label }) => (
                <button
                    key={id}
                    onClick={() => onChange(id)}
                    className={`px-4 py-2.5 text-sm font-medium ${
                        active === id
                            ? 'border-b-2 border-teal-500 text-teal-600'
                            : 'text-stone-500 hover:text-stone-700'
                    }`}
                >
                    {label}
                </button>
            ))}
        </div>
    );
}

// ── Profile tab ────────────────────────────────────────────────────────────────

function ProfileTab({ user }: { user: UserData }) {
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        name: user.name,
        email: user.email,
    });

    return (
        <div className="max-w-md space-y-6">
            <div>
                <h2 className="text-base font-semibold text-stone-900">Profile information</h2>
                <p className="mt-1 text-sm text-stone-500">Update your name and email address.</p>
            </div>

            <form
                onSubmit={(e) => { e.preventDefault(); patch('/settings/profile'); }}
                className="space-y-4"
            >
                <div className="space-y-1.5">
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        className="h-9"
                    />
                    {errors.name && <p className="text-xs text-red-500">{errors.name}</p>}
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        className="h-9"
                    />
                    {errors.email && <p className="text-xs text-red-500">{errors.email}</p>}
                </div>

                <div className="flex items-center gap-3">
                    <Button
                        type="submit"
                        size="sm"
                        className="h-8 bg-teal-600 px-4 text-xs hover:bg-teal-700"
                        disabled={processing}
                    >
                        Save changes
                    </Button>
                    {recentlySuccessful && (
                        <span className="flex items-center gap-1 text-xs text-green-600">
                            <CheckCircle2 className="size-3.5" /> Saved
                        </span>
                    )}
                </div>
            </form>

            <Separator />

            <div>
                <h2 className="text-base font-semibold text-stone-900">Password</h2>
                <p className="mt-1 text-sm text-stone-500">
                    <Link href="/settings/security" className="text-teal-600 hover:underline">
                        Change your password →
                    </Link>
                </p>
            </div>
        </div>
    );
}

// ── MT provider form ───────────────────────────────────────────────────────────

function MtProviderCard({ provider, config }: { provider: string; config: MtConfig | undefined }) {
    const [showKey, setShowKey] = useState(false);
    const { data, setData, patch, processing, recentlySuccessful } = useForm({
        provider,
        api_key: '',
    });

    const label = PROVIDER_LABELS[provider] ?? provider;

    return (
        <div className="rounded-lg border border-stone-200 p-4">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="text-sm font-semibold text-stone-900">{label}</h3>
                {config?.is_active ? (
                    <span className="flex items-center gap-1 text-xs text-green-600">
                        <CheckCircle2 className="size-3.5" /> Connected
                    </span>
                ) : (
                    <span className="flex items-center gap-1 text-xs text-stone-400">
                        <XCircle className="size-3.5" /> Not configured
                    </span>
                )}
            </div>

            <form
                onSubmit={(e) => { e.preventDefault(); patch('/settings/mt'); }}
                className="flex items-center gap-2"
            >
                <div className="relative flex-1">
                    <Input
                        type={showKey ? 'text' : 'password'}
                        placeholder={config?.is_active ? '••••••••••••••• (saved)' : 'Paste API key…'}
                        value={data.api_key}
                        onChange={(e) => setData('api_key', e.target.value)}
                        className="h-8 pr-8 text-xs font-mono"
                    />
                    <button
                        type="button"
                        className="absolute right-2 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600"
                        onClick={() => setShowKey((v) => !v)}
                        tabIndex={-1}
                    >
                        {showKey ? <EyeOff className="size-3.5" /> : <Eye className="size-3.5" />}
                    </button>
                </div>
                <Button
                    type="submit"
                    size="sm"
                    className="h-8 bg-teal-600 px-3 text-xs hover:bg-teal-700"
                    disabled={processing || !data.api_key}
                >
                    Save
                </Button>
                {recentlySuccessful && (
                    <span className="text-xs text-green-600">Saved</span>
                )}
            </form>

            {config?.is_active && config.usage_monthly_chars > 0 && (
                <p className="mt-2 text-xs text-stone-400">
                    {config.usage_monthly_chars.toLocaleString()} chars used this month
                </p>
            )}
        </div>
    );
}

function MtTab({ mtConfigs, mtProviders }: { mtConfigs: Record<string, MtConfig>; mtProviders: string[] }) {
    const configured = mtProviders.filter((p) => mtConfigs[p]?.is_active);

    return (
        <div className="max-w-lg space-y-6">
            <div>
                <h2 className="text-base font-semibold text-stone-900">Machine Translation</h2>
                <p className="mt-1 text-sm text-stone-500">
                    API keys are stored encrypted and never shared.
                </p>
            </div>

            {configured.length === 0 && (
                <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700">
                    No MT provider configured. Add an API key below to enable MT suggestions in the editor.
                </div>
            )}

            <div className="space-y-3">
                {mtProviders.map((provider) => (
                    <MtProviderCard key={provider} provider={provider} config={mtConfigs[provider]} />
                ))}
            </div>
        </div>
    );
}

// ── QA tab ─────────────────────────────────────────────────────────────────────

function QaTab({ qaDefaults }: { qaDefaults: Record<string, boolean | number> }) {
    const checks = Object.keys(QA_CHECK_LABELS);
    const initial = Object.fromEntries(
        checks.map((k) => [k, qaDefaults[k] ?? false])
    ) as Record<string, boolean>;

    const { data, setData, patch, processing, recentlySuccessful } = useForm<
        Record<string, boolean | number>
    >({
        ...initial,
        length_ratio_max: (qaDefaults.length_ratio_max as number) ?? 2.5,
    });

    return (
        <div className="max-w-md space-y-6">
            <div>
                <h2 className="text-base font-semibold text-stone-900">QA Defaults</h2>
                <p className="mt-1 text-sm text-stone-500">
                    Applied to new projects. Override per project in project settings.
                </p>
            </div>

            <form
                onSubmit={(e) => { e.preventDefault(); patch('/settings/qa'); }}
                className="space-y-4"
            >
                <div className="space-y-3 rounded-lg border border-stone-200 p-4">
                    {checks.map((key) => (
                        <label key={key} className="flex items-center gap-3 text-sm">
                            <input
                                type="checkbox"
                                className="size-4 rounded border-stone-300 text-teal-600 focus:ring-teal-500"
                                checked={!!data[key]}
                                onChange={(e) => setData(key, e.target.checked)}
                            />
                            <span className="text-stone-700">{QA_CHECK_LABELS[key]}</span>
                        </label>
                    ))}

                    {!!data.length_ratio && (
                        <div className="ml-7 flex items-center gap-2 pt-1">
                            <Label htmlFor="length_ratio_max" className="text-xs text-stone-500 whitespace-nowrap">
                                Max ratio
                            </Label>
                            <Input
                                id="length_ratio_max"
                                type="number"
                                step="0.1"
                                min="1"
                                max="10"
                                value={data.length_ratio_max as number}
                                onChange={(e) => setData('length_ratio_max', parseFloat(e.target.value))}
                                className="h-7 w-20 text-xs"
                            />
                        </div>
                    )}
                </div>

                <div className="flex items-center gap-3">
                    <Button
                        type="submit"
                        size="sm"
                        className="h-8 bg-teal-600 px-4 text-xs hover:bg-teal-700"
                        disabled={processing}
                    >
                        Save defaults
                    </Button>
                    {recentlySuccessful && (
                        <span className="flex items-center gap-1 text-xs text-green-600">
                            <CheckCircle2 className="size-3.5" /> Saved
                        </span>
                    )}
                </div>
            </form>
        </div>
    );
}

// ── Main page ──────────────────────────────────────────────────────────────────

export default function SettingsIndex({ user, mtConfigs, qaDefaults, mtProviders }: Props) {
    const [tab, setTab] = useState<Tab>('profile');

    return (
        <AppLayout>
            <Head title="Settings" />
            <div className="flex flex-1 flex-col gap-0 p-6">
                <h1 className="mb-4 text-2xl font-semibold text-stone-900">Settings</h1>
                <TabBar active={tab} onChange={setTab} />
                <div className="pt-6">
                    {tab === 'profile' && <ProfileTab user={user} />}
                    {tab === 'mt' && <MtTab mtConfigs={mtConfigs} mtProviders={mtProviders} />}
                    {tab === 'qa' && <QaTab qaDefaults={qaDefaults} />}
                </div>
            </div>
        </AppLayout>
    );
}

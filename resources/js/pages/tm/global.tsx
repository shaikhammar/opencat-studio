import { Head, Link, router } from '@inertiajs/react';
import { Download, Trash2, Upload } from 'lucide-react';
import { useRef } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import type { TranslationMemory } from '@/types/tm';

interface TmUnit {
    id: number;
    source_text: string;
    target_text: string;
    source_lang: string;
    target_lang: string;
    created_at: string;
}

interface Paginator {
    data: TmUnit[];
    total: number;
    current_page: number;
    last_page: number;
}

interface Props {
    tm: TranslationMemory | null;
    entries: Paginator | { data: [] };
}

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

export default function GlobalTm({ tm, entries }: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const data = (entries as Paginator).data ?? [];
    const total = (entries as Paginator).total ?? 0;

    function handleImport(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('file', file);
        router.post('/tm/import', fd);
    }

    return (
        <AppLayout>
            <Head title="Global Translation Memory" />
            <div className="flex flex-1 flex-col gap-6 p-6">

                {/* Header */}
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold text-stone-900">Global Translation Memory</h1>
                        <p className="mt-1 text-sm text-stone-500">
                            Shared across all projects
                            {tm && <> · <span>{total.toLocaleString()} {total === 1 ? 'entry' : 'entries'}</span></>}
                        </p>
                    </div>
                    {tm && (
                        <div className="flex items-center gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                className="h-8 gap-1.5 text-xs"
                                onClick={() => fileInputRef.current?.click()}
                            >
                                <Upload className="size-3.5" />
                                Import TMX
                            </Button>
                            <Button asChild size="sm" variant="outline" className="h-8 gap-1.5 text-xs">
                                <Link href="/tm/export">
                                    <Download className="size-3.5" />
                                    Export TMX
                                </Link>
                            </Button>
                            <input ref={fileInputRef} type="file" accept=".tmx" className="hidden" onChange={handleImport} />
                        </div>
                    )}
                </div>

                <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs text-amber-700">
                    This TM is shared across all projects. Entries are added when you confirm translations in any project that uses the global TM.
                </div>

                <Separator />

                {!tm ? (
                    <div className="rounded-lg border border-dashed border-stone-200 px-6 py-12 text-center">
                        <p className="text-sm text-stone-500">No global TM configured.</p>
                        <p className="mt-1 text-xs text-stone-400">
                            A global TM is created automatically when you create your first project with global TM enabled.
                        </p>
                    </div>
                ) : data.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-stone-200 px-6 py-12 text-center">
                        <p className="text-sm text-stone-500">No entries yet.</p>
                        <p className="mt-1 text-xs text-stone-400">
                            Confirm translations in your projects or import a TMX file to add entries.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-lg border border-stone-200">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-stone-200 bg-stone-50">
                                    <th className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Source</th>
                                    <th className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Target</th>
                                    <th className="w-20 px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Langs</th>
                                    <th className="w-32 px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Added</th>
                                </tr>
                            </thead>
                            <tbody>
                                {data.map((entry) => (
                                    <tr key={entry.id} className="border-b border-stone-100 last:border-0 hover:bg-stone-50">
                                        <td className="px-4 py-3 text-stone-700">{entry.source_text}</td>
                                        <td className="px-4 py-3 text-stone-900">{entry.target_text}</td>
                                        <td className="px-4 py-3 text-xs text-stone-400">
                                            {entry.source_lang.toUpperCase()} → {entry.target_lang.toUpperCase()}
                                        </td>
                                        <td className="px-4 py-3 text-xs text-stone-400">{formatDate(entry.created_at)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

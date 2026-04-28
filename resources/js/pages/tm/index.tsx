import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, Download, Trash2, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import type { Project } from '@/types/project';
import type { TranslationMemory } from '@/types/tm';

// ── Types ──────────────────────────────────────────────────────────────────────

interface TmUnit {
    id: number;
    source_text: string;
    target_text: string;
    source_lang: string;
    target_lang: string;
    created_at: string;
    last_used_at: string | null;
}

interface Paginator {
    data: TmUnit[];
    total: number;
    current_page: number;
    last_page: number;
    per_page: number;
}

interface Props {
    project: Project;
    tm: TranslationMemory | null;
    entries: Paginator | { data: [] };
}

// ── Helpers ────────────────────────────────────────────────────────────────────

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function isRtl(lang: string) {
    return ['ar', 'he', 'ur', 'fa', 'ps'].includes(lang.toLowerCase().split('-')[0]);
}

// ── Main page ──────────────────────────────────────────────────────────────────

export default function TmIndex({ project, tm, entries }: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [search, setSearch] = useState('');
    const [searchResults, setSearchResults] = useState<TmUnit[] | null>(null);
    const [searching, setSearching] = useState(false);

    const data = (entries as Paginator).data ?? [];
    const total = (entries as Paginator).total ?? 0;
    const langPair = `${project.source_lang.toUpperCase()} → ${project.target_lang.toUpperCase()}`;

    function handleImport(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('file', file);
        router.post(`/projects/${project.id}/tm/import`, fd);
    }

    async function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        if (!search.trim()) { setSearchResults(null); return; }
        setSearching(true);
        try {
            const raw = document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='))?.split('=')[1];
            const token = raw ? decodeURIComponent(raw) : '';
            const res = await fetch(`/api/projects/${project.id}/tm/search?q=${encodeURIComponent(search.trim())}`, {
                headers: { Accept: 'application/json', 'X-XSRF-TOKEN': token },
            });
            const json = await res.json();
            setSearchResults(Array.isArray(json) ? json : []);
        } catch {
            setSearchResults([]);
        } finally {
            setSearching(false);
        }
    }

    function handleDelete(entryId: number) {
        router.delete(`/projects/${project.id}/tm/entries/${entryId}`);
    }

    const displayRows = searchResults ?? data;
    const targetDir = isRtl(project.target_lang) ? 'rtl' : 'ltr';
    const sourceDir = isRtl(project.source_lang) ? 'rtl' : 'ltr';

    return (
        <AppLayout>
            <Head title={`Translation Memory — ${project.name}`} />
            <div className="flex flex-1 flex-col gap-6 p-6">

                {/* Header */}
                <div>
                    <Link
                        href={`/projects/${project.id}`}
                        className="mb-2 flex items-center gap-1 text-sm text-stone-400 hover:text-stone-700"
                    >
                        <ChevronLeft className="size-3.5" />
                        {project.name}
                    </Link>
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-semibold text-stone-900">Translation Memory</h1>
                            <p className="mt-1 text-sm text-stone-500">
                                {langPair}
                                {tm && (
                                    <> · <span>{total.toLocaleString()} {total === 1 ? 'entry' : 'entries'}</span></>
                                )}
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
                                    <Link href={`/projects/${project.id}/tm/export`}>
                                        <Download className="size-3.5" />
                                        Export TMX
                                    </Link>
                                </Button>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".tmx"
                                    className="hidden"
                                    onChange={handleImport}
                                />
                            </div>
                        )}
                    </div>
                </div>

                <Separator />

                {!tm ? (
                    <div className="rounded-lg border border-dashed border-stone-200 px-6 py-12 text-center">
                        <p className="text-sm text-stone-500">No translation memory for this project.</p>
                        <p className="mt-1 text-xs text-stone-400">
                            Entries are added automatically when you confirm translations.
                        </p>
                    </div>
                ) : (
                    <>
                        {/* Concordance search */}
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <Input
                                type="search"
                                placeholder="Search source text…"
                                value={search}
                                onChange={(e) => { setSearch(e.target.value); if (!e.target.value) setSearchResults(null); }}
                                className="h-8 max-w-sm text-sm"
                            />
                            <Button type="submit" size="sm" className="h-8 bg-teal-600 px-4 text-xs hover:bg-teal-700" disabled={searching}>
                                {searching ? 'Searching…' : 'Search'}
                            </Button>
                            {searchResults !== null && (
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    className="h-8 text-xs"
                                    onClick={() => { setSearch(''); setSearchResults(null); }}
                                >
                                    Clear
                                </Button>
                            )}
                        </form>

                        {/* Entries table */}
                        {displayRows.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-stone-200 px-6 py-12 text-center">
                                <p className="text-sm text-stone-500">
                                    {searchResults !== null ? 'No results found.' : 'No entries yet.'}
                                </p>
                                {searchResults === null && (
                                    <p className="mt-1 text-xs text-stone-400">
                                        Entries are added automatically when you confirm translations,
                                        or you can import a TMX file.
                                    </p>
                                )}
                            </div>
                        ) : (
                            <div className="overflow-x-auto rounded-lg border border-stone-200">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-stone-200 bg-stone-50">
                                            <th className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                                                Source
                                            </th>
                                            <th className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                                                Target
                                            </th>
                                            <th className="w-32 px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                                                Added
                                            </th>
                                            <th className="w-12 px-4 py-2.5" />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {displayRows.map((entry) => (
                                            <tr key={entry.id} className="border-b border-stone-100 last:border-0 hover:bg-stone-50">
                                                <td className="px-4 py-3 text-stone-700" dir={sourceDir}>
                                                    {entry.source_text}
                                                </td>
                                                <td
                                                    className="px-4 py-3 text-stone-900"
                                                    dir={targetDir}
                                                    style={{ textAlign: targetDir === 'rtl' ? 'right' : 'left' }}
                                                >
                                                    {entry.target_text}
                                                </td>
                                                <td className="px-4 py-3 text-xs text-stone-400">
                                                    {formatDate(entry.created_at)}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <button
                                                        onClick={() => handleDelete(entry.id)}
                                                        className="text-stone-300 hover:text-red-500"
                                                        title="Delete entry"
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}

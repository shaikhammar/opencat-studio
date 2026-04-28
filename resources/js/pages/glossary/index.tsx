import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronLeft, Plus, Trash2, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import type { Project } from '@/types/project';
import type { Glossary } from '@/types/tm';

// ── Types ──────────────────────────────────────────────────────────────────────

interface GlossaryTerm {
    id: number;
    source: string;
    target: string;
    domain: string | null;
}

interface Props {
    project: Project;
    glossary: Glossary | null;
    terms: GlossaryTerm[];
}

// ── Add-term inline form ───────────────────────────────────────────────────────

function AddTermRow({ projectId, onDone }: { projectId: string; onDone: () => void }) {
    const { data, setData, post, processing, reset } = useForm({
        source: '',
        target: '',
        domain: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(`/projects/${projectId}/glossary/terms`, {
            onSuccess: () => { reset(); onDone(); },
        });
    }

    return (
        <tr className="border-b border-teal-100 bg-teal-50">
            <td className="px-4 py-2">
                <Input
                    autoFocus
                    value={data.source}
                    onChange={(e) => setData('source', e.target.value)}
                    placeholder="Source term"
                    className="h-7 text-xs"
                />
            </td>
            <td className="px-4 py-2">
                <Input
                    value={data.target}
                    onChange={(e) => setData('target', e.target.value)}
                    placeholder="Target term"
                    className="h-7 text-xs"
                />
            </td>
            <td className="px-4 py-2">
                <Input
                    value={data.domain}
                    onChange={(e) => setData('domain', e.target.value)}
                    placeholder="Domain (optional)"
                    className="h-7 text-xs"
                />
            </td>
            <td className="px-4 py-2">
                <form onSubmit={handleSubmit} className="flex gap-1.5">
                    <Button type="submit" size="sm" className="h-7 bg-teal-600 px-3 text-xs hover:bg-teal-700" disabled={processing || !data.source || !data.target}>
                        Save
                    </Button>
                    <Button type="button" size="sm" variant="outline" className="h-7 px-3 text-xs" onClick={onDone}>
                        Cancel
                    </Button>
                </form>
            </td>
        </tr>
    );
}

// ── Main page ──────────────────────────────────────────────────────────────────

export default function GlossaryIndex({ project, glossary, terms }: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [addingTerm, setAddingTerm] = useState(false);
    const [search, setSearch] = useState('');

    const langPair = `${project.source_lang.toUpperCase()} → ${project.target_lang.toUpperCase()}`;

    function handleImport(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('file', file);
        router.post(`/projects/${project.id}/glossary/import`, fd);
    }

    function handleDelete(termId: number) {
        router.delete(`/projects/${project.id}/glossary/terms/${termId}`);
    }

    const filtered = search.trim()
        ? terms.filter(
              (t) =>
                  t.source.toLowerCase().includes(search.toLowerCase()) ||
                  t.target.toLowerCase().includes(search.toLowerCase()),
          )
        : terms;

    return (
        <AppLayout>
            <Head title={`Glossary — ${project.name}`} />
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
                            <h1 className="text-2xl font-semibold text-stone-900">Glossary</h1>
                            <p className="mt-1 text-sm text-stone-500">
                                {langPair}
                                {glossary && (
                                    <> · <span>{terms.length.toLocaleString()} {terms.length === 1 ? 'term' : 'terms'}</span></>
                                )}
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            {glossary && (
                                <>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        className="h-8 gap-1.5 text-xs"
                                        onClick={() => fileInputRef.current?.click()}
                                    >
                                        <Upload className="size-3.5" />
                                        Import TBX
                                    </Button>
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept=".tbx"
                                        className="hidden"
                                        onChange={handleImport}
                                    />
                                </>
                            )}
                            <Button
                                size="sm"
                                className="h-8 gap-1.5 bg-teal-600 text-xs hover:bg-teal-700"
                                onClick={() => setAddingTerm(true)}
                            >
                                <Plus className="size-3.5" />
                                Add term
                            </Button>
                        </div>
                    </div>
                </div>

                <Separator />

                {!glossary && terms.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-stone-200 px-6 py-12 text-center">
                        <p className="text-sm text-stone-500">No glossary terms yet.</p>
                        <p className="mt-1 text-xs text-stone-400">
                            Add a term manually or import a TBX file.
                        </p>
                        <Button
                            size="sm"
                            className="mt-4 h-8 gap-1.5 bg-teal-600 text-xs hover:bg-teal-700"
                            onClick={() => setAddingTerm(true)}
                        >
                            <Plus className="size-3.5" />
                            Add first term
                        </Button>
                    </div>
                ) : (
                    <>
                        {/* Search */}
                        {(terms.length > 5 || search) && (
                            <Input
                                type="search"
                                placeholder="Search terms…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="h-8 max-w-sm text-sm"
                            />
                        )}

                        {/* Table */}
                        <div className="overflow-x-auto rounded-lg border border-stone-200">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-stone-200 bg-stone-50">
                                        <th className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Source term</th>
                                        <th className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Target term</th>
                                        <th className="w-36 px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Domain</th>
                                        <th className="w-12 px-4 py-2.5" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {addingTerm && (
                                        <AddTermRow projectId={project.id} onDone={() => setAddingTerm(false)} />
                                    )}
                                    {filtered.length === 0 && !addingTerm ? (
                                        <tr>
                                            <td colSpan={4} className="px-4 py-8 text-center text-sm text-stone-400">
                                                No terms match your search.
                                            </td>
                                        </tr>
                                    ) : (
                                        filtered.map((term) => (
                                            <tr key={term.id} className="border-b border-stone-100 last:border-0 hover:bg-stone-50">
                                                <td className="px-4 py-3 text-stone-700">{term.source}</td>
                                                <td className="px-4 py-3 font-medium text-stone-900">{term.target}</td>
                                                <td className="px-4 py-3 text-xs text-stone-400">{term.domain ?? '—'}</td>
                                                <td className="px-4 py-3 text-right">
                                                    <button
                                                        onClick={() => handleDelete(term.id)}
                                                        className="text-stone-300 hover:text-red-500"
                                                        title="Delete term"
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}

import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import type { Glossary } from '@/types/tm';

interface GlossaryTerm {
    id: number;
    source: string;
    target: string;
    domain: string | null;
}

interface Props {
    glossary: Glossary | null;
    terms: GlossaryTerm[];
}

export default function GlobalGlossary({ glossary, terms }: Props) {
    const [search, setSearch] = useState('');

    const filtered = search.trim()
        ? terms.filter(
              (t) =>
                  t.source.toLowerCase().includes(search.toLowerCase()) ||
                  t.target.toLowerCase().includes(search.toLowerCase()),
          )
        : terms;

    return (
        <AppLayout>
            <Head title="Global Glossary" />
            <div className="flex flex-1 flex-col gap-6 p-6">

                {/* Header */}
                <div>
                    <h1 className="text-2xl font-semibold text-stone-900">Global Glossary</h1>
                    <p className="mt-1 text-sm text-stone-500">
                        Shared across all projects
                        {glossary && (
                            <> · <span>{terms.length.toLocaleString()} {terms.length === 1 ? 'term' : 'terms'}</span></>
                        )}
                    </p>
                </div>

                <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs text-amber-700">
                    This glossary is shared across all projects. Terms are used for terminology highlighting in the editor.
                </div>

                <Separator />

                {!glossary || terms.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-stone-200 px-6 py-12 text-center">
                        <p className="text-sm text-stone-500">No global glossary terms yet.</p>
                        <p className="mt-1 text-xs text-stone-400">
                            Terms added to individual project glossaries appear here when the project uses the global glossary.
                        </p>
                    </div>
                ) : (
                    <>
                        {terms.length > 5 && (
                            <Input
                                type="search"
                                placeholder="Search terms…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="h-8 max-w-sm text-sm"
                            />
                        )}

                        <div className="overflow-x-auto rounded-lg border border-stone-200">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-stone-200 bg-stone-50">
                                        <th className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Source term</th>
                                        <th className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Target term</th>
                                        <th className="w-36 px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Domain</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filtered.length === 0 ? (
                                        <tr>
                                            <td colSpan={3} className="px-4 py-8 text-center text-sm text-stone-400">
                                                No terms match your search.
                                            </td>
                                        </tr>
                                    ) : (
                                        filtered.map((term) => (
                                            <tr key={term.id} className="border-b border-stone-100 last:border-0 hover:bg-stone-50">
                                                <td className="px-4 py-3 text-stone-700">{term.source}</td>
                                                <td className="px-4 py-3 font-medium text-stone-900">{term.target}</td>
                                                <td className="px-4 py-3 text-xs text-stone-400">{term.domain ?? '—'}</td>
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

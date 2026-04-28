import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Clock,
    FileText,
    Loader2,
    Play,
    Plus,
    Upload,
} from 'lucide-react';
import { useEffect, useRef } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import type { Project, ProjectFile } from '@/types/project';
import type { TranslationMemory, Glossary } from '@/types/tm';

// ── Types ─────────────────────────────────────────────────────────────────────

type NextStep = 'processing' | 'no_files' | 'translate' | 'translating' | 'complete';

interface Props {
    project: Project;
    files: ProjectFile[];
    tm: TranslationMemory | null;
    glossary: Glossary | null;
    nextStep: NextStep;
    isPolling: boolean;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function progressPct(file: ProjectFile): number {
    if (file.segment_count === 0) return 0;
    return Math.round((file.translated_count / file.segment_count) * 100);
}

// ── Next-step banner ──────────────────────────────────────────────────────────

function Banner({ nextStep, projectId }: { nextStep: NextStep; projectId: string }) {
    const base = 'flex items-center gap-3 rounded-lg border px-4 py-3 text-sm';

    if (nextStep === 'processing') {
        return (
            <div className={`${base} border-amber-200 bg-amber-50 text-amber-700`}>
                <Loader2 className="size-4 shrink-0 animate-spin" />
                Preparing your files… this usually takes under a minute.
            </div>
        );
    }
    if (nextStep === 'translate') {
        return (
            <div className={`${base} border-teal-200 bg-teal-50 text-teal-700`}>
                <Play className="size-4 shrink-0" />
                Your files are ready. Click a file to start translating.
            </div>
        );
    }
    if (nextStep === 'complete') {
        return (
            <div className={`${base} border-green-200 bg-green-50 text-green-700`}>
                <CheckCircle2 className="size-4 shrink-0" />
                All segments translated — ready to export.
            </div>
        );
    }
    if (nextStep === 'no_files') {
        return (
            <div className={`${base} border-stone-200 bg-stone-50 text-stone-500`}>
                <Upload className="size-4 shrink-0" />
                No files uploaded yet. Add a file to get started.
            </div>
        );
    }
    return null;
}

// ── File status cell ──────────────────────────────────────────────────────────

function FileStatusCell({ file }: { file: ProjectFile }) {
    if (file.status === 'processing' || file.status === 'pending') {
        return (
            <span className="flex items-center gap-1.5 text-amber-600">
                <Loader2 className="size-3.5 animate-spin" />
                Processing
            </span>
        );
    }
    if (file.status === 'error') {
        return (
            <span className="flex items-center gap-1.5 text-red-500" title={file.error_message ?? undefined}>
                <AlertTriangle className="size-3.5" />
                Error
            </span>
        );
    }
    const dotColor = file.status === 'ready' ? 'bg-green-500' : 'bg-stone-300';
    const label = file.status.charAt(0).toUpperCase() + file.status.slice(1);
    return (
        <span className="flex items-center gap-1.5 text-stone-600">
            <span className={`size-2 rounded-full ${dotColor}`} />
            {label}
        </span>
    );
}

// ── File progress cell ────────────────────────────────────────────────────────

function FileProgressCell({ file }: { file: ProjectFile }) {
    if (file.status === 'processing' || file.status === 'pending') {
        return <span className="text-stone-400">—</span>;
    }
    const pct = progressPct(file);
    return (
        <div className="flex items-center gap-2">
            <div className="h-1.5 w-20 overflow-hidden rounded-full bg-stone-100">
                <div
                    className="h-full rounded-full bg-teal-500 transition-all"
                    style={{ width: `${pct}%` }}
                />
            </div>
            <span className="tabular-nums text-stone-600">{pct}%</span>
        </div>
    );
}

// ── File table ────────────────────────────────────────────────────────────────

function FileTable({ files, project }: { files: ProjectFile[]; project: Project }) {
    if (files.length === 0) return null;
    return (
        <div className="overflow-x-auto rounded-lg border border-stone-200">
            <table className="w-full text-sm">
                <thead>
                    <tr className="border-b border-stone-200 bg-stone-50">
                        {['File', 'Format', 'Words', 'Progress', 'Status', 'Actions'].map((h) => (
                            <th
                                key={h}
                                className="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-stone-500"
                            >
                                {h}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {files.map((file) => {
                        const canTranslate = file.status === 'ready';
                        const canExport = file.status === 'ready' && file.translated_count > 0;
                        return (
                            <tr key={file.id} className="border-b border-stone-100 last:border-0 hover:bg-stone-50">
                                <td className="px-4 py-3">
                                    <span className="flex items-center gap-2 font-medium text-stone-900">
                                        <FileText className="size-4 shrink-0 text-stone-400" />
                                        {file.original_name}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <span className="rounded bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-600">
                                        {file.file_format.toUpperCase()}
                                    </span>
                                </td>
                                <td className="px-4 py-3 tabular-nums text-stone-600">
                                    {file.word_count > 0 ? file.word_count.toLocaleString() : '—'}
                                </td>
                                <td className="px-4 py-3">
                                    <FileProgressCell file={file} />
                                </td>
                                <td className="px-4 py-3 text-xs">
                                    <FileStatusCell file={file} />
                                </td>
                                <td className="px-4 py-3">
                                    {canTranslate ? (
                                        <div className="flex items-center gap-2">
                                            <Button asChild size="sm" className="h-7 bg-teal-600 px-3 text-xs hover:bg-teal-700">
                                                <Link href={`/projects/${project.id}/files/${file.id}/editor`}>
                                                    Translate
                                                </Link>
                                            </Button>
                                            {canExport && (
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    variant="outline"
                                                    className="h-7 px-3 text-xs"
                                                >
                                                    <Link href={`/projects/${project.id}/files/${file.id}/export/download`}>
                                                        Export
                                                    </Link>
                                                </Button>
                                            )}
                                        </div>
                                    ) : (
                                        <span className="text-stone-300">—</span>
                                    )}
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

// ── TM section ────────────────────────────────────────────────────────────────

function TmSection({ tm, project }: { tm: TranslationMemory | null; project: Project }) {
    return (
        <div>
            <h2 className="text-base font-semibold text-stone-900">Translation Memory</h2>
            <Separator className="my-3" />
            {tm ? (
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-medium text-stone-700">{tm.name}</p>
                        <p className="mt-0.5 text-xs text-stone-400">
                            {tm.entry_count.toLocaleString()} {tm.entry_count === 1 ? 'entry' : 'entries'}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button asChild size="sm" variant="outline" className="h-7 px-3 text-xs">
                            <Link href={`/projects/${project.id}/tm`}>Search TM</Link>
                        </Button>
                        <Button asChild size="sm" variant="outline" className="h-7 px-3 text-xs">
                            <Link href={`/projects/${project.id}/tm/import`}>Import TMX</Link>
                        </Button>
                        <Button asChild size="sm" variant="outline" className="h-7 px-3 text-xs">
                            <Link href={`/projects/${project.id}/tm/export`}>Export TMX</Link>
                        </Button>
                    </div>
                </div>
            ) : (
                <div className="rounded-lg border border-dashed border-stone-200 px-6 py-8 text-center">
                    <p className="text-sm text-stone-500">No translation memory for this project.</p>
                    <p className="mt-1 text-xs text-stone-400">
                        Translations you confirm are saved automatically once you add a TM.
                    </p>
                </div>
            )}
        </div>
    );
}

// ── Glossary section ──────────────────────────────────────────────────────────

function GlossarySection({ glossary, project }: { glossary: Glossary | null; project: Project }) {
    return (
        <div>
            <h2 className="text-base font-semibold text-stone-900">Glossary</h2>
            <Separator className="my-3" />
            {glossary ? (
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-medium text-stone-700">{glossary.name}</p>
                        <p className="mt-0.5 text-xs text-stone-400">
                            {glossary.term_count.toLocaleString()} {glossary.term_count === 1 ? 'term' : 'terms'}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button asChild size="sm" variant="outline" className="h-7 px-3 text-xs">
                            <Link href={`/projects/${project.id}/glossary`}>View Glossary</Link>
                        </Button>
                    </div>
                </div>
            ) : (
                <div className="rounded-lg border border-dashed border-stone-200 px-6 py-8 text-center">
                    <p className="text-sm text-stone-500">No glossary for this project.</p>
                </div>
            )}
        </div>
    );
}

// ── Main page ─────────────────────────────────────────────────────────────────

export default function ShowProject({ project, files, tm, glossary, nextStep, isPolling }: Props) {
    const fileInputRef = useRef<HTMLInputElement>(null);

    // Poll every 3 s while files are processing
    useEffect(() => {
        if (!isPolling) return;
        const id = setInterval(() => {
            router.reload({ only: ['files', 'nextStep', 'isPolling'] });
        }, 3000);
        return () => clearInterval(id);
    }, [isPolling]);

    function handleFileUpload(e: React.ChangeEvent<HTMLInputElement>) {
        const selected = e.target.files;
        if (!selected || selected.length === 0) return;
        const fd = new FormData();
        Array.from(selected).forEach((f) => fd.append('files[]', f));
        router.post(`/projects/${project.id}/files`, fd);
    }

    const langPair = `${project.source_lang.toUpperCase()} → ${project.target_lang.toUpperCase()}`;

    return (
        <>
            <Head title={project.name} />
            <div className="flex flex-1 flex-col gap-6 p-6">

                {/* Header */}
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold text-stone-900">{project.name}</h1>
                        <div className="mt-1.5 flex items-center gap-2 text-sm text-stone-500">
                            <Badge variant="outline" className="font-mono text-xs">
                                {langPair}
                            </Badge>
                            <span>·</span>
                            <span className="capitalize">{project.status}</span>
                            <span>·</span>
                            <span className="flex items-center gap-1">
                                <Clock className="size-3" />
                                Created {formatDate(project.created_at)}
                            </span>
                        </div>
                    </div>
                    <Button
                        size="sm"
                        variant="outline"
                        className="shrink-0 text-stone-500"
                        onClick={() => router.delete(`/projects/${project.id}`)}
                    >
                        Archive
                    </Button>
                </div>

                {/* Next-step banner */}
                <Banner nextStep={nextStep} projectId={project.id} />

                {/* Files section */}
                <div>
                    <div className="flex items-center justify-between">
                        <h2 className="text-base font-semibold text-stone-900">
                            Files {files.length > 0 && `(${files.length})`}
                        </h2>
                        <Button
                            size="sm"
                            variant="outline"
                            className="h-8 gap-1.5 text-xs"
                            onClick={() => fileInputRef.current?.click()}
                        >
                            <Plus className="size-3.5" />
                            Upload file
                        </Button>
                        <input
                            ref={fileInputRef}
                            type="file"
                            multiple
                            accept=".docx,.pptx,.xlsx,.html,.txt,.po,.xliff,.xml"
                            className="hidden"
                            onChange={handleFileUpload}
                        />
                    </div>
                    <div className="mt-3">
                        <FileTable files={files} project={project} />
                    </div>
                </div>

                {/* TM + Glossary */}
                <TmSection tm={tm} project={project} />
                <GlossarySection glossary={glossary} project={project} />
            </div>
        </>
    );
}

import { Head, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    ChevronLeft,
    Download,
    Loader2,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import EditorLayout from '@/layouts/editor-layout';
import type { Project, ProjectFile } from '@/types/project';
import type { QaIssue, Segment, SegmentStatus, TagMap, TmMatch } from '@/types/segment';

// ── Types ──────────────────────────────────────────────────────────────────────

type SaveStatus = 'idle' | 'saving' | 'saved' | 'error';
type PanelTab = 'tm' | 'mt' | 'qa';

interface UserSettings {
    autosaveMs: number;
    fontSize: number;
    mtAutoRequest: boolean;
}

interface Props {
    project: Project;
    file: ProjectFile;
    segments: Segment[];
    userSettings: UserSettings;
}

// ── Helpers ────────────────────────────────────────────────────────────────────

const RTL_LANGS = new Set(['ar', 'he', 'ur', 'fa', 'ps', 'sd', 'ug', 'yi', 'dv']);

function isRtl(lang: string): boolean {
    return RTL_LANGS.has(lang.toLowerCase().split('-')[0]);
}

function csrfFetch(url: string, options: RequestInit = {}): Promise<Response> {
    const raw = document.cookie.split('; ').find((r) => r.startsWith('XSRF-TOKEN='))?.split('=')[1];
    const token = raw ? decodeURIComponent(raw) : '';
    return fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': token,
            ...(options.headers ?? {}),
        },
    });
}

function tmPctColor(pct: number): string {
    if (pct === 100) return 'text-green-600';
    if (pct >= 95) return 'text-emerald-600';
    return 'text-amber-600';
}

function tmBadgeColor(pct: number): string {
    if (pct === 100) return 'bg-green-100 text-green-700';
    return 'bg-amber-100 text-amber-700';
}

// ── Status dot ─────────────────────────────────────────────────────────────────

function StatusDot({ status, hasQa }: { status: SegmentStatus; hasQa?: boolean }) {
    if (hasQa) {
        return (
            <span className="flex size-4 items-center justify-center rounded-full bg-red-500 text-[8px] font-bold text-white">
                !
            </span>
        );
    }
    const map: Record<SegmentStatus, string> = {
        untranslated: 'size-4 rounded-full border-2 border-stone-300',
        draft: 'size-4 rounded-full bg-amber-400',
        translated: 'size-4 rounded-full bg-green-500',
        reviewed: 'size-4 rounded-full bg-violet-500',
        approved: 'size-4 rounded-full bg-green-700',
        rejected: 'size-4 rounded-full bg-red-500',
    };
    return <span className={map[status]} />;
}

// ── Tag chip ───────────────────────────────────────────────────────────────────

function TagChip({ tag }: { tag: TagMap }) {
    const base = 'inline-flex items-center rounded border px-1.5 py-0 font-mono text-xs leading-5 select-none';
    if (tag.type === 'self') {
        return <span className={`${base} border-violet-200 bg-violet-100 text-violet-700`}>{tag.display_text}</span>;
    }
    return <span className={`${base} border-indigo-200 bg-indigo-100 text-indigo-700`}>{tag.display_text}</span>;
}

// ── Source cell ────────────────────────────────────────────────────────────────

function SourceCell({ text, tags, lang }: { text: string; tags: TagMap[]; lang: string }) {
    const dir = isRtl(lang) ? 'rtl' : 'ltr';
    if (!tags || tags.length === 0) {
        return (
            <div className="px-3 py-2 text-sm text-stone-800" dir={dir}>
                {text}
            </div>
        );
    }

    // Interleave text and tag chips using a simple placeholder approach
    // Tags are stored separately; source_text has {1}, {/1}, {2/} placeholders
    const parts = text.split(/(\{[^}]+\})/g);
    const tagById: Record<string, TagMap> = {};
    for (const t of tags) {
        const key = t.type === 'open' ? `{${t.id}}` : t.type === 'close' ? `{/${t.id}}` : `{${t.id}/}`;
        tagById[key] = t;
    }

    return (
        <div className="px-3 py-2 text-sm text-stone-800" dir={dir}>
            {parts.map((part, i) => {
                const tag = tagById[part];
                if (tag) return <TagChip key={i} tag={tag} />;
                return <span key={i}>{part}</span>;
            })}
        </div>
    );
}

// ── Segment row ────────────────────────────────────────────────────────────────

interface RowProps {
    segment: Segment;
    isActive: boolean;
    targetLang: string;
    sourceLang: string;
    qaIssues: QaIssue[];
    onActivate: (id: string) => void;
    onTargetChange: (id: string, value: string) => void;
    onConfirm: (id: string, advance: boolean) => void;
    fontSize: number;
}

function SegmentRow({
    segment,
    isActive,
    targetLang,
    sourceLang,
    qaIssues,
    onActivate,
    onTargetChange,
    onConfirm,
    fontSize,
}: RowProps) {
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const targetDir = isRtl(targetLang) ? 'rtl' : 'ltr';
    const hasQa = qaIssues.length > 0;

    useEffect(() => {
        if (isActive) textareaRef.current?.focus();
    }, [isActive]);

    function handleKeyDown(e: React.KeyboardEvent<HTMLTextAreaElement>) {
        if (e.key === 'Tab') {
            e.preventDefault();
            onConfirm(segment.id, !e.shiftKey);
            return;
        }
        if (e.key === 'Enter' && e.ctrlKey) {
            e.preventDefault();
            onConfirm(segment.id, !e.shiftKey);
            return;
        }
        if (e.key === ' ' && e.ctrlKey) {
            e.preventDefault();
            onTargetChange(segment.id, segment.source_text);
        }
    }

    const rowBg = isActive ? 'bg-teal-50' : 'bg-white hover:bg-stone-50';
    const leftBorder = isActive ? 'border-l-[3px] border-l-teal-500' : 'border-l-[3px] border-l-transparent';

    return (
        <tr
            className={`border-b border-stone-100 last:border-0 ${rowBg} ${leftBorder}`}
            onClick={() => onActivate(segment.id)}
        >
            {/* Status dot */}
            <td className="w-9 px-2 py-2">
                <div className="flex items-center justify-center">
                    <StatusDot status={segment.status} hasQa={hasQa} />
                </div>
            </td>

            {/* Segment number */}
            <td className="w-12 px-2 py-2 text-right text-xs text-stone-400">
                {segment.segment_number}
            </td>

            {/* Source */}
            <td className="w-1/2 border-l border-stone-100">
                <SourceCell text={segment.source_text} tags={segment.source_tags ?? []} lang={sourceLang} />
            </td>

            {/* Target */}
            <td className="w-1/2 border-l border-stone-100">
                {isActive ? (
                    <textarea
                        ref={textareaRef}
                        className="w-full resize-none px-3 py-2 text-sm text-stone-900 outline-none ring-2 ring-inset ring-teal-500 focus:bg-teal-50"
                        style={{ fontSize, direction: targetDir, textAlign: targetDir === 'rtl' ? 'right' : 'left', minHeight: 60 }}
                        dir={targetDir}
                        value={segment.target_text ?? ''}
                        placeholder="Type translation…"
                        onChange={(e) => onTargetChange(segment.id, e.target.value)}
                        onKeyDown={handleKeyDown}
                        rows={3}
                    />
                ) : (
                    <div
                        className="min-h-[44px] px-3 py-2 text-sm text-stone-900"
                        style={{ fontSize, direction: targetDir, textAlign: targetDir === 'rtl' ? 'right' : 'left' }}
                        dir={targetDir}
                    >
                        {segment.target_text ? (
                            segment.target_text
                        ) : (
                            <span className="text-stone-300">—</span>
                        )}
                    </div>
                )}
            </td>

            {/* TM% */}
            <td className="w-14 pr-3 text-right text-xs font-medium">
                {segment.tm_match_percent ? (
                    <span className={tmPctColor(segment.tm_match_percent)}>
                        {segment.tm_match_percent}%
                    </span>
                ) : null}
            </td>
        </tr>
    );
}

// ── TM match card ──────────────────────────────────────────────────────────────

function TmMatchCard({ match, targetLang, onInsert }: { match: TmMatch; targetLang: string; onInsert: (text: string) => void }) {
    const targetDir = isRtl(targetLang) ? 'rtl' : 'ltr';
    return (
        <div className="mx-3 mb-2 rounded-lg border border-stone-200 bg-white p-3">
            <div className="mb-2 flex items-center gap-2">
                <span className={`rounded px-1.5 py-0.5 text-xs font-semibold ${tmBadgeColor(match.percent)}`}>
                    {match.percent}%
                </span>
                <span className="text-xs text-stone-400">{match.origin === 'tm' ? 'project TM' : match.origin}</span>
            </div>
            <p className="mb-1 text-xs text-stone-500">Source:</p>
            <p className="mb-2 text-sm text-stone-700">{match.source_text}</p>
            <p className="mb-1 text-xs text-stone-500">Target:</p>
            <p className="mb-3 text-sm text-stone-900" dir={targetDir} style={{ textAlign: targetDir === 'rtl' ? 'right' : 'left' }}>
                {match.target_text}
            </p>
            <button
                className="text-xs font-medium text-teal-600 hover:text-teal-700"
                onClick={() => onInsert(match.target_text)}
            >
                Insert ↵
            </button>
        </div>
    );
}

// ── Side panel ─────────────────────────────────────────────────────────────────

interface SidePanelProps {
    projectId: string;
    fileId: string;
    activeSegment: Segment | null;
    targetLang: string;
    qaIssues: QaIssue[];
    onInsert: (text: string) => void;
}

function SidePanel({ projectId, fileId, activeSegment, targetLang, qaIssues, onInsert }: SidePanelProps) {
    const [tab, setTab] = useState<PanelTab>('tm');
    const [tmMatches, setTmMatches] = useState<TmMatch[]>([]);
    const [tmLoading, setTmLoading] = useState(false);
    const activeSegmentId = activeSegment?.id;

    useEffect(() => {
        if (!activeSegmentId || tab !== 'tm') return;
        setTmLoading(true);
        csrfFetch(`/api/projects/${projectId}/files/${fileId}/segments/${activeSegmentId}/tm`)
            .then((r) => r.json())
            .then((data) => setTmMatches(Array.isArray(data) ? data : []))
            .catch(() => setTmMatches([]))
            .finally(() => setTmLoading(false));
    }, [activeSegmentId, tab, projectId, fileId]);

    const tabs: { id: PanelTab; label: string }[] = [
        { id: 'tm', label: 'TM' },
        { id: 'mt', label: 'MT' },
        { id: 'qa', label: `QA${qaIssues.length > 0 ? ` (${qaIssues.length})` : ''}` },
    ];

    return (
        <div className="flex w-96 shrink-0 flex-col border-l border-stone-200 bg-stone-50">
            {/* Tab bar */}
            <div className="flex border-b border-stone-200 bg-white">
                {tabs.map(({ id, label }) => (
                    <button
                        key={id}
                        className={`px-4 py-2.5 text-sm font-medium ${tab === id ? 'border-b-2 border-teal-500 text-teal-600' : 'text-stone-500 hover:text-stone-700'}`}
                        onClick={() => setTab(id)}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {/* Panel content */}
            <div className="flex-1 overflow-y-auto py-3">
                {tab === 'tm' && (
                    <>
                        <p className="px-4 pb-2 text-xs font-semibold uppercase tracking-wide text-stone-400">
                            TM Matches
                        </p>
                        {tmLoading ? (
                            <div className="flex items-center justify-center py-8">
                                <Loader2 className="size-4 animate-spin text-stone-400" />
                            </div>
                        ) : tmMatches.length > 0 ? (
                            tmMatches.map((m, i) => (
                                <TmMatchCard key={i} match={m} targetLang={targetLang} onInsert={onInsert} />
                            ))
                        ) : (
                            <p className="px-4 py-6 text-center text-sm text-stone-400">
                                No TM matches for this segment.
                            </p>
                        )}
                    </>
                )}

                {tab === 'mt' && (
                    <p className="px-4 py-6 text-center text-sm text-stone-400">
                        {activeSegment ? 'MT not configured for this project.' : 'Select a segment.'}
                    </p>
                )}

                {tab === 'qa' && (
                    <>
                        <p className="px-4 pb-2 text-xs font-semibold uppercase tracking-wide text-stone-400">
                            QA Issues
                        </p>
                        {qaIssues.length === 0 ? (
                            <p className="px-4 py-6 text-center text-sm text-stone-400">No QA issues.</p>
                        ) : (
                            qaIssues.map((issue, i) => (
                                <div
                                    key={i}
                                    className={`mx-3 mb-2 rounded border-l-4 bg-white p-2 ${issue.severity === 'error' ? 'border-red-400' : 'border-amber-400'}`}
                                >
                                    <p className="text-sm text-stone-800">{issue.message}</p>
                                    <p className="text-xs text-stone-500">Segment {issue.segment_number}</p>
                                </div>
                            ))
                        )}
                    </>
                )}
            </div>
        </div>
    );
}

// ── Shortcut overlay ───────────────────────────────────────────────────────────

function ShortcutOverlay({ onClose }: { onClose: () => void }) {
    const sections = [
        {
            title: 'Navigation',
            items: [
                ['Tab / Shift+Tab', 'Confirm + next / prev'],
                ['Ctrl+Enter', 'Confirm + advance'],
            ],
        },
        {
            title: 'Segment',
            items: [
                ['Ctrl+Space', 'Copy source text'],
                ['Ctrl+M', 'Insert top TM match'],
            ],
        },
        {
            title: 'Other',
            items: [['Ctrl+?', 'Toggle this dialog']],
        },
    ];

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40" onClick={onClose}>
            <div className="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl" onClick={(e) => e.stopPropagation()}>
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-sm font-semibold text-stone-900">Keyboard Shortcuts</h2>
                    <button className="text-stone-400 hover:text-stone-600" onClick={onClose}>✕</button>
                </div>
                {sections.map((s) => (
                    <div key={s.title}>
                        <p className="mb-1.5 mt-3 text-xs font-semibold uppercase tracking-wide text-stone-400">{s.title}</p>
                        {s.items.map(([key, desc]) => (
                            <div key={key} className="flex items-center justify-between py-1">
                                <span className="rounded bg-stone-100 px-1.5 py-0.5 font-mono text-xs text-stone-700">{key}</span>
                                <span className="text-xs text-stone-600">{desc}</span>
                            </div>
                        ))}
                    </div>
                ))}
            </div>
        </div>
    );
}

// ── Main page ──────────────────────────────────────────────────────────────────

export default function EditorPage({ project, file, segments: initialSegments, userSettings }: Props) {
    const [segments, setSegments] = useState<Segment[]>(initialSegments);
    const [activeId, setActiveId] = useState<string | null>(initialSegments[0]?.id ?? null);
    const [saveStatus, setSaveStatus] = useState<SaveStatus>('idle');
    const [showShortcuts, setShowShortcuts] = useState(false);
    const saveTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const activeSegment = segments.find((s) => s.id === activeId) ?? null;
    const activeIndex = segments.findIndex((s) => s.id === activeId);

    const translatedCount = segments.filter((s) => ['translated', 'reviewed', 'approved'].includes(s.status)).length;
    const totalWords = segments.reduce((sum, s) => sum + s.word_count, 0);
    const translatedWords = segments
        .filter((s) => ['translated', 'reviewed', 'approved'].includes(s.status))
        .reduce((sum, s) => sum + s.word_count, 0);
    const progressPct = totalWords > 0 ? Math.round((translatedWords / totalWords) * 100) : 0;

    const sourceDir = isRtl(project.source_lang) ? 'rtl' : 'ltr';

    // Keyboard shortcuts at page level
    useEffect(() => {
        function handleKey(e: KeyboardEvent) {
            if (e.ctrlKey && e.key === '?') {
                e.preventDefault();
                setShowShortcuts((v) => !v);
            }
        }
        window.addEventListener('keydown', handleKey);
        return () => window.removeEventListener('keydown', handleKey);
    }, []);

    // Debounced autosave
    const scheduleSave = useCallback(
        (seg: Segment) => {
            if (saveTimerRef.current) clearTimeout(saveTimerRef.current);
            setSaveStatus('saving');
            saveTimerRef.current = setTimeout(async () => {
                try {
                    const res = await csrfFetch(
                        `/api/projects/${project.id}/files/${file.id}/segments/${seg.id}`,
                        {
                            method: 'PATCH',
                            body: JSON.stringify({
                                target_text: seg.target_text,
                                target_tags: seg.target_tags ?? [],
                                status: seg.status === 'untranslated' && seg.target_text ? 'draft' : seg.status,
                            }),
                        },
                    );
                    if (res.ok) {
                        const updated: Segment = await res.json();
                        setSegments((prev) => prev.map((s) => (s.id === updated.id ? updated : s)));
                        setSaveStatus('saved');
                        setTimeout(() => setSaveStatus('idle'), 2500);
                    } else {
                        setSaveStatus('error');
                    }
                } catch {
                    setSaveStatus('error');
                }
            }, userSettings.autosaveMs);
        },
        [project.id, file.id, userSettings.autosaveMs],
    );

    function handleTargetChange(id: string, value: string) {
        setSegments((prev) =>
            prev.map((s) => (s.id === id ? { ...s, target_text: value } : s)),
        );
        const seg = segments.find((s) => s.id === id);
        if (seg) scheduleSave({ ...seg, target_text: value });
    }

    function handleConfirm(id: string, advance: boolean) {
        const seg = segments.find((s) => s.id === id);
        if (!seg) return;

        const newStatus: SegmentStatus =
            seg.target_text && seg.target_text.trim() ? 'translated' : 'untranslated';

        const updated = { ...seg, status: newStatus };
        setSegments((prev) => prev.map((s) => (s.id === id ? updated : s)));
        scheduleSave(updated);

        if (advance) {
            const next = segments[activeIndex + 1];
            if (next) setActiveId(next.id);
        }
    }

    function handleInsertTm(text: string) {
        if (!activeId) return;
        handleTargetChange(activeId, text);
    }

    const langPair = `${project.source_lang.toUpperCase()} → ${project.target_lang.toUpperCase()}`;

    return (
        <EditorLayout>
            <Head title={`${file.original_name} — ${project.name}`} />

            {/* Top bar */}
            <div className="flex h-14 shrink-0 items-center justify-between border-b border-stone-200 bg-white px-4">
                <div className="flex items-center gap-3 text-sm">
                    <Link
                        href={`/projects/${project.id}`}
                        className="flex items-center gap-1 text-stone-500 hover:text-stone-900"
                    >
                        <ChevronLeft className="size-4" />
                        {project.name}
                    </Link>
                    <span className="text-stone-300">·</span>
                    <span className="font-medium text-stone-900">{file.original_name}</span>
                    <span className="text-stone-300">·</span>
                    <span className="text-stone-500">{langPair}</span>
                </div>

                {/* Progress */}
                <div className="flex items-center gap-3">
                    <span className="text-sm text-stone-600">
                        {translatedWords.toLocaleString()} / {totalWords.toLocaleString()} words ({progressPct}%)
                    </span>
                    <div className="h-1.5 w-28 overflow-hidden rounded-full bg-stone-200">
                        <div className="h-full rounded-full bg-teal-500 transition-all" style={{ width: `${progressPct}%` }} />
                    </div>
                </div>

                {/* Actions */}
                <div className="flex items-center gap-2">
                    <Button asChild size="sm" variant="outline" className="h-8 gap-1.5 text-xs">
                        <Link href={`/projects/${project.id}/files/${file.id}/export/download`}>
                            <Download className="size-3.5" />
                            Export
                        </Link>
                    </Button>
                </div>
            </div>

            {/* Main area */}
            <div className="flex flex-1 overflow-hidden">
                {/* Segment table */}
                <div className="flex-1 overflow-y-auto">
                    <table className="w-full text-sm">
                        <thead className="sticky top-0 z-10 border-b border-stone-200 bg-stone-50">
                            <tr>
                                <th className="w-9" />
                                <th className="w-12 px-2 py-2 text-right text-xs font-semibold uppercase tracking-wide text-stone-400">#</th>
                                <th className="w-1/2 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-stone-400" dir={sourceDir}>
                                    Source
                                </th>
                                <th className="w-1/2 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-stone-400">
                                    Target
                                </th>
                                <th className="w-14 pr-3 text-right text-xs font-semibold uppercase tracking-wide text-stone-400">TM%</th>
                            </tr>
                        </thead>
                        <tbody>
                            {segments.map((seg) => (
                                <SegmentRow
                                    key={seg.id}
                                    segment={seg}
                                    isActive={seg.id === activeId}
                                    sourceLang={project.source_lang}
                                    targetLang={project.target_lang}
                                    qaIssues={[]}
                                    onActivate={setActiveId}
                                    onTargetChange={handleTargetChange}
                                    onConfirm={handleConfirm}
                                    fontSize={userSettings.fontSize}
                                />
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Side panel */}
                <SidePanel
                    projectId={project.id}
                    fileId={file.id}
                    activeSegment={activeSegment}
                    targetLang={project.target_lang}
                    qaIssues={[]}
                    onInsert={handleInsertTm}
                />
            </div>

            {/* Status bar */}
            <div className="flex h-8 shrink-0 items-center gap-4 border-t border-stone-200 bg-stone-50 px-4 text-xs text-stone-500">
                <span>
                    Seg {activeIndex + 1} / {segments.length}
                </span>
                <span>·</span>
                <span>{activeSegment?.word_count ?? 0} words</span>
                <span>·</span>
                {saveStatus === 'saving' && (
                    <span className="flex items-center gap-1">
                        <Loader2 className="size-3 animate-spin" /> Saving…
                    </span>
                )}
                {saveStatus === 'saved' && (
                    <span className="flex items-center gap-1 text-green-500">
                        <CheckCircle2 className="size-3" /> Auto-saved
                    </span>
                )}
                {saveStatus === 'error' && <span className="text-red-500">Save error</span>}
                {saveStatus === 'idle' && <span>{translatedCount} / {segments.length} segments translated</span>}
                <span className="ml-auto text-stone-400">Ctrl+? for shortcuts</span>
            </div>

            {showShortcuts && <ShortcutOverlay onClose={() => setShowShortcuts(false)} />}
        </EditorLayout>
    );
}

import { Head, Link, router } from '@inertiajs/react';
import { Check, ChevronDown, ChevronRight, FileText, Upload, X } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { LANGUAGES } from '@/lib/languages';

interface Props {
    globalTmExists: boolean;
    globalTmEntryCount: number;
}

// ── Step indicator ────────────────────────────────────────────────────────────

const STEPS = ['Project Details', 'Translation Memory', 'Upload Files'] as const;

function StepIndicator({ current }: { current: number }) {
    return (
        <div className="flex items-center justify-center gap-0 mb-8">
            {STEPS.map((label, i) => {
                const n = i + 1;
                const done = n < current;
                const active = n === current;
                return (
                    <div key={n} className="flex items-center">
                        <div className="flex flex-col items-center gap-1.5">
                            <div
                                className={[
                                    'flex size-7 items-center justify-center rounded-full text-xs font-semibold transition-colors',
                                    done ? 'bg-teal-600 text-white' : '',
                                    active ? 'bg-teal-600 text-white ring-4 ring-teal-100' : '',
                                    !done && !active ? 'border-2 border-stone-300 text-stone-400 bg-white' : '',
                                ].join(' ')}
                            >
                                {done ? <Check className="size-3.5 stroke-[3]" /> : n}
                            </div>
                            <span
                                className={[
                                    'text-xs font-medium whitespace-nowrap',
                                    active ? 'text-teal-600' : 'text-stone-400',
                                ].join(' ')}
                            >
                                {label}
                            </span>
                        </div>
                        {i < STEPS.length - 1 && (
                            <div
                                className={[
                                    'h-0.5 w-16 mx-2 mb-5 transition-colors',
                                    done ? 'bg-teal-600' : 'bg-stone-200',
                                ].join(' ')}
                            />
                        )}
                    </div>
                );
            })}
        </div>
    );
}

// ── Card wrapper ──────────────────────────────────────────────────────────────

function WizardCard({ children }: { children: React.ReactNode }) {
    return (
        <div className="rounded-xl border border-stone-200 bg-white shadow-sm p-8">
            {children}
        </div>
    );
}

function CardHeader({ title, step }: { title: string; step: number }) {
    return (
        <div className="flex items-baseline justify-between mb-6 pb-6 border-b border-stone-100">
            <h2 className="text-2xl font-semibold text-stone-900">{title}</h2>
            <span className="text-sm text-stone-400">Step {step} of 3</span>
        </div>
    );
}

function CardFooter({ children }: { children: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between mt-8 pt-6 border-t border-stone-100">
            {children}
        </div>
    );
}

// ── Language select ───────────────────────────────────────────────────────────

function LangSelect({
    value,
    onChange,
    exclude,
    placeholder,
    error,
}: {
    value: string;
    onChange: (v: string) => void;
    exclude?: string;
    placeholder: string;
    error?: string;
}) {
    return (
        <div>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger className={error ? 'border-red-400' : ''}>
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {LANGUAGES.filter((l) => l.code !== exclude).map((l) => (
                        <SelectItem key={l.code} value={l.code}>
                            {l.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            {error && <p className="mt-1 text-xs text-red-500">{error}</p>}
        </div>
    );
}

// ── TM section box ────────────────────────────────────────────────────────────

function TmBox({ children }: { children: React.ReactNode }) {
    return (
        <div className="rounded-lg border border-stone-200 bg-stone-50 p-4 space-y-3">
            {children}
        </div>
    );
}

function TmSectionLabel({ children }: { children: React.ReactNode }) {
    return (
        <p className="text-xs font-semibold uppercase tracking-wide text-stone-400">
            {children}
        </p>
    );
}

// ── File row ──────────────────────────────────────────────────────────────────

function formatBytes(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function FileRow({ file, onRemove }: { file: File; onRemove: () => void }) {
    const ext = file.name.split('.').pop()?.toUpperCase() ?? '';
    return (
        <div className="flex items-center gap-3 rounded-lg border border-stone-200 bg-white px-4 py-2.5">
            <FileText className="size-4 shrink-0 text-stone-400" />
            <span className="flex-1 truncate text-sm font-medium text-stone-700">{file.name}</span>
            <span className="shrink-0 rounded bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-600">
                {ext}
            </span>
            <span className="shrink-0 text-xs text-stone-400">{formatBytes(file.size)}</span>
            <button
                type="button"
                onClick={onRemove}
                className="shrink-0 text-stone-400 hover:text-red-500 transition-colors"
            >
                <X className="size-4" />
            </button>
        </div>
    );
}

// ── Wizard data types ─────────────────────────────────────────────────────────

interface WizardData {
    name: string;
    source_lang: string;
    target_lang: string;
    description: string;
    use_global_tm: boolean;
    mt_prefill: boolean;
}

// ── Main page ─────────────────────────────────────────────────────────────────

export default function CreateProject({ globalTmExists }: Props) {
    const [step, setStep] = useState(1);
    const [data, setData] = useState<WizardData>({
        name: '',
        source_lang: '',
        target_lang: '',
        description: '',
        use_global_tm: globalTmExists,
        mt_prefill: false,
    });
    const [files, setFiles] = useState<File[]>([]);
    const [isDragging, setIsDragging] = useState(false);
    const [tmxExpanded, setTmxExpanded] = useState(false);
    const [mtExpanded, setMtExpanded] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const fileInputRef = useRef<HTMLInputElement>(null);

    const set = <K extends keyof WizardData>(key: K, value: WizardData[K]) =>
        setData((prev) => ({ ...prev, [key]: value }));

    // ── File handling ────────────────────────────────────────────────────────

    const SUPPORTED = ['docx', 'pptx', 'xlsx', 'html', 'txt', 'po', 'xliff', 'xml'];

    const acceptFiles = useCallback((incoming: FileList | null) => {
        if (!incoming) return;
        const valid = Array.from(incoming).filter((f) => {
            const ext = f.name.split('.').pop()?.toLowerCase() ?? '';
            return SUPPORTED.includes(ext) && f.size <= 50 * 1024 * 1024;
        });
        setFiles((prev) => {
            const existing = new Set(prev.map((f) => f.name));
            return [...prev, ...valid.filter((f) => !existing.has(f.name))];
        });
    }, []);

    const handleDrop = useCallback(
        (e: React.DragEvent) => {
            e.preventDefault();
            setIsDragging(false);
            acceptFiles(e.dataTransfer.files);
        },
        [acceptFiles],
    );

    // ── Submit ───────────────────────────────────────────────────────────────

    function handleSubmit() {
        setProcessing(true);
        const fd = new FormData();
        fd.append('name', data.name);
        fd.append('source_lang', data.source_lang);
        fd.append('target_lang', data.target_lang);
        if (data.description) fd.append('description', data.description);
        fd.append('use_global_tm', data.use_global_tm ? '1' : '0');
        fd.append('create_project_tm', '1');
        fd.append('mt_prefill', data.mt_prefill ? '1' : '0');
        files.forEach((f) => fd.append('files[]', f));

        router.post('/projects', fd, {
            onFinish: () => setProcessing(false),
            onError: (errs) => {
                setErrors(errs as Record<string, string>);
                // Jump back to step 1 if name/lang errors
                if (errs.name || errs.source_lang || errs.target_lang) setStep(1);
            },
        });
    }

    // ── Step 1 ───────────────────────────────────────────────────────────────

    const step1Valid = data.name.trim() !== '' && data.source_lang !== '' && data.target_lang !== '';

    const renderStep1 = () => (
        <WizardCard>
            <CardHeader title="Create a project" step={1} />

            <div className="space-y-5">
                <div>
                    <Label htmlFor="name" className="text-sm font-medium text-stone-700">
                        Project name <span className="text-red-500">*</span>
                    </Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => set('name', e.target.value)}
                        placeholder="e.g. Legal Contract Translation"
                        className={`mt-1 ${errors.name ? 'border-red-400' : ''}`}
                        autoFocus
                    />
                    {errors.name && <p className="mt-1 text-xs text-red-500">{errors.name}</p>}
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label className="text-sm font-medium text-stone-700">
                            Source language <span className="text-red-500">*</span>
                        </Label>
                        <div className="mt-1">
                            <LangSelect
                                value={data.source_lang}
                                onChange={(v) => set('source_lang', v)}
                                exclude={data.target_lang}
                                placeholder="Select language…"
                                error={errors.source_lang}
                            />
                        </div>
                    </div>
                    <div>
                        <Label className="text-sm font-medium text-stone-700">
                            Target language <span className="text-red-500">*</span>
                        </Label>
                        <div className="mt-1">
                            <LangSelect
                                value={data.target_lang}
                                onChange={(v) => set('target_lang', v)}
                                exclude={data.source_lang}
                                placeholder="Select language…"
                                error={errors.target_lang}
                            />
                        </div>
                    </div>
                </div>

                <div>
                    <Label htmlFor="description" className="text-sm font-medium text-stone-700">
                        Description <span className="text-stone-400 font-normal">(optional)</span>
                    </Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => set('description', e.target.value)}
                        placeholder="Optional: project notes, client name, domain…"
                        className="mt-1 resize-none"
                        rows={3}
                    />
                </div>
            </div>

            <CardFooter>
                <Button variant="ghost" asChild className="text-stone-500 hover:text-stone-700">
                    <Link href="/dashboard">Cancel</Link>
                </Button>
                <Button
                    onClick={() => setStep(2)}
                    disabled={!step1Valid}
                    className="bg-teal-600 hover:bg-teal-700"
                >
                    Next →
                </Button>
            </CardFooter>
        </WizardCard>
    );

    // ── Step 2 ───────────────────────────────────────────────────────────────

    const renderStep2 = () => (
        <WizardCard>
            <CardHeader title="Translation Memory" step={2} />

            <p className="text-sm text-stone-600 mb-5">
                Translations you confirm are saved automatically and suggested as matches in future projects.
            </p>

            <div className="space-y-4">
                <TmBox>
                    <TmSectionLabel>Project TM</TmSectionLabel>
                    <div className="flex items-start gap-3">
                        <Checkbox checked disabled className="mt-0.5 opacity-60" />
                        <div>
                            <p className="text-sm font-medium text-stone-700">
                                Create a project TM for this project
                            </p>
                            <p className="mt-1 text-xs text-stone-500">
                                Translations confirmed in this project are saved here automatically.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={() => setTmxExpanded((v) => !v)}
                        className="flex items-center gap-1.5 text-sm text-teal-600 hover:text-teal-700"
                    >
                        {tmxExpanded
                            ? <ChevronDown className="size-4" />
                            : <ChevronRight className="size-4" />}
                        Import existing TMX file
                    </button>
                    {tmxExpanded && (
                        <div className="mt-2">
                            <Input type="file" accept=".tmx" className="text-sm" />
                            <p className="mt-1 text-xs text-stone-400">
                                Entries will be imported into this project's TM after creation.
                            </p>
                        </div>
                    )}
                </TmBox>

                <TmBox>
                    <TmSectionLabel>Global TM</TmSectionLabel>
                    <div className="flex items-start gap-3">
                        <Checkbox
                            checked={data.use_global_tm}
                            onCheckedChange={(v) => set('use_global_tm', Boolean(v))}
                            className="mt-0.5"
                        />
                        <div>
                            <p className="text-sm font-medium text-stone-700">
                                Also use the global TM
                            </p>
                            <p className="mt-1 text-xs text-stone-500">
                                Matches from all your past projects appear in the TM panel.
                                Confirmed translations are saved to both TMs.
                            </p>
                        </div>
                    </div>
                </TmBox>
            </div>

            <CardFooter>
                <Button variant="ghost" onClick={() => setStep(1)} className="text-stone-500 hover:text-stone-700">
                    ← Back
                </Button>
                <Button onClick={() => setStep(3)} className="bg-teal-600 hover:bg-teal-700">
                    Next →
                </Button>
            </CardFooter>
        </WizardCard>
    );

    // ── Step 3 ───────────────────────────────────────────────────────────────

    const renderStep3 = () => (
        <WizardCard>
            <CardHeader title="Upload your files" step={3} />

            {/* Drop zone */}
            <div
                onDragOver={(e) => { e.preventDefault(); setIsDragging(true); }}
                onDragLeave={() => setIsDragging(false)}
                onDrop={handleDrop}
                onClick={() => fileInputRef.current?.click()}
                className={[
                    'rounded-xl border-2 border-dashed p-10 text-center cursor-pointer transition-colors',
                    isDragging
                        ? 'border-teal-400 bg-teal-50'
                        : 'border-stone-300 bg-stone-50 hover:border-teal-300 hover:bg-stone-100',
                ].join(' ')}
            >
                <Upload
                    className={[
                        'mx-auto size-8 mb-3 transition-colors',
                        isDragging ? 'text-teal-500' : 'text-stone-400',
                    ].join(' ')}
                />
                <p className="text-sm font-medium text-stone-700">
                    Drop files here, or click to browse
                </p>
                <p className="mt-1 text-xs text-stone-400">
                    Supported: DOCX · PPTX · XLSX · HTML · TXT · PO · XLIFF · XML
                </p>
                <p className="text-xs text-stone-400">Max 50 MB per file</p>
                <input
                    ref={fileInputRef}
                    type="file"
                    multiple
                    accept=".docx,.pptx,.xlsx,.html,.txt,.po,.xliff,.xml"
                    className="hidden"
                    onChange={(e) => acceptFiles(e.target.files)}
                />
            </div>

            {/* File list */}
            {files.length > 0 && (
                <div className="mt-4 space-y-2">
                    {files.map((file, i) => (
                        <FileRow
                            key={`${file.name}-${i}`}
                            file={file}
                            onRemove={() => setFiles((prev) => prev.filter((_, idx) => idx !== i))}
                        />
                    ))}
                </div>
            )}

            {/* MT pre-fill (collapsed) */}
            <div className="mt-6">
                <button
                    type="button"
                    onClick={() => setMtExpanded((v) => !v)}
                    className="flex items-center gap-2 text-sm text-stone-600 hover:text-stone-800"
                >
                    {mtExpanded
                        ? <ChevronDown className="size-4 text-stone-400" />
                        : <ChevronRight className="size-4 text-stone-400" />}
                    Auto-fill untranslated segments with machine translation
                </button>
                {mtExpanded && (
                    <div className="mt-3 rounded-lg border border-stone-200 bg-stone-50 p-4 space-y-3">
                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="mt_prefill"
                                checked={data.mt_prefill}
                                onCheckedChange={(v) => set('mt_prefill', Boolean(v))}
                            />
                            <Label htmlFor="mt_prefill" className="text-sm text-stone-700 cursor-pointer">
                                Auto-fill with machine translation
                            </Label>
                        </div>
                        <p className="text-xs text-stone-500">
                            Requires a DeepL or Google API key in Settings. Off by default.
                        </p>
                        <p className="text-xs italic text-stone-400">
                            Tip: Project templates with saved MT preferences are coming in V2.
                        </p>
                    </div>
                )}
            </div>

            <CardFooter>
                <Button variant="ghost" onClick={() => setStep(2)} className="text-stone-500 hover:text-stone-700">
                    ← Back
                </Button>
                <Button
                    onClick={handleSubmit}
                    disabled={files.length === 0 || processing}
                    className="bg-teal-600 hover:bg-teal-700"
                >
                    {processing ? 'Creating…' : 'Create project →'}
                </Button>
            </CardFooter>
        </WizardCard>
    );

    // ── Render ────────────────────────────────────────────────────────────────

    return (
        <>
            <Head title="Create project" />
            <div className="mx-auto max-w-2xl py-8 px-4">
                <StepIndicator current={step} />
                {step === 1 && renderStep1()}
                {step === 2 && renderStep2()}
                {step === 3 && renderStep3()}
            </div>
        </>
    );
}

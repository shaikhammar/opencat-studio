import { Link } from '@inertiajs/react';
import { ArrowRight, Clock, FileText, Loader2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ProjectSummary } from '@/types/project';

function ProgressBar({ pct }: { pct: number }) {
    return (
        <div className="h-1.5 w-full overflow-hidden rounded-full bg-stone-100">
            <div
                className="h-full rounded-full bg-teal-500 transition-all"
                style={{ width: `${pct}%` }}
            />
        </div>
    );
}

function StatusBadge({ status }: { status: ProjectSummary['status'] }) {
    const map = {
        active: 'bg-teal-50 text-teal-700 border-teal-200',
        completed: 'bg-stone-100 text-stone-600 border-stone-200',
        archived: 'bg-stone-50 text-stone-400 border-stone-200',
    } as const;
    return (
        <span className={`inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium capitalize ${map[status]}`}>
            {status}
        </span>
    );
}

function NextStepChip({ project }: { project: ProjectSummary }) {
    if (project.has_processing_files) {
        return (
            <span className="inline-flex items-center gap-1 text-xs text-amber-600">
                <Loader2 className="size-3 animate-spin" />
                Processing…
            </span>
        );
    }
    if (project.progress_pct === 100) {
        return <span className="text-xs text-teal-600">Translation complete — export?</span>;
    }
    if (project.total_segments > 0 && project.progress_pct === 0) {
        return (
            <span className="inline-flex items-center gap-1 text-xs text-teal-600">
                Ready to translate <ArrowRight className="size-3" />
            </span>
        );
    }
    return null;
}

export function ProjectCard({ project }: { project: ProjectSummary }) {
    const lastActivity = project.last_activity_at
        ? new Date(project.last_activity_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
        : new Date(project.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });

    return (
        <div className="flex flex-col gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm transition hover:shadow-md">
            <div className="flex items-start justify-between gap-2">
                <div className="flex-1 min-w-0">
                    <h3 className="truncate text-sm font-semibold text-stone-900">{project.name}</h3>
                    <Badge variant="outline" className="mt-1 font-mono text-xs">
                        {project.source_lang.toUpperCase()} → {project.target_lang.toUpperCase()}
                    </Badge>
                </div>
                <StatusBadge status={project.status} />
            </div>

            <div className="space-y-1">
                <div className="flex justify-between text-xs text-stone-500">
                    <span>{project.translated_count.toLocaleString()} / {project.total_segments.toLocaleString()} segments</span>
                    <span>{project.progress_pct}%</span>
                </div>
                <ProgressBar pct={project.progress_pct} />
            </div>

            <div className="flex items-center justify-between text-xs text-stone-400">
                <span className="flex items-center gap-1">
                    <FileText className="size-3" />
                    {project.files_count} {project.files_count === 1 ? 'file' : 'files'}
                </span>
                <span className="flex items-center gap-1">
                    <Clock className="size-3" />
                    {lastActivity}
                </span>
            </div>

            <NextStepChip project={project} />

            <Button asChild size="sm" className="mt-auto w-full bg-teal-600 hover:bg-teal-700">
                <Link href={`/projects/${project.id}`}>Open project</Link>
            </Button>
        </div>
    );
}

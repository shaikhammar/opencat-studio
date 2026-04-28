import { Head, Link } from '@inertiajs/react';
import { FolderOpen, Plus } from 'lucide-react';
import { ProjectCard } from '@/components/project-card';
import { Button } from '@/components/ui/button';
import type { ProjectSummary } from '@/types/project';

interface Props {
    projects: ProjectSummary[];
}

export default function Dashboard({ projects }: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold text-stone-900">My Projects</h1>
                    <Button asChild size="sm" className="bg-teal-600 hover:bg-teal-700">
                        <Link href="/projects/create">
                            <Plus className="mr-1.5 size-4" />
                            New Project
                        </Link>
                    </Button>
                </div>

                {projects.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-stone-200 py-20 text-center">
                        <div className="flex size-14 items-center justify-center rounded-full bg-stone-100">
                            <FolderOpen className="size-7 text-stone-400" />
                        </div>
                        <div className="space-y-1">
                            <p className="text-sm font-medium text-stone-700">No projects yet</p>
                            <p className="text-sm text-stone-400">Create your first project to start translating.</p>
                        </div>
                        <Button asChild className="bg-teal-600 hover:bg-teal-700">
                            <Link href="/projects/create">Create project →</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {projects.map((project) => (
                            <ProjectCard key={project.id} project={project} />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

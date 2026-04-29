import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import type { AppLayoutProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const { redisAvailable } = usePage().props;

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {!redisAvailable && (
                    <div className="px-4 pt-4">
                        <Alert className="border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                            <TriangleAlertIcon className="text-amber-600 dark:text-amber-400" />
                            <AlertTitle>Queue service unavailable</AlertTitle>
                            <AlertDescription>
                                Redis is not reachable. Background jobs (file processing, QA runs, TM imports) cannot run until the connection is restored.
                            </AlertDescription>
                        </Alert>
                    </div>
                )}
                {children}
            </AppContent>
        </AppShell>
    );
}

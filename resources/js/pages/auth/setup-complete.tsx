import { Head, Link } from '@inertiajs/react';

export default function SetupComplete() {
    return (
        <>
            <Head title="Already set up" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-stone-50 px-4">
                <div className="w-full max-w-sm space-y-6 text-center">
                    <div className="space-y-2">
                        <h1 className="text-2xl font-semibold text-stone-900">OpenCAT is set up</h1>
                        <p className="text-sm text-stone-500">
                            This instance already has an account. Registration is disabled.
                        </p>
                        <p className="text-xs text-stone-400">
                            To reset for a fresh install, truncate the <code className="font-mono">users</code> table.
                        </p>
                    </div>
                    <Link
                        href="/login"
                        className="inline-flex w-full items-center justify-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
                    >
                        Go to login
                    </Link>
                </div>
            </div>
        </>
    );
}

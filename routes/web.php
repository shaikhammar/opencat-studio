<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\GlossaryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TmController;
use Illuminate\Support\Facades\Route;

// ── Auth (Fortify handles login/register/password reset) ──────────
Route::inertia('/', 'welcome')->name('home');

// Block /register once the single user account exists (D-S1)
Route::middleware('registration.enabled')->group(function () {
    Route::inertia('/register', 'auth/register')->name('register');
});

// ── Authenticated routes ──────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show')->can('view', 'project');
    Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update')->can('update', 'project');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy')->can('delete', 'project');

    // Files
    Route::post('/projects/{project}/files', [FileController::class, 'store'])->name('files.store')->can('update', 'project');
    Route::delete('/projects/{project}/files/{file}', [FileController::class, 'destroy'])->name('files.destroy')->can('update', 'project');

    // Editor
    Route::get('/projects/{project}/files/{file}/editor', [EditorController::class, 'show'])->name('editor.show')->can('view', 'project');

    // Export
    Route::post('/projects/{project}/files/{file}/export', [ExportController::class, 'store'])->name('export.store')->can('view', 'project');
    Route::get('/projects/{project}/files/{file}/export/download', [ExportController::class, 'download'])->name('export.download')->can('view', 'project');

    // Project TM
    Route::get('/projects/{project}/tm', [TmController::class, 'show'])->name('tm.show')->can('view', 'project');
    Route::post('/projects/{project}/tm/import', [TmController::class, 'import'])->name('tm.import')->can('update', 'project');
    Route::get('/projects/{project}/tm/export', [TmController::class, 'export'])->name('tm.export')->can('view', 'project');
    Route::delete('/projects/{project}/tm/entries/{entry}', [TmController::class, 'destroyEntry'])->name('tm.entries.destroy')->can('update', 'project');

    // Global TM
    Route::get('/tm', [TmController::class, 'global'])->name('tm.global');
    Route::post('/tm/import', [TmController::class, 'importGlobal'])->name('tm.global.import');
    Route::get('/tm/export', [TmController::class, 'exportGlobal'])->name('tm.global.export');

    // Project Glossary
    Route::get('/projects/{project}/glossary', [GlossaryController::class, 'show'])->name('glossary.show')->can('view', 'project');
    Route::post('/projects/{project}/glossary/import', [GlossaryController::class, 'import'])->name('glossary.import')->can('update', 'project');
    Route::post('/projects/{project}/glossary/terms', [GlossaryController::class, 'storeTerm'])->name('glossary.terms.store')->can('update', 'project');
    Route::delete('/projects/{project}/glossary/terms/{termId}', [GlossaryController::class, 'destroyTerm'])->name('glossary.terms.destroy')->can('update', 'project');

    // Global Glossary
    Route::get('/glossary', [GlossaryController::class, 'global'])->name('glossary.global');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::patch('/settings/mt', [SettingsController::class, 'updateMt'])->name('settings.mt');
    Route::patch('/settings/qa', [SettingsController::class, 'updateQa'])->name('settings.qa');
});

require __DIR__.'/settings.php';

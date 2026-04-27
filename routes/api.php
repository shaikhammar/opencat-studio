<?php

use App\Http\Controllers\Api\MtController;
use App\Http\Controllers\Api\QaRunController;
use App\Http\Controllers\Api\SegmentController;
use App\Http\Controllers\Api\TmLookupController;
use Illuminate\Support\Facades\Route;

// Editor JSON API — consumed by React editor via fetch (same session cookie as Inertia)
Route::middleware(['auth', 'throttle:api'])->group(function () {

    // Segments
    Route::get('/projects/{project}/files/{file}/segments', [SegmentController::class, 'index']);
    Route::get('/projects/{project}/files/{file}/segments/{segment}', [SegmentController::class, 'show']);
    Route::patch('/projects/{project}/files/{file}/segments/{segment}', [SegmentController::class, 'update']);

    // TM lookup and concordance
    Route::get('/projects/{project}/files/{file}/segments/{segment}/tm', [TmLookupController::class, 'lookup']);
    Route::get('/projects/{project}/tm/search', [TmLookupController::class, 'concordance']);

    // MT suggestion
    Route::post('/projects/{project}/files/{file}/segments/{segment}/mt', [MtController::class, 'suggest']);

    // QA
    Route::post('/projects/{project}/files/{file}/qa', [QaRunController::class, 'store']);
    Route::get('/projects/{project}/files/{file}/qa/results', [QaRunController::class, 'results']);
});

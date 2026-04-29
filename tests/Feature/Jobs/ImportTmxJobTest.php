<?php

use App\Jobs\ImportTmxJob;
use App\Models\TranslationMemory;
use App\Services\TmService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

test('job calls import service and deletes tmp file', function () {
    Storage::fake();
    Storage::put('tmp/tmx/test.tmx', '<tmx/>');

    $tm = TranslationMemory::factory()->create();

    $this->mock(TmService::class, function ($mock) {
        $mock->shouldReceive('importTmx')->once()->andReturn(5);
    });

    (new ImportTmxJob('tmp/tmx/test.tmx', $tm))->handle(app(TmService::class));

    Storage::assertMissing('tmp/tmx/test.tmx');
});

test('import job increments tm entry count via service', function () {
    Storage::fake();
    Storage::put('tmp/tmx/test.tmx', '<tmx/>');

    $tm = TranslationMemory::factory()->create(['entry_count' => 0]);

    $this->mock(TmService::class, function ($mock) use ($tm) {
        $mock->shouldReceive('importTmx')
            ->once()
            ->andReturnUsing(function () use ($tm) {
                DB::table('translation_memories')
                    ->where('id', $tm->id)
                    ->increment('entry_count', 3);

                return 3;
            });
    });

    (new ImportTmxJob('tmp/tmx/test.tmx', $tm))->handle(app(TmService::class));

    expect($tm->fresh()->entry_count)->toBe(3);
});

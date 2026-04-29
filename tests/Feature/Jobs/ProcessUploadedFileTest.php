<?php

use App\Jobs\PopulateMtSuggestions;
use App\Jobs\ProcessUploadedFile;
use App\Models\ProjectFile;
use App\Models\Segment;
use App\Support\FrameworkBridge;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

function makeSegmentPair(string $source = 'Hello world'): object
{
    return new class($source)
    {
        public function __construct(private string $text) {}

        public function getSourceText(): string
        {
            return $this->text;
        }

        public function getSourceTags(): array
        {
            return [];
        }
    };
}

function makeExtractResult(object $document, string $skeleton = 'skeleton'): object
{
    return new class($document, $skeleton)
    {
        public object $document;

        public string $skeleton;

        public function __construct(object $doc, string $skel)
        {
            $this->document = $doc;
            $this->skeleton = $skel;
        }
    };
}

function makeDocument(array $pairs): object
{
    return new class($pairs)
    {
        public function __construct(private array $pairs) {}

        public function getSegmentPairs(): array
        {
            return $this->pairs;
        }
    };
}

test('job processes file and creates segments', function () {
    Storage::fake('local');
    Queue::fake();

    $file = ProjectFile::factory()->create(['file_format' => 'txt', 'status' => 'pending']);
    Storage::put($file->storage_path, 'Hello world. Second sentence.');

    $pairs = [makeSegmentPair('Hello world.'), makeSegmentPair('Second sentence.')];
    $doc = makeDocument($pairs);
    $result = makeExtractResult($doc);

    $bridge = Mockery::mock(FrameworkBridge::class);
    $bridge->shouldReceive('makeFileFilter')->with('txt')->andReturn(
        new class($result)
        {
            public function __construct(private object $result) {}

            public function extract(string $content): object
            {
                return $this->result;
            }
        }
    );
    $bridge->shouldReceive('makeSegmentationEngine')->andReturn(
        new class
        {
            public function segment(object $document): void {}
        }
    );
    app()->instance(FrameworkBridge::class, $bridge);

    (new ProcessUploadedFile($file))->handle($bridge);

    $file->refresh();
    expect($file->status)->toBe('ready');
    expect($file->segment_count)->toBe(2);
    expect($file->word_count)->toBeGreaterThan(0);

    expect(Segment::where('file_id', $file->id)->count())->toBe(2);
});

test('job marks file as error on failure', function () {
    Storage::fake('local');
    Queue::fake();

    $file = ProjectFile::factory()->create(['file_format' => 'txt', 'status' => 'pending']);
    Storage::put($file->storage_path, 'content');

    $bridge = Mockery::mock(FrameworkBridge::class);
    $bridge->shouldReceive('makeFileFilter')->andReturn(
        new class
        {
            public function extract(string $content): never
            {
                throw new RuntimeException('Parse error');
            }
        }
    );
    $bridge->shouldReceive('makeSegmentationEngine')->andReturn(new class
    {
        public function segment(object $doc): void {}
    });
    app()->instance(FrameworkBridge::class, $bridge);

    expect(fn () => (new ProcessUploadedFile($file))->handle($bridge))->toThrow(RuntimeException::class);

    $file->refresh();
    expect($file->status)->toBe('error');
    expect($file->error_message)->toBe('Parse error');
});

test('job dispatches mt suggestions when mt prefill is enabled', function () {
    Storage::fake('local');
    Queue::fake();

    $file = ProjectFile::factory()->create(['file_format' => 'txt', 'status' => 'pending']);
    Storage::put($file->storage_path, 'Hello.');

    $doc = makeDocument([makeSegmentPair('Hello.')]);
    $result = makeExtractResult($doc);

    $bridge = Mockery::mock(FrameworkBridge::class);
    $bridge->shouldReceive('makeFileFilter')->andReturn(
        new class($result)
        {
            public function __construct(private object $result) {}

            public function extract(string $content): object
            {
                return $this->result;
            }
        }
    );
    $bridge->shouldReceive('makeSegmentationEngine')->andReturn(new class
    {
        public function segment(object $doc): void {}
    });
    app()->instance(FrameworkBridge::class, $bridge);

    (new ProcessUploadedFile($file, ['mtPrefill' => true]))->handle($bridge);

    Queue::assertPushed(PopulateMtSuggestions::class);
});

test('job uses the correct queue', function () {
    $file = ProjectFile::factory()->make();
    $job = new ProcessUploadedFile($file);

    expect($job->queue)->toBeNull();
});

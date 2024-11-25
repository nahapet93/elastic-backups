<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use stdClass;

class ElasticSearchService
{
    private string $scheme;

    private string $host;

    private string $port;

    private const string IMPORT_DIRECTORY = 'import';

    /**
     * @var string[]
     */
    private array $importFiles;

    public function __construct(
        protected Filesystem $filesystem,
        protected ElasticDump $elasticDump
    ) {
        $this->scheme = config('olc.elasticsearch.scheme');
        $this->host = config('olc.elasticsearch.host');
        $this->port = config('olc.elasticsearch.port');
        $this->importFiles = $this->filesystem->files(self::IMPORT_DIRECTORY);
    }

    /**
     * @return array<string[]>
     *
     * @throws ConnectionException
     */
    public function getIndexes(): array
    {
        $url = $this->scheme.'://'.$this->host.':'.$this->port;
        $response = Http::baseUrl($url)->get('/_stats');
        $lines = $response->json()['indices'];
        $restores = $this->getRestores();

        $olcIndexes = array_filter(
            $lines,
            fn (string $key) => str($key)->startsWith('olc'),
            ARRAY_FILTER_USE_KEY
        );

        return array_map(
            function (string $key, array $value) use ($restores): array {
                $lastRestore = $restores->where('index_name', $key)->sortByDesc('id')->first();

                return [
                    'name' => $key,
                    'uuid' => $value['uuid'],
                    'prefix' => $this->getPrefix($key),
                    'dump' => $lastRestore?->dump_name,
                    'last_restore_date' => $lastRestore?->last_restored_at,
                ];
            },
            array_keys($olcIndexes),
            array_values($olcIndexes),
        );
    }

    public function elasticDump(string $index): bool|string
    {
        $filePath = self::IMPORT_DIRECTORY.DIRECTORY_SEPARATOR.$index.now()->format('_Ymd_His').'.json';
        $fullPath = $this->filesystem->path($filePath);
        $url = $this->scheme.'://'.$this->host.':'.$this->port.'/'.$index;
        $result = $this->elasticDump->process($url, $fullPath);

        if ($result->failed() || ! $this->filesystem->exists($filePath)) {
            return false;
        }

        return $fullPath;
    }

    public function elasticImport(string $index, string $file): bool
    {
        $fullPath = $this->filesystem->path($file);
        $url = $this->scheme.'://'.$this->host.':'.$this->port.'/'.$index;
        $result = $this->elasticDump->process($fullPath, $url);

        if (! $result->successful()) {
            return false;
        }

        DB::table('elastic_backups')
            ->insert([
                'index_name' => $index,
                'dump_name' => basename($file),
                'last_restored_at' => now(),
            ]);

        return true;
    }

    private function getPrefix(string $indexName): string
    {
        preg_match('/olc_\d+/', $indexName, $matches);

        return $matches ? $matches[0] : '';
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function getRestores(): Collection
    {
        return DB::table('elastic_backups')->get();
    }

    /**
     * @return string[]
     */
    public function getDumpList(string $indexName): array
    {
        $dumps = array_filter(
            $this->importFiles,
            fn (string $file) => str(basename($file))->startsWith($indexName),
        );

        return array_combine($dumps, $dumps);
    }
}

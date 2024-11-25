<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ElasticSearchService
{
    private string $scheme;
    private string $host;
    private string $port;

    public function __construct(
        protected Filesystem $filesystem,
        protected ElasticDump $elasticDump
    ) {
        $this->scheme = config('olc.elasticsearch.scheme');
        $this->host = config('olc.elasticsearch.host');
        $this->port = config('olc.elasticsearch.port');
    }

    public function getIndexes(): array
    {
        $url = $this->scheme . '://' . $this->host . ':' . $this->port;
        $response = Http::baseUrl($url)->get('/_stats');
        $lines = $response->json()['indices'];
        $restores = $this->getRestores();

        $olcIndexes = array_filter(
            $lines,
            fn(string $key) => str($key)->startsWith('olc'),
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
                    'last_restore_date' => $lastRestore?->last_restored_at
                ];
            },
            array_keys($olcIndexes),
            array_values($olcIndexes),
        );
    }

    public function elasticDump(string $index): bool|string
    {
        $filePath = 'import' . DIRECTORY_SEPARATOR . $index . now()->format('_Ymd_His') . '.json';
        $fullPath = $this->filesystem->path($filePath);
        $url = $this->scheme . '://' . $this->host . ':' . $this->port . '/' . $index;
        $result = $this->elasticDump->process($url, $fullPath);

        if ($result->failed() || !$this->filesystem->exists($filePath)) {
            return false;
        }

        return $fullPath;
    }

    public function elasticImport(string $index, string $file): bool
    {
        $fullPath = $this->filesystem->path($file);
        $url = $this->scheme . '://' . $this->host . ':' . $this->port . '/' . $index;
        $result = $this->elasticDump->process($fullPath, $url);

        if (!$result->successful()) {
            return false;
        }

        DB::table('elastic_backups')
            ->insert([
                'index_name' => $index,
                'dump_name' => basename($file),
                'last_restored_at' => now()
            ]);
        return true;
    }

    private function getPrefix(string $indexName): string
    {
        preg_match('/olc_\d+/', $indexName, $matches);
        return $matches ? $matches[0] : '';
    }

    private function getRestores(): Collection
    {
        return DB::table('elastic_backups')->get();
    }

    public function getDumpList(string $indexName): array
    {
        $dumps = array_filter(
            $this->filesystem->files('import'),
            fn(string $file) => str($file)->startsWith('import/' . $indexName),
        );

        return array_combine($dumps, $dumps);
    }
}

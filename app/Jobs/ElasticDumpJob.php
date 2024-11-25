<?php

namespace App\Jobs;

use App\Services\ElasticSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ElasticDumpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $indexes = app(ElasticSearchService::class)->getIndexes();

        foreach ($indexes as $index) {
            app(ElasticSearchService::class)->elasticDump($index['name']);
        }
    }
}

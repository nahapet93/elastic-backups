<?php

namespace App\Models;

use App\Services\ElasticSearchService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Sushi\Sushi;

/**
 * @property string $name
 */
class ElasticBackup extends Model
{
    use Sushi;

    /**
     * @return array<string[]>
     *
     * @throws ConnectionException
     */
    public function getRows(): array
    {
        return app(ElasticSearchService::class)->getIndexes();
    }
}

<?php

namespace App\Models;

use App\Services\ElasticSearchService;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

/** @property string $name */
class ElasticBackup extends Model
{
    use Sushi;

    public function getRows()
    {
        return app(ElasticSearchService::class)->getIndexes();
    }
}

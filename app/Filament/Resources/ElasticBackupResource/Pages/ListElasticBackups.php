<?php

namespace App\Filament\Resources\ElasticBackupResource\Pages;

use App\Filament\Resources\ElasticBackupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListElasticBackups extends ListRecords
{
    protected static string $resource = ElasticBackupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

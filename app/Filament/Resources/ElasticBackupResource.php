<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ElasticBackupResource\Pages;
use App\Models\ElasticBackup;
use App\Services\ElasticSearchService;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ElasticBackupResource extends Resource
{
    protected static ?string $model = ElasticBackup::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('uuid'),
                TextColumn::make('dump'),
                TextColumn::make('last_restore_date'),
            ])
            ->defaultGroup('prefix')
            ->actions([
                Action::make('restore')
                    ->form([
                        Select::make('file')
                            ->label('Dump file')
                            ->options(
                                fn (ElasticBackup $backup) => app(ElasticSearchService::class)
                                    ->getDumpList($backup->name)
                            )
                            ->required(),
                    ])
                    ->action(
                        function (array $data, ElasticBackup $backup) {
                            $import = app(ElasticSearchService::class)
                                ->elasticImport($backup->name, $data['file']);

                            if ($import) {
                                return Notification::make()
                                    ->title('Restore from dump successful.')
                                    ->success()
                                    ->send();
                            }

                            return Notification::make()
                                ->title('Could not restore from dump.')
                                ->danger()
                                ->send();
                        }
                    ),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListElasticBackups::route('/'),
        ];
    }
}

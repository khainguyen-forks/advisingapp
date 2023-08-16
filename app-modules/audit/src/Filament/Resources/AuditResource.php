<?php

namespace Assist\Audit\Filament\Resources;

use Filament\Resources\Resource;
use OwenIt\Auditing\Models\Audit;
use Assist\Audit\Filament\Resources\AuditResource\Pages;

class AuditResource extends Resource
{
    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAudits::route('/'),
            'view' => Pages\ViewAudit::route('/{record}'),
        ];
    }
}

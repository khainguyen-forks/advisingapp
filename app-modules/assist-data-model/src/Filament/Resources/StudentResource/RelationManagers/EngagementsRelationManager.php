<?php

/*
<COPYRIGHT>

Copyright © 2022-2023, Canyon GBS LLC

All rights reserved.

This file is part of a project developed using Laravel, which is an open-source framework for PHP.
Canyon GBS LLC acknowledges and respects the copyright of Laravel and other open-source
projects used in the development of this solution.

This project is licensed under the Affero General Public License (AGPL) 3.0.
For more details, see https://github.com/canyongbs/assistbycanyongbs/blob/main/LICENSE.

Notice:
- The copyright notice in this file and across all files and applications in this
 repository cannot be removed or altered without violating the terms of the AGPL 3.0 License.
- The software solution, including services, infrastructure, and code, is offered as a
 Software as a Service (SaaS) by Canyon GBS LLC.
- Use of this software implies agreement to the license terms and conditions as stated
 in the AGPL 3.0 License.

For more information or inquiries please visit our website at
https://www.canyongbs.com or contact us via email at legal@canyongbs.com.

</COPYRIGHT>
*/

namespace Assist\AssistDataModel\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use App\Filament\Columns\IdColumn;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Assist\Engagement\Models\Engagement;
use Filament\Forms\Components\Component;
use Filament\Tables\Actions\CreateAction;
use Assist\AssistDataModel\Models\Student;
use Filament\Infolists\Components\Fieldset;
use Filament\Forms\Components\MorphToSelect;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Assist\Engagement\Enums\EngagementDeliveryMethod;
use Assist\Engagement\Enums\EngagementDeliveryStatus;
use Assist\Engagement\Actions\CreateEngagementDeliverable;
use App\Filament\Resources\RelationManagers\RelationManager;
use Assist\Engagement\Filament\Resources\EngagementResource\Pages\CreateEngagement;

class EngagementsRelationManager extends RelationManager
{
    protected static string $relationship = 'engagements';

    public function form(Form $form): Form
    {
        $createEngagementForm = (resolve(CreateEngagement::class))->form($form);

        $formComponents = collect($createEngagementForm->getComponents())->filter(function (Component $component) {
            if (! $component instanceof MorphToSelect) {
                return true;
            }
        })->toArray();

        return $createEngagementForm
            ->schema([
                Hidden::make('recipient_id')
                    ->default($this->getOwnerRecord()->identifier()),
                Hidden::make('recipient_type')
                    ->default(resolve(Student::class)->getMorphClass()),
                ...$formComponents,
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('user.name')
                    ->label('Created By'),
                Fieldset::make('Content')
                    ->schema([
                        TextEntry::make('subject')
                            ->hidden(fn (Engagement $engagement): bool => $engagement->deliverable->channel === EngagementDeliveryMethod::Sms),
                        TextEntry::make('body'),
                    ]),
                Fieldset::make('deliverable')
                    ->label('Delivery Information')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('deliverable.channel')
                            ->label('Channel'),
                        IconEntry::make('deliverable.delivery_status')
                            ->icon(fn (EngagementDeliveryStatus $state): string => match ($state) {
                                EngagementDeliveryStatus::Successful => 'heroicon-o-check-circle',
                                EngagementDeliveryStatus::Awaiting => 'heroicon-o-clock',
                                EngagementDeliveryStatus::Failed => 'heroicon-o-x-circle',
                            })
                            ->color(fn (EngagementDeliveryStatus $state): string => match ($state) {
                                EngagementDeliveryStatus::Successful => 'success',
                                EngagementDeliveryStatus::Awaiting => 'info',
                                EngagementDeliveryStatus::Failed => 'danger',
                            })
                            ->label('Status'),
                        TextEntry::make('deliverable.delivered_at')
                            ->label('Delivered At'),
                        TextEntry::make('deliverable.delivery_response')
                            ->label('Response'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                IdColumn::make(),
                TextColumn::make('subject'),
                TextColumn::make('body'),
                TextColumn::make('deliverable.channel')
                    ->label('Delivery Channel'),
            ])
            ->filters([
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(function (Engagement $engagement, array $data) {
                        $this->afterCreate($engagement, $data['delivery_method']);
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function afterCreate(Engagement $engagement, string $deliveryMethod): void
    {
        $createEngagementDeliverable = resolve(CreateEngagementDeliverable::class);

        $createEngagementDeliverable($engagement, $deliveryMethod);
    }
}

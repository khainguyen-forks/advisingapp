<?php

/*
<COPYRIGHT>

    Copyright © 2016-2024, Canyon GBS LLC. All rights reserved.

    Advising App™ is licensed under the Elastic License 2.0. For more details,
    see https://github.com/canyongbs/advisingapp/blob/main/LICENSE.

    Notice:

    - You may not provide the software to third parties as a hosted or managed
      service, where the service provides users with access to any substantial set of
      the features or functionality of the software.
    - You may not move, change, disable, or circumvent the license key functionality
      in the software, and you may not remove or obscure any functionality in the
      software that is protected by the license key.
    - You may not alter, remove, or obscure any licensing, copyright, or other notices
      of the licensor in the software. Any use of the licensor’s trademarks is subject
      to applicable law.
    - Canyon GBS LLC respects the intellectual property rights of others and expects the
      same in return. Canyon GBS™ and Advising App™ are registered trademarks of
      Canyon GBS LLC, and we are committed to enforcing and protecting our trademarks
      vigorously.
    - The software solution, including services, infrastructure, and code, is offered as a
      Software as a Service (SaaS) by Canyon GBS LLC.
    - Use of this software implies agreement to the license terms and conditions as stated
      in the Elastic License 2.0.

    For more information or inquiries please visit our website at
    https://www.canyongbs.com or contact us via email at legal@canyongbs.com.

</COPYRIGHT>
*/

namespace AdvisingApp\Task\Histories;

use App\Models\User;
use Laravel\Pennant\Feature;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use AdvisingApp\Task\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use AdvisingApp\Timeline\Models\History;
use App\Filament\Resources\UserResource;
use AdvisingApp\Prospect\Models\Prospect;
use AdvisingApp\StudentDataModel\Models\Student;
use Illuminate\Database\Eloquent\Casts\Attribute;
use AdvisingApp\Timeline\Timelines\TaskHistoryTimeline;
use AdvisingApp\Timeline\Models\Contracts\ProvidesATimeline;

class TaskHistory extends History implements ProvidesATimeline
{
    public function timeline(): TaskHistoryTimeline
    {
        return new TaskHistoryTimeline($this);
    }

    public static function getTimelineData(Model $forModel): Collection
    {
        if (Feature::active('educatable-alerts-timeline')) {
            /* @var Student|Prospect $forModel */
            return $forModel->taskHistories()->get();
        }

        return collect();
    }

    public function formatted(): Attribute
    {
        return Attribute::get(
            fn () => collect($this->new)
                ->map(function ($value, $key) {
                    return match ($key) {
                        'status' => [
                            'key' => 'Status',
                            'old' => array_key_exists($key, $this->old)
                                ? TaskStatus::tryFrom($this->old[$key])?->getLabel()
                                : null,
                            'new' => TaskStatus::tryFrom($value)?->getLabel(),
                        ],
                        'due' => [
                            'key' => 'Due',
                            'old' => array_key_exists($key, $this->old)
                                ? Carbon::parse($this->old[$key])->format('m-d-Y')
                                : null,
                            'new' => Carbon::parse($value)->format('m-d-Y'),
                        ],
                        'assigned_to' => [
                            'key' => 'Assigned to',
                            'old' => array_key_exists($key, $this->old)
                                ? User::find($this->old[$key])?->name
                                : null,
                            'new' => User::find($value)?->name,
                            'extra' => [
                                'old' => [
                                    'link' => array_key_exists($key, $this->old)
                                        ? UserResource::getUrl('view', ['record' => $this->old[$key]])
                                        : null,
                                ],
                                'new' => [
                                    'link' => UserResource::getUrl('view', ['record' => $value]),
                                ],
                            ],
                        ],
                        'created_by' => [
                            'key' => 'Created by',
                            'old' => array_key_exists($key, $this->old)
                                ? User::find($this->old[$key])?->name
                                : null,
                            'new' => User::find($value)?->name,
                            'extra' => [
                                'old' => [
                                    'link' => array_key_exists($key, $this->old)
                                        ? UserResource::getUrl('view', ['record' => $this->old[$key]])
                                        : null,
                                ],
                                'new' => [
                                    'link' => UserResource::getUrl('view', ['record' => $value]),
                                ],
                            ],
                        ],
                        default => [
                            'key' => str($key)->headline()->toString(),
                            'old' => $this->old[$key] ?? null,
                            'new' => $value,
                        ],
                    };
                })
                ->filter()
        );
    }
}

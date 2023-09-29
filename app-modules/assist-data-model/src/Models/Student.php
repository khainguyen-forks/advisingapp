<?php

namespace Assist\AssistDataModel\Models;

use Assist\Task\Models\Task;
use Assist\Alert\Models\Alert;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Contracts\Auditable;
use Assist\Engagement\Models\EngagementFile;
use Assist\ServiceManagement\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Assist\Engagement\Models\EngagementFileEntities;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Assist\AssistDataModel\Models\Contracts\Educatable;
use Assist\Notifications\Models\Contracts\Subscribable;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Assist\Timeline\Models\Contracts\HasFilamentResource;
use Assist\Notifications\Models\Concerns\HasSubscriptions;
use Assist\Authorization\Models\Concerns\DefinesPermissions;
use Assist\Audit\Models\Concerns\Auditable as AuditableTrait;
use Assist\AssistDataModel\Filament\Resources\StudentResource;
use Assist\Engagement\Models\Concerns\HasManyMorphedEngagements;
use Assist\Interaction\Models\Concerns\HasManyMorphedInteractions;
use Assist\Engagement\Models\Concerns\HasManyMorphedEngagementResponses;

/**
 * @mixin IdeHelperStudent
 */
class Student extends Model implements Auditable, Subscribable, Educatable, HasFilamentResource
{
    use AuditableTrait;
    use HasFactory;
    use DefinesPermissions;
    use Notifiable;
    use HasManyMorphedEngagements;
    use HasManyMorphedEngagementResponses;
    use HasManyMorphedInteractions;
    use HasSubscriptions;

    protected $primaryKey = 'sisid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'sisid' => 'string',
    ];

    public $timestamps = false;

    public function identifier(): string
    {
        return $this->sisid;
    }

    public static function displayNameKey(): string
    {
        return 'full_name';
    }

    public function serviceRequests(): MorphMany
    {
        return $this->morphMany(
            related: ServiceRequest::class,
            name: 'respondent',
            type: 'respondent_type',
            id: 'respondent_id',
            localKey: 'sisid'
        );
    }

    public function engagementFiles(): MorphToMany
    {
        return $this->morphToMany(
            related: EngagementFile::class,
            name: 'entity',
            table: 'engagement_file_entities',
            foreignPivotKey: 'entity_id',
            relatedPivotKey: 'engagement_file_id',
            relation: 'engagementFiles',
        )
            ->using(EngagementFileEntities::class)
            ->withTimestamps();
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'concern');
    }

    public function alerts(): MorphMany
    {
        return $this->morphMany(Alert::class, 'concern');
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class, 'sisid', 'sisid');
    }

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class, 'sisid', 'sisid');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'sisid', 'sisid');
    }

    public static function filamentResource(): string
    {
        return StudentResource::class;
    }
}
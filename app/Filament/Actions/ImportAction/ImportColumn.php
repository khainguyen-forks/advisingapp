<?php

/*
<COPYRIGHT>

    Copyright © 2022-2023, Canyon GBS LLC. All rights reserved.

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

namespace App\Filament\Actions\ImportAction;

use Closure;
use App\Imports\Importer;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Components\Component;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportColumn extends Component
{
    protected string $name;

    protected string | Closure | null $label = null;

    protected bool | Closure $isMappingRequired = false;

    protected int | Closure | null $decimalPlaces = null;

    protected bool | Closure $isNumeric = false;

    protected bool | Closure $isBoolean = false;

    protected bool | Closure $isBlankStateIgnored = false;

    protected string | Closure | null $arraySeparator = null;

    /**
     * @var array<string> | Closure
     */
    protected array | Closure $guesses = [];

    protected ?Closure $fillRecordUsing = null;

    protected ?Closure $sanitizeStateUsing = null;

    protected array | Closure $dataValidationRules = [];

    protected array | Closure $nestedRecursiveDataValidationRules = [];

    protected ?Importer $importer = null;

    protected mixed $example = null;

    protected string | Closure | null $relationship = null;

    protected string | array | Closure | null $resolveRelationshipUsing = null;

    protected array $resolvedRelatedRecords = [];

    final public function __construct(string $name)
    {
        $this->name($name);
    }

    public static function make(string $name): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure();

        return $static;
    }

    public function getSelect(): Select
    {
        return Select::make($this->getName())
            ->label($this->label)
            ->placeholder('Select a column')
            ->required($this->isMappingRequired);
    }

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function label(string | Closure | null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function example(mixed $example): static
    {
        $this->example = $example;

        return $this;
    }

    public function requiredMapping(bool | Closure $condition = true): static
    {
        $this->isMappingRequired = $condition;

        return $this;
    }

    public function numeric(bool | Closure $condition = true, int | Closure | null $decimalPlaces = null): static
    {
        $this->isNumeric = $condition;
        $this->decimalPlaces = $decimalPlaces;

        return $this;
    }

    public function boolean(bool | Closure $condition = true): static
    {
        $this->isBoolean = $condition;

        return $this;
    }

    public function ignoreBlankState(bool | Closure $condition = true): static
    {
        $this->isBlankStateIgnored = $condition;

        return $this;
    }

    public function rules(array | Closure $rules): static
    {
        $this->dataValidationRules = $rules;

        return $this;
    }

    public function nestedRecursiveRules(array | Closure $rules): static
    {
        $this->nestedRecursiveDataValidationRules = $rules;

        return $this;
    }

    public function array(string | Closure | null $separator = ','): static
    {
        $this->arraySeparator = $separator;

        return $this;
    }

    /**
     * @param array<string> | Closure $guesses
     */
    public function guess(array | Closure $guesses): static
    {
        $this->guesses = $guesses;

        return $this;
    }

    public function importer(?Importer $importer): static
    {
        $this->importer = $importer;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getGuesses(): array
    {
        $guesses = $this->evaluate($this->guesses);
        array_unshift($guesses, $this->getName());

        return array_reduce($guesses, function (array $carry, string $guess): array {
            $guess = (string) Str::of($guess)
                ->lower()
                ->replace('-', ' ')
                ->replace('_', ' ');
            $carry[] = $guess;

            if (Str::of($guess)->contains(' ')) {
                $carry[] = (string) Str::of($guess)->replace(' ', '-');
                $carry[] = (string) Str::of($guess)->replace(' ', '_');
            }

            return $carry;
        }, []);
    }

    public function sanitizeStateUsing(?Closure $callback): static
    {
        $this->sanitizeStateUsing = $callback;

        return $this;
    }

    public function fillRecordUsing(?Closure $callback): static
    {
        $this->fillRecordUsing = $callback;

        return $this;
    }

    public function sanitizeState(mixed $state, array $options): mixed
    {
        $originalState = $state;

        if (filled($arraySeparator = $this->getArraySeparator())) {
            $state = collect(explode($arraySeparator, strval($state)))
                ->map(fn (mixed $stateItem): mixed => $this->sanitizeStateItem($stateItem))
                ->filter(fn (mixed $stateItem): bool => filled($stateItem))
                ->all();
        } else {
            $state = $this->sanitizeStateItem($state);
        }

        if ($this->sanitizeStateUsing) {
            return $this->evaluate($this->sanitizeStateUsing, [
                'originalState' => $originalState,
                'state' => $state,
                'options' => $options,
            ]);
        }

        return $state;
    }

    public function fillRecord(mixed $state): void
    {
        if ($this->fillRecordUsing) {
            $this->evaluate($this->fillRecordUsing, [
                'state' => $state,
            ]);

            return;
        }

        $relationship = $this->getRelationship();

        if ($relationship) {
            $relationship->associate($this->resolveRelatedRecord($state));

            return;
        }

        $this->getRecord()->{$this->getName()} = $state;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDataValidationRules(): array
    {
        $rules = $this->evaluate($this->dataValidationRules);

        if ($this->hasRelationship()) {
            $rules[] = function (string $attribute, mixed $state, Closure $fail) {
                if (blank($state)) {
                    return;
                }

                $record = $this->resolveRelatedRecord($state);

                if ($record) {
                    return;
                }

                $fail(__('validation.exists', ['attribute' => $attribute]));
            };
        }

        return $rules;
    }

    public function resolveRelatedRecord(mixed $state): ?Model
    {
        if (array_key_exists($state, $this->resolvedRelatedRecords)) {
            return $this->resolvedRelatedRecords[$state];
        }

        /** @var BelongsTo $relationship */
        $relationship = Relation::noConstraints(fn () => $this->getRelationship());
        $relationshipQuery = $relationship->getQuery();

        if (blank($this->resolveRelationshipUsing)) {
            return $this->resolvedRelatedRecords[$state] = $relationshipQuery
                ->where($relationship->getQualifiedOwnerKeyName(), $state)
                ->first();
        }

        $resolveUsing = $this->evaluate($this->resolveRelationshipUsing, [
            'state' => $state,
        ]);

        if ($resolveUsing instanceof Model) {
            return $this->resolvedRelatedRecords[$state] = $resolveUsing;
        }

        if (! (is_array($resolveUsing) || is_string($resolveUsing))) {
            return null;
        }

        $resolveUsing = Arr::wrap($resolveUsing);

        $isFirst = true;

        foreach ($resolveUsing as $columnToResolve) {
            $whereClause = $isFirst ? 'where' : 'orWhere';

            $relationshipQuery->{$whereClause}(
                $columnToResolve,
                $state,
            );

            $isFirst = false;
        }

        return $this->resolvedRelatedRecords[$state] = $relationshipQuery->first();
    }

    public function getNestedRecursiveDataValidationRules(): array
    {
        return $this->evaluate($this->nestedRecursiveDataValidationRules);
    }

    public function isNumeric(): bool
    {
        return (bool) $this->evaluate($this->isNumeric);
    }

    public function isBoolean(): bool
    {
        return (bool) $this->evaluate($this->isBoolean);
    }

    public function isBlankStateIgnored(): bool
    {
        return (bool) $this->evaluate($this->isBlankStateIgnored);
    }

    public function getDecimalPlaces(): ?int
    {
        return $this->evaluate($this->decimalPlaces);
    }

    public function getArraySeparator(): ?string
    {
        return $this->evaluate($this->arraySeparator);
    }

    public function isArray(): bool
    {
        return filled($this->getArraySeparator());
    }

    public function getImporter(): ?Importer
    {
        return $this->importer;
    }

    public function getExample(): mixed
    {
        return $this->evaluate($this->example);
    }

    public function relationship(string | Closure | null $name = null, string | array | Closure | null $resolveUsing = null): static
    {
        $this->relationship = $name ?? $this->getName();
        $this->resolveRelationshipUsing = $resolveUsing;

        return $this;
    }

    public function getRelationship(): ?BelongsTo
    {
        $name = $this->getRelationshipName();

        if (blank($name)) {
            return null;
        }

        return $this->getRecord()->{$name}();
    }

    public function getRelationshipName(): ?string
    {
        return $this->evaluate($this->relationship);
    }

    public function getRecord(): ?Model
    {
        return $this->getImporter()->getRecord();
    }

    public function hasRelationship(): bool
    {
        return filled($this->getRelationshipName());
    }

    protected function sanitizeStateItem(mixed $state): mixed
    {
        if (is_string($state)) {
            $state = trim($state);
        }

        if (blank($state)) {
            return null;
        }

        if ($this->isBoolean()) {
            return $this->sanitizeBooleanStateItem($state);
        }

        if ($this->isNumeric()) {
            return $this->sanitizeNumericStateItem($state);
        }

        return $state;
    }

    protected function sanitizeBooleanStateItem(mixed $state): bool
    {
        // Narrow down the possible values of the state to make comparison easier.
        $state = strtolower(strval($state));

        return match ($state) {
            '1', 'true', 'yes', 'y', 'on' => true,
            '0', 'false', 'no', 'n', 'off' => false,
            default => (bool) $state,
        };
    }

    protected function sanitizeNumericStateItem(mixed $state): int | float
    {
        $state = floatval(preg_replace('/[^0-9.]/', '', $state));

        $decimalPlaces = $this->getDecimalPlaces();

        if ($decimalPlaces === null) {
            return $state;
        }

        return round($state, $decimalPlaces);
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'data' => [$this->getImporter()->getData()],
            'importer' => [$this->getImporter()],
            'options' => [$this->getImporter()->getOptions()],
            'originalData' => [$this->getImporter()->getOriginalData()],
            'record' => [$this->getRecord()],
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        $record = $this->getRecord();

        return match ($parameterType) {
            Importer::class => [$this->getImporter()],
            Model::class, $record ? $record::class : null => [$record],
            default => parent::resolveDefaultClosureDependencyForEvaluationByType($parameterType),
        };
    }
}

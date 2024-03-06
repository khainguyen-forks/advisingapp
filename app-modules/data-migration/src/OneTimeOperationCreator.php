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

namespace AdvisingApp\DataMigration;

use Throwable;
use SplFileInfo;
use ErrorException;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class OneTimeOperationCreator
{
    protected string $operationsDirectory;

    protected string $providedName;

    protected string $operationName = '';

    protected bool $essential = false;

    public function __construct()
    {
        $this->operationsDirectory = OneTimeOperationManager::getDirectoryPath();
    }

    /**
     * @throws Throwable
     */
    public static function createOperationFile(string $name, bool $essential = false): OneTimeOperationFile
    {
        $instance = new self();
        $instance->setProvidedName($name);
        $instance->setEssential($essential);

        return OneTimeOperationFile::make($instance->createFile());
    }

    /**
     * @throws Throwable
     */
    public function createFile(): SplFileInfo
    {
        $path = $this->getPath();
        $stub = $this->getStubFilepath();
        $this->ensureDirectoryExists();

        throw_if(File::exists($path), ErrorException::class, sprintf('File already exists: %s', $path));

        File::put($path, $stub);

        return new SplFileInfo($path);
    }

    public function getOperationName(): string
    {
        if (! $this->operationName) {
            $this->initOperationName();
        }

        return $this->operationName;
    }

    public function setProvidedName(string $providedName): void
    {
        $this->providedName = $providedName;
    }

    public function setEssential(bool $essential): void
    {
        $this->essential = $essential;
    }

    protected function getPath(): string
    {
        return $this->operationsDirectory . DIRECTORY_SEPARATOR . $this->getOperationName();
    }

    protected function getStubFilepath(): string
    {
        // check for custom stub file
        if (File::exists(base_path('stubs/one-time-operation.stub'))) {
            return File::get(base_path('stubs/one-time-operation.stub'));
        }

        if ($this->essential) {
            return File::get(__DIR__ . '/../stubs/one-time-operation-essential.stub');
        }

        return File::get(__DIR__ . '/../stubs/one-time-operation.stub');
    }

    protected function getDatePrefix(): string
    {
        return Carbon::now()->format('Y_m_d_His');
    }

    protected function initOperationName(): void
    {
        $this->operationName = $this->getDatePrefix() . '_' . Str::snake($this->providedName) . '.php';
    }

    protected function ensureDirectoryExists(): void
    {
        File::ensureDirectoryExists($this->operationsDirectory);
    }
}
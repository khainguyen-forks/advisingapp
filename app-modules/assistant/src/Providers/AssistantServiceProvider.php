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

namespace AdvisingApp\Assistant\Providers;

use Filament\Panel;
use App\Concerns\ImplementsGraphQL;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use AdvisingApp\Assistant\Models\Prompt;
use AdvisingApp\Assistant\AssistantPlugin;
use AdvisingApp\Assistant\Models\PromptType;
use AdvisingApp\Assistant\Models\AiAssistant;
use AdvisingApp\Assistant\Models\AssistantChat;
use App\Registries\RoleBasedAccessControlRegistry;
use AdvisingApp\Assistant\Observers\PromptObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use AdvisingApp\Assistant\Models\AssistantChatFolder;
use AdvisingApp\Assistant\Models\AssistantChatMessage;
use AdvisingApp\IntegrationAI\Events\AIPromptInitiated;
use AdvisingApp\Assistant\Observers\AiAssistantObserver;
use AdvisingApp\Assistant\Models\AssistantChatMessageLog;
use AdvisingApp\Assistant\Registries\AssistantRbacRegistry;
use AdvisingApp\Assistant\Listeners\LogAssistantChatMessage;
use AdvisingApp\Assistant\Services\AIInterface\Enums\AIChatMessageFrom;

class AssistantServiceProvider extends ServiceProvider
{
    use ImplementsGraphQL;

    public function register(): void
    {
        Panel::configureUsing(fn (Panel $panel) => ($panel->getId() !== 'admin') || $panel->plugin(new AssistantPlugin()));
    }

    public function boot(): void
    {
        Relation::morphMap([
            'ai_assistant' => AiAssistant::class,
            'assistant_chat_folder' => AssistantChatFolder::class,
            'assistant_chat_message_log' => AssistantChatMessageLog::class,
            'assistant_chat_message' => AssistantChatMessage::class,
            'assistant_chat' => AssistantChat::class,
            'prompt_type' => PromptType::class,
            'prompt' => Prompt::class,
        ]);

        $this->registerObservers();

        $this->registerEvents();

        RoleBasedAccessControlRegistry::register(AssistantRbacRegistry::class);

        $this->discoverSchema(__DIR__ . '/../../graphql/*');
        $this->registerEnum(AIChatMessageFrom::class);
    }

    protected function registerObservers(): void
    {
        Prompt::observe(PromptObserver::class);
        AiAssistant::observe(AiAssistantObserver::class);
    }

    protected function registerEvents(): void
    {
        Event::listen(AIPromptInitiated::class, LogAssistantChatMessage::class);
    }
}

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

namespace Assist\KnowledgeBase\Policies;

use App\Models\User;
use App\Enums\Feature;
use Illuminate\Auth\Access\Response;
use App\Concerns\FeatureAccessEnforcedPolicyBefore;
use Assist\KnowledgeBase\Models\KnowledgeBaseCategory;
use App\Policies\Contracts\FeatureAccessEnforcedPolicy;

class KnowledgeBaseCategoryPolicy implements FeatureAccessEnforcedPolicy
{
    use FeatureAccessEnforcedPolicyBefore;

    public function viewAny(User $user): Response
    {
        return $user->canOrElse(
            abilities: 'knowledge_base_category.view-any',
            denyResponse: 'You do not have permissions to view knowledge base categories.'
        );
    }

    public function view(User $user, KnowledgeBaseCategory $knowledgeBaseCategory): Response
    {
        return $user->canOrElse(
            abilities: ['knowledge_base_category.*.view', "knowledge_base_category.{$knowledgeBaseCategory->id}.view"],
            denyResponse: 'You do not have permissions to view this knowledge base category.'
        );
    }

    public function create(User $user): Response
    {
        return $user->canOrElse(
            abilities: 'knowledge_base_category.create',
            denyResponse: 'You do not have permissions to create knowledge base categories.'
        );
    }

    public function update(User $user, KnowledgeBaseCategory $knowledgeBaseCategory): Response
    {
        return $user->canOrElse(
            abilities: ['knowledge_base_category.*.update', "knowledge_base_category.{$knowledgeBaseCategory->id}.update"],
            denyResponse: 'You do not have permissions to update this knowledge base category.'
        );
    }

    public function delete(User $user, KnowledgeBaseCategory $knowledgeBaseCategory): Response
    {
        return $user->canOrElse(
            abilities: ['knowledge_base_category.*.delete', "knowledge_base_category.{$knowledgeBaseCategory->id}.delete"],
            denyResponse: 'You do not have permissions to delete this knowledge base category.'
        );
    }

    public function restore(User $user, KnowledgeBaseCategory $knowledgeBaseCategory): Response
    {
        return $user->canOrElse(
            abilities: ['knowledge_base_category.*.restore', "knowledge_base_category.{$knowledgeBaseCategory->id}.restore"],
            denyResponse: 'You do not have permissions to restore this knowledge base category.'
        );
    }

    public function forceDelete(User $user, KnowledgeBaseCategory $knowledgeBaseCategory): Response
    {
        return $user->canOrElse(
            abilities: ['knowledge_base_category.*.force-delete', "knowledge_base_category.{$knowledgeBaseCategory->id}.force-delete"],
            denyResponse: 'You do not have permissions to permanently delete this knowledge base category.'
        );
    }

    protected function requiredFeatures(): array
    {
        return [Feature::KnowledgeManagement];
    }
}

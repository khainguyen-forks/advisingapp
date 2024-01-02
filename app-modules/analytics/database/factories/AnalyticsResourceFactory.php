<?php

namespace AdvisingApp\Analytics\Database\Factories;

use AdvisingApp\Analytics\Models\AnalyticsResource;
use Illuminate\Database\Eloquent\Factories\Factory;
use AdvisingApp\Analytics\Models\AnalyticsResourceSource;
use AdvisingApp\Analytics\Enums\AnalyticsResourceCategory;

/**
 * @extends Factory<AnalyticsResource>
 */
class AnalyticsResourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => str(fake()->unique()->word())->ucfirst(),
            'description' => fake()->sentences(asText: true),
            'url' => fake()->optional()->url(),
            'category' => fake()->randomElement(AnalyticsResourceCategory::cases()),
            'is_active' => fake()->boolean(),
            'is_included_in_data_portal' => fake()->boolean(),
        ];
    }

    public function configure(): AnalyticsResourceFactory|Factory
    {
        return $this->afterMaking(function (AnalyticsResource $analyticsResource) {
            $analyticsResource
                ->source()
                ->associate(fake()->optional()->randomElement([AnalyticsResourceSource::inRandomOrder()->first() ?? AnalyticsResourceSource::factory()->create()]));
        });
    }
}

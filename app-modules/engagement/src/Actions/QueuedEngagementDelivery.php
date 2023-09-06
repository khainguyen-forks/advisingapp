<?php

namespace Assist\Engagement\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Assist\Engagement\Models\EngagementDeliverable;
use Assist\Engagement\Actions\Contracts\EngagementChannel;

abstract class QueuedEngagementDelivery implements EngagementChannel, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public EngagementDeliverable $deliverable
    ) {}

    public function handle(): void
    {
        ray('QueuedEngagementDelivery()');

        // TODO Remove this simulation of the delivery taking a bit of time...
        sleep(5);

        $this->deliver();
    }
}

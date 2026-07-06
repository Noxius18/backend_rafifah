<?php

namespace Tests\Feature;

use App\Console\Kernel;
use Tests\TestCase;

class ConsoleScheduleTest extends TestCase
{
    public function test_scheduler_registers_shared_hosting_jobs(): void
    {
        /** @var Kernel $kernel */
        $kernel = app(Kernel::class);
        $events = collect($kernel->resolveConsoleSchedule()->events());

        $zoomReminder = $events->first(fn ($event) => str_contains($event->command ?? '', 'zoom:send-reminders'));
        $queueWorker = $events->first(fn ($event) => str_contains($event->command ?? '', 'queue:work database --stop-when-empty --tries=3 --max-time=50'));

        $this->assertNotNull($zoomReminder);
        $this->assertSame('* * * * *', $zoomReminder->expression);
        $this->assertTrue($zoomReminder->withoutOverlapping);

        $this->assertNotNull($queueWorker);
        $this->assertSame('* * * * *', $queueWorker->expression);
        $this->assertTrue($queueWorker->withoutOverlapping);
    }
}

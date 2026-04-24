<?php

namespace UnifiedAppointments\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use UnifiedAppointments\Filament\Pages\AvailabilityCalendarPage;

class UnifiedAppointmentsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'unified-appointments';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            AvailabilityCalendarPage::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
    }

    public static function make(): static
    {
        return new static();
    }
}

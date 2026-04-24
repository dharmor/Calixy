<?php

namespace UnifiedAppointments\Filament\Pages;

use BackedEnum;
use UnifiedAppointments\Themes\ThemeManager;

class AvailabilityCalendarPage extends \Filament\Pages\Page
{
    protected static string | \UnitEnum | null $navigationGroup = 'Scheduling';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Availability';

    protected static ?string $title = 'Availability Calendar';

    protected string $view = 'unified-appointments::filament.pages.availability-calendar';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var ThemeManager $themeManager */
        $themeManager = app(ThemeManager::class);
        $queryParameter = $themeManager->themeQueryParameter();
        $requestedTheme = request()->query($queryParameter);

        return [
            'theme' => $themeManager->resolve(is_string($requestedTheme) ? $requestedTheme : null),
            'activeThemeKey' => $themeManager->resolveKey(is_string($requestedTheme) ? $requestedTheme : null),
            'availableThemes' => $themeManager->all(),
            'themeQueryParameter' => $queryParameter,
            'themeSwitcherEnabled' => $themeManager->themeSwitcherEnabled(),
        ];
    }
}

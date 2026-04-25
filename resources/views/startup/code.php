<?php

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatLocal = static function (?string $value, string $timezone): string {
    if ($value === null || $value === '') {
        return '—';
    }

    return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone($timezone))
        ->format('M j, Y g:i A');
};
$formatInput = static function (?string $value, string $timezone): string {
    if ($value === null || $value === '') {
        return '';
    }

    return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone($timezone))
        ->format('Y-m-d\TH:i');
};
$buildUrl = static function (array $overrides = []) use ($context): string {
    $query = array_filter(
        array_merge($context, $overrides),
        static fn ($value): bool => $value !== null && $value !== '',
    );

    return '?' . http_build_query($query);
};
$themeVariables = '';
$workspaceBrandImageUrl = \UnifiedAppointments\Support\LogoSourceResolver::firstAvailableDataUri([
    [dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'Calixy.jpeg', 'image/jpeg'],
]);
$selectedPageMeta = $pages[$currentPage] ?? ['label' => 'Startup', 'description' => ''];
$selectedServiceName = $selectedServiceId !== null ? ($serviceMap[(string) $selectedServiceId] ?? ('Service ' . $selectedServiceId)) : 'No service selected';
$selectedLocationName = $selectedLocationKey !== null
    ? ($locationMap[(string) $selectedLocationKey] ?? (string) $selectedLocationKey)
    : 'All locations';
$selectedOwnerLabel = 'No owner selected';
$selectedOwnerKey = explode(':', $selectedOwnerToken, 2)[1] ?? '';
$slotPreview = array_slice($slots, 0, 8);
$defaultAppointmentTime = $slotPreview !== []
    ? $slotPreview[0]->startsAt->setTimezone(new DateTimeZone($appTimezone))->format('H:i')
    : '09:00';
$isEditingLocation = $editingLocation !== null;
$isEditingTeamMember = $editingTeamMember !== null;
$upcomingPreview = array_slice($upcomingAppointments, 0, 8);
$selectedDayPreview = array_slice($selectedDayAppointments, 0, 10);
$selectedMonthStart = new DateTimeImmutable($selectedMonth . '-01 00:00:00', new DateTimeZone($appTimezone));
$previousMonth = $selectedMonthStart->modify('-1 month');
$nextMonth = $selectedMonthStart->modify('+1 month');
$weekdayHeadings = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$navGroups = [];
$googleConnection = $calendarConnectionMap['google'] ?? null;
$microsoftConnection = $calendarConnectionMap['microsoft'] ?? null;
$emailNotification = $notificationMap['email'] ?? null;
$digestNotification = $notificationMap['daily_digest'] ?? null;
$mailServerHost = $emailServerConfig['mail_host'] ?? '';
$mailServerPort = $emailServerConfig['mail_port'] ?? '587';
$mailServerEncryption = $emailServerConfig['mail_encryption'] ?? 'tls';
$mailServerUsername = $emailServerConfig['mail_username'] ?? '';
$mailServerFromAddress = $emailServerConfig['mail_from_address'] ?? '';
$mailServerFromName = $emailServerConfig['mail_from_name'] ?? $companyName;
$mailServerReplyTo = $emailServerConfig['mail_reply_to'] ?? '';

foreach ($owners as $ownerOption) {
    if ($ownerOption['token'] === $selectedOwnerToken) {
        $selectedOwnerLabel = $ownerOption['label'];
        break;
    }
}

foreach ($pages as $pageKey => $pageMeta) {
    $group = $pageMeta['group'] ?? 'Workspace';
    $navGroups[$group][$pageKey] = $pageMeta;
}

foreach (($theme['css_variables'] ?? []) as $variable => $value) {
    $themeVariables .= sprintf("%s: %s;\n", $variable, $value);
}

$startupPageTemplate = match ($currentPage) {
    'dashboard' => 'dashboard',
    'calendar' => 'calendar',
    'booking' => 'booking',
    'appointments' => 'appointments',
    'services' => 'services',
    'locations' => 'locations',
    'team' => 'team',
    'notifications' => 'notifications',
    'booking-policy' => 'booking-policy',
    'about' => 'about',
    'google' => 'google',
    'microsoft' => 'microsoft',
    'waitlist' => 'waitlist',
    default => 'default',
};

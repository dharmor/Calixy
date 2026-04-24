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
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape($companyName) ?> Booking</title>
    <style>
        :root {
            <?= $themeVariables ?>
            color-scheme: light;
            --ua-success: #1e7d4d;
            --ua-success-bg: rgba(30, 125, 77, 0.12);
            --ua-danger: #b53939;
            --ua-danger-bg: rgba(181, 57, 57, 0.12);
            --ua-warning-bg: rgba(199, 107, 18, 0.10);
            --ua-sidebar-width: 360px;
            --ua-radius: 26px;
            --ua-input-radius: 16px;
            --ua-shell-max: 1480px;
            --ua-sidebar-background: #121826;
            --ua-sidebar-surface: rgba(255, 255, 255, 0.05);
            --ua-sidebar-border: rgba(255, 255, 255, 0.08);
            --ua-sidebar-text: #f8fafc;
            --ua-sidebar-muted: #94a3b8;
            font-family: Arial, "Helvetica Neue", sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 1.25rem;
            background: var(--ua-page-background);
            color: var(--ua-text);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .app-shell,
        .metric-grid,
        .content-grid,
        .split,
        .form-grid,
        .controls,
        .slots,
        .list,
        .calendar-grid,
        .calendar-head {
            display: grid;
            gap: 1rem;
        }

        .app-shell {
            width: min(100%, var(--ua-shell-max));
            margin: 0 auto;
            grid-template-columns: var(--ua-sidebar-width) minmax(0, 1fr);
            align-items: start;
        }

        .sidebar,
        .panel,
        .metric {
            border: 1px solid var(--ua-border);
            border-radius: var(--ua-radius);
            box-shadow: var(--ua-shadow);
            backdrop-filter: blur(12px);
        }

        .sidebar {
            position: sticky;
            top: 1.25rem;
            padding: 1.25rem;
            background: var(--ua-sidebar-background);
            color: var(--ua-sidebar-text);
            display: grid;
            gap: 1rem;
            max-height: calc(100vh - 2.5rem);
            overflow-y: auto;
            align-content: start;
            font-size: 0.94rem;
            font-family: Arial, "Helvetica Neue", sans-serif;
        }

        .panel {
            background: var(--ua-card-background);
            padding: 1.25rem;
        }

        .panel + .panel {
            margin-top: 1rem;
        }

        .main {
            min-width: 0;
        }

        .eyebrow,
        .status,
        .nav-link,
        .pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            border-radius: 999px;
            font-weight: 700;
        }

        .eyebrow,
        .status,
        .pill {
            padding: 0.4rem 0.8rem;
            background: var(--ua-pill-background);
            color: var(--ua-accent);
        }

        .nav {
            display: grid;
            gap: 1rem;
            align-content: start;
        }

        .nav-group {
            display: grid;
            gap: 0.35rem;
        }

        .sidebar::-webkit-scrollbar {
            width: 0.65rem;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.35);
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: padding-box;
        }

        .nav-heading,
        .sidebar-title {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--ua-sidebar-muted);
            font-weight: 800;
        }

        .nav-link {
            display: grid;
            grid-template-columns: 1.65rem minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.65rem;
            padding: 0.58rem 0.68rem;
            border: 1px solid transparent;
            background: transparent;
            color: var(--ua-sidebar-text);
            border-radius: 14px;
            transition: border-color 0.16s ease, background 0.16s ease;
        }

        .nav-link:hover {
            border-color: var(--ua-sidebar-border);
            background: rgba(255, 255, 255, 0.05);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.10);
            color: var(--ua-sidebar-text);
            border-color: rgba(255, 255, 255, 0.12);
            box-shadow: inset 3px 0 0 var(--ua-accent);
        }

        .nav-link-secondary {
            background: var(--ua-sidebar-surface);
            border-color: var(--ua-sidebar-border);
        }

        .nav-icon {
            width: 1.65rem;
            height: 1.65rem;
            display: inline-grid;
            place-items: center;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            color: var(--ua-sidebar-text);
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .nav-link.active .nav-icon {
            background: var(--ua-accent);
            color: var(--ua-accent-contrast);
        }

        .nav-copy {
            display: grid;
            gap: 0.08rem;
            min-width: 0;
        }

        .nav-label {
            font-size: 0.88rem;
            font-weight: 700;
            line-height: 1.18;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-meta {
            color: var(--ua-sidebar-muted);
            font-size: 0.75rem;
            line-height: 1.3;
            display: none;
        }

        .nav-link-secondary .nav-meta {
            display: block;
        }

        .nav-badge {
            padding: 0.2rem 0.48rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            color: var(--ua-sidebar-muted);
            font-size: 0.68rem;
            font-weight: 800;
        }

        h1 {
            margin: 0.35rem 0 0.35rem;
            font-size: clamp(1.7rem, 2.6vw, 2.2rem);
            line-height: 1.08;
        }

        h2,
        h3,
        h4 {
            margin: 0;
        }

        .subtitle,
        .section-copy,
        .muted,
        .list-meta {
            color: var(--ua-muted-text);
            line-height: 1.45;
        }

        .subtitle {
            margin: 0;
            font-size: 0.9rem;
            color: var(--ua-sidebar-muted);
        }

        .sidebar-block {
            display: grid;
            gap: 0.6rem;
            padding: 0.88rem;
            border-radius: 18px;
            background: var(--ua-sidebar-surface);
            border: 1px solid var(--ua-sidebar-border);
        }

        .sidebar-stack {
            display: grid;
            gap: 0.85rem;
            align-content: start;
        }

        .sidebar .muted {
            color: var(--ua-sidebar-muted);
        }

        .sidebar label {
            font-size: 0.84rem;
            font-weight: 700;
            gap: 0.35rem;
        }

        .workspace-card {
            display: grid;
            gap: 0.55rem;
            padding: 0.95rem;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.03) 100%);
            border: 1px solid var(--ua-sidebar-border);
        }

        .workspace-mini {
            color: var(--ua-sidebar-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 800;
        }

        .project-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.38rem 0.64rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            color: var(--ua-sidebar-text);
            font-size: 0.76rem;
            font-weight: 700;
        }

        .brand-lockup {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .brand-logo {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            object-fit: cover;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--ua-sidebar-border);
            padding: 0.2rem;
        }

        .logo-preview {
            display: inline-flex;
            align-items: center;
            gap: 0.9rem;
            padding: 0.8rem 0.95rem;
            border-radius: 18px;
            background: var(--ua-card-muted-background);
            border: 1px solid var(--ua-border);
        }

        .logo-preview img {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            object-fit: cover;
            background: #fff;
            border: 1px solid var(--ua-border);
        }

        .detail-list {
            display: grid;
            gap: 0.35rem;
        }

        .detail-row {
            display: grid;
            grid-template-columns: minmax(88px, auto) minmax(0, 1fr);
            gap: 0.65rem;
            align-items: start;
        }

        .detail-label {
            color: var(--ua-sidebar-muted);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 800;
        }

        .detail-value {
            color: var(--ua-sidebar-text);
            font-size: 0.85rem;
            line-height: 1.35;
            word-break: break-word;
        }

        .section-header {
            margin-bottom: 1rem;
        }

        .flash {
            margin-bottom: 1rem;
            padding: 1rem 1.15rem;
            border-radius: 20px;
            border: 1px solid var(--ua-border);
            box-shadow: var(--ua-shadow);
        }

        .flash.success {
            background: var(--ua-success-bg);
            color: var(--ua-success);
        }

        .flash.error {
            background: var(--ua-danger-bg);
            color: var(--ua-danger);
        }

        .metric-grid {
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            margin-bottom: 1rem;
        }

        .metric {
            background: var(--ua-card-background);
            padding: 1.15rem;
            overflow: hidden;
            border-radius: 999px;
            display: grid;
            justify-items: center;
            text-align: center;
        }

        .metric-label {
            color: var(--ua-muted-text);
            font-size: 0.82rem;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
            text-align: center;
        }

        .metric-value {
            font-size: 1.4rem;
            font-weight: 800;
            line-height: 1.15;
            text-align: center;
        }

        .content-grid {
            grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.65fr);
            align-items: start;
        }

        .split {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .form-grid.two {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .form-grid.three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        label {
            display: grid;
            gap: 0.45rem;
            font-size: 0.92rem;
            font-weight: 700;
        }

        input,
        select,
        textarea,
        button {
            width: 100%;
            border: 1px solid var(--ua-border);
            border-radius: var(--ua-input-radius);
            font: inherit;
        }

        input,
        select,
        textarea {
            padding: 0.82rem 0.95rem;
            background: rgba(255, 255, 255, 0.88);
            color: var(--ua-text);
        }

        textarea {
            min-height: 104px;
            resize: vertical;
        }

        button {
            cursor: pointer;
            padding: 0.88rem 1rem;
            background: var(--ua-accent);
            color: var(--ua-accent-contrast);
            font-weight: 800;
            transition: transform 0.16s ease, opacity 0.16s ease;
        }

        button:hover {
            transform: translateY(-1px);
            opacity: 0.96;
        }

        .ghost-button {
            background: transparent;
            color: var(--ua-accent);
        }

        .danger-button {
            background: #c03939;
            color: #fff8f8;
        }

        .controls {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            align-items: end;
        }

        .slots {
            grid-template-columns: repeat(auto-fill, minmax(138px, 1fr));
        }

        .slot-label {
            display: block;
            cursor: pointer;
        }

        .slot-label input {
            display: none;
        }

        .slot-pill {
            border: 1px solid var(--ua-border);
            border-radius: 999px;
            background: var(--ua-card-muted-background);
            text-align: center;
            padding: 0.8rem 0.9rem;
            font-weight: 700;
        }

        .slot-label input:checked + .slot-pill {
            background: var(--ua-accent);
            color: var(--ua-accent-contrast);
            border-color: var(--ua-accent);
        }

        .empty,
        .snapshot {
            padding: 1rem;
            border-radius: 18px;
            background: var(--ua-card-muted-background);
            line-height: 1.5;
        }

        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .quick-link {
            padding: 0.7rem 1rem;
            border-radius: 999px;
            border: 1px solid var(--ua-border);
            background: var(--ua-card-muted-background);
            font-weight: 700;
        }

        .appointment-stack {
            display: grid;
            gap: 1rem;
        }

        .appointment-card {
            display: grid;
            gap: 1rem;
            padding: 1rem;
            border-radius: 20px;
            background: var(--ua-card-muted-background);
            border: 1px solid var(--ua-border);
        }

        .appointment-card-header {
            display: flex;
            flex-wrap: wrap;
            align-items: start;
            justify-content: space-between;
            gap: 0.85rem;
        }

        .appointment-fields {
            display: grid;
            gap: 0.65rem;
        }

        .appointment-field {
            display: grid;
            gap: 0.2rem;
        }

        .appointment-label {
            color: var(--ua-muted-text);
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 800;
        }

        .appointment-value {
            color: var(--ua-text);
            line-height: 1.45;
            word-break: break-word;
        }

        .appointment-actions {
            display: grid;
            gap: 0.85rem;
        }

        .appointment-action-form {
            display: grid;
            gap: 0.65rem;
            padding-top: 0.85rem;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
        }

        .appointment-action-form label {
            gap: 0.35rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 0.8rem 0.75rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.07);
            vertical-align: top;
        }

        th {
            color: var(--ua-muted-text);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .list {
            gap: 0.75rem;
        }

        .list-item {
            padding: 0.9rem 1rem;
            border-radius: 18px;
            background: var(--ua-card-muted-background);
            border: 1px solid var(--ua-border);
        }

        .list-title {
            font-weight: 800;
            margin-bottom: 0.25rem;
        }

        .calendar-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .calendar-nav {
            display: flex;
            gap: 0.75rem;
        }

        .calendar-nav a {
            padding: 0.65rem 0.95rem;
            border-radius: 999px;
            border: 1px solid var(--ua-border);
            background: var(--ua-card-muted-background);
            font-weight: 700;
        }

        .calendar-head {
            grid-template-columns: repeat(7, minmax(0, 1fr));
            margin-bottom: 0.75rem;
        }

        .calendar-head div {
            text-align: center;
            color: var(--ua-muted-text);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
        }

        .calendar-grid {
            grid-template-columns: repeat(7, minmax(0, 1fr));
        }

        .calendar-cell {
            min-height: 110px;
            padding: 0.8rem;
            border-radius: 18px;
            border: 1px solid var(--ua-border);
            background: var(--ua-card-muted-background);
            display: grid;
            align-content: space-between;
            gap: 0.4rem;
        }

        .calendar-cell.outside {
            opacity: 0.58;
        }

        .calendar-cell.selected {
            border-color: var(--ua-accent);
            background: rgba(255, 255, 255, 0.82);
        }

        .calendar-day {
            font-weight: 800;
        }

        .calendar-count {
            justify-self: start;
            padding: 0.3rem 0.65rem;
            border-radius: 999px;
            background: var(--ua-pill-background);
            color: var(--ua-accent);
            font-size: 0.82rem;
            font-weight: 800;
        }

        .calendar-today {
            color: var(--ua-accent);
        }

        @media (max-width: 1240px) {
            .app-shell,
            .content-grid,
            .split,
            .metric-grid,
            .controls {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                max-height: none;
                overflow: visible;
            }
        }

        @media (max-width: 760px) {
            body {
                padding: 0.8rem;
            }

            .form-grid.two,
            .form-grid.three,
            .calendar-grid,
            .calendar-head {
                grid-template-columns: 1fr;
            }

            .calendar-cell {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="workspace-card">
            <div class="workspace-mini">Starter Workspace</div>
            <div class="project-chip">Starter Edition</div>
            <div class="brand-lockup">
                <?php if ($companyLogoUrl !== null && $companyLogoUrl !== ''): ?>
                    <img class="brand-logo" src="<?= $escape($companyLogoUrl) ?>" alt="<?= $escape($companyName) ?> logo">
                <?php endif; ?>
                <div>
                    <h1><?= $escape($companyName) ?></h1>
                    <p class="subtitle">Generic booking operations, team scheduling, and calendar sync in one SQLite starter workspace.</p>
                </div>
            </div>
        </div>

        <nav class="nav" aria-label="Startup menu">
            <?php foreach ($navGroups as $groupLabel => $groupPages): ?>
                <div class="nav-group">
                    <div class="nav-heading"><?= $escape($groupLabel) ?></div>
                    <?php foreach ($groupPages as $pageKey => $pageMeta): ?>
                        <a class="nav-link <?= $pageKey === $currentPage ? 'active' : '' ?>" href="<?= $escape($buildUrl(['page' => $pageKey])) ?>">
                            <span class="nav-icon"><?= $escape($pageMeta['icon'] ?? 'PG') ?></span>
                            <span class="nav-copy">
                                <span class="nav-label"><?= $escape($pageMeta['label']) ?></span>
                            </span>
                            <?php if ($pageKey === $currentPage): ?>
                                <span class="nav-badge">Open</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-stack">
            <div class="sidebar-block">
                <div class="sidebar-title">Selected Section</div>
                <div><strong><?= $escape($selectedPageMeta['label']) ?></strong></div>
                <div class="muted"><?= $escape($selectedPageMeta['description']) ?></div>
                <div class="detail-list">
                    <div class="detail-row">
                        <div class="detail-label">Service</div>
                        <div class="detail-value"><?= $escape($selectedServiceName) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Location</div>
                        <div class="detail-value"><?= $escape($selectedLocationName) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Owner</div>
                        <div class="detail-value"><?= $escape($selectedOwnerLabel) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Date</div>
                        <div class="detail-value"><?= $escape($selectedDate) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Timezone</div>
                        <div class="detail-value"><?= $escape($appTimezone) ?></div>
                    </div>
                </div>
            </div>

            <div class="sidebar-block">
                <div class="sidebar-title">Booking Context</div>
                <form method="get" class="list">
                    <input type="hidden" name="page" value="<?= $escape($currentPage) ?>">
                    <input type="hidden" name="month" value="<?= $escape($selectedMonth) ?>">
                    <input type="hidden" name="<?= $escape($themeQueryParameter) ?>" value="<?= $escape($themeKey) ?>">
                    <label>
                        Location
                        <select name="location">
                            <option value="">All locations</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= $escape($location['location_key']) ?>" <?= $selectedLocationKey === $location['location_key'] ? 'selected' : '' ?>>
                                    <?= $escape($location['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Team / owner
                        <select name="owner">
                            <?php foreach ($owners as $owner): ?>
                                <option value="<?= $escape($owner['token']) ?>" <?= $owner['token'] === $selectedOwnerToken ? 'selected' : '' ?>>
                                    <?= $escape($owner['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Service
                        <select name="service">
                            <?php if ($services === []): ?>
                                <option value="">No services in this context</option>
                            <?php else: ?>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?= $escape($service['id']) ?>" <?= (string) $service['id'] === (string) $selectedServiceId ? 'selected' : '' ?>>
                                        <?= $escape($service['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </label>
                    <label>
                        Date
                        <input type="date" name="date" value="<?= $escape($selectedDate) ?>">
                    </label>
                    <button type="submit">Update Context</button>
                </form>
            </div>

            <?php if ($selectedService !== null): ?>
                <div class="sidebar-block">
                    <div class="sidebar-title">Booking Policy</div>
                    <div class="detail-list">
                        <div class="detail-row">
                            <div class="detail-label">Duration</div>
                            <div class="detail-value"><?= $escape((string) $selectedService['duration_minutes']) ?> min</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Buffers</div>
                            <div class="detail-value"><?= $escape((string) $selectedService['buffer_before_minutes']) ?> before / <?= $escape((string) $selectedService['buffer_after_minutes']) ?> after</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Deposit</div>
                            <div class="detail-value"><?= $selectedService['deposit_amount'] === null ? 'Optional' : '$' . $escape(number_format((float) $selectedService['deposit_amount'], 2)) ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">No-show</div>
                            <div class="detail-value"><?= $selectedService['no_show_fee_amount'] === null ? 'Optional' : '$' . $escape(number_format((float) $selectedService['no_show_fee_amount'], 2)) ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="sidebar-block">
                <div class="sidebar-title">Display</div>
                <form method="get">
                    <input type="hidden" name="page" value="<?= $escape($currentPage) ?>">
                    <input type="hidden" name="location" value="<?= $selectedLocationKey === null ? '' : $escape($selectedLocationKey) ?>">
                    <input type="hidden" name="service" value="<?= $selectedServiceId === null ? '' : $escape($selectedServiceId) ?>">
                    <input type="hidden" name="owner" value="<?= $escape($selectedOwnerToken) ?>">
                    <input type="hidden" name="date" value="<?= $escape($selectedDate) ?>">
                    <input type="hidden" name="month" value="<?= $escape($selectedMonth) ?>">
                    <label>
                        Theme preset
                        <select name="<?= $escape($themeQueryParameter) ?>" onchange="this.form.submit()">
                            <?php foreach ($themeOptions as $option): ?>
                                <option value="<?= $escape($option['key']) ?>" <?= $option['key'] === $themeKey ? 'selected' : '' ?>>
                                    <?= $escape($option['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </form>
                <div class="muted"><?= $escape($theme['description'] ?? '') ?></div>
                <div class="muted">SQLite file: <code>database/unified-appointments.sqlite</code></div>
            </div>

            <div class="sidebar-block">
                <div class="sidebar-title">System Status</div>
                <div class="detail-list">
                    <div class="detail-row">
                        <div class="detail-label">Timezone</div>
                        <div class="detail-value"><?= $escape($appTimezone) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Google</div>
                        <div class="detail-value"><?= $googleConnection === null ? 'Not connected' : $escape($googleConnection['calendar_identifier']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Microsoft</div>
                        <div class="detail-value"><?= $microsoftConnection === null ? 'Not connected' : $escape($microsoftConnection['calendar_identifier']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?= $emailNotification !== null && (int) ($emailNotification['is_enabled'] ?? 0) === 1 ? 'Enabled' : 'Disabled' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <main class="main">
        <?php if ($flash !== null && $flash['message'] !== ''): ?>
            <div class="flash <?= $escape($flash['type']) ?>">
                <?= $escape($flash['message']) ?>
            </div>
        <?php endif; ?>

        <section class="metric-grid">
            <?php foreach ($metrics as $metric): ?>
                <div class="metric">
                    <div class="metric-label"><?= $escape($metric['label']) ?></div>
                    <div class="metric-value"><?= $escape($metric['value']) ?></div>
                </div>
            <?php endforeach; ?>
        </section>

        <?php if ($currentPage === 'dashboard'): ?>
            <section class="section-header">
                <h2>Overview</h2>
                <div class="section-copy">A quick operational view, patterned after booking dashboards that keep the day’s openings, client demand, and schedule health visible at a glance.</div>
            </section>
            <section class="content-grid">
                <div>
                    <article class="panel">
                        <h3>Today’s Openings</h3>
                        <?php if ($slotPreview === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No open slots are visible in the current context. Jump to Services to expand availability or to Waitlist to capture requests.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($slotPreview as $slot): ?>
                                    <div class="list-item">
                                        <div class="list-title"><?= $escape($slot->startsAt->setTimezone(new DateTimeZone($appTimezone))->format('g:i A')) ?></div>
                                        <div class="list-meta"><?= $escape($selectedServiceName) ?> · <?= $escape($selectedOwnerLabel) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>

                    <article class="panel">
                        <h3>Upcoming Appointments</h3>
                        <?php if ($upcomingPreview === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No upcoming appointments yet.</div>
                        <?php else: ?>
                            <div style="overflow:auto; margin-top: 1rem;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Service</th>
                                            <th>Start</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcomingPreview as $appointment): ?>
                                            <tr>
                                                <td><?= $escape($appointment['customer_name']) ?></td>
                                                <td><?= $escape($serviceMap[(string) $appointment['service_id']] ?? ('Service ' . $appointment['service_id'])) ?></td>
                                                <td><?= $escape($formatLocal($appointment['starts_at_utc'], $appTimezone)) ?></td>
                                                <td><span class="status"><?= $escape($appointment['status']) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Selected Day</h3>
                        <?php if ($selectedDayPreview === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No appointments are scheduled on <?= $escape($selectedDate) ?>.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($selectedDayPreview as $appointment): ?>
                                    <div class="list-item">
                                        <div class="list-title"><?= $escape($appointment['customer_name']) ?></div>
                                        <div class="list-meta"><?= $escape($formatLocal($appointment['starts_at_utc'], $appTimezone)) ?> · <?= $escape($appointment['status']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                    <article class="panel">
                        <h3>Quick Actions</h3>
                        <div class="quick-links" style="margin-top: 1rem;">
                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'calendar'])) ?>">Open Calendar</a>
                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'booking'])) ?>">Open New Appointment</a>
                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'services'])) ?>">Open Services</a>
                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'waitlist'])) ?>">View Waitlist</a>
                        </div>
                    </article>
                </div>
            </section>
        <?php elseif ($currentPage === 'calendar'): ?>
            <section class="section-header">
                <h2>Calendar</h2>
                <div class="section-copy">Inspired by calendar-first booking flows, this view shows appointment volume per day and opens the selected day’s appointment list when you click a date.</div>
            </section>
            <section class="content-grid">
                <div>
                    <article class="panel">
                        <div class="calendar-toolbar">
                            <div>
                                <h3><?= $escape($selectedMonthStart->format('F Y')) ?></h3>
                                <div class="muted">Selected day: <?= $escape($selectedDate) ?></div>
                            </div>
                            <div class="calendar-nav">
                                <a href="<?= $escape($buildUrl(['page' => 'calendar', 'month' => $previousMonth->format('Y-m'), 'date' => $previousMonth->format('Y-m-01')])) ?>">Previous</a>
                                <a href="<?= $escape($buildUrl(['page' => 'calendar', 'month' => (new DateTimeImmutable('today', new DateTimeZone($appTimezone)))->format('Y-m'), 'date' => (new DateTimeImmutable('today', new DateTimeZone($appTimezone)))->format('Y-m-d')])) ?>">Today</a>
                                <a href="<?= $escape($buildUrl(['page' => 'calendar', 'month' => $nextMonth->format('Y-m'), 'date' => $nextMonth->format('Y-m-01')])) ?>">Next</a>
                            </div>
                        </div>
                        <div class="calendar-head">
                            <?php foreach ($weekdayHeadings as $heading): ?>
                                <div><?= $escape($heading) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="calendar-grid">
                            <?php foreach ($calendarWeeks as $week): ?>
                                <?php foreach ($week as $day): ?>
                                    <a class="calendar-cell <?= !$day['in_month'] ? 'outside' : '' ?> <?= $day['is_selected'] ? 'selected' : '' ?>" href="<?= $escape($buildUrl(['page' => 'appointments', 'date' => $day['date'], 'month' => substr((string) $day['date'], 0, 7)])) ?>">
                                        <div class="calendar-day <?= $day['is_today'] ? 'calendar-today' : '' ?>"><?= $escape($day['day']) ?></div>
                                        <div class="calendar-count"><?= $escape((string) $day['count']) ?> booked</div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Day Agenda</h3>
                        <?php if ($selectedDayPreview === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No appointments are scheduled on <?= $escape($selectedDate) ?>.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($selectedDayPreview as $appointment): ?>
                                    <div class="list-item">
                                        <div class="list-title"><?= $escape($appointment['customer_name']) ?></div>
                                        <div class="list-meta"><?= $escape($formatLocal($appointment['starts_at_utc'], $appTimezone)) ?> · <?= $escape($serviceMap[(string) $appointment['service_id']] ?? ('Service ' . $appointment['service_id'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                    <article class="panel">
                        <h3>Quick Actions</h3>
                        <div class="quick-links" style="margin-top: 1rem;">
                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'booking'])) ?>">Book On This Day</a>
                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'appointments'])) ?>">Open Appointment List</a>
                        </div>
                    </article>
                </div>
            </section>
        <?php elseif ($currentPage === 'booking'): ?>
            <section class="section-header">
                <h2>New Appointment</h2>
                <div class="section-copy">Set the booking context, review today’s availability, and book a start time directly. The left pane keeps the active service, location, owner, and date visible while the workspace stays focused.</div>
            </section>
            <section class="content-grid">
                <div>
                    <article class="panel">
                        <h3>Find Availability</h3>
                        <form method="get" class="controls" style="margin-top: 1rem;">
                            <input type="hidden" name="page" value="booking">
                            <input type="hidden" name="month" value="<?= $escape($selectedMonth) ?>">
                            <input type="hidden" name="<?= $escape($themeQueryParameter) ?>" value="<?= $escape($themeKey) ?>">
                            <label>
                                Service
                                <select name="service">
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?= $escape($service['id']) ?>" <?= (string) $service['id'] === (string) $selectedServiceId ? 'selected' : '' ?>>
                                            <?= $escape($service['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                Owner
                                <select name="owner">
                                    <?php foreach ($owners as $owner): ?>
                                        <option value="<?= $escape($owner['token']) ?>" <?= $owner['token'] === $selectedOwnerToken ? 'selected' : '' ?>>
                                            <?= $escape($owner['label']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                Date
                                <input type="date" name="date" value="<?= $escape($selectedDate) ?>">
                            </label>
                            <label>
                                Reload
                                <button type="submit">Load Availability</button>
                            </label>
                        </form>

                        <?php if ($slotPreview === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No suggested openings are visible in the current context. You can still try a time below, or move to Services to expand availability.</div>
                        <?php else: ?>
                            <div class="snapshot" style="margin-top: 1rem;">
                                Suggested openings:
                                <?= $escape(implode(', ', array_map(
                                    static fn ($slot): string => $slot->startsAt->setTimezone(new DateTimeZone($appTimezone))->format('g:i A'),
                                    $slotPreview,
                                ))) ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="book_appointment">
                            <div class="form-grid two">
                                <label>
                                    Appointment time
                                    <input type="time" name="appointment_time" value="<?= $escape($defaultAppointmentTime) ?>" required>
                                </label>
                                <label>
                                    Booking date
                                    <input type="text" value="<?= $escape($selectedDate) ?>" readonly>
                                </label>
                            </div>
                            <div class="muted">Time is entered in <?= $escape($appTimezone) ?>.</div>
                            <div class="form-grid two">
                                <label>
                                    Customer name
                                    <input type="text" name="customer_name" placeholder="Jordan Blake" required>
                                </label>
                                <label>
                                    Email
                                    <input type="email" name="customer_email" placeholder="jordan@example.com">
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Phone
                                    <input type="text" name="customer_phone" placeholder="555-0100">
                                </label>
                                <label>
                                    Notes
                                    <input type="text" name="booking_notes" placeholder="Arrival notes or intake details">
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Reminder send time
                                    <input type="datetime-local" name="reminder_send_at">
                                </label>
                                <label>
                                    Reminder delivery
                                    <input type="text" value="Client e-mail reminder" readonly>
                                </label>
                            </div>
                            <div class="muted">Leave the reminder blank to skip it. Reminder time uses <?= $escape($appTimezone) ?> and requires the client e-mail plus a configured mail server in Services.</div>
                            <button type="submit">Book Appointment</button>
                        </form>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Selected Day Bookings</h3>
                        <?php if ($selectedDayPreview === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No appointments are scheduled for this date yet.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($selectedDayPreview as $appointment): ?>
                                    <div class="list-item">
                                        <div class="list-title"><?= $escape($appointment['customer_name']) ?></div>
                                        <div class="list-meta"><?= $escape($formatLocal($appointment['starts_at_utc'], $appTimezone)) ?> · <?= $escape($appointment['status']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                    <article class="panel">
                        <h3>Client Flow Ideas</h3>
                        <div class="snapshot" style="margin-top: 1rem;">
                            Common booking apps emphasize reminders, intake details, and no-show policies before the appointment. This starter now surfaces policy details in the left pane so those decisions stay visible during booking.
                        </div>
                    </article>
                </div>
            </section>
        <?php elseif ($currentPage === 'appointments'): ?>
            <section class="section-header">
                <h2>Appointment List</h2>
                <div class="section-copy">Review the appointments for the selected day, then reschedule or cancel them from one focused workspace.</div>
            </section>
            <section class="content-grid">
                <div>
                    <article class="panel">
                        <h3>Appointments for <?= $escape($selectedDate) ?></h3>
                        <?php if ($selectedDayAppointments === []): ?>
                        <div class="empty" style="margin-top: 1rem;">No appointments are scheduled for this day. Use New Appointment to create one or return to Calendar to choose another date.</div>
                        <?php else: ?>
                            <div class="appointment-stack" style="margin-top: 1rem;">
                                <?php foreach ($selectedDayAppointments as $appointment): ?>
                                    <article class="appointment-card">
                                        <div class="appointment-card-header">
                                            <div>
                                                <div class="list-title"><?= $escape($appointment['customer_name']) ?></div>
                                                <div class="list-meta"><?= $escape($formatLocal($appointment['starts_at_utc'], $appTimezone)) ?></div>
                                            </div>
                                            <span class="status"><?= $escape($appointment['status']) ?></span>
                                        </div>

                                        <div class="appointment-fields">
                                            <div class="appointment-field">
                                                <div class="appointment-label">Service</div>
                                                <div class="appointment-value"><?= $escape($serviceMap[(string) $appointment['service_id']] ?? ('Service ' . $appointment['service_id'])) ?></div>
                                            </div>
                                            <div class="appointment-field">
                                                <div class="appointment-label">Location</div>
                                                <div class="appointment-value"><?= $escape($locationMap[(string) ($appointment['location_id'] ?? '')] ?? 'No location') ?></div>
                                            </div>
                                            <div class="appointment-field">
                                                <div class="appointment-label">Team</div>
                                                <div class="appointment-value">
                                                    <?php if (($appointment['staff_id'] ?? null) !== null): ?>
                                                        <?= $escape($teamMap[(string) $appointment['staff_id']]['name'] ?? ('Staff: ' . $appointment['staff_id'])) ?>
                                                    <?php elseif (($appointment['resource_id'] ?? null) !== null): ?>
                                                        <?= $escape('Resource: ' . $appointment['resource_id']) ?>
                                                    <?php else: ?>
                                                        <?= $escape('No owner assigned') ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="appointment-field">
                                                <div class="appointment-label">Contact</div>
                                                <div class="appointment-value"><?= $escape($appointment['customer_email'] ?: $appointment['customer_phone'] ?: 'No contact provided') ?></div>
                                            </div>
                                            <div class="appointment-field">
                                                <div class="appointment-label">Reminder</div>
                                                <div class="appointment-value">
                                                    <?php if (($appointment['reminder_send_at_utc'] ?? null) !== null && $appointment['reminder_send_at_utc'] !== ''): ?>
                                                        <?= $escape(ucfirst((string) ($appointment['reminder_status'] ?? 'pending'))) ?>
                                                        on <?= $escape($formatLocal($appointment['reminder_send_at_utc'], $appTimezone)) ?>
                                                        <?php if (($appointment['reminder_sent_at_utc'] ?? null) !== null && $appointment['reminder_sent_at_utc'] !== ''): ?>
                                                            · sent <?= $escape($formatLocal($appointment['reminder_sent_at_utc'], $appTimezone)) ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?= $escape('No reminder scheduled') ?>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (($appointment['reminder_last_error'] ?? null) !== null && $appointment['reminder_last_error'] !== ''): ?>
                                                    <div class="muted" style="margin-top: 0.35rem; color: var(--ua-danger);"><?= $escape($appointment['reminder_last_error']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (($appointment['notes'] ?? '') !== '' && $appointment['notes'] !== null): ?>
                                                <div class="appointment-field">
                                                    <div class="appointment-label">Notes</div>
                                                    <div class="appointment-value"><?= $escape($appointment['notes']) ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="appointment-actions">
                                            <form method="post" class="appointment-action-form">
                                                <?= $contextFields ?>
                                                <input type="hidden" name="action" value="save_reminder_schedule">
                                                <input type="hidden" name="appointment_id" value="<?= $escape($appointment['id']) ?>">
                                                <label>
                                                    Send reminder at
                                                    <input type="datetime-local" name="reminder_send_at" value="<?= $escape($formatInput($appointment['reminder_send_at_utc'] ?? null, $appTimezone)) ?>">
                                                </label>
                                                <div class="muted">Reminder goes to <?= $escape($appointment['customer_email'] ?: 'the saved client e-mail once added') ?>.</div>
                                                <button type="submit" class="ghost-button">Save Reminder</button>
                                            </form>

                                            <form method="post" class="appointment-action-form">
                                                <?= $contextFields ?>
                                                <input type="hidden" name="action" value="reschedule_appointment">
                                                <input type="hidden" name="appointment_id" value="<?= $escape($appointment['id']) ?>">
                                                <label>
                                                    Reschedule to
                                                    <input type="datetime-local" name="new_start" value="<?= $escape($formatInput($appointment['starts_at_utc'], $appTimezone)) ?>">
                                                </label>
                                                <button type="submit" class="ghost-button">Save New Time</button>
                                            </form>

                                            <form method="post" class="appointment-action-form">
                                                <?= $contextFields ?>
                                                <input type="hidden" name="action" value="cancel_appointment">
                                                <input type="hidden" name="appointment_id" value="<?= $escape($appointment['id']) ?>">
                                                <label>
                                                    Cancellation reason
                                                    <input type="text" name="cancellation_reason" placeholder="Optional reason">
                                                </label>
                                                <button type="submit" class="danger-button">Cancel Appointment</button>
                                            </form>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Day Snapshot</h3>
                        <?php if ($selectedDayAppointments === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No appointments are scheduled on <?= $escape($selectedDate) ?>.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($selectedDayAppointments as $appointment): ?>
                                    <div class="list-item">
                                        <div class="list-title"><?= $escape($appointment['customer_name']) ?></div>
                                        <div class="list-meta"><?= $escape($formatLocal($appointment['starts_at_utc'], $appTimezone)) ?> · <?= $escape($appointment['status']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                    <article class="panel">
                        <h3>Quick Actions</h3>
                        <div class="quick-links" style="margin-top: 1rem;">
                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'calendar'])) ?>">Back To Calendar</a>
                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'booking'])) ?>">Book On This Day</a>
                        </div>
                    </article>
                </div>
            </section>
        <?php elseif ($currentPage === 'services'): ?>
            <section class="section-header">
                <h2>Services</h2>
                <div class="section-copy">Manage branding, system timezone, mail delivery, services, recurring availability, and blackout dates without leaving the starter workspace.</div>
            </section>
            <section class="split">
                <div>
                    <article class="panel">
                        <h3>Brand & System</h3>
                        <form method="post" enctype="multipart/form-data" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="save_system_config">
                            <?php if ($companyLogoUrl !== null && $companyLogoUrl !== ''): ?>
                                <div class="logo-preview">
                                    <img src="<?= $escape($companyLogoUrl) ?>" alt="<?= $escape($companyName) ?> logo preview">
                                    <div>
                                        <strong>Current logo</strong><br>
                                        <span class="muted"><?= $escape($companyLogoUrl) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <label>
                                Company name
                                <input type="text" name="company_name" value="<?= $escape($companyName) ?>" placeholder="Your Company" required>
                            </label>
                            <label>
                                Logo URL or relative path
                                <input type="text" name="company_logo_url" value="<?= $escape($companyLogoUrl ?? '') ?>" placeholder="https://example.com/logo.png">
                            </label>
                            <label>
                                Upload logo
                                <input type="file" name="company_logo_file" accept=".png,.jpg,.jpeg,.gif,.webp,.svg,image/*">
                            </label>
                            <label>
                                System timezone
                                <select name="app_timezone">
                                    <?php foreach ($timezoneGroups as $region => $timezoneGroup): ?>
                                        <optgroup label="<?= $escape($region) ?>">
                                            <?php foreach ($timezoneGroup as $timezoneOption): ?>
                                                <option value="<?= $escape($timezoneOption['timezone_key']) ?>" <?= $timezoneOption['timezone_key'] === $appTimezone ? 'selected' : '' ?>>
                                                    <?= $escape($timezoneOption['label']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button type="submit">Save Brand & System</button>
                        </form>
                    </article>

                    <article class="panel">
                        <h3>E-mail Server</h3>
                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="save_email_server">
                            <div class="form-grid two">
                                <label>
                                    SMTP host
                                    <input type="text" name="mail_host" value="<?= $escape($mailServerHost) ?>" placeholder="smtp.example.com">
                                </label>
                                <label>
                                    Port
                                    <input type="number" name="mail_port" value="<?= $escape($mailServerPort) ?>" min="1" max="65535" placeholder="587">
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Encryption
                                    <select name="mail_encryption">
                                        <?php foreach (['none' => 'None', 'tls' => 'TLS', 'ssl' => 'SSL'] as $encryptionKey => $encryptionLabel): ?>
                                            <option value="<?= $escape($encryptionKey) ?>" <?= $mailServerEncryption === $encryptionKey ? 'selected' : '' ?>><?= $escape($encryptionLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>
                                    Username
                                    <input type="text" name="mail_username" value="<?= $escape($mailServerUsername) ?>" placeholder="mailer@example.com">
                                </label>
                            </div>
                            <label>
                                Password
                                <input type="password" name="mail_password" value="" placeholder="<?= $mailServerUsername !== '' ? 'Leave blank to keep the current password' : 'SMTP password' ?>">
                            </label>
                            <div class="form-grid two">
                                <label>
                                    From address
                                    <input type="email" name="mail_from_address" value="<?= $escape($mailServerFromAddress) ?>" placeholder="bookings@example.com">
                                </label>
                                <label>
                                    From name
                                    <input type="text" name="mail_from_name" value="<?= $escape($mailServerFromName) ?>" placeholder="<?= $escape($companyName) ?>">
                                </label>
                            </div>
                            <label>
                                Reply-to address
                                <input type="email" name="mail_reply_to" value="<?= $escape($mailServerReplyTo) ?>" placeholder="support@example.com">
                            </label>
                            <div class="muted">Scheduled reminders use this SMTP server. Leave the password blank when you want to preserve the current saved password.</div>
                            <button type="submit">Save E-mail Server</button>
                        </form>
                    </article>

                    <article class="panel">
                        <h3>Add Service</h3>
                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="create_service">
                            <label>
                                Service name
                                <input type="text" name="service_name" placeholder="Standard Session" required>
                            </label>
                            <div class="form-grid two">
                                <label>
                                    Location
                                    <select name="service_location_key">
                                        <option value="">No location yet</option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?= $escape($location['location_key']) ?>" <?= ($selectedLocationKey !== null ? $selectedLocationKey === $location['location_key'] : ($selectedService !== null && $selectedService['location_id'] === $location['location_key'])) ? 'selected' : '' ?>>
                                                <?= $escape($location['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>
                                    Service timezone
                                    <select name="service_timezone">
                                        <?php foreach ($timezoneGroups as $region => $timezoneGroup): ?>
                                            <optgroup label="<?= $escape($region) ?>">
                                                <?php foreach ($timezoneGroup as $timezoneOption): ?>
                                                    <option value="<?= $escape($timezoneOption['timezone_key']) ?>" <?= $timezoneOption['timezone_key'] === $appTimezone ? 'selected' : '' ?>>
                                                        <?= $escape($timezoneOption['label']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Duration (minutes)
                                    <input type="number" min="1" name="duration_minutes" value="60">
                                </label>
                                <label>
                                    Slot interval
                                    <input type="number" min="1" name="slot_interval_minutes" value="30">
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Buffer before
                                    <input type="number" min="0" name="buffer_before_minutes" value="15">
                                </label>
                                <label>
                                    Buffer after
                                    <input type="number" min="0" name="buffer_after_minutes" value="15">
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Lead time
                                    <input type="number" min="0" name="lead_time_minutes" value="0">
                                </label>
                                <label>
                                    Max advance days
                                    <input type="number" min="1" name="max_advance_days" value="120">
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Deposit amount
                                    <input type="number" min="0" step="0.01" name="deposit_amount" placeholder="25.00">
                                </label>
                                <label>
                                    No-show fee
                                    <input type="number" min="0" step="0.01" name="no_show_fee_amount" placeholder="35.00">
                                </label>
                            </div>
                            <button type="submit">Save Service</button>
                        </form>
                    </article>

                    <article class="panel">
                        <h3>Weekly Availability</h3>
                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="add_rule">
                            <div class="form-grid two">
                                <label>
                                    Team member
                                    <select name="rule_owner_id" <?= $teamMembers === [] ? 'disabled' : '' ?>>
                                        <?php if ($teamMembers === []): ?>
                                            <option value="">Add a team member for this location first</option>
                                        <?php else: ?>
                                            <?php foreach ($teamMembers as $member): ?>
                                                <option value="<?= $escape($member['member_key']) ?>" <?= $selectedOwnerKey === $member['member_key'] ? 'selected' : '' ?>>
                                                    <?= $escape($member['name']) ?> · <?= $escape($member['role']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </label>
                                <label>
                                    Location
                                    <select name="rule_location_key">
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?= $escape($location['location_key']) ?>" <?= $selectedLocationKey === $location['location_key'] ? 'selected' : '' ?>>
                                                <?= $escape($location['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="form-grid three">
                                <label>
                                    Weekday
                                    <select name="rule_weekday">
                                        <?php foreach ($weekdayLabels as $weekday => $label): ?>
                                            <option value="<?= $escape($weekday) ?>"><?= $escape($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>
                                    Start
                                    <input type="time" name="rule_start_time" value="09:00">
                                </label>
                                <label>
                                    End
                                    <input type="time" name="rule_end_time" value="17:00">
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Slot interval
                                    <input type="number" min="1" name="rule_slot_interval_minutes" value="30">
                                </label>
                                <label>
                                    Rule timezone
                                    <select name="rule_timezone">
                                        <?php foreach ($timezoneGroups as $region => $timezoneGroup): ?>
                                            <optgroup label="<?= $escape($region) ?>">
                                                <?php foreach ($timezoneGroup as $timezoneOption): ?>
                                                    <option value="<?= $escape($timezoneOption['timezone_key']) ?>" <?= $timezoneOption['timezone_key'] === $appTimezone ? 'selected' : '' ?>>
                                                        <?= $escape($timezoneOption['label']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <button type="submit" <?= $teamMembers === [] ? 'disabled' : '' ?>>Add Availability Rule</button>
                        </form>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Blackout Dates</h3>
                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="add_exception">
                            <div class="form-grid two">
                                <label>
                                    Team member
                                    <select name="exception_owner_id" <?= $teamMembers === [] ? 'disabled' : '' ?>>
                                        <?php if ($teamMembers === []): ?>
                                            <option value="">Add a team member for this location first</option>
                                        <?php else: ?>
                                            <?php foreach ($teamMembers as $member): ?>
                                                <option value="<?= $escape($member['member_key']) ?>" <?= $selectedOwnerKey === $member['member_key'] ? 'selected' : '' ?>>
                                                    <?= $escape($member['name']) ?> · <?= $escape($member['role']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </label>
                                <label>
                                    Location
                                    <select name="exception_location_key">
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?= $escape($location['location_key']) ?>" <?= $selectedLocationKey === $location['location_key'] ? 'selected' : '' ?>>
                                                <?= $escape($location['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Starts
                                    <input type="datetime-local" name="exception_starts_at" value="<?= $escape($selectedDate) ?>T12:00">
                                </label>
                                <label>
                                    Ends
                                    <input type="datetime-local" name="exception_ends_at" value="<?= $escape($selectedDate) ?>T13:00">
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Exception timezone
                                    <select name="exception_timezone">
                                        <?php foreach ($timezoneGroups as $region => $timezoneGroup): ?>
                                            <optgroup label="<?= $escape($region) ?>">
                                                <?php foreach ($timezoneGroup as $timezoneOption): ?>
                                                    <option value="<?= $escape($timezoneOption['timezone_key']) ?>" <?= $timezoneOption['timezone_key'] === $appTimezone ? 'selected' : '' ?>>
                                                        <?= $escape($timezoneOption['label']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>
                                    Notes
                                    <input type="text" name="exception_notes" placeholder="Lunch break, holiday, room maintenance">
                                </label>
                            </div>
                            <button type="submit" <?= $teamMembers === [] ? 'disabled' : '' ?>>Add Blackout</button>
                        </form>
                    </article>

                    <article class="panel">
                        <h3>Service Catalog</h3>
                        <?php if ($services === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No services have been created yet.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($services as $service): ?>
                                    <div class="list-item">
                                        <div class="list-title"><?= $escape($service['name']) ?></div>
                                        <div class="list-meta">
                                            <?= $escape((string) $service['duration_minutes']) ?> min ·
                                            <?= $escape($locationMap[(string) ($service['location_id'] ?? '')] ?? 'No location') ?> ·
                                            <?= $escape($service['timezone']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>

                    <article class="panel">
                        <h3>Current Services Snapshot</h3>
                        <div class="snapshot" style="margin-top: 1rem;">
                            <?= $escape((string) count($services)) ?> services ·
                            <?= $escape((string) count($availabilityRules)) ?> recurring rules ·
                            <?= $escape((string) count($availabilityExceptions)) ?> blackout windows
                        </div>
                    </article>
                </div>
            </section>
        <?php elseif ($currentPage === 'locations'): ?>
            <section class="section-header">
                <h2>Locations</h2>
                <div class="section-copy">Add physical or virtual booking destinations and capture the timezone each location should use for scheduling.</div>
            </section>
            <section class="split">
                <div>
                    <article class="panel">
                        <h3><?= $isEditingLocation ? 'Edit Location' : 'Add Location' ?></h3>
                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="<?= $isEditingLocation ? 'update_location' : 'create_location' ?>">
                            <?php if ($isEditingLocation): ?>
                                <input type="hidden" name="existing_location_key" value="<?= $escape($editingLocation['location_key']) ?>">
                            <?php endif; ?>
                            <div class="form-grid two">
                                <label>
                                    Location key
                                    <input type="text" name="location_key" value="<?= $escape($editingLocation['location_key'] ?? '') ?>" placeholder="north-hub" <?= $isEditingLocation ? 'readonly' : '' ?> required>
                                </label>
                                <label>
                                    Location name
                                    <input type="text" name="location_name" value="<?= $escape($editingLocation['name'] ?? '') ?>" placeholder="North Hub" required>
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Contact name
                                    <input type="text" name="location_contact_name" value="<?= $escape($editingLocation['contact_name'] ?? '') ?>" placeholder="Booking Desk">
                                </label>
                                <label>
                                    Email
                                    <input type="email" name="location_email" value="<?= $escape($editingLocation['email'] ?? '') ?>" placeholder="desk@example.com">
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Phone
                                    <input type="text" name="location_phone" value="<?= $escape($editingLocation['phone'] ?? '') ?>" placeholder="555-0123">
                                </label>
                                <label>
                                    Timezone
                                    <select name="location_timezone">
                                        <?php foreach ($timezoneGroups as $region => $timezoneGroup): ?>
                                            <optgroup label="<?= $escape($region) ?>">
                                                <?php foreach ($timezoneGroup as $timezoneOption): ?>
                                                    <option value="<?= $escape($timezoneOption['timezone_key']) ?>" <?= $timezoneOption['timezone_key'] === ($editingLocation['timezone'] ?? $appTimezone) ? 'selected' : '' ?>>
                                                        <?= $escape($timezoneOption['label']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <label>
                                Address line 1
                                <input type="text" name="location_address_line_1" value="<?= $escape($editingLocation['address_line_1'] ?? '') ?>" placeholder="100 Atlantic Avenue">
                            </label>
                            <label>
                                Address line 2
                                <input type="text" name="location_address_line_2" value="<?= $escape($editingLocation['address_line_2'] ?? '') ?>" placeholder="Suite 400">
                            </label>
                            <div class="form-grid three">
                                <label>
                                    City
                                    <input type="text" name="location_city" value="<?= $escape($editingLocation['city'] ?? '') ?>" placeholder="Halifax">
                                </label>
                                <label>
                                    State / Province
                                    <input type="text" name="location_state_province" value="<?= $escape($editingLocation['state_province'] ?? '') ?>" placeholder="NS">
                                </label>
                                <label>
                                    Postal code
                                    <input type="text" name="location_postal_code" value="<?= $escape($editingLocation['postal_code'] ?? '') ?>" placeholder="B3H 1A1">
                                </label>
                            </div>
                            <label>
                                Country
                                <input type="text" name="location_country" value="<?= $escape($editingLocation['country'] ?? '') ?>" placeholder="Canada">
                            </label>
                            <div class="quick-links">
                                <button type="submit"><?= $isEditingLocation ? 'Update Location' : 'Save Location' ?></button>
                                <?php if ($isEditingLocation): ?>
                                    <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'locations', 'edit_location' => null])) ?>">Cancel Edit</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Location Directory</h3>
                        <?php if ($locations === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No locations are configured yet.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($locations as $location): ?>
                                    <div class="list-item">
                                        <div class="list-title"><?= $escape($location['name']) ?></div>
                                        <div class="list-meta"><?= $escape($location['location_key']) ?> · <?= $escape($location['timezone']) ?></div>
                                        <div class="list-meta">
                                            <?= $escape(trim(implode(', ', array_filter([
                                                $location['address_line_1'] ?? '',
                                                $location['city'] ?? '',
                                                $location['state_province'] ?? '',
                                                $location['country'] ?? '',
                                            ])))) ?: 'Address pending' ?>
                                        </div>
                                        <div class="quick-links" style="margin-top: 0.85rem;">
                                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'locations', 'edit_location' => $location['location_key']])) ?>">Edit Location</a>
                                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'locations', 'location' => $location['location_key'], 'edit_location' => null])) ?>">Use In Context</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>

                    <article class="panel">
                        <h3>Coverage Snapshot</h3>
                        <div class="snapshot" style="margin-top: 1rem;">
                            <?= $escape((string) count($locations)) ?> locations ·
                            <?= $escape((string) count(array_filter($services, static fn (array $service): bool => ($service['location_id'] ?? null) !== null))) ?> services assigned ·
                            <?= $escape((string) count(array_filter($teamMembers, static fn (array $member): bool => ($member['location_key'] ?? null) !== null))) ?> team members assigned
                        </div>
                    </article>
                </div>
            </section>
        <?php elseif ($currentPage === 'team'): ?>
            <section class="section-header">
                <h2>Team</h2>
                <div class="section-copy">Track who owns availability, who receives bookings, and where each person belongs.</div>
            </section>
            <section class="split">
                <div>
                    <article class="panel">
                        <h3><?= $isEditingTeamMember ? 'Edit Team Member' : 'Add Team Member' ?></h3>
                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="<?= $isEditingTeamMember ? 'update_team_member' : 'create_team_member' ?>">
                            <?php if ($isEditingTeamMember): ?>
                                <input type="hidden" name="existing_member_key" value="<?= $escape($editingTeamMember['member_key']) ?>">
                            <?php endif; ?>
                            <div class="form-grid two">
                                <label>
                                    Team key
                                    <input type="text" name="member_key" value="<?= $escape($editingTeamMember['member_key'] ?? '') ?>" placeholder="main-team" <?= $isEditingTeamMember ? 'readonly' : '' ?> required>
                                </label>
                                <label>
                                    Full name
                                    <input type="text" name="member_name" value="<?= $escape($editingTeamMember['name'] ?? '') ?>" placeholder="Avery Morgan" required>
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Role
                                    <input type="text" name="member_role" value="<?= $escape($editingTeamMember['role'] ?? '') ?>" placeholder="Booking Manager" required>
                                </label>
                                <label>
                                    Owner type
                                    <select name="member_owner_type">
                                        <option value="staff" <?= ($editingTeamMember['owner_type'] ?? 'staff') === 'staff' ? 'selected' : '' ?>>Staff</option>
                                        <option value="resource" <?= ($editingTeamMember['owner_type'] ?? 'staff') === 'resource' ? 'selected' : '' ?>>Resource</option>
                                    </select>
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Location
                                    <select name="member_location_key">
                                        <option value="" <?= $isEditingTeamMember ? (($editingTeamMember['location_key'] ?? null) === null ? 'selected' : '') : ($selectedLocationKey === null ? 'selected' : '') ?>>No location yet</option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?= $escape($location['location_key']) ?>" <?= $isEditingTeamMember ? (($editingTeamMember['location_key'] ?? '') === $location['location_key'] ? 'selected' : '') : ($selectedLocationKey === $location['location_key'] ? 'selected' : '') ?>><?= $escape($location['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>
                                    Timezone
                                    <select name="member_timezone">
                                        <?php foreach ($timezoneGroups as $region => $timezoneGroup): ?>
                                            <optgroup label="<?= $escape($region) ?>">
                                                <?php foreach ($timezoneGroup as $timezoneOption): ?>
                                                    <option value="<?= $escape($timezoneOption['timezone_key']) ?>" <?= $timezoneOption['timezone_key'] === ($editingTeamMember['timezone'] ?? $appTimezone) ? 'selected' : '' ?>>
                                                        <?= $escape($timezoneOption['label']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Email
                                    <input type="email" name="member_email" value="<?= $escape($editingTeamMember['email'] ?? '') ?>" placeholder="team@example.com">
                                </label>
                                <label>
                                    Phone
                                    <input type="text" name="member_phone" value="<?= $escape($editingTeamMember['phone'] ?? '') ?>" placeholder="555-0123">
                                </label>
                            </div>
                            <label>
                                <input type="checkbox" name="member_is_active" value="1" <?= (int) ($editingTeamMember['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                                Mark as active for booking
                            </label>
                            <div class="quick-links">
                                <button type="submit"><?= $isEditingTeamMember ? 'Update Team Member' : 'Save Team Member' ?></button>
                                <?php if ($isEditingTeamMember): ?>
                                    <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'team', 'edit_team_member' => null])) ?>">Cancel Edit</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Team Directory</h3>
                        <?php if ($teamMembers === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No team members are configured yet.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($teamMembers as $member): ?>
                                    <div class="list-item">
                                        <div class="list-title"><?= $escape($member['name']) ?></div>
                                        <div class="list-meta">
                                            <?= $escape($member['member_key']) ?> · <?= $escape($member['role']) ?> · <?= $escape(ucfirst($member['owner_type'])) ?>
                                        </div>
                                        <div class="list-meta">
                                            <?= $escape($locationMap[(string) ($member['location_key'] ?? '')] ?? 'Unassigned location') ?> · <?= $escape($member['timezone']) ?> · <?= (int) ($member['is_active'] ?? 0) === 1 ? 'Active' : 'Inactive' ?>
                                        </div>
                                        <div class="quick-links" style="margin-top: 0.85rem;">
                                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'team', 'edit_team_member' => $member['member_key']])) ?>">Edit Team Member</a>
                                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'team', 'owner' => $member['owner_type'] . ':' . $member['member_key'], 'edit_team_member' => null])) ?>">Use As Owner</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>

                    <article class="panel">
                        <h3>Availability Owners</h3>
                        <?php if ($owners === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No availability owners are available yet.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($owners as $ownerOption): ?>
                                    <div class="list-item">
                                        <div class="list-title"><?= $escape($ownerOption['label']) ?></div>
                                        <div class="list-meta"><?= $escape($ownerOption['token']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                </div>
            </section>
        <?php elseif ($currentPage === 'notifications'): ?>
            <section class="section-header">
                <h2>Notifications</h2>
                <div class="section-copy">Set default reminder and digest preferences, then review reminder delivery results from the starter app.</div>
            </section>
            <section class="content-grid">
                <div>
                    <?php foreach ([
                        'email' => $emailNotification,
                        'daily_digest' => $digestNotification,
                    ] as $channel => $setting): ?>
                        <article class="panel">
                            <h3><?= $escape(ucwords(str_replace('_', ' ', $channel))) ?></h3>
                            <form method="post" class="list" style="margin-top: 1rem;">
                                <?= $contextFields ?>
                                <input type="hidden" name="action" value="save_notification_setting">
                                <input type="hidden" name="notification_channel" value="<?= $escape($channel) ?>">
                                <label>
                                    Recipient
                                    <input type="text" name="notification_recipient" value="<?= $escape($setting['recipient'] ?? '') ?>" placeholder="ops@example.com">
                                </label>
                                <div class="form-grid two">
                                    <label>
                                        Sender name
                                        <input type="text" name="notification_sender_name" value="<?= $escape($setting['sender_name'] ?? $companyName) ?>">
                                    </label>
                                    <label>
                                        Trigger summary
                                        <input type="text" name="notification_trigger_summary" value="<?= $escape($setting['trigger_summary'] ?? '') ?>" placeholder="What should trigger this?">
                                    </label>
                                </div>
                                <label>
                                    Delivery notes
                                    <textarea name="notification_config_payload" placeholder="Delivery notes, vendor notes, or setup reminders"><?= $escape($setting['config_payload'] ?? '') ?></textarea>
                                </label>
                                <label>
                                    <input type="checkbox" name="notification_is_enabled" value="1" <?= $setting !== null && (int) ($setting['is_enabled'] ?? 0) === 1 ? 'checked' : '' ?>>
                                    Enable <?= $escape(ucwords(str_replace('_', ' ', $channel))) ?>
                                </label>
                                <button type="submit">Save <?= $escape(ucwords(str_replace('_', ' ', $channel))) ?></button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div>
                    <article class="panel">
                        <h3>Notification Snapshot</h3>
                        <div class="list" style="margin-top: 1rem;">
                            <?php foreach ($notificationSettings as $setting): ?>
                                <div class="list-item">
                                    <div class="list-title"><?= $escape(ucwords(str_replace('_', ' ', $setting['channel']))) ?></div>
                                    <div class="list-meta">
                                        <?= (int) ($setting['is_enabled'] ?? 0) === 1 ? 'Enabled' : 'Disabled' ?> ·
                                        <?= $escape($setting['recipient'] ?: 'No recipient yet') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>

                    <article class="panel">
                        <h3>Reminder Delivery</h3>
                        <div class="detail-list" style="margin-top: 1rem;">
                            <div class="detail-row">
                                <div class="detail-label">Mail Server</div>
                                <div class="detail-value"><?= $escape($mailServerHost !== '' ? $mailServerHost . ':' . $mailServerPort : 'Not configured yet') ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">From</div>
                                <div class="detail-value"><?= $escape($mailServerFromAddress !== '' ? $mailServerFromName . ' <' . $mailServerFromAddress . '>' : 'Not configured') ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Client Reminders</div>
                                <div class="detail-value"><?= $emailNotification !== null && (int) ($emailNotification['is_enabled'] ?? 0) === 1 ? 'Enabled' : 'Disabled' ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Setup</div>
                                <div class="detail-value">SMTP credentials are managed in Services.</div>
                            </div>
                        </div>
                    </article>

                    <article class="panel">
                        <h3>Recent Reminder E-mails</h3>
                        <?php if ($emailDispatches === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No reminder e-mails have been recorded yet.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($emailDispatches as $dispatch): ?>
                                    <div class="list-item">
                                        <div class="list-title">
                                            <?= $escape(ucfirst((string) ($dispatch['event_key'] ?? 'update'))) ?>
                                            <span class="status"><?= $escape((string) ($dispatch['status'] ?? 'unknown')) ?></span>
                                        </div>
                                        <div class="list-meta"><?= $escape((string) ($dispatch['recipient'] ?? 'No recipient')) ?> · <?= $escape($formatLocal($dispatch['created_at_utc'] ?? null, $appTimezone)) ?></div>
                                        <div class="muted" style="margin-top: 0.35rem;"><?= $escape((string) ($dispatch['subject_line'] ?? 'No subject')) ?></div>
                                        <div class="muted" style="margin-top: 0.35rem;"><?= $escape((string) ($dispatch['message_body'] ?? '')) ?></div>
                                        <?php if (!empty($dispatch['error_message'])): ?>
                                            <div class="muted" style="margin-top: 0.35rem; color: var(--ua-danger);"><?= $escape((string) $dispatch['error_message']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                </div>
            </section>
        <?php elseif ($currentPage === 'google'): ?>
            <section class="section-header">
                <h2>Google</h2>
                <div class="section-copy">Store the starter app’s Google Calendar sync metadata here. Pro can later replace this with the full install workflow.</div>
            </section>
            <section class="content-grid">
                <div>
                    <article class="panel">
                        <h3>Google Calendar Connection</h3>
                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="save_calendar_connection">
                            <input type="hidden" name="provider" value="google">
                            <label>
                                Calendar identifier
                                <input type="text" name="calendar_identifier" value="<?= $escape($googleConnection['calendar_identifier'] ?? '') ?>" placeholder="team@group.calendar.google.com" required>
                            </label>
                            <div class="form-grid two">
                                <label>
                                    Connection status
                                    <input type="text" name="connection_status" value="<?= $escape($googleConnection['access_token_encrypted'] ?? '') ?>" placeholder="Connected, pending OAuth, or API ready">
                                </label>
                                <label>
                                    Token / lease expiry
                                    <input type="datetime-local" name="expires_at" value="<?= $escape($formatInput($googleConnection['expires_at_utc'] ?? null, $appTimezone)) ?>">
                                </label>
                            </div>
                            <label>
                                Sync notes
                                <textarea name="sync_notes" placeholder="Scopes, webhook notes, or next setup step"><?= $escape($googleConnection['refresh_token_encrypted'] ?? '') ?></textarea>
                            </label>
                            <button type="submit">Save Google Settings</button>
                        </form>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Current Google Snapshot</h3>
                        <?php if ($googleConnection === null): ?>
                            <div class="empty" style="margin-top: 1rem;">Google is not connected yet.</div>
                        <?php else: ?>
                            <div class="detail-list" style="margin-top: 1rem;">
                                <div class="detail-row">
                                    <div class="detail-label">Calendar</div>
                                    <div class="detail-value"><?= $escape($googleConnection['calendar_identifier']) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Status</div>
                                    <div class="detail-value"><?= $escape($googleConnection['access_token_encrypted'] ?: 'No status recorded') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Expires</div>
                                    <div class="detail-value"><?= $escape($formatLocal($googleConnection['expires_at_utc'] ?? null, $appTimezone)) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </article>
                </div>
            </section>
        <?php elseif ($currentPage === 'microsoft'): ?>
            <section class="section-header">
                <h2>Microsoft</h2>
                <div class="section-copy">Store the starter app’s Microsoft 365 sync metadata here and keep the setup separate from the core booking workflow.</div>
            </section>
            <section class="content-grid">
                <div>
                    <article class="panel">
                        <h3>Microsoft Calendar Connection</h3>
                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="save_calendar_connection">
                            <input type="hidden" name="provider" value="microsoft">
                            <label>
                                Calendar identifier
                                <input type="text" name="calendar_identifier" value="<?= $escape($microsoftConnection['calendar_identifier'] ?? '') ?>" placeholder="calendar@contoso.com" required>
                            </label>
                            <div class="form-grid two">
                                <label>
                                    Connection status
                                    <input type="text" name="connection_status" value="<?= $escape($microsoftConnection['access_token_encrypted'] ?? '') ?>" placeholder="Connected, pending OAuth, or API ready">
                                </label>
                                <label>
                                    Token / lease expiry
                                    <input type="datetime-local" name="expires_at" value="<?= $escape($formatInput($microsoftConnection['expires_at_utc'] ?? null, $appTimezone)) ?>">
                                </label>
                            </div>
                            <label>
                                Sync notes
                                <textarea name="sync_notes" placeholder="Tenant notes, webhook notes, or next setup step"><?= $escape($microsoftConnection['refresh_token_encrypted'] ?? '') ?></textarea>
                            </label>
                            <button type="submit">Save Microsoft Settings</button>
                        </form>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Current Microsoft Snapshot</h3>
                        <?php if ($microsoftConnection === null): ?>
                            <div class="empty" style="margin-top: 1rem;">Microsoft is not connected yet.</div>
                        <?php else: ?>
                            <div class="detail-list" style="margin-top: 1rem;">
                                <div class="detail-row">
                                    <div class="detail-label">Calendar</div>
                                    <div class="detail-value"><?= $escape($microsoftConnection['calendar_identifier']) ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Status</div>
                                    <div class="detail-value"><?= $escape($microsoftConnection['access_token_encrypted'] ?: 'No status recorded') ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Expires</div>
                                    <div class="detail-value"><?= $escape($formatLocal($microsoftConnection['expires_at_utc'] ?? null, $appTimezone)) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </article>
                </div>
            </section>
        <?php elseif ($currentPage === 'waitlist'): ?>
            <section class="section-header">
                <h2>Waitlist</h2>
                <div class="section-copy">Capture demand when the day is full and keep the queue visible in one place.</div>
            </section>
            <section class="content-grid">
                <div>
                    <article class="panel">
                        <h3>Add Waitlist Entry</h3>
                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="add_waitlist">
                            <div class="form-grid two">
                                <label>
                                    Customer name
                                    <input type="text" name="waitlist_name" placeholder="Customer name" required>
                                </label>
                                <label>
                                    Email
                                    <input type="email" name="waitlist_email" placeholder="customer@example.com">
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Phone
                                    <input type="text" name="waitlist_phone" placeholder="555-0123">
                                </label>
                                <label>
                                    Preferred start
                                    <input type="datetime-local" name="preferred_start" value="<?= $escape($selectedDate) ?>T09:00" required>
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Preferred end
                                    <input type="datetime-local" name="preferred_end" value="<?= $escape($selectedDate) ?>T17:00" required>
                                </label>
                                <label>
                                    Confirm details
                                    <button type="submit">Add To Waitlist</button>
                                </label>
                            </div>
                        </form>
                    </article>

                    <article class="panel">
                        <h3>Waitlist Queue</h3>
                        <?php if ($waitlistEntries === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No waitlist entries yet.</div>
                        <?php else: ?>
                            <div style="overflow:auto; margin-top: 1rem;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Service</th>
                                            <th>Window</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($waitlistEntries as $entry): ?>
                                            <tr>
                                                <td><?= $escape($entry['customer_name']) ?></td>
                                                <td><?= $escape($serviceMap[(string) $entry['service_id']] ?? ('Service ' . $entry['service_id'])) ?></td>
                                                <td><?= $escape($formatLocal($entry['preferred_start_utc'], $appTimezone)) ?> to <?= $escape($formatLocal($entry['preferred_end_utc'], $appTimezone)) ?></td>
                                                <td><span class="status"><?= $escape($entry['status']) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Open Slots Snapshot</h3>
                        <?php if ($slotPreview === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No open slots are visible in the current context.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($slotPreview as $slot): ?>
                                    <div class="list-item">
                                        <div class="list-title"><?= $escape($slot->startsAt->setTimezone(new DateTimeZone($appTimezone))->format('g:i A')) ?></div>
                                        <div class="list-meta"><?= $escape($selectedServiceName) ?> · <?= $escape($selectedOwnerLabel) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                    <article class="panel">
                        <h3>Quick Actions</h3>
                        <div class="quick-links" style="margin-top: 1rem;">
                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'booking'])) ?>">Back To Booking</a>
                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'calendar'])) ?>">Open Calendar</a>
                        </div>
                    </article>
                </div>
            </section>
        <?php else: ?>
            <section class="section-header">
                <h2>Waitlist</h2>
                <div class="section-copy">Capture demand when the day is full and keep the queue visible in one place.</div>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>

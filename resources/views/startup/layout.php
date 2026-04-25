<?php
$showAppointmentDialog = $flash !== null
    && ($flash['message'] ?? '') !== ''
    && ($flash['type'] ?? '') === 'success'
    && ($flash['action'] ?? '') === 'book_appointment';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape($companyName) ?> Booking</title>
    <style>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'colors.php'; ?>

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

        .section-copy,
        .muted,
        .list-meta {
            color: var(--ua-muted-text);
            line-height: 1.45;
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

        .workspace-mini {
            color: var(--ua-sidebar-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 800;
        }

        .sidebar-brand {
            display: grid;
            gap: 0.75rem;
        }

        .sidebar-copy {
            display: grid;
            gap: 0.75rem;
            min-width: 0;
        }

        .brand-banner {
            display: block;
            width: min(100%, 220px);
            height: auto;
            justify-self: center;
            border-radius: 16px;
            border: 1px solid var(--ua-sidebar-border);
            background: rgba(255, 255, 255, 0.04);
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

        .logo-preview-featured {
            display: block;
            width: min(100%, 360px);
            padding: 0.4rem;
            border-radius: 28px;
        }

        .logo-preview-featured img {
            display: block;
            width: 100%;
            height: auto;
            border-radius: 22px;
            padding: 0;
            box-shadow: none;
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

        .flash-dialog {
            width: min(100% - 2rem, 28rem);
            padding: 0;
            border: none;
            border-radius: 28px;
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.28);
            background: transparent;
        }

        .flash-dialog::backdrop {
            background: rgba(15, 23, 42, 0.42);
            backdrop-filter: blur(5px);
        }

        .flash-dialog-card {
            display: grid;
            gap: 1rem;
            padding: 1.5rem;
            border: 1px solid var(--ua-border);
            border-radius: 28px;
            background: var(--ua-card-background);
            color: var(--ua-text);
        }

        .flash-dialog-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            width: fit-content;
            padding: 0.42rem 0.8rem;
            border-radius: 999px;
            background: var(--ua-success-bg);
            color: var(--ua-success);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .flash-dialog-title {
            margin: 0;
            font-size: clamp(1.3rem, 2.3vw, 1.7rem);
            line-height: 1.1;
        }

        .flash-dialog-copy {
            margin: 0;
            color: var(--ua-muted-text);
            line-height: 1.6;
        }

        .flash-dialog-actions {
            display: flex;
            justify-content: flex-end;
        }

        .flash-dialog-actions button {
            min-width: 8rem;
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

        input[type="checkbox"] {
            width: auto;
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

        .inline-checkbox {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.82rem 0.95rem;
            border: 1px solid var(--ua-border);
            border-radius: var(--ua-input-radius);
            background: rgba(255, 255, 255, 0.88);
            font-weight: 600;
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
        <div class="sidebar-brand">
            <div class="workspace-mini">Starter Workspace</div>
            <div class="sidebar-copy">
                <h1><?= $escape($companyName) ?></h1>
                <?php if ($workspaceBrandImageUrl !== null && $workspaceBrandImageUrl !== ''): ?>
                    <img class="brand-banner" src="<?= $escape($workspaceBrandImageUrl) ?>" alt="<?= $escape($companyName) ?> banner">
                <?php endif; ?>
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
                    <div class="sidebar-title">Service Snapshot</div>
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
                    <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'booking-policy'])) ?>">Edit Booking Policy</a>
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
                <div class="muted">Theme and workflow settings are managed from this workspace.</div>
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
        <?php if ($flash !== null && $flash['message'] !== '' && !$showAppointmentDialog): ?>
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

        <?php require __DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . $startupPageTemplate . '.php'; ?>
    </main>
</div>
<?php if ($showAppointmentDialog): ?>
    <dialog class="flash-dialog" id="appointment-entered-dialog" aria-labelledby="appointment-entered-title">
        <div class="flash-dialog-card">
            <div class="flash-dialog-badge">Appointment saved</div>
            <h2 class="flash-dialog-title" id="appointment-entered-title">Appointment Entered</h2>
            <p class="flash-dialog-copy"><?= $escape($flash['message']) ?></p>
            <form method="dialog" class="flash-dialog-actions">
                <button type="submit" autofocus>Continue</button>
            </form>
        </div>
    </dialog>
    <script>
        (function () {
            const dialog = document.getElementById('appointment-entered-dialog');

            if (dialog && typeof dialog.showModal === 'function' && !dialog.open) {
                dialog.showModal();
            }
        }());
    </script>
<?php endif; ?>
</body>
</html>

            <section class="section-header">
                <h2>Services</h2>
                <div class="section-copy">Manage branding, system timezone, mail delivery, services, recurring availability, and blackout dates without leaving the starter workspace.</div>
            </section>
            <section class="split">
                <div>
                    <article class="panel">
                        <h3>Brand & System</h3>
                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="save_system_config">
                            <label>
                                Company name
                                <input type="text" name="company_name" value="<?= $escape($companyName) ?>" placeholder="Your Company" required>
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

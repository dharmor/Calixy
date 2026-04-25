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

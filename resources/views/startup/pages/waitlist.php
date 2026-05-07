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

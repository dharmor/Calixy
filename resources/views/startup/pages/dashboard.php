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

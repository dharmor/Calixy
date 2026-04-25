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

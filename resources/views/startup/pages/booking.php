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
                                    <span class="inline-checkbox">
                                        <input type="checkbox" name="send_email_reminder" value="1" checked>
                                        Send reminder e-mail to client
                                    </span>
                                </label>
                            </div>
                            <div class="muted">Set a reminder time only when delivery is enabled. Reminder time uses <?= $escape($appTimezone) ?> and requires the client e-mail plus a configured mail server in Services.</div>
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

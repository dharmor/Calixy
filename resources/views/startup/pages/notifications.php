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

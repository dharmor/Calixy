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

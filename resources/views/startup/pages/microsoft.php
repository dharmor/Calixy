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

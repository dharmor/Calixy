            <section class="section-header">
                <h2>Booking Policy</h2>
                <div class="section-copy">Edit the client-facing policy text used by your booking team.</div>
            </section>
            <section class="content-grid">
                <div>
                    <article class="panel">
                        <h3>Policy Editor</h3>
                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="save_booking_policy">
                            <label>
                                Policy summary
                                <textarea name="booking_policy_summary" placeholder="Appointments can be rescheduled or cancelled up to 24 hours in advance." required><?= $escape($bookingPolicy['summary'] ?? '') ?></textarea>
                            </label>
                            <label>
                                Cancellation policy
                                <textarea name="booking_policy_cancellation" placeholder="Define cancellation timelines and related fees." required><?= $escape($bookingPolicy['cancellation'] ?? '') ?></textarea>
                            </label>
                            <label>
                                No-show policy
                                <textarea name="booking_policy_no_show" placeholder="Define no-show fees and outcomes." required><?= $escape($bookingPolicy['no_show'] ?? '') ?></textarea>
                            </label>
                            <button type="submit">Save Booking Policy</button>
                        </form>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Current Policy Snapshot</h3>
                        <div class="detail-list" style="margin-top: 1rem;">
                            <div class="detail-row">
                                <div class="detail-label">Summary</div>
                                <div class="detail-value"><?= $escape($bookingPolicy['summary'] ?? '') ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Cancellation</div>
                                <div class="detail-value"><?= $escape($bookingPolicy['cancellation'] ?? '') ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">No-show</div>
                                <div class="detail-value"><?= $escape($bookingPolicy['no_show'] ?? '') ?></div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

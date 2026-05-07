            <section class="section-header">
                <h2>About</h2>
                <div class="section-copy">Application identity and runtime details.</div>
            </section>
            <section class="content-grid">
                <div>
                    <article class="panel">
                        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                            <?php if ($companyLogoDisplayUrl !== null && $companyLogoDisplayUrl !== ''): ?>
                                <div class="logo-preview logo-preview-featured">
                                    <img src="<?= $escape($companyLogoDisplayUrl) ?>" alt="<?= $escape($companyName) ?> logo">
                                </div>
                            <?php else: ?>
                                <div class="empty" style="margin-top: 0.75rem;">No logo is currently configured.</div>
                            <?php endif; ?>
                            <div class="detail-list" style="min-width: min(100%, 220px);">
                                <div class="detail-row">
                                    <div class="detail-label" style="color: #0b5cad;">Website</div>
                                    <div class="detail-value" style="color: #0b5cad;">
                                        <a href="https://www.daves-corner.com" target="_blank" rel="noopener" style="color: #0b5cad;">https://www.daves-corner.com</a>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label" style="color: #0b5cad;">contact</div>
                                    <div class="detail-value" style="color: #0b5cad;">
                                        <a href="mailto:dave@daves-corner.com" style="color: #0b5cad;">dave@daves-corner.com</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Application Version</h3>
                        <div style="margin-top: 1rem; display: grid; gap: 0.35rem;">
                            <div class="detail-value" style="color: var(--ua-text); font-weight: 800;"><?= $escape($applicationName) ?></div>
                            <div class="detail-value" style="font-size: 0.8rem; font-weight: 700; color: var(--ua-text);"><?= $escape($applicationVersion) ?></div>
                            <div style="margin-top: 0.5rem; display: grid; gap: 0.2rem;">
                                <div style="color: var(--ua-muted-text); font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 800;">Database</div>
                                <div class="detail-value" style="color: var(--ua-text);"><?= $escape($databaseDriverLabel) ?></div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="section-header">
                <h2>Locations</h2>
                <div class="section-copy">Add physical or virtual booking destinations and capture the timezone each location should use for scheduling.</div>
            </section>
            <section class="split">
                <div>
                    <article class="panel">
                        <h3><?= $isEditingLocation ? 'Edit Location' : 'Add Location' ?></h3>
                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="<?= $isEditingLocation ? 'update_location' : 'create_location' ?>">
                            <?php if ($isEditingLocation): ?>
                                <input type="hidden" name="existing_location_key" value="<?= $escape($editingLocation['location_key']) ?>">
                            <?php endif; ?>
                            <div class="form-grid two">
                                <label>
                                    Location key
                                    <input type="text" name="location_key" value="<?= $escape($editingLocation['location_key'] ?? '') ?>" placeholder="north-hub" <?= $isEditingLocation ? 'readonly' : '' ?> required>
                                </label>
                                <label>
                                    Location name
                                    <input type="text" name="location_name" value="<?= $escape($editingLocation['name'] ?? '') ?>" placeholder="North Hub" required>
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Contact name
                                    <input type="text" name="location_contact_name" value="<?= $escape($editingLocation['contact_name'] ?? '') ?>" placeholder="Booking Desk">
                                </label>
                                <label>
                                    Email
                                    <input type="email" name="location_email" value="<?= $escape($editingLocation['email'] ?? '') ?>" placeholder="desk@example.com">
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Phone
                                    <input type="text" name="location_phone" value="<?= $escape($editingLocation['phone'] ?? '') ?>" placeholder="555-0123">
                                </label>
                                <label>
                                    Timezone
                                    <select name="location_timezone">
                                        <?php foreach ($timezoneGroups as $region => $timezoneGroup): ?>
                                            <optgroup label="<?= $escape($region) ?>">
                                                <?php foreach ($timezoneGroup as $timezoneOption): ?>
                                                    <option value="<?= $escape($timezoneOption['timezone_key']) ?>" <?= $timezoneOption['timezone_key'] === ($editingLocation['timezone'] ?? $appTimezone) ? 'selected' : '' ?>>
                                                        <?= $escape($timezoneOption['label']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <label>
                                Address line 1
                                <input type="text" name="location_address_line_1" value="<?= $escape($editingLocation['address_line_1'] ?? '') ?>" placeholder="100 Atlantic Avenue">
                            </label>
                            <label>
                                Address line 2
                                <input type="text" name="location_address_line_2" value="<?= $escape($editingLocation['address_line_2'] ?? '') ?>" placeholder="Suite 400">
                            </label>
                            <div class="form-grid three">
                                <label>
                                    City
                                    <input type="text" name="location_city" value="<?= $escape($editingLocation['city'] ?? '') ?>" placeholder="Halifax">
                                </label>
                                <label>
                                    State / Province
                                    <input type="text" name="location_state_province" value="<?= $escape($editingLocation['state_province'] ?? '') ?>" placeholder="NS">
                                </label>
                                <label>
                                    Postal code
                                    <input type="text" name="location_postal_code" value="<?= $escape($editingLocation['postal_code'] ?? '') ?>" placeholder="B3H 1A1">
                                </label>
                            </div>
                            <label>
                                Country
                                <input type="text" name="location_country" value="<?= $escape($editingLocation['country'] ?? '') ?>" placeholder="Canada">
                            </label>
                            <div class="quick-links">
                                <button type="submit"><?= $isEditingLocation ? 'Update Location' : 'Save Location' ?></button>
                                <?php if ($isEditingLocation): ?>
                                    <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'locations', 'edit_location' => null])) ?>">Cancel Edit</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Location Directory</h3>
                        <?php if ($locations === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No locations are configured yet.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($locations as $location): ?>
                                    <div class="list-item">
                                        <div class="list-title"><?= $escape($location['name']) ?></div>
                                        <div class="list-meta"><?= $escape($location['location_key']) ?> · <?= $escape($location['timezone']) ?></div>
                                        <div class="list-meta">
                                            <?= $escape(trim(implode(', ', array_filter([
                                                $location['address_line_1'] ?? '',
                                                $location['city'] ?? '',
                                                $location['state_province'] ?? '',
                                                $location['country'] ?? '',
                                            ])))) ?: 'Address pending' ?>
                                        </div>
                                        <div class="quick-links" style="margin-top: 0.85rem;">
                                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'locations', 'edit_location' => $location['location_key']])) ?>">Edit Location</a>
                                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'locations', 'location' => $location['location_key'], 'edit_location' => null])) ?>">Use In Context</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>

                    <article class="panel">
                        <h3>Coverage Snapshot</h3>
                        <div class="snapshot" style="margin-top: 1rem;">
                            <?= $escape((string) count($locations)) ?> locations ·
                            <?= $escape((string) count(array_filter($services, static fn (array $service): bool => ($service['location_id'] ?? null) !== null))) ?> services assigned ·
                            <?= $escape((string) count(array_filter($teamMembers, static fn (array $member): bool => ($member['location_key'] ?? null) !== null))) ?> team members assigned
                        </div>
                    </article>
                </div>
            </section>

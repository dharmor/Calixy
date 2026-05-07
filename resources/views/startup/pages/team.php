            <section class="section-header">
                <h2>Team</h2>
                <div class="section-copy">Track who owns availability, who receives bookings, and where each person belongs.</div>
            </section>
            <section class="split">
                <div>
                    <article class="panel">
                        <h3><?= $isEditingTeamMember ? 'Edit Team Member' : 'Add Team Member' ?></h3>
                        <form method="post" class="list" style="margin-top: 1rem;">
                            <?= $contextFields ?>
                            <input type="hidden" name="action" value="<?= $isEditingTeamMember ? 'update_team_member' : 'create_team_member' ?>">
                            <?php if ($isEditingTeamMember): ?>
                                <input type="hidden" name="existing_member_key" value="<?= $escape($editingTeamMember['member_key']) ?>">
                            <?php endif; ?>
                            <div class="form-grid two">
                                <label>
                                    Team key
                                    <input type="text" name="member_key" value="<?= $escape($editingTeamMember['member_key'] ?? '') ?>" placeholder="main-team" <?= $isEditingTeamMember ? 'readonly' : '' ?> required>
                                </label>
                                <label>
                                    Full name
                                    <input type="text" name="member_name" value="<?= $escape($editingTeamMember['name'] ?? '') ?>" placeholder="Avery Morgan" required>
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Role
                                    <input type="text" name="member_role" value="<?= $escape($editingTeamMember['role'] ?? '') ?>" placeholder="Booking Manager" required>
                                </label>
                                <label>
                                    Owner type
                                    <select name="member_owner_type">
                                        <option value="staff" <?= ($editingTeamMember['owner_type'] ?? 'staff') === 'staff' ? 'selected' : '' ?>>Staff</option>
                                        <option value="resource" <?= ($editingTeamMember['owner_type'] ?? 'staff') === 'resource' ? 'selected' : '' ?>>Resource</option>
                                    </select>
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Location
                                    <select name="member_location_key">
                                        <option value="" <?= $isEditingTeamMember ? (($editingTeamMember['location_key'] ?? null) === null ? 'selected' : '') : ($selectedLocationKey === null ? 'selected' : '') ?>>No location yet</option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?= $escape($location['location_key']) ?>" <?= $isEditingTeamMember ? (($editingTeamMember['location_key'] ?? '') === $location['location_key'] ? 'selected' : '') : ($selectedLocationKey === $location['location_key'] ? 'selected' : '') ?>><?= $escape($location['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>
                                    Timezone
                                    <select name="member_timezone">
                                        <?php foreach ($timezoneGroups as $region => $timezoneGroup): ?>
                                            <optgroup label="<?= $escape($region) ?>">
                                                <?php foreach ($timezoneGroup as $timezoneOption): ?>
                                                    <option value="<?= $escape($timezoneOption['timezone_key']) ?>" <?= $timezoneOption['timezone_key'] === ($editingTeamMember['timezone'] ?? $appTimezone) ? 'selected' : '' ?>>
                                                        <?= $escape($timezoneOption['label']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Email
                                    <input type="email" name="member_email" value="<?= $escape($editingTeamMember['email'] ?? '') ?>" placeholder="team@example.com">
                                </label>
                                <label>
                                    Phone
                                    <input type="text" name="member_phone" value="<?= $escape($editingTeamMember['phone'] ?? '') ?>" placeholder="555-0123">
                                </label>
                            </div>
                            <label>
                                <input type="checkbox" name="member_is_active" value="1" <?= (int) ($editingTeamMember['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                                Mark as active for booking
                            </label>
                            <div class="quick-links">
                                <button type="submit"><?= $isEditingTeamMember ? 'Update Team Member' : 'Save Team Member' ?></button>
                                <?php if ($isEditingTeamMember): ?>
                                    <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'team', 'edit_team_member' => null])) ?>">Cancel Edit</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </article>
                </div>

                <div>
                    <article class="panel">
                        <h3>Team Directory</h3>
                        <?php if ($teamMembers === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No team members are configured yet.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($teamMembers as $member): ?>
                                    <div class="list-item">
                                        <div class="list-title"><?= $escape($member['name']) ?></div>
                                        <div class="list-meta">
                                            <?= $escape($member['member_key']) ?> · <?= $escape($member['role']) ?> · <?= $escape(ucfirst($member['owner_type'])) ?>
                                        </div>
                                        <div class="list-meta">
                                            <?= $escape($locationMap[(string) ($member['location_key'] ?? '')] ?? 'Unassigned location') ?> · <?= $escape($member['timezone']) ?> · <?= (int) ($member['is_active'] ?? 0) === 1 ? 'Active' : 'Inactive' ?>
                                        </div>
                                        <div class="quick-links" style="margin-top: 0.85rem;">
                                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'team', 'edit_team_member' => $member['member_key']])) ?>">Edit Team Member</a>
                                            <a class="quick-link" href="<?= $escape($buildUrl(['page' => 'team', 'owner' => $member['owner_type'] . ':' . $member['member_key'], 'edit_team_member' => null])) ?>">Use As Owner</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>

                    <article class="panel">
                        <h3>Availability Owners</h3>
                        <?php if ($owners === []): ?>
                            <div class="empty" style="margin-top: 1rem;">No availability owners are available yet.</div>
                        <?php else: ?>
                            <div class="list" style="margin-top: 1rem;">
                                <?php foreach ($owners as $ownerOption): ?>
                                    <div class="list-item">
                                        <div class="list-title"><?= $escape($ownerOption['label']) ?></div>
                                        <div class="list-meta"><?= $escape($ownerOption['token']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                </div>
            </section>

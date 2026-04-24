@php
    $themeStyle = collect($theme['css_variables'] ?? [])
        ->map(fn ($value, $key) => $key . ': ' . $value)
        ->implode('; ');
@endphp

<x-filament-panels::page>
    <div class="ua-shell space-y-6" style="{{ $themeStyle }}">
        <style>
            .ua-shell .ua-hero {
                background: var(--ua-page-background);
                border: 1px solid var(--ua-border);
                border-radius: 28px;
                box-shadow: var(--ua-shadow);
                color: var(--ua-text);
                overflow: hidden;
                position: relative;
            }

            .ua-shell .ua-hero::after {
                background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.55), transparent 38%);
                content: "";
                inset: 0;
                pointer-events: none;
                position: absolute;
            }

            .ua-shell .ua-hero-content,
            .ua-shell .ua-grid {
                position: relative;
                z-index: 1;
            }

            .ua-shell .ua-card {
                background: var(--ua-card-background);
                border: 1px solid var(--ua-border);
                border-radius: 22px;
                box-shadow: var(--ua-shadow);
                color: var(--ua-text);
            }

            .ua-shell .ua-muted-card {
                background: var(--ua-card-muted-background);
            }

            .ua-shell .ua-kicker {
                color: var(--ua-muted-text);
                font-size: 0.76rem;
                font-weight: 700;
                letter-spacing: 0.18em;
                text-transform: uppercase;
            }

            .ua-shell .ua-pill {
                align-items: center;
                background: var(--ua-pill-background);
                border: 1px solid transparent;
                border-radius: 999px;
                color: var(--ua-accent);
                display: inline-flex;
                font-size: 0.8rem;
                font-weight: 700;
                gap: 0.5rem;
                padding: 0.6rem 0.95rem;
                text-decoration: none;
                transition: transform 140ms ease, border-color 140ms ease, background 140ms ease;
            }

            .ua-shell .ua-pill:hover {
                border-color: var(--ua-border);
                transform: translateY(-1px);
            }

            .ua-shell .ua-pill-active {
                background: var(--ua-accent);
                color: var(--ua-accent-contrast);
            }

            .ua-shell .ua-stat {
                background: var(--ua-card-muted-background);
                border: 1px solid var(--ua-border);
                border-radius: 20px;
                padding: 1rem;
            }

            .ua-shell .ua-stat-label {
                color: var(--ua-muted-text);
                font-size: 0.8rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .ua-shell .ua-stat-value {
                color: var(--ua-text);
                font-size: 1.35rem;
                font-weight: 800;
                margin-top: 0.35rem;
            }

            .ua-shell .ua-copy {
                color: var(--ua-muted-text);
            }
        </style>

        <section class="ua-hero">
            <div class="ua-hero-content grid gap-6 px-6 py-8 md:grid-cols-[1.45fr_0.95fr] md:px-8 md:py-10">
                <div class="space-y-4">
                    <div class="ua-kicker">Unified Appointments</div>
                    <div class="space-y-3">
                        <h2 class="text-3xl font-black tracking-tight">{{ $theme['label'] }} Theme</h2>
                        <p class="max-w-2xl text-sm leading-6 ua-copy">
                            {{ $theme['description'] }}
                            The scheduling engine is installed and ready to power staff calendars, resources, waitlists, and timezone-safe
                            booking flows from the Unified Databases connection.
                        </p>
                    </div>
                    @if ($themeSwitcherEnabled)
                        <form method="GET" action="{{ url()->current() }}" class="pt-2">
                            @foreach (request()->query() as $key => $value)
                                @continue($key === $themeQueryParameter)
                                @if (! is_array($value))
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <label for="ua-theme-picker" class="ua-kicker">Theme</label>
                            <select
                                id="ua-theme-picker"
                                name="{{ $themeQueryParameter }}"
                                onchange="this.form.submit()"
                                class="mt-3 block w-full max-w-xs rounded-2xl border px-4 py-3 text-sm font-semibold"
                                style="background: var(--ua-card-background); border-color: var(--ua-border); color: var(--ua-text);"
                            >
                                @foreach ($availableThemes as $key => $availableTheme)
                                    <option value="{{ $key }}" @selected($activeThemeKey === $key)>{{ $availableTheme['label'] }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>

                <div class="ua-card ua-muted-card grid gap-3 p-5">
                    <div class="ua-stat">
                        <div class="ua-stat-label">Active Theme</div>
                        <div class="ua-stat-value">{{ $theme['label'] }}</div>
                    </div>
                    <div class="ua-stat">
                        <div class="ua-stat-label">Available Themes</div>
                        <div class="ua-stat-value">{{ count($availableThemes) }}</div>
                    </div>
                    <div class="ua-stat">
                        <div class="ua-stat-label">Theme Switching</div>
                        <div class="ua-stat-value">{{ $themeSwitcherEnabled ? 'Enabled' : 'Managed' }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="ua-grid grid gap-4 md:grid-cols-3">
            <article class="ua-card p-5">
                <div class="ua-kicker">Scheduling Core</div>
                <h3 class="mt-3 text-lg font-bold">Availability Engine</h3>
                <p class="mt-2 text-sm leading-6 ua-copy">
                    Recurring rules, blackout dates, booking buffers, and overlap checks stay portable across supported database drivers.
                </p>
            </article>

            <article class="ua-card p-5">
                <div class="ua-kicker">Unified DB</div>
                <h3 class="mt-3 text-lg font-bold">Driver-Agnostic Storage</h3>
                <p class="mt-2 text-sm leading-6 ua-copy">
                    The package loads its connection from the Unified Databases library so the same engine can target SQLite, MySQL,
                    PostgreSQL, and more.
                </p>
            </article>

            <article class="ua-card p-5">
                <div class="ua-kicker">Filament Ready</div>
                <h3 class="mt-3 text-lg font-bold">Configurable UI Themes</h3>
                <p class="mt-2 text-sm leading-6 ua-copy">
                    Add your own theme presets in config, set a default, or let teams preview different looks through the built-in switcher.
                </p>
            </article>
        </section>
    </div>
</x-filament-panels::page>

<?php

namespace UnifiedAppointments\Themes;

final class ThemeManager
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $themes = array_replace_recursive($this->builtInThemes(), $this->configuredThemes());
        $normalized = [];

        foreach ($themes as $key => $theme) {
            if (!is_array($theme)) {
                continue;
            }

            $normalized[(string) $key] = $this->normalizeTheme((string) $key, $theme);
        }

        return $normalized;
    }

    public function defaultThemeKey(): string
    {
        $default = (string) ($this->uiConfig()['theme'] ?? 'sunrise');
        $themes = $this->all();

        if (isset($themes[$default])) {
            return $default;
        }

        return (string) (array_key_first($themes) ?? 'sunrise');
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(?string $requestedTheme = null): array
    {
        $themes = $this->all();
        $key = $requestedTheme && isset($themes[$requestedTheme])
            ? $requestedTheme
            : $this->defaultThemeKey();

        return $themes[$key];
    }

    public function resolveKey(?string $requestedTheme = null): string
    {
        return (string) $this->resolve($requestedTheme)['key'];
    }

    public function themeQueryParameter(): string
    {
        return (string) ($this->uiConfig()['theme_query_parameter'] ?? 'theme');
    }

    public function themeSwitcherEnabled(): bool
    {
        return filter_var(
            $this->uiConfig()['allow_theme_switcher'] ?? true,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        ) ?? true;
    }

    /**
     * @param array<string, mixed> $theme
     * @return array<string, mixed>
     */
    private function normalizeTheme(string $key, array $theme): array
    {
        $variables = $theme['css_variables'] ?? [];

        if (!is_array($variables)) {
            $variables = [];
        }

        return [
            'key' => $key,
            'label' => (string) ($theme['label'] ?? ucfirst($key)),
            'description' => (string) ($theme['description'] ?? 'Custom appointment theme.'),
            'css_variables' => $variables,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function uiConfig(): array
    {
        $ui = $this->config['ui'] ?? [];

        return is_array($ui) ? $ui : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function configuredThemes(): array
    {
        $themes = $this->uiConfig()['themes'] ?? [];

        return is_array($themes) ? $themes : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function builtInThemes(): array
    {
        return [
            'sunrise' => [
                'label' => 'Sunrise',
                'description' => 'Warm gold and sand tones for a welcoming booking experience.',
                'css_variables' => [
                    '--ua-page-background' => 'linear-gradient(135deg, #fff3d6 0%, #ffe7c2 35%, #f8d9a0 100%)',
                    '--ua-card-background' => '#fffaf0',
                    '--ua-card-muted-background' => 'rgba(255, 255, 255, 0.54)',
                    '--ua-border' => 'rgba(180, 83, 9, 0.18)',
                    '--ua-text' => '#4a2706',
                    '--ua-muted-text' => '#7c4a12',
                    '--ua-accent' => '#c76b12',
                    '--ua-accent-contrast' => '#fffaf0',
                    '--ua-pill-background' => 'rgba(199, 107, 18, 0.12)',
                    '--ua-shadow' => '0 22px 46px rgba(122, 67, 18, 0.16)',
                ],
            ],
            'atlantic' => [
                'label' => 'Atlantic',
                'description' => 'Deep ocean blues with bright panels for calm scheduling dashboards.',
                'css_variables' => [
                    '--ua-page-background' => 'linear-gradient(135deg, #dceefd 0%, #bddbf3 45%, #86b7d8 100%)',
                    '--ua-card-background' => '#f8fcff',
                    '--ua-card-muted-background' => 'rgba(255, 255, 255, 0.58)',
                    '--ua-border' => 'rgba(8, 76, 117, 0.18)',
                    '--ua-text' => '#08253a',
                    '--ua-muted-text' => '#27506b',
                    '--ua-accent' => '#0b6ea8',
                    '--ua-accent-contrast' => '#f8fcff',
                    '--ua-pill-background' => 'rgba(11, 110, 168, 0.12)',
                    '--ua-shadow' => '0 22px 46px rgba(10, 74, 112, 0.16)',
                ],
            ],
            'evergreen' => [
                'label' => 'Evergreen',
                'description' => 'Forest greens and cream panels for a grounded, premium feel.',
                'css_variables' => [
                    '--ua-page-background' => 'linear-gradient(135deg, #edf6e3 0%, #dbeccb 45%, #b4d1a6 100%)',
                    '--ua-card-background' => '#fbfdf8',
                    '--ua-card-muted-background' => 'rgba(255, 255, 255, 0.56)',
                    '--ua-border' => 'rgba(35, 86, 46, 0.18)',
                    '--ua-text' => '#17331d',
                    '--ua-muted-text' => '#406347',
                    '--ua-accent' => '#2f6b3c',
                    '--ua-accent-contrast' => '#fbfdf8',
                    '--ua-pill-background' => 'rgba(47, 107, 60, 0.12)',
                    '--ua-shadow' => '0 22px 46px rgba(35, 78, 44, 0.15)',
                ],
            ],
        ];
    }
}

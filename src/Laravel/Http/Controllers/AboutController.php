<?php

namespace UnifiedAppointments\Laravel\Http\Controllers;

use Illuminate\Contracts\View\View;
use UnifiedAppointments\Support\AboutMetadataResolver;
use UnifiedAppointments\Support\LogoSourceResolver;

/**
 * AboutController.
 */
class AboutController extends Controller
{
    /**
     * Render the package About page with runtime branding details.
     *
     * @return View
     */
    public function __invoke(): View
    {
        $configuredName = config('unified-appointments.ui.application_name');
        $configuredVersion = config('unified-appointments.ui.version');
        $applicationName = AboutMetadataResolver::resolveName(
            is_string($configuredName) ? $configuredName : null,
            is_string(config('app.name')) ? config('app.name') : 'Calixy',
        );
        $logoUrl = config('unified-appointments.ui.logo_url');
        $packageRoot = dirname(__DIR__, 4);
        $imagesPath = function_exists('resource_path')
            ? resource_path('images')
            : $packageRoot . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'images';
        $publicPath = function_exists('public_path')
            ? public_path()
            : $packageRoot . DIRECTORY_SEPARATOR . 'public';
        $basePath = function_exists('base_path')
            ? base_path()
            : $packageRoot;
        $resolvedLogoUrl = LogoSourceResolver::resolve(is_string($logoUrl) ? $logoUrl : null, [
            $publicPath,
            $basePath,
            $packageRoot,
            $imagesPath,
        ]) ?? $this->defaultLogoDataUri($imagesPath);

        return view('unified-appointments::about', [
            'applicationName' => $applicationName,
            'logoUrl' => $resolvedLogoUrl,
            'packageVersion' => AboutMetadataResolver::resolveVersion(
                is_string($configuredVersion) ? $configuredVersion : null,
                $packageRoot,
                'calixy/unified-appointments',
            ),
            'donationwareUrl' => (string) config('unified-appointments.ui.donationware_url', 'https://github.com/sponsors'),
        ]);
    }

    /**
     * Build a data URI for the default About logo from bundled image assets.
     *
     * @return string|null Base64 data URI when an image is available, otherwise null.
     */
    private function defaultLogoDataUri(string $imagesPath): ?string
    {
        return LogoSourceResolver::firstAvailableDataUri([
            [$imagesPath . DIRECTORY_SEPARATOR . 'logo2.jpeg', 'image/jpeg'],
            [$imagesPath . DIRECTORY_SEPARATOR . 'logo2.jpg', 'image/jpeg'],
            [$imagesPath . DIRECTORY_SEPARATOR . 'logo2.png', 'image/png'],
        ]);
    }
}

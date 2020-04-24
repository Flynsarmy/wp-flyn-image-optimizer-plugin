<?php

namespace FlynIO;

use FlynIO\Image\Optimizer;
use FlynIO\Image\Scaler;
use Symfony\Component\Process\ExecutableFinder;

class Backend
{
    public function init()
    {
        add_action('admin_menu', function () {
            add_media_page(
                'Flyn Image Optimizer',
                'Image Optimizer',
                'manage_options',
                'flyn-image-optimizer',
                [$this, 'mediaPage']
            );
        });
    }

    /**
     * Returns a table of binaries, their filtered paths and whether or not
     * they're installed on the system.
     *
     * @return array List of [binary => string, filteredPath => string, installed => bool]
     */
    public function getInstalledBinaryTable(): array
    {
        $optimizer = new Optimizer();
        $binaries = $optimizer->getBinaryPaths();
        $installed = $optimizer->getInstalledBinaries();

        foreach ($binaries as $binary => $filteredPath) {
            $binaries[$binary] = [
                'binary' => $binary,
                'filteredPath' => $filteredPath,
                'installed' => $installed[$filteredPath],
            ];
        }

        // Add imagemagick - a special case
        $binaries['imagick'] = [
            'binary' => 'imagick',
            'filteredPath' => 'PHP extension',
            'installed' => class_exists("\Imagick") && !empty(\Imagick::queryFormats()),
        ];

        return $binaries;
    }

    public function mediaPage()
    {
        // phpcs:ignore Generic.Files.LineLength.TooLong
        wp_enqueue_style('jquery-ui-smoothness', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css', [], null);
        wp_enqueue_script('jquery-ui-tooltip');

        list($minDimensions, $maxDimensions) = (new Scaler())->getAllowedDimensions();

        echo Utils::requireWith(__DIR__ . "/../views/backend/menu_page.php", [
            'binaries' => $this->getInstalledBinaryTable(),
            'minDimensions' => $minDimensions,
            'maxDimensions' => $maxDimensions,
        ]);
    }
}

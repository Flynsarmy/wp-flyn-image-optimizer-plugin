<?php

namespace FlynIO\Image;

use FlynIO\Utils;
use ImageOptimizer\OptimizerFactory;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;

class Optimizer
{
    // Checking if binaries are installed is a slow process. This is a cache
    // of the results. Used in isBinaryInstalledAtPath()
    protected array $binaryInstallationCheckCache = [];

    public array $binaries = [
        'advpng',
        'advancecomp',
        'gifsicle',
        'jpegoptim',
        'jpegtran',
        'optipng',
        'pngcrush',
        'pngnq',
        'pngout',
        'pngquant',
        'svgo'
    ];
    
    public function optimize(string $filepath): void
    {
        $this->getFactory()->get()->optimize($filepath);
    }

    /**
     * Returns the binary path that will attempt to be executed by the optimizer
     *
     * @return array  [binaryName => binaryPath, ...]
     */
    public function getBinaryPaths(): array
    {
        // Get the options that will be passed to the optimizer
        // See if there are any overrides for binary paths
        $options = $this->getOptions();
        
        // Loop through checking for an _bin option override
        $filteredPaths = array_map(function ($binary) use ($options) {
            return Utils::arrayGet($options, "{$binary}_bin", $binary);
        }, $this->binaries);

        return array_combine($this->binaries, $filteredPaths);
    }

    /**
     * Returns a list of binaries and their installation state
     *
     * @return array  [binaryName => (bool) installed, ...]
     */
    public function getInstalledBinaries(): array
    {
        $binaries = [];
        
        foreach ($this->getBinaryPaths() as $binary => $path) {
            $binaries[$binary] = $this->isBinaryInstalledAtPath($path);
        }

        return $binaries;
    }

    public function isBinaryInstalled(string $binary): bool
    {
        return $this->isBinaryInstalledAtPath($this->getBinaryPaths()[$binary]);
    }

    /**
     * Checks if a binary exists at a given path. Caches the result.
     *
     * @param string $path
     * @return boolean
     */
    public function isBinaryInstalledAtPath(string $path): bool
    {
        if (!isset($this->binaryInstallationCheckCache[$path])) {
            $process = new Process(['which', $path]);
            $process->run();

            $this->binaryInstallationCheckCache[$path] = $process->isSuccessful();
        }

        return $this->binaryInstallationCheckCache[$path];
    }

    public function getFactory(): OptimizerFactory
    {
        return new OptimizerFactory($this->getOptions(), $this->getLogger());
    }
    
    public function getOptions(): array
    {
        return apply_filters('flynio-optimizer-options', []);
    }

    public function getLogger(): \Psr\Log\AbstractLogger
    {
        return apply_filters('flynio-optimizer-logger', new NullLogger());
    }
}

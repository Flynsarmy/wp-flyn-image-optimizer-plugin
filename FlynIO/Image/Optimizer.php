<?php

namespace FlynIO\Image;

use FlynIO\Utils;
use ImageOptimizer\OptimizerFactory;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;

class Optimizer
{
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
     * @return array
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

    public function getInstalledBinaries(): array
    {
        $binaries = [];
        
        foreach ($this->getBinaryPaths() as $path) {
            $binaries[$path] = $this->isBinaryInstalled($path);
        }

        return $binaries;
    }

    public function isBinaryInstalled(string $path): bool
    {
        $process = new Process(['which', $path]);
        $process->run();

        return $process->isSuccessful();
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

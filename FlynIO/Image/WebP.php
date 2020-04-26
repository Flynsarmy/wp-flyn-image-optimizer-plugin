<?php

namespace FlynIO\Image;

use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class WebP
{
    public ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(['driver' => 'imagick']);
    }

    public function canConvert(): bool
    {
        return class_exists("\Imagick") && in_array('WEBP', \Imagick::queryFormats());
    }

    public function convert(Image $image, string $toFilePath): void
    {
        $image->save($toFilePath);
    }
}

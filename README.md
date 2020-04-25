# Flyn Image Optimizer for WordPress

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
![Build Status - PHP](https://github.com/Flynsarmy/wp-flyn-image-optimizer-plugin/workflows/CI%20-%20PHP/badge.svg)
[![Code Quality](https://scrutinizer-ci.com/g/Flynsarmy/wp-flyn-image-optimizer-plugin/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Flynsarmy/wp-flyn-image-optimizer-plugin/?branch=master)

This plugin automatically compresses and scales down overly large images on upload.

## Installation

* `git clone` to */wp-content/plugins/flyn-wp-image-optimizer*
* `composer install`
* For Ubuntu: `apt-get update && apt-get install advancecomp pngcrush gifsicle jpegoptim imagemagick pngnq optipng pngquant`
* For OSX: `brew install advancecomp pngcrush gifsicle jpegoptim imagemagick pngnq optipng pngquant svgo`
* Restart `apache` and `php-fpm` services

## Usage

For maximum performance, this plugins functionality is changed through filters rather than an admin page. See the Filters section below for details.

WebP generation is enabled by default but automatic replacing on frontend images isn't. This is because you probably don't have WebP files generated for all your existing images yet. Run the mass converter in `Media - Tools` section of admin before enabling this functionality.

## Filters

### Setting minimum/maximum upload dimensions

By default uploaded images are scaled to a minimum of 100x100 or maximum of 3840x3840. This can be changed with:

```php
add_filter('flynio-limit-dimensions', function ($dimensions) {
    return [
        [150, 150],  // minimum x/y dimensions
        [1920, 1080], // maximum x/y dimensions
    ];
});
```

### Setting which image types to convert to JPG on upload

By default bitmaps and tiffs are converted to jpeg on upload. This can be overridden with:

```php
add_filter('flynio-mimes-to-convert', function ($mimes) {
    return ['image/bmp', 'image/tif', 'image/tiff'];
});
```

### Setting optimizer options

If using homebrew you'll likely want to overwrite the image optimizer's default filepaths like so:

```php
add_filter('flynio-optimizer-options', function ($options) {
    return array_merge($options, [
        'jpegtran_bin' => '/usr/local/bin/jpegtran',
        'jpegoptim_bin' => '/usr/local/bin/jpegoptim',
        'pngcrush_bin' => '/usr/local/bin/pngcrush',
        'optipng_bin' => '/usr/local/bin/optipng',
        'pngquant_bin' => '/usr/local/bin/pngquant',
    ]);
});
```

### Custom optimizer logger

By default `NullLogger` is used by the image optimizer. Set a custom logger with the following:

```php
class StdoutLogger extends \Psr\Log\AbstractLogger
{ 
    public function log($level, $message, array $context = [])
    {
        if (!empty($context['exception'])) {
            echo $context['exception']->getMessage();
        } else {
            echo $message;
        }

        echo "<br/>\n";
    }
}
add_filter('flynio-optimizer-logger', function ($logger) {
    return new StdoutLogger();
});
```

### Don't want automatic WebP image generation?
```php
add_filter('flynio-generate-webp-images', '__return_false');
```

### Enable <img> to <picture> conversion on site frontend
```php
add_filter('flynio-use-webp-images', '__return_true');
```
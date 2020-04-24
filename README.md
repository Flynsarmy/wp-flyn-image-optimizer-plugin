# Flyn Image Optimizer for WordPRess

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
![Build Status - PHP](https://github.com/Flynsarmy/wp-flyn-image-optimizer-plugin/workflows/CI%20-%20PHP/badge.svg)

This plugin automatically compresses and scales down overly large images on upload.

## Installation

* `git clone` to */wp-content/plugins/flyn-wp-image-optimizer*
* `composer install`
* For Ubuntu: `apt-get update && apt-get install advancecomp pngcrush gifsicle jpegoptim imagemagick pngnq optipng pngquant`
* For OSX: `brew install advancecomp pngcrush gifsicle jpegoptim imagemagick pngnq optipng pngquant svgo`
* Restart `apache` and `php-fpm`

## For OSX

If using homebrew you'll likely want to overwrite the image optimizer's default filepaths like so:

```php
add_filter('flyn-wpio-optimizer-options', function ($options) {
    return array_merge($options, [
        'jpegtran_bin' => '/usr/local/bin/jpegtran',
        'jpegoptim_bin' => '/usr/local/bin/jpegoptim',
        'pngcrush_bin' => '/usr/local/bin/pngcrush',
        'optipng_bin' => '/usr/local/bin/optipng',
        'pngquant_bin' => '/usr/local/bin/pngquant',
    ]);
});
```

## Custom Logger

By default `NullLogger` is used. Set a custom logger with the following:

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
add_filter('flyn-wpio-optimizer-logger', function ($logger) {
    return new StdoutLogger();
});
```

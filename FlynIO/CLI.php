<?php

namespace FlynIO;

use FlynIO\Image\Optimizer;
use FlynIO\Image\Scaler;
use FlynIO\Image\WebP;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use WP_CLI;
use WP_CLI_Command;
use WP_Query;

/**
 * Imports files as attachments, regenerates thumbnails, or lists registered image sizes.
 *
 * ## EXAMPLES
 *
 *     # Generates msising WebP copies of all attachments, without confirmation.
 *     $ wp media regenerate --yes
 *     Found 3 images to regenerate.
 *     1/3 Regenerated thumbnails for ID 760.
 *     2/3 Regenerated thumbnails for ID 757.
 *     3/3 Regenerated thumbnails for ID 756.
 *     Success: Regenerated 3 of 3 images.
 *
 *     # Generate missing WebP copies for all attachments that have IDs between 1000 and 2000.
 *     $ seq 1000 2000 | xargs wp media regenerate
 *     Found 4 images to regenerate.
 *     1/4 Regenerated thumbnails for ID 1027.
 *     2/4 Regenerated thumbnails for ID 1022.
 *     3/4 Regenerated thumbnails for ID 1045.
 *     4/4 Regenerated thumbnails for ID 1023.
 *     Success: Regenerated 4 of 4 images.
 *
 *     # Generates msising WebP copies of all attachments, optimizes full-size
 *     # images, and scales them if necessary.
 *     $ wp media regenerate --scale --optimize
 *     Do you really want to regenerate the "large" image size for all images? [y/n] y
 *     Found 3 images to regenerate.
 *     1/3 Regenerated thumbnails for ID 760.
 *     2/3 Regenerated thumbnails for ID 757.
 *     3/3 Regenerated thumbnails for ID 756.
 *     Success: Regenerated 3 of 3 images.
 *
 *     # Generate only missing WebP copies of the thumbnails of "large" image size for all images.
 *     $ wp media regenerate --image_size=large
 *     Do you really want to regenerate the "large" image size for all images? [y/n] y
 *     Found 3 images to regenerate.
 *     1/3 Regenerated "large" thumbnail for ID 760.
 *     2/3 No "large" thumbnail regeneration needed for ID 757.
 *     3/3 Regenerated "large" thumbnail for ID 756.
 *     Success: Regenerated 3 of 3 images.
 *
 */
class CLI extends WP_CLI_Command
{

    /**
     * Clear the WP object cache after this many regenerations/imports.
     *
     * @var integer
     */
    public const WP_CLEAR_OBJECT_CACHE_INTERVAL = 500;

    public Optimizer $optimizer;
    public Scaler $scaler;
    public WebP $webp;
    public ImageManager $manager;

    /**
     * /path/to/wp-content/uploads/
     */
    public string $baseUploadDir;

    /**
     * Generates missing WebP thumbnails for one or more attachments.
     *
     * ## OPTIONS
     *
     * [<attachment-id>...]
     * : One or more IDs of the attachments to regenerate.
     *
     * [--image_size=<image_size>]
     * : Name of the image size to regenerate. Only thumbnails of this image size will be regenerated,
     * thumbnails of other image sizes will not.
     *
     * [--scale]
     * : Scale full-size images out of the allowed min/max size. Ignored when using --full_size argument.
     *
     * [--optimize]
     * : Optimize file size of full-size images. Ignored when using --full_size argument.
     *
     * [--yes]
     * : Answer yes to the confirmation message. Confirmation only shows when no IDs passed as arguments.
     *
     * ## EXAMPLES
     *
     *     # Generates msising WebP copies of all attachments, without confirmation.
     *     $ wp media regenerate --yes
     *     Found 3 images to regenerate.
     *     1/3 Regenerated thumbnails for ID 760.
     *     2/3 Regenerated thumbnails for ID 757.
     *     3/3 Regenerated thumbnails for ID 756.
     *     Success: Regenerated 3 of 3 images.
     *
     *     # Generate missing WebP copies for all attachments that have IDs between 1000 and 2000.
     *     $ seq 1000 2000 | xargs wp media regenerate
     *     Found 4 images to regenerate.
     *     1/4 Regenerated thumbnails for ID 1027.
     *     2/4 Regenerated thumbnails for ID 1022.
     *     3/4 Regenerated thumbnails for ID 1045.
     *     4/4 Regenerated thumbnails for ID 1023.
     *     Success: Regenerated 4 of 4 images.
     *
     *     # Generates msising WebP copies of all attachments, optimizes full-size
     *     # images, and scales them if necessary.
     *     $ wp media regenerate --scale --optimize
     *     Do you really want to regenerate the "large" image size for all images? [y/n] y
     *     Found 3 images to regenerate.
     *     1/3 Regenerated thumbnails for ID 760.
     *     2/3 Regenerated thumbnails for ID 757.
     *     3/3 Regenerated thumbnails for ID 756.
     *     Success: Regenerated 3 of 3 images.
     *
     *     # Generate only missing WebP copies of the thumbnails of "large" image size for all images.
     *     $ wp media regenerate --image_size=large
     *     Do you really want to regenerate the "large" image size for all images? [y/n] y
     *     Found 3 images to regenerate.
     *     1/3 Regenerated "large" thumbnail for ID 760.
     *     2/3 No "large" thumbnail regeneration needed for ID 757.
     *     3/3 Regenerated "large" thumbnail for ID 756.
     *     Success: Regenerated 3 of 3 images.
     */
    public function regenerate(array $args, array $assoc_args = []): void
    {
        $assoc_args = wp_parse_args(
            $assoc_args,
            [ 'image_size' => '' ]
        );

        // Validate image_size arg if passed
        $image_size = $assoc_args['image_size'];
        if ($image_size && !in_array($image_size, get_intermediate_image_sizes(), true)) {
            WP_CLI::error(sprintf('Unknown image size "%s".', $image_size));
        }

        $scale  = array_key_exists('scale', $assoc_args);
        $optimize = array_key_exists('optimize', $assoc_args);
        $yes = array_key_exists('yes', $assoc_args);

        // Handle skipping confirmation message when working on all attachments
        if (empty($args) && !$yes) {
            if ($image_size) {
                WP_CLI::confirm(sprintf(
                    'Do you really want to regenerate the "%s" image size for all images?',
                    $image_size
                ), $assoc_args);
            } else {
                WP_CLI::confirm('Do you really want to regenerate all images?', $assoc_args);
            }
        }

        try {
            $this->scaler = new Scaler();
            $this->optimizer = new Optimizer();
            $this->webp = new WebP();
            $this->manager = new ImageManager(['driver' => 'imagick']);
        } catch (\Exception $e) {
            WP_CLI::error($e->getMessage());
        }
        $this->baseUploadDir = wp_upload_dir()['basedir'] . '/';

        // Check that the scaler and webp generator will work
        if ($scale && !$this->scaler->canScale()) {
            // phpcs:ignore Generic.Files.LineLength.TooLong
            WP_CLI::error("The image scaler needs both imagick PHP extension and imagemagick app installed on your server to operate.");
        }
        if (!$this->webp->canConvert()) {
            // phpcs:ignore Generic.Files.LineLength.TooLong
            WP_CLI::error("The WebP generator needs both imagick PHP extension with WEBP support and imagemagick app installed on your server to operate.");
        }

        $images = $this->getImagePostIDs($args);
        $count  = $images->post_count;

        if (!$count) {
            WP_CLI::warning('No images found.');
            return;
        }

        WP_CLI::log(
            sprintf(
                'Found %1$d %2$s to regenerate.',
                $count,
                _n('image', 'images', $count)
            )
        );

        $number    = 0;
        $successes = 0;
        $errors    = 0;
        $skips     = 0;
        foreach ($images->posts as $postId) {
            $number++;
            if (0 === $number % self::WP_CLEAR_OBJECT_CACHE_INTERVAL) {
                $this->wpClearOjectCache();
            }
            $this->processRegeneration(
                $postId,
                $optimize,
                $scale,
                $image_size,
                $number . '/' .
                $count,
                $successes,
                $errors,
                $skips
            );
        }

        $this->reportBatchOperationResults('image', 'regenerate', $count, $successes, $errors, $skips);
    }

    // phpcs:ignore Generic.Files.LineLength.TooLong
    private function processRegeneration(int $id, bool $optimize, bool $scale, string $image_size, string $progress, int &$successes, int &$errors, int &$skips): void
    {
        $att_desc = sprintf('ID %d', $id);
        $thumbnail_desc = $image_size ? sprintf('"%s" thumbnail', $image_size) : 'thumbnail';

        // Note: zero-length string returned if no metadata, for instance if PDF or non-standard image (eg an SVG).
        $metadata = wp_get_attachment_metadata($id);
        if (!is_array($metadata)) {
            WP_CLI::warning("Unable to load metadata for $att_desc.");
            $errors++;
            return;
        }

        $imageDir = $this->baseUploadDir . dirname($metadata['file']) . '/';
        $fullsizepath = $this->baseUploadDir . $metadata['file'];

        // Handle operations on the full size image
        $scaled = $optimized = false;
        $created = 0; //How many WebP's were generated
        $paths = []; // List of image paths we'll generate WebP's off of
        if (empty($image_size)) {
            if ($scale) {
                $img = $this->manager->make($fullsizepath);
                $scaled = $this->scaler->scale($img);
                
                if ($scaled) {
                    $img->save();
                    $metadata['width'] = $img->width();
                    $metadata['height'] = $img->height();

                    wp_update_attachment_metadata($id, $metadata);
                }
            }

            if ($optimize) {
                $this->optimizer->optimize($fullsizepath);
                $optimized = true;
            }

            // Add full size
            $paths[] = $fullsizepath;
            // Add thumbs
            foreach ($metadata['sizes'] as $size) {
                $paths[] = $imageDir . $size['file'];
            }

            $created = $this->maybeCreateWebPs($paths);
        } else {
            // Only working on a single file-size
            $paths[] = $imageDir . $metadata['sizes'][$image_size]['file'];
        }

        $created = $this->maybeCreateWebPs($paths);
        
        if (!$scaled && !$optimized && $created === 0) {
            WP_CLI::log("$progress Skipped $thumbnail_desc regeneration for $att_desc.");
            $skips++;
        } else {
            $messages = [];
            if ($scaled) {
                $messages[] = sprintf("Scaled to %dx%d", $metadata['width'], $metadata['height']);
            }
            if ($optimized) {
                $messages[] = "Optimized filesize";
            }
            if ($created) {
                $messages[] = sprintf("Created %d WebP files", $created);
            }
            WP_CLI::log("$progress " . implode(', ', $messages) . " for $att_desc.");
            $successes++;
        }
            
        return;
    }

    private function maybeCreateWebPs(array $paths): int
    {
        $converted = 0;

        foreach ($paths as $fromPath) {
            $toPath = $fromPath . ".webp";

            if (file_exists($toPath) || !file_exists($fromPath)) {
                continue;
            }

            try {
                $img = $this->manager->make($fromPath);
                $this->webp->convert($img, $toPath);
                $converted++;
            } catch (\Exception $e) {
                ;
            }
        }

        return $converted;
    }

    /**
     * Get images from the installation.
     *
     * @param array $args                  The query arguments to use. Optional.
     *
     * @return WP_Query The query result.
     */
    private function getImagePostIDs(array $args = [])
    {
        $query_args = array(
            'post_type'      => 'attachment',
            'post__in'       => $args,
            'post_mime_type' => ['image/jpeg', 'image/png'],
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
        );

        return new WP_Query($query_args);
    }

    /**
     * Clear WordPress internal object caches.
     *
     * In long-running scripts, the internal caches on `$wp_object_cache` and `$wpdb`
     * can grow to consume gigabytes of memory. Periodically calling this utility
     * can help with memory management.
     *
     * @access public
     * @category System
     * @deprecated 1.5.0
     */
    public function wpClearOjectCache()
    {
        global $wpdb, $wp_object_cache;

        $wpdb->queries = [];

        if (! is_object($wp_object_cache)) {
            return;
        }

        // The following are Memcached (Redux) plugin specific (see https://core.trac.wordpress.org/ticket/31463).
        if (isset($wp_object_cache->group_ops)) {
            $wp_object_cache->group_ops = [];
        }
        if (isset($wp_object_cache->stats)) {
            $wp_object_cache->stats = [];
        }
        if (isset($wp_object_cache->memcache_debug)) {
            $wp_object_cache->memcache_debug = [];
        }
        // Used by `WP_Object_Cache` also.
        if (isset($wp_object_cache->cache)) {
            $wp_object_cache->cache = [];
        }
    }

    /**
     * Report the results of the same operation against multiple resources.
     *
     * @access public
     * @category Input
     *
     * @param string       $noun      Resource being affected (e.g. plugin)
     * @param string       $verb      Type of action happening to the noun (e.g. activate)
     * @param integer      $total     Total number of resource being affected.
     * @param integer      $successes Number of successful operations.
     * @param integer      $failures  Number of failures.
     * @param null|integer $skips     Optional. Number of skipped operations. Default null (don't show skips).
     */
    public function reportBatchOperationResults($noun, $verb, $total, $successes, $failures, $skips = null)
    {
        $plural_noun           = $noun . 's';
        $past_tense_verb       = $this->pastTenseVerb($verb);
        $past_tense_verb_upper = ucfirst($past_tense_verb);
        if ($failures) {
            $failed_skipped_message = null === $skips ?
                '' :
                " ({$failures} failed" . ($skips ? ", {$skips} skipped" : '') . ')';
            if ($successes) {
                WP_CLI::error(
                    "Only {$past_tense_verb} {$successes} of {$total} {$plural_noun}{$failed_skipped_message}."
                );
            } else {
                WP_CLI::error(
                    "No {$plural_noun} {$past_tense_verb}{$failed_skipped_message}."
                );
            }
        } else {
            $skipped_message = $skips ? " ({$skips} skipped)" : '';
            if ($successes || $skips) {
                WP_CLI::success("{$past_tense_verb_upper} {$successes} of {$total} {$plural_noun}{$skipped_message}.");
            } else {
                $message = $total > 1 ? ucfirst($plural_noun) : ucfirst($noun);
                WP_CLI::success("{$message} already {$past_tense_verb}.");
            }
        }
    }

    /**
     * Returns past tense of verb, with limited accuracy. Only regular verbs catered for, apart from "reset".
     *
     * @param string $verb Verb to return past tense of.
     * @return string
     */
    public function pastTenseVerb(string $verb): string
    {
        static $irregular = array(
            'reset' => 'reset',
        );
        if (isset($irregular[ $verb ])) {
            return $irregular[ $verb ];
        }
        $last = substr($verb, -1);
        if ('e' === $last) {
            $verb = substr($verb, 0, -1);
        } elseif ('y' === $last && ! preg_match('/[aeiou]y$/', $verb)) {
            $verb = substr($verb, 0, -1) . 'i';
        } elseif (preg_match('/^[^aeiou]*[aeiou][^aeiouhwxy]$/', $verb)) {
            // Rule of thumb that most (all?) one-voweled regular verbs ending in vowel +
            // consonant (excluding "h", "w", "x", "y") double their final consonant - misses
            // many cases (eg "submit").
            $verb .= $last;
        }
        return $verb . 'ed';
    }
}

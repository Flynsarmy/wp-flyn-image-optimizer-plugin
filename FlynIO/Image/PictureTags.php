<?php

namespace FlynIO\Image;

use FlynIO\Utils;

/**
 * Class PictureTags - convert an <img> tag to a <picture> tag and add the webp versions of the images
 * Based this code on code from the ShortPixel plugin, which used code from Responsify WP plugin
 */

class PictureTags
{
    // Processing will not be done on images with this class
    public string $ignoreClass = 'flynio-processed';

    /**
     * Extracts and returns attributes of a given HTML element
     *
     * @param string $htmlTag
     * @return array  Associative array of name => value
     */
    public function getAttributes(string $htmlTag): array
    {
        if (function_exists("mb_convert_encoding")) {
            $htmlTag = mb_convert_encoding($htmlTag, 'HTML-ENTITIES', 'UTF-8');
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlTag);
        $domTag = $dom->getElementsByTagName('img')->item(0);
        
        $attributes = [];
        foreach ($domTag->attributes as $attr) {
            $attributes[$attr->nodeName] = $attr->nodeValue;
        }

        return $attributes;
    }

    /**
     * Converts an array of attributes to a valid HTML attributes string
     *
     * @param  array $attributes
     * @return string
     */
    public function arrayToHTMLAttribs(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $attribute => $value) {
            $html .= "{$attribute}=\"{$value}\" ";
        }
        
        return $html;
    }

    /**
     * Replace a <img> with a <picture> tag. Ignore any tags with the
     * $ignoreClass class.
     *
     * @param array $match
     * @return string
     */
    private function replaceCallback(array $match): string
    {
        $imgTag = $match[0];

        // Do nothing with images that have the 'webpexpress-processed' class.
        if (strpos($imgTag, $this->ignoreClass)) {
            return $imgTag;
        }

        // We're only replacing jpegs and pngs
        if (!preg_match("/\.(jpg|jpeg|png)/i", $imgTag)) {
            return $imgTag;
        }

        // Grab all image attribs, assigning defaults for our essential ones
        $imgAttribs = array_merge([
            'class' => '',
        ], $this->getAttributes($imgTag));

        // Add the ignore class so we don't double-process this image
        $imgAttribs['class'] = trim($imgAttribs['class'] . ' ' . $this->ignoreClass);

        $sourceAttribs = [
            'type' => "image/webp",
        ];
        if (isset($imgAttribs['srcset'])) {
            $sourceAttribs['srcset'] = preg_replace("/\.(jpg|jpeg|png)/i", ".$1.webp", $imgAttribs['srcset']);
        } else {
            $sourceAttribs['srcset'] = $imgAttribs['src'] . '.webp';
        }
        if (isset($imgAttribs['sizes'])) {
            $sourceAttribs['sizes'] = $imgAttribs['sizes'];
        }
            
        
        return '<picture>' .
            '<source ' . $this->arrayToHTMLAttribs($sourceAttribs) . '/>' .
            '<img ' . $this->arrayToHTMLAttribs($imgAttribs) . ' />' .
            '</picture>';
    }

    /**
     * Replaces all <img> tags with <picture> tags where possible.
     *
     * @param string $html
     * @return string
     */
    public function replace(string $html): string
    {
        // @todo Don't replace img tags already in picture tags
        return preg_replace_callback('/<img.+?\/>/i', [$this, 'replaceCallback'], $html);
    }
}

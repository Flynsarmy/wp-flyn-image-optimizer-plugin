<?php
    list($minDimensions, $maxDimensions) = $scaler->getAllowedDimensions();
?>

<h3>
    Image Scaling
    <span class="dashicons dashicons-info" title="These values can be overridden with the 'flynio-limit-dimensions' filter."></span>
</h3>
<?php if (!$scaler->canScale()): ?>
    <div class="notice notice-error notice-alt inline">
        <p>The image scaler needs both imagick PHP extension and imagemagick app installed on your server to operate.</p>
    </div>
<?php endif; ?>
<p>Images outside the dimensions <strong><?= $minDimensions[0] . 'x' . $minDimensions[1] ?></strong> and <strong><?= $maxDimensions[0] . 'x' . $maxDimensions[1] ?></strong> will be scaled to within these dimensions automatically on upload.</p>
<p>&nbsp;</p>

<h3>
    Image Conversion
    <span class="dashicons dashicons-info" title="These values can be overridden with the 'flynio-mimes-to-convert' filter."></span>
</h3>
<?php if (!$converter->canConvert()): ?>
    <div class="notice notice-error notice-alt inline">
        <p>The image converter needs both imagick PHP extension and imagemagick app installed on your server to operate.</p>
    </div>
<?php endif; ?>
<?php if (empty($converter->getMimeTypesToConvert())): ?>
    <p>No images will be converted to JPEG automatically on upload.</p>
<?php else: ?>
    <p>Images with the mime types <strong><?= implode(', ', $converter->getMimeTypesToConvert()) ?></strong> will be converted to JPEG automatically on upload.</p>
<?php endif; ?>
<p>&nbsp;</p>

<h3>Installed Binaries</h3>
<p>Below is a list of binaries used by this plugin for image optimization. Try to have as many of these installed as possible for optimal image compression.</p>
<table id="fbio-binary-table" class="wp-list-table widefat striped">
    <thead>
        <tr>
            <th>Binary</th>
            <th>
                Executable Path
                <span class="dashicons dashicons-info" title="This value can be overridden with the 'flynio-optimizer-options' filter."></span>
            </th>
            <th>Installed</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($binaries as $binary): ?>
            <tr>
                <td><?= $binary['binary'] ?></th>
                <td><?= $binary['filteredPath'] ?></th>
                <td class="<?= $binary['installed'] ? 'yes' : 'no' ?>"><?= $binary['installed'] ? 'yes' : 'no' ?></th>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
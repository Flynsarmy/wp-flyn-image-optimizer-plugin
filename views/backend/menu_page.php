<style>
    #fbio-binary-table td.yes {color:green}
    #fbio-binary-table td.no {color:red}
</style>

<div class="wrap">
    <h1>Flyn Image Optimizer</h1>

    <p>Images outside the dimensions <?= $minDimensions[0].'x'.$minDimensions[1] ?> and <?= $maxDimensions[0].'x'.$maxDimensions[1] ?> will be scaled to within these dimensions automatically on upload.</p>

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
</div>

<script>
    jQuery(document).ready(function($) {
        $('#fbio-binary-table .dashicons-info').tooltip();
    });
</script>
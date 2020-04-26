<style>
    #fbio-binary-table td.yes, h3 .enabled {color:green}
    #fbio-binary-table td.no, h3 .disabled {color:red}

    .tab-content {display:none}
    .tab-content.tab-content-active {display:block}
</style>

<div id="fbio-wrap" class="wrap">
    <h1>Flyn Image Optimizer</h1>

    <p>Automatic compression, conversion and scaling of images on upload.</p>

    <nav class="nav-tab-wrapper">
        <label for="nav-tab-status" class="nav-tab nav-tab-active" role="tab">Status</label>
        <label for="nav-tab-tools" class="nav-tab" role="tab">Tools</label>
        <label for="nav-tab-help" class="nav-tab" role="tab">Help</label>
    </nav>

    <div id="nav-tab-status" class="tab-content tab-content-active">
        <?php echo FlynIO\Utils::requireWith(__DIR__ . '/tabs/status.php', compact(['webp', 'converter', 'scaler', 'binaries'])); ?>
    </div>

    <div id="nav-tab-tools" class="tab-content">
        tools tab
    </div>

    <div id="nav-tab-help" class="tab-content">
        help tab
    </div>    
</div>

<script>
    jQuery(document).ready(function($) {
        $('#fbio-wrap .dashicons-info[title]').tooltip();

        $('.nav-tab').click(function() {
            $(this).addClass('nav-tab-active').siblings().removeClass('nav-tab-active');
            $('#'+$(this).attr('for')).addClass('tab-content-active').siblings().removeClass('tab-content-active');
        });
    });
</script>